
<?php

if (!class_exists('configManager')) {

	class configManager
	{
		
		public $minerData = array(
		'numOfGpu' => NULL,
		'ipAdress' => NULL,
		'hostName' => NULL,
		'minerUid' => NULL,
		);
		public $xmrData = array(
		'currency' => NULL,
		'walletAdress' => NULL,
		'poolAdress' => NULL,
		'multipleIntesity' => NULL,
		'worksize' => NULL,
		);
		

		private $configFileName = 'ConfigManager.json';

		
		function __construct()
		{
		
			if (file_exists($this->configFileName)) 
			{
				$this->minerData = json_decode(file_get_contents($this->configFileName), TRUE);
			}
			while (is_null($this->minerData["minerUid"]))
			{
				echo"Enter Miner-ID:";
				$this->minerData['minerUid'] = trim(fgets(STDIN)); // reads one line from STDIN
			}
			
			$this->retrieveHw();
			$this->getMinerConfig();			

			file_put_contents($this->configFileName, json_encode($this->minerData));
			
		}

		private function retrieveHw()
		{			
			
			$this->getNewGpuInfo();	
			$this->minerData['ipAdress'] = shell_exec("ip route get 8.8.4.4 | head -1 | awk '{print $7}'");
			$this->minerData['hostName'] = shell_exec('hostname');
			$this->getNewGpuInfo();
		}

		private function getNewGpuInfo()
		{
			$gpuInfo = shell_exec('clinfo -l');
			$gpuInfo = explode("\n",$gpuInfo);

			foreach ($gpuInfo as &$value) 
			{
			   $value = substr($value,12);
			}
			array_pop($gpuInfo);
			array_shift($gpuInfo);
			unset($value);
			$this->minerData['numOfGpu'] = count($gpuInfo);
		}
		private function getMinerConfig()
		{
			$url = 'home.ccs.at:8080/GetMinerConfig.php'; 
			//Initiate cURL.
			$ch = curl_init($url);


			//The JSON data.
			$jsonData = array(
			'ipAdress' => $this->minerData['ipAdress'],
			'minerUid' => $this->minerData['minerUid'],
			);
			 
			//Encode the array into JSON.
			$jsonDataEncoded = json_encode($jsonData);
			//echo "--------------\n" . 'Json-Data' . $jsonDataEncoded . "\n--------------";
			 
			//Tell cURL that we want to send a POST request.
			curl_setopt($ch, CURLOPT_POST, 1);

			//Send echos to return var
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			 
			//Attach our encoded JSON string to the POST fields.
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
			 
			//Set the content type to application/json
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
			 
			$result = curl_exec($ch);
			
			curl_close($ch);
			$result = json_decode($result,true);
			$result	= $result['0'];

			//var_dump($result);
			

			$this->xmrData['currency'] = $result['Currency'];
			$this->xmrData['walletAdress'] = $result['WalletAdress'];
			$this->xmrData['multipleIntesity'] = $result['multipleIntesity'];
			$this->xmrData['worksize'] = $result['worksize'];
			$this->xmrData['poolAdress'] = $result['PoolAdress'];	
			
			

		}
	}
	
}




?>
