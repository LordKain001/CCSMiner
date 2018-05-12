<?php

function getGpuInfo()
{
	
 $descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	   2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);

	$env = array('some_option' => 'aeiou');

echo "xmr-stak start\n";

$clinfoProcess = array(
	"process" => "clinfo -l",
	"directory" => "",
	"descriptorspec"  => $descriptorspec,
	"pipes" => NULL,
	"resource" => NULL,
	);
	
$clinfoProcess["resource"] = proc_open($clinfoProcess["process"], $clinfoProcess["descriptorspec"], $clinfoProcess["pipes"], $clinfoProcess["directory"], $env );	
//var_dump($xmrStakProcess);	

sleep(5);

if (is_resource($clinfoProcess["resource"])) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt
	
	$pipes = $clinfoProcess["pipes"];
	
	//stream_set_timeout($pipes[1], 2);
	stream_set_blocking($pipes[1], FALSE);
	$gpuInfo["info"] = stream_get_contents($pipes[1]);// read from the pipe 
	 
    unset($pipes);


}else
 {
 echo "No Data\n";
 }

$gpuInfo["info"] = explode("\n",$gpuInfo["info"]);
foreach ($gpuInfo["info"] as &$value) 
	{
	   $value = substr($value,12);
	}
	array_pop($gpuInfo["info"]);
	array_shift($gpuInfo["info"]);
	unset($value);
	$gpuInfo["count"] = count($gpuInfo["info"]);

	return $gpuInfo;

	
}

function isHashrate($value)
{
	$query = "| ";
	if(substr( $value, 0, strlen($query) ) === $query)
	{
		return true;
	}
}


function getNumOfGpu(){

	
}

function extractHashrate($report)
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

function ParsexmrReport($xmrReport)
{

	if (!(isset($xmrReport["hashreport"])) || !(isset($xmrReport["connection"])) ||  !(isset($xmrReport["results"]))) {
		return "\nData not Set\n";
	}





	$xmrReport["cpuReport"] = substr($xmrReport["hashreport"], strpos($xmrReport["hashreport"], "HASHRATE REPORT - CPU"), strpos($xmrReport["hashreport"], "HASHRATE REPORT - AMD"));
	$xmrReport["cpuReport"] = explode("\n", $xmrReport["cpuReport"]);
	$xmrReport["cpuReport"] = array_filter($xmrReport["cpuReport"], "isHashrate");

	$xmrReport["amdReport"] = substr($xmrReport["hashreport"], strpos($xmrReport["hashreport"], "HASHRATE REPORT - AMD"));
	$xmrReport["amdReport"] = explode("\n", $xmrReport["amdReport"]);	
	$xmrReport["amdReport"] = array_filter($xmrReport["amdReport"], "isHashrate");
	$xmrReport["amdReport"] = extractHashrate($xmrReport["amdReport"]);

	unset($xmrReport["hashreport"]);
	
	

	//$xmrReport["cpuReport"] = extractHashrate($xmrReport["cpuReport"]);
	
	//var_dump($xmrReport);
	echo "\n------------------------------------------\n";
	$gpuInfo = getGpuInfo();
	$numOfGpu = $gpuInfo["count"];
	$gpus = [];
	$k = 0;
	for ($i=0; $i < $numOfGpu; $i++) {

		array_push($gpus,array('ID' => $i+1,'10s' => "start", 'AVG' => "start"));
		$thread = [];
		array_push($thread,array_slice($xmrReport["amdReport"][$k++],1,3));
		array_push($thread,array_slice($xmrReport["amdReport"][$k++],1,3));

		
		$gpus[$i]["10s"] = round($thread[0]["10s"] + $thread[1]["10s"]);		
		$gpus[$i]["AVG"] = round((array_sum($thread[0]) + array_sum($thread[1])) / count($thread[0]));

		echo "GPU " . $gpus[$i]['ID'] . ": " . $gpus[$i]['10s'] . "H/s AVG:" . $gpus[$i]['AVG'] ."H/s\n";
	}
	unset($k,$i);
	echo "Total: " . array_sum(array_column($gpus, 'AVG'))/1000 ."kH/s";
	echo "\n------------------------------------------\n";

	$xmrReport["gpus"] = $gpus;


	unset($xmrReport["amdReport"]);

	return $xmrReport;
}



?>