
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
			while (empty($this->minerData["minerUid"]))
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
			
			$this->minerData['ipAdress'] = shell_exec("ip route get 8.8.4.4 | head -1 | awk '{print $7}'");
			$this->minerData['hostName'] = shell_exec('hostname');
			$this->getNewGpuInfo();
		}

		private function getNewGpuInfo()
		{
			$this->gpuData = Array();

			echo "ConfigManager: try to get clinfo \n";
			$gpuInfo = shell_exec('/opt/amdgpu-pro/bin/clinfo');
			echo "ConfigManager: Done \n";


			$gpuInfo = str_replace(' ', '', $gpuInfo);
			$gpuInfo = str_replace('\t', '', $gpuInfo);
			$gpuInfo = explode("\n",$gpuInfo);
			

			foreach ($gpuInfo as &$value) 
			{
			   $value = trim(preg_replace('/\s+/', ' ', $value));
			}
			unset($value);

			$gpuData["Name"] = array_values(array_filter($gpuInfo, array($this, 'isGpuName')));
			$gpuData["BoardName"] = array_values(array_filter($gpuInfo, array($this, 'isGpuBoardName')));
			$gpuData["DeviceTopology"] = array_values(array_filter($gpuInfo, array($this, 'isGpuDeviceTopologyName')));
			$gpuData["Numberofdevices"] = intval(trim(strstr(implode("",array_filter($gpuInfo, array($this, 'isNumberofdevices')))," ")));		

			
			$this->minerData['numOfGpu'] = $gpuData["Numberofdevices"];


			for ($i=0; $i <$gpuData["Numberofdevices"]; $i++) {
				$this->gpuData[$i]["ID"] = $i;
				$this->gpuData[$i]["Name"] = trim(strstr($gpuData["Name"][$i], " "));
				$this->gpuData[$i]["BoardName"] = trim(strstr($gpuData["BoardName"][$i], " "));
				$this->gpuData[$i]["DeviceTopology"] = trim(strstr($gpuData["DeviceTopology"][$i], " "));
				
			}
			
			var_dump($this->gpuData);
			
			sleep(10);


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

		private function isGpuName($value)
		{
			$query = "Name:";
			if(substr( $value, 0, strlen($query) ) === $query)
			{
				return true;
			}
		}
		private function isGpuBoardName($value)
		{
			$query = "Boardname:";
			if(substr( $value, 0, strlen($query) ) === $query)
			{
				return true;
			}
		}
		private function isGpuDeviceTopologyName($value)
		{
			$query = "DeviceTopology:";
			if(substr( $value, 0, strlen($query) ) === $query)
			{
				return true;
			}
		}

		private function isNumberofdevices($value)
		{
			$query = "Numberofdevices:";
			if(substr( $value, 0, strlen($query) ) === $query)
			{
				return true;
			}
		}
		

	}
	
}




?>
