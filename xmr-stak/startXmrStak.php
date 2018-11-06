<?php





if (!class_exists('xmrStak')) {

	class xmrStak
	{
		public $xmrReport = array();
		
		private $descriptorspec = array(
	   	0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   	1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	   	2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
		);

		private $env = array('some_option' => 'aeiou');

		public $xmrStakProcess = array(
		"process" => "./xmr-stak",
		"directory" => "xmr-stak",
		"descriptorspec"  => NULL,
		"pipes" => NULL,
		"resource" => NULL,
		);

		private $gpus = array();


		/*
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
		);*/

		function __construct($xmrInputData, $minerInputData)
		{
			//Sanity checks
			
			if(file_exists('amd.txt')){
			    unlink('amd.txt');
			}else{
			    echo 'amd.txt not found';
			}
			if(file_exists('pools.txt')){
			    unlink('pools.txt');
			}else{
			    echo 'pools.txt not found';
			}


			if (isset($minerInputData['minerUid'])) {
				$minerId = $minerInputData['minerUid'];	
			}

			if (isset($minerInputData['numOfGpu'])) {
				$numOfGpu = $minerInputData['numOfGpu'];	
			}


			if (isset($xmrInputData['poolAdress'])) {
				$poolAdress = $xmrInputData['poolAdress'];	
			}

			if (isset($xmrInputData['walletAdress'])) {
				$Walletadress = $xmrInputData['walletAdress'];	
			}

			if (isset($xmrInputData['currency'])) {
				$currency = $xmrInputData['currency'];	
			}

			if ((isset($xmrInputData['multipleIntesity']) || (!$xmrInputData['multipleIntesity'] > 0))) {
				$multipleIntesity = $xmrInputData['multipleIntesity'];	
			}else
			{
				$multipleIntesity = 25;
			}

			if ((isset($xmrInputData['worksize']) || (!$xmrInputData['worksize'] > 0))) {
				$worksize = $xmrInputData['worksize'];	
			}else
			{
				$worksize = 16;
			}


			$this->xmrStakProcess["descriptorspec"] = $this->descriptorspec;


			for ($i=0; $i < $numOfGpu; $i++) {
				array_push($this->gpus,array('ID' => $i+1,'Status' => "new" ,'10s' => 0, 'AVG' => 0));
			}
			unset($i);











			$pooldata = '"pool_list" :
			[
			  {"pool_address" : "'. $poolAdress . '",
			  "wallet_address" : "'. $Walletadress . '",
			  "rig_id" : "'. $minerId . '",
			  "pool_password" : "'. $minerId . '",
			  "use_nicehash" : true,
			  "use_tls" : false,
			  "tls_fingerprint" : "",
			  "pool_weight" : 1 },
			],
			"currency" : "'.$currency.'",';

			file_put_contents("pools.txt", $pooldata);




			$amdData = '"gpu_threads_conf" : [';
							
			$intensity = $worksize * $multipleIntesity;
			$counter = 0;


			for ($i=0; $i < $numOfGpu; $i++) { 
				  $amdData .= '
			{
				"index" : '. $counter .',
				"intensity" : '.$intensity.',
				"worksize" : '.$worksize.',
				"affine_to_cpu" : false,
				"strided_index" : 1,
				"mem_chunk" : 2,
				"unroll" : 8,
				"comp_mode" : true
			},
			{
				"index" : '. $counter .',
				"intensity" : '.$intensity.',
				"worksize" : '.$worksize.',
				"affine_to_cpu" : false,
				"strided_index" : 1,
				"mem_chunk" : 2,
				"unroll" : 8,
				"comp_mode" : true
			},';
			}		
			unset($i);
			
			
			$amdData .= '
				],

				"platform_index" : 0,';

			//var_dump($amdData);

			file_put_contents("amd.txt", $amdData);

		}


		public function startMining()
		{

			echo "Start xmr-stak";

			$this->xmrStakProcess["resource"] = proc_open($this->xmrStakProcess["process"], $this->xmrStakProcess["descriptorspec"], $this->xmrStakProcess["pipes"], $this->xmrStakProcess["directory"], $this->env );	
		}

		public function UpdateReport()
		{

			echo "update xmr-stak";

			if (is_resource($this->xmrStakProcess["resource"])) {
		    // $pipes now looks like this:
		    // 0 => writeable handle connected to child stdin
		    // 1 => readable handle connected to child stdout
		    // Any error output will be appended to /tmp/error-output.txt
			
			$pipes = $this->xmrStakProcess["pipes"];
			
			//stream_set_timeout($pipes[1], 2);
			stream_set_blocking($pipes[1], FALSE);
			echo stream_get_contents($pipes[1]);// read from the pipe 
			
			fwrite($pipes[0], "h");
			sleep(1);
			$this->xmrReport["hashreport"] = stream_get_contents($pipes[1]);// read from the pipe 
			fwrite($pipes[0], "c");
			sleep(1);
			$this->xmrReport["connection"] = stream_get_contents($pipes[1]);// read from the pipe 
			fwrite($pipes[0], "r");
			sleep(1);
			$this->xmrReport["results"] = stream_get_contents($pipes[1]);// read from the pipe 
			sleep(1);

			$this->ParsexmrReport($this->xmrReport);

			unset($pipes);


			}else
			{
			echo "No Data\n";
			}


			var_dump($this->gpus);


		}




		private function isHashrate($value)
		{
			$query = "| ";
			if(substr( $value, 0, strlen($query) ) === $query)
			{
				return true;
			}
		}


		private function extractHashrate($report)
		{
			$hashrate = [];
			$keys = NULL;
			$value = NULL;
			foreach ($report as $value) 
			{

				$value = explode("|", $value);
				array_shift($value);
				array_pop($value);
				foreach ($value as &$value1) {
					$value1 = trim($value1);
				}
				unset($value1);
				
				array_push($hashrate, array_slice($value, 0, 4));
				array_push($hashrate, array_slice($value, 4, 4));
			}
			unset($value);
			$keys = array_shift($hashrate);
			array_shift($hashrate);

			array_filter($hashrate);

			foreach ($hashrate as &$value) 
			{
				array_filter($value);		
				$value = array_combine($keys, $value);
			}
			unset($value);


			return $hashrate;
		}

		private function ParsexmrReport($xmrReport)
		{

			var_dump($xmrReport);

			if (!(isset($xmrReport["hashreport"])) || !(isset($xmrReport["connection"])) ||  !(isset($xmrReport["results"]))) {
				return "\nData not Set\n";
			}

			$xmrReport["cpuReport"] = substr($xmrReport["hashreport"], strpos($xmrReport["hashreport"], "HASHRATE REPORT - CPU"), strpos($xmrReport["hashreport"], "HASHRATE REPORT - AMD"));
			$xmrReport["cpuReport"] = explode("\n", $xmrReport["cpuReport"]);
			$xmrReport["cpuReport"] = array_filter($xmrReport["cpuReport"], array($this, 'isHashrate'));

			$xmrReport["amdReport"] = substr($xmrReport["hashreport"], strpos($xmrReport["hashreport"], "HASHRATE REPORT - AMD"));
			$xmrReport["amdReport"] = explode("\n", $xmrReport["amdReport"]);	
			$xmrReport["amdReport"] = array_filter($xmrReport["amdReport"], array($this, 'isHashrate'));
			$xmrReport["amdReport"] = $this->extractHashrate($xmrReport["amdReport"]);

			unset($xmrReport["hashreport"]);
			
			

			//$xmrReport["cpuReport"] = extractHashrate($xmrReport["cpuReport"]);
			
			//var_dump($xmrReport);
			echo "\n------------------------------------------\n";
			$gpus = [];
			$k = 0;
			if (count($this->gpus)) {
				$numOfGpu = count($this->gpus);
			}else
			{
				$numOfGpu = 0;
			}
			
			for ($i=0; $i < $numOfGpu; $i++) {

				array_push($gpus,array('ID' => $i+1,'10s' => "start", 'AVG' => "start"));
				$thread = [];
				if (!is_null($xmrReport["amdReport"])) {
					array_push($thread,array_slice($xmrReport["amdReport"][$k++],1,3));
					array_push($thread,array_slice($xmrReport["amdReport"][$k++],1,3));

					if ($thread[0]["10s"] == "(na)" || $thread[1]["10s"] == "(na)") {
						$gpus[$i]["10s"] = 0;	
					}else
					{
						$gpus[$i]["10s"] = round($thread[0]["10s"] + $thread[1]["10s"]);
						$gpus[$i]["Status"] = "started";
					}

					if ($gpus[$i]["Status"] == "started") {
						if ($thread[0]["10s"] == "(na)" || $thread[1]["10s"] == "(na)") {
						$gpus[$i]["Status"] = "failed";	
						}
					}					

					if (count($thread[0]) != 0) {
						$gpus[$i]["AVG"] = round((array_sum($thread[0]) + array_sum($thread[1])) / count($thread[0]));
					}else
					{
						$gpus[$i]["AVG"] = 0;
					}

				}
				//echo "GPU " . $gpus[$i]['ID'] . ": " . $gpus[$i]['10s'] . "H/s AVG:" . $gpus[$i]['AVG'] ."H/s\n";
			}
			unset($k,$i);

			$this->gpus = $gpus;
			
			//echo "Total: " . array_sum(array_column($gpus, 'AVG'))/1000 ."kH/s";
			echo "\n------------------------------------------\n";

			$xmrReport["gpus"] = $gpus;


			unset($xmrReport["amdReport"]);

			return $xmrReport;
		}

		public function ReportHashRates()
		{
			foreach ($this->gpus as $key => $value) {
				echo "GPU " . $value['ID'] . ": " . $value['10s'] . "H/s AVG:" . $value['AVG'] ."H/s\n";
			}
			echo "Total: " . array_sum(array_column($this->gpus, 'AVG'))/1000 ."kH/s";
			
		}
	}
}



			




























