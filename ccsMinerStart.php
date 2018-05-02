<?php

//include 'ConfigManager/ConfigManager.php';


/*
$ConfigManager = new configManager;
//var_dump($ConfigManager);
echo "end of Configmanager";
	sleep(5);
*/

 $descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	   2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);

$env = array('some_option' => 'aeiou');

echo "xmr-stak Fork start\n";

$xmrStakProcess = array(
	"process" => "php ./startXmrStak.php",
	"directory" => "xmr-stak",
	"descriptorspec"  => $descriptorspec,
	"pipes" => NULL,
	"resource" => NULL,
	);
	
$xmrStakProcess["resource"] = proc_open($xmrStakProcess["process"], $xmrStakProcess["descriptorspec"], $xmrStakProcess["pipes"], $xmrStakProcess["directory"], $env );	
var_dump($xmrStakProcess);	
echo "xmr-stak Fork Succes\n";





echo "MinerAlive Fork start\n";

$minerAliveProcess = array(
	"process" => "php ./MinerAlive.php",
	"directory" => "MinerAlive",
	"descriptorspec"  => $descriptorspec,
	"pipes" => NULL,
	"resource" => NULL,
	);

$minerAliveProcess["resource"] = proc_open($minerAliveProcess["process"], $minerAliveProcess["descriptorspec"], $minerAliveProcess["pipes"], $minerAliveProcess["directory"], $env );	
var_dump($minerAliveProcess);	
echo "Miner Alive Fork Succes\n";


		
while (1) {
	 sleep(5);
   
	echo "\n------------------------------------------\n";
	echo "---------------alive----------------------\n";
	echo "------------------------------------------\n";

$minerAliveProcess["resource"] = proc_open($minerAliveProcess["process"], $minerAliveProcess["descriptorspec"], $minerAliveProcess["pipes"], $minerAliveProcess["directory"], $env );
if (is_resource($minerAliveProcess["resource"])) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt
	$pipes = $minerAliveProcess["pipes"];
	stream_set_blocking($pipes[1], FALSE);
	echo stream_get_contents($pipes[1]);// read from the pipe 

    
}else
 {
 echo "No Data\n";
 }




	echo "\n------------------------------------------\n";
	echo "---------------xmr-stak:----------------------\n";
	echo "------------------------------------------\n";

  if (is_resource($xmrStakProcess["resource"])) {
    // $pipes now looks like this:
    // 0 => writeable handle connected to child stdin
    // 1 => readable handle connected to child stdout
    // Any error output will be appended to /tmp/error-output.txt
	
	$pipes = $xmrStakProcess["pipes"];
	
	//stream_set_timeout($pipes[1], 2);
	stream_set_blocking($pipes[1], FALSE);

	fwrite($pipes[0], "h");
	fwrite($pipes[0], "c");
	fwrite($pipes[0], "r");
	sleep(1);
    echo stream_get_contents($pipes[1]);// read from the pipe 

    


}else
 {
 echo "No Data\n";
 }
 



	//exec("sudo php ./MinerAlive.php");


 	echo "\n------------------------------------------\n";
	echo "---------------sleep----------------------\n";
	echo "------------------------------------------\n";
  	sleep(5);
}

?>
