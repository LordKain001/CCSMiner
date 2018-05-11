<?php

function isHashrate($value)
{
	$query = "| ";
	if(substr( $value, 0, strlen($query) ) === $query)
	{
		return true;
	}
}


function getNumOfGpu(){

	$gpuInfo = shell_exec('clinfo -l');
	$gpuInfo = explode("\n",$gpuInfo);

	foreach ($gpuInfo as &$value) 
	{
	   $value = substr($value,12);
	}
	array_pop($gpuInfo);
	array_shift($gpuInfo);
	unset($value);
	return count($gpuInfo);
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
	$numOfGpu = getNumOfGpu();
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