/*










$tries = 0;
while(1)
{
echo "Starting to configure XMR-Stak: $tries \n";

$configFile = '../config.json';


if (file_exists($configFile))
{
	$config = json_decode(file_get_contents($configFile), TRUE);	
}else
{
	$config = array(
		"minerName" => NULL,
		"installStatus" => 4,
	);

}

var_dump($config["minerName"]);

$ipAdress = array_shift(preg_split("/\\r\\n|\\r|\\n/",shell_exec("/sbin/ifconfig | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'")));
  
shell_exec('rm amd.txt');
shell_exec('rm pools.txt');

//
//----Pools---
//
$url = 'home.ccs.at:8080/GetMinerConfig.php'; 
//Initiate cURL.
$ch = curl_init($url);


//The JSON data.
$jsonData = array(
'minerUid' => $config["minerName"],
'ipAdress' => $ipAdress,
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

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$result = json_decode(curl_exec($ch),True);
curl_close($ch);


if($tries>5)
{
	$poolAdress = "pool.supportxmr.com:3333";
	$Walletadress = "47fWF6DkSumWrMxkpkM1vJ7ZBKrs8SaK7FJUgeVi622y5wedi39TNroQpyCFLyAF59BUGauxFeKXjXMZJiV2dU6iKoPdx2r";
	$currency = "monero";
	$minerId = "BackUp_Miner";
	$worksize = 16;
	$multipleIntesity = 25;
}else
{
	$result = array_shift($result);
	var_dump($result);

	if (isset($result['MinerId'])) {
		$minerId = $result['MinerId'];	
	}

	if (isset($result['PoolAdress'])) {
		$poolAdress = $result['PoolAdress'];	
	}
	if (isset($result['WalletAdress'])) {
		$Walletadress = $result['WalletAdress'];	
	}
	if (isset($result['Currency'])) {
		$currency = $result['Currency'];	
	}
	if (isset($result['multipleIntesity'])) {
		$multipleIntesity = $result['multipleIntesity'];	
	}else
	{
		$multipleIntesity = 25;
	}
	if (isset($result['worksize'])) {
		$worksize = $result['worksize'];	
	}else
	{
		$worksize = 25;
	}
}




$pooldata = '"pool_list" :
[
  {"pool_address" : "'. $poolAdress . '",
  "wallet_address" : "'. $Walletadress . '",
  "rig_id" : "'. $minerId . '",
  "pool_password" : "'. $minerId . '",
  "use_nicehash" : true,
  "use_tls" : false,
  "tls_fingerprint" : "",
  "pool_weight" : 1 },
],
"currency" : "'.$currency.'",';


//var_dump($pooldata);

file_put_contents("pools.txt", $pooldata);




$amdData = '
	"gpu_threads_conf" : [';

if($multipleIntesity >0)
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
	$numOfGpu = count($gpuInfo);



	
	$intensity = $worksize * $multipleIntesity;
	$counter = 0;

	
	foreach ($gpuInfo as $value) {
	  $amdData .= '
	{
		"index" : '. $counter .',
		"intensity" : '.$intensity.',
		"worksize" : '.$worksize.',
		"affine_to_cpu" : false,
		"strided_index" : 1,
		"mem_chunk" : 2,
		"unroll" : 8,
		"comp_mode" : true
	},
	{
		"index" : '. $counter .',
		"intensity" : '.$intensity.',
		"worksize" : '.$worksize.',
		"affine_to_cpu" : false,
		"strided_index" : 1,
		"mem_chunk" : 2,
		"unroll" : 8,
		"comp_mode" : true
	},';
	  $counter++;
	}
	unset($counter,$value);

	
}
$amdData .= '
	],

	"platform_index" : 0,';

//var_dump($amdData);

file_put_contents("amd.txt", $amdData);

echo "Exporting Vars\n";

exec("export GPU_FORCE_64BIT_PTR=1");
exec("export GPU_MAX_HEAP_SIZE=100");
exec("export GPU_MAX_ALLOC_PERCENT=100");
exec("export GPU_SINGLE_ALLOC_PERCENT=100");

 passthru("./xmr-stak");
 echo "\n------------------------------------------\n";
 echo "---------------xmr-stak failed------------\n";
 echo "------------------------------------------\n";
 $tries++;
 sleep(20);
}



		}

	}
}


*/




?>
