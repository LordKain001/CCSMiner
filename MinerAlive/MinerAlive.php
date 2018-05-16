
<?php

function getDataFromShell(&$Data, $processCommand, $dir)
{
	$descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	   2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);

	$env = array('some_option' => 'aeiou');

$process = array(
	"process" => $processCommand,
	"directory" => $dir,
	"descriptorspec"  => $descriptorspec,
	"pipes" => NULL,
	"resource" => NULL,
	);
	
$process["resource"] = proc_open($process["process"], $process["descriptorspec"], $process["pipes"], $process["directory"], $env );	
//var_dump($xmrStakProcess);	

sleep(5);

if (is_resource($process["resource"])) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt
	
	$pipes = $process["pipes"];
	
	//stream_set_timeout($pipes[1], 2);
	stream_set_blocking($pipes[1], FALSE);
	$Data = stream_get_contents($pipes[1]);// read from the pipe 
	 
    unset($pipes);


}else
 {
	throw new Exception('No Process Stream Ressource');
 }

}

function isAmdPart($var)
{
	
	if(substr( $var, 0, strlen($query) ) === $query)
	{
		return true;
	}
}

function getGpuTemps($verboseLevel = NULL)
{
	$sensors = Null;
	

	try {
		getDataFromShell($sensors,"sensors","");
		
	} catch (Exception $e) {
		echo"Error Reading Sensor Data";
	}

 	$sensors = explode("\n\n",$sensors);
 	$temp = [];
 	$query = "amd";
 	array_pop($sensors);
 

 	foreach ($sensors as &$value) {
 		$value =explode("\n",$value);

 		$value[2] = preg_replace('!\s+!', ' ',$value[2]);
 		$value[2] = explode(" ",$value[2]);
		array_shift($value[2]);
		$value[2] = array_shift($value[2]);
 		

 		$value[3] = preg_replace('!\s+!', ' ',$value[3]);
 		$value[3] = explode(" ",$value[3]);
		array_shift($value[3]);
		$value[3] = array_shift($value[3]);
 		

 		if (substr( $value[0], 0, strlen($query) ) === $query) {
 			$temp[$value[0]] = [];
 			$temp[$value[0]] += ["FanSpeed" => $value[2]];
 			$temp[$value[0]] += ["temp" => $value[3]];
 		} 			

 	} 	
 	
	$sensors = json_encode($temp);
	if ($verboseLevel) {
		echo "\n";
		foreach ($temp as $key => $value) {
			echo $key . " " . $value . "\n";
		}
	}
	return $sensors;
}


$configFile = '../config.json';

	//check for Reset
	//If it cant pass the whole script the Error count will increase(2 times wirte on file)
	$statusFile = './status.json';


	if (file_exists($statusFile))
	{
		$status = json_decode(file_get_contents($statusFile), TRUE);	
	}else
	{
		$status = array(
			"errors" => 0,
			"sucess" => 0,
		);

		file_put_contents($statusFile, json_encode($status));
	}
	
	echo "\nErrors:" . $status["errors"] . "\n";
	echo "successes:" . $status["sucess"]. "\n";

	$status["errors"]++;
	if($status["errors"] >= 10)
	{
		echo "------------------------------------------------------------------------------------------------\n";
		echo "---------------------------------------Alive Failed---------------------------------------------\n";
		echo "------------------------------------------------------------------------------------------------\n";
		$status["errors"] = 0;
		$status["sucess"] = 0;
		file_put_contents($statusFile, json_encode($status));
		shell_exec("echo s | sudo tee /proc/sysrq-trigger");
		shell_exec("echo U | sudo tee /proc/sysrq-trigger");
		shell_exec("echo b | sudo tee /proc/sysrq-trigger");
	}
	file_put_contents($statusFile, json_encode($status));

	//Get Data from PC
	$output = shell_exec('ls -lart');
	$gpuInfo = shell_exec('clinfo -l');
	$ipAdress = shell_exec("/sbin/ifconfig | grep 'inet addr' | cut -d: -f2 | awk '{print $1}'");
	$mininglog = shell_exec('journalctl -u ccsMiner.service -n50');
	$Timestamp = shell_exec('date');
	$temperature = getGpuTemps(1);
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

	file_put_contents($statusFile, json_encode($status));

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

