
<?php
$configFile = '../config.json';

	//check for Reset
	//If it cant pass the whole script the Error count will increase(2 times wirte on file)
	$file = './Status.php';
	try {
		$status = json_decode(file_get_contents($file), TRUE);	
	} catch (Exception $e) {
		echo "Failed";
	}

	
	echo "\nErrors:" . $status["errors"];
	echo "\nsuccesses:" . $status["sucess"];

	$status["errors"]++;
	if($status["errors"] >= 10)
	{
		echo "------------------------------------------------------------------------------------------------\n";
		echo "---------------------------------------Alive Failed---------------------------------------------\n";
		echo "------------------------------------------------------------------------------------------------\n";
		$status["errors"] = 0;
		$status["sucess"] = 0;
		file_put_contents($file, json_encode($status));
		shell_exec("echo s | sudo tee /proc/sysrq-trigger");
		shell_exec("echo U | sudo tee /proc/sysrq-trigger");
		shell_exec("echo b | sudo tee /proc/sysrq-trigger");
	}
	file_put_contents($file, json_encode($status));

	//Get Data from PC
	$output = shell_exec('ls -lart');
	$gpuInfo = shell_exec('clinfo -l');
	$ipAdress = shell_exec("/sbin/ifconfig | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'");
	$mininglog = shell_exec('journalctl -u ccsMiner.service -n50');
	$Timestamp = shell_exec('date');
	$temperature = shell_exec('sensors');
	$hostName = shell_exec('hostname');
	$uptime = shell_exec("uptime -p");

	//script Data filled by user
	if (file_exists($configFile)) 
	{
		$config = json_decode(file_get_contents($configFile), TRUE);
	}else
	{
		$config["minerName"] = NULL;
	}

	echo"\nminerName: " . $config["minerName"];
//--------------------------
//Versioning
//--------------------------
	echo "\nget Git verions:";
	exec("git fetch");
	$versionBehind = explode("\n",shell_exec('git status -sb'));
	$versionBehind = array_shift($versionBehind);
	$versionBehind = substr($versionBehind,strrpos($versionBehind, '['));

	$scriptVersion = exec('git rev-parse --short HEAD');


	echo $scriptVersion . $versionBehind . "\n";

//--------------------------
//Mining Log
//--------------------------


	$mininglog = explode("\n",$mininglog);
	array_shift($mininglog);
	$mininglog = implode("\n",$mininglog);




	$url = 'home.ccs.at:8080/AliveService.php'; 
	//Initiate cURL.
	$ch = curl_init($url);
	 
	//The JSON data.
	$jsonData = array(
		'scriptversion' => $scriptVersion . $versionBehind,
		'name' => $config["minerName"],
		'hostname' => $hostName,
		'gpuInfo' => $gpuInfo,
		'ipAdress' => $ipAdress,
		'mininglog' => $mininglog,
		'Timestamp' => $Timestamp,
		'Sensors' => $temperature,
		"uptime" => $uptime,
	);
	 
	//Encode the array into JSON.
	$jsonDataEncoded = json_encode($jsonData);
	//echo "--------------\n" . 'Json-Data' . $jsonDataEncoded . "\n--------------";
	 
	//Tell cURL that we want to send a POST request.
	curl_setopt($ch, CURLOPT_POST, 1);
	 
	//Attach our encoded JSON string to the POST fields.
	curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);

	//Send echos to return var
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	 
	//Set the content type to application/json
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); 
	 
	if(curl_exec($ch) === false)
	{
		echo 'Curl-Fehler: ' . curl_error($ch);
		$status["errors"]++;
	}
	else
	{
		echo "Server erreicht\n";
		$status["sucess"]++;
		$status["errors"] = 0;
		
	}
	curl_close($ch);

	file_put_contents($file, json_encode($status));

exit;

function getGitBranch()
{
    $shellOutput = [];
    exec('git branch | ' . "grep ' * '", $shellOutput);
    foreach ($shellOutput as $line) {
        if (strpos($line, '* ') !== false) {
            return trim(strtolower(str_replace('* ', '', $line)));
        }
    }
    return null;
}
?>

