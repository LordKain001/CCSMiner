<?php

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
	$currency = "monero7";
	$minerId = "BackUp_Miner";
	$multipleIntesity = 50;
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
		$multipleIntesity = 50;
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



	$worksize = 8;
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
		"comp_mode" : true
	},
	{
		"index" : '. $counter .',
		"intensity" : '.$intensity.',
		"worksize" : '.$worksize.',
		"affine_to_cpu" : false,
		"strided_index" : 1,
		"mem_chunk" : 2,
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


?>
