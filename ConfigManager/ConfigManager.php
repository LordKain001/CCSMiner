
<?php


class configManager
{
	
	public $data = array(
	'numOfGpu' => NULL,
	'ipAdress' => NULL,
	'hostName' => NULL,
	'walletAdress' => NULL,
	'poolAdress' => NULL,
	'minerUid' => NULL,
	);
	

	private $configFileName = 'config.json';



	
	function __construct()
	{
		$this->getNewData();
	}

	function getNewData()
	{		
		if (!file_exists($this->configFileName)) 
		{
			$this->data = json_decode(file_get_contents($this->configFileName), TRUE);
		}
		
		$this->getNewGpuInfo();	
		$this->data['ipAdress'] = array_shift(preg_split("/\\r\\n|\\r|\\n/",shell_exec("/sbin/ifconfig | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'");
		//$this->data['ipAdress'] = array_shift(preg_split("/\\r\\n|\\r|\\n/",$this->data['ipAdress']));
		$this->data['hostName'] = shell_exec('hostname');
		
		$this->getMinerConfig();
		
		file_put_contents($this->configFileName, json_encode($this->data));
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
		$this->data['numOfGpu'] = count($gpuInfo);
	}
	private function getMinerConfig()
	{
		$url = 'home.ccs.at:8080/GetMinerConfig.php'; 
		//Initiate cURL.
		$ch = curl_init($url);


		//The JSON data.
		$jsonData = array(
		'hostName' => $this->data['hostName'],
		'minerUid' => $this->data['minerUid'],
		);
		 
		//Encode the array into JSON.
		$jsonDataEncoded = json_encode($jsonData);
		//echo "--------------\n" . 'Json-Data' . $jsonDataEncoded . "\n--------------";
		 
		//Tell cURL that we want to send a POST request.
		curl_setopt($ch, CURLOPT_POST, 1);
		 
		//Attach our encoded JSON string to the POST fields.
		curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);
		 
		//Set the content type to application/json
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
		 
		$result = curl_exec($ch);
		
		curl_close($ch);
		
		var_dump($result);
		

	}
}
	

?>