
<?php
$configFileName = '../config.json';

echo "------------------------------------------------------------------------------------------------\n";
echo "---------------------------------------start of script------------------------------------------\n";
echo "------------------------------------------------------------------------------------------------\n";

	//check for Reset
	//If it cant pass the whole script the Error count will increase(2 times wirte on file)
	$file = './Status.php';
	try {
		$status = json_decode(file_get_contents($file), TRUE);	
	} catch (Exception $e) {
		echo "Failed";
	}

	Var_dump($status);
	$status["errors"]++;
	if($status["errors"] >= 10)
	{
		$status["errors"] = 0;
		file_put_contents($file, json_encode($status));
		shell_exec("echo s | sudo tee /proc/sysrq-trigger");
		shell_exec("echo b | sudo tee /proc/sysrq-trigger");
	}
	file_put_contents($file, json_encode($status));

	//Get Data from PC
	$output = shell_exec('ls -lart');
	$gpuInfo = shell_exec('clinfo -l');
	$ipAdress = shell_exec("/sbin/ifconfig | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'");
	$mininglog = shell_exec('journalctl -u xmr-stak.service -n50');
	$Timestamp = shell_exec('date');
	$temperature = shell_exec('sensors');
	$hostName = shell_exec('hostname');

	//script Data filled by user
	if (file_exists($configFileName)) 
	{
		$minerName = json_decode(file_get_contents($configFileName), TRUE);
	}else
	{
		$minerName = NULL;
	}
	$scriptVersion = '1.1';


	$mininglog = explode("\n",$mininglog);
	array_shift($mininglog);
	$mininglog = implode("\n",$mininglog);




	$url = 'home.ccs.at:8080/AliveService.php'; 
	//Initiate cURL.
	$ch = curl_init($url);
	 
	//The JSON data.
	$jsonData = array(
		'scriptversion' => $scriptVersion,
		'name' => $minerName,
		'hostname' => $hostName,
		'gpuInfo' => $gpuInfo,
		'ipAdress' => $ipAdress,
		'mininglog' => $mininglog,
		'Timestamp' => $Timestamp,
		'Sensors' => $temperature,
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
	 
	if(curl_exec($ch) === false)
	{
		echo 'Curl-Fehler: ' . curl_error($ch);
		$status["errors"]++;
	}
	else
	{
		echo "Operation ohne Fehler vollständig ausgeführt\n";
		$status["sucess"]++;
		$status["errors"] = 0;
		
	}
	curl_close($ch);

	file_put_contents($file, json_encode($status));


?>