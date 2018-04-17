<?php

$tries = 0;
while(1)
{
echo "Starting to configure XMR-Stak";
var_dump($tries);

$configFileName = '../config.json';

if (file_exists($configFileName)) 
{
	$rigId = json_decode(file_get_contents($configFileName), TRUE);
}else
{
	$rigId = NULL;
}


var_dump($rigId);

$ipAdress = array_shift(preg_split("/\\r\\n|\\r|\\n/",shell_exec("/sbin/ifconfig | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'")));
  
shell_exec('rm amd.txt');
shell_exec('rm pools.txt');

echo "files deleted\n";

//
//----Pools---
//
$url = 'home.ccs.at:8080/GetMinerConfig.php'; 
//Initiate cURL.
$ch = curl_init($url);


//The JSON data.
$jsonData = array(
'minerUid' => $rigId,
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


if($tries>2)
{
	$poolAdress = "pool.supportxmr.com:3333";
	$Walletadress = "47fWF6DkSumWrMxkpkM1vJ7ZBKrs8SaK7FJUgeVi622y5wedi39TNroQpyCFLyAF59BUGauxFeKXjXMZJiV2dU6iKoPdx2r";
	$currency = "monero7";
}else
{
	$result = array_shift($result);
	var_dump($result);
	$rigId = $result['MinerId'];
	$poolAdress = $result['PoolAdress'];
	$Walletadress = $result['WalletAdress'];
	$currency = $result['Currency'];
}

file_put_contents($configFileName, json_encode($rigId));



$pooldata = '"pool_list" :
[
  {"pool_address" : "'. $poolAdress . '",
  "wallet_address" : "'. $Walletadress . '",
  "rig_id" : "'. $rigId . '",
  "pool_password" : "'. $rigId . '",
  "use_nicehash" : false,
  "use_tls" : false,
  "tls_fingerprint" : "",
  "pool_weight" : 1 },
],
"currency" : "'.$currency.'",';


//var_dump($pooldata);

file_put_contents("pools.txt", $pooldata);


$gpuInfo = shell_exec('clinfo -l');
$gpuInfo = explode("\n",$gpuInfo);


foreach ($gpuInfo as &$value) {
   $value = substr($value,12);
}
array_pop($gpuInfo);
array_shift($gpuInfo);
unset($value);
$numOfGpu = count($gpuInfo);

$amdData = '
"gpu_threads_conf" : [';


//var_dump($gpuInfo);

$worksize = 8;
$intensity = $worksize * 50;
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

$amdData .= '
],

"platform_index" : 0,';


//var_dump($amdData);

file_put_contents("amd.txt", $amdData);


 passthru("./xmr-stak");
 echo "\n------------------------------------------\n";
 echo "---------------xmr-stak failed------------\n";
 echo "------------------------------------------\n";
 $tries++;
 sleep(20);
}


?>
