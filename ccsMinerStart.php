<?php

include "./ConfigManager/ConfigManager.php";
include "xmrParser.php";



passthru("sudo chmod 777 -R ../CCSMiner");

$configFile = "config.json";
$config = NULL;


if (file_exists($configFile))
{
	$config = json_decode(file_get_contents($configFile), TRUE);	
}else
{
	$config = array(
		"minerName" => "",
		"installStatus" => 1,
	);
	echo"Enter Miner-ID:";
	$config["minerName"] = trim(fgets(STDIN)); // reads one line from STDIN
	file_put_contents($configFile, json_encode($config));
}

echo "MinerName: " . $config["minerName"] . "\n";

	{
		"index" : 0,
		"intensity" : 400,
		"worksize" : 16,
		"affine_to_cpu" : false,
		"strided_index" : 1,
		"mem_chunk" : 2,
		"unroll" : 8,
		"comp_mode" : true
	},

	//check for Reset
	//If it cant pass the whole script the Error count will increase(2 times wirte on file)
	$statusFile = './MinerAlive/status.json';
	unlink($statusFile);


switch ($config["installStatus"]) {
	case '1':
		if (!file_exists("/etc/systemd/system/ccsMiner.service"))
		{
			passthru("sudo cp -v ccsMiner.service /etc/systemd/system");
		}

		if (!file_exists("/etc/systemd/system/multi-user.target.wants/ccsMiner.service"))
		{
			//passthru("sudo cp -v ccsMiner.service /etc/systemd/system/multi-user.target.wants");
		}

		passthru('systemctl enable ccsMiner.service');		

		passthru('echo "vm.nr_hugepages=128" >> /etc/sysctl.conf');
		passthru('echo "kernel.panic = 1" >> /etc/sysctl.conf');
		passthru('echo "kernel.sysrq = 1" >> /etc/sysctl.conf');
		passthru('sysctl -p');

		passthru('echo "soft memlock 262144" >> /etc/security/limits.conf');
		passthru('echo "hard memlock 262144" >> /etc/security/limits.conf');

		break;

		
	case '2':
		passthru("sudo apt install -y libssl-dev cmake build-essential libhwloc-dev lm-sensors git ssh php php-curl clinfo libmicrohttpd-dev libssl-dev cmake build-essential libhwloc-dev opencl-amdgpu-pro-dev");
		passthru("sudo apt dist-upgrade -y");
		passthru("sudo apt update -y");
		passthru("sudo apt upgrade -y");

		break;
	
	case '3':
		passthru("wget --referer=http://support.amd.com https://drivers.amd.com/drivers/linux/amdgpu-pro-18.40-676022-ubuntu-18.04.tar.xz");
		passthru("sudo chmod 777 amdgpu-pro-18.40-676022-ubuntu-18.04.tar.xz");
		passthru("tar -Jxvf amdgpu-pro-18.40-676022-ubuntu-18.04.tar.xz");
		passthru("sudo chmod 777 -R amdgpu-pro-18.40-676022-ubuntu-18.04");
		passthru("amdgpu-pro-18.40-676022-ubuntu-18.04/amdgpu-pro-install -y --opencl=pal,legacy --headless");
	
	case '4':

	
	default:
		# code...
		break;
}






if ($config["installStatus"]<4) {
	
	$config["installStatus"]++;
	var_dump($config);
	file_put_contents($configFile, json_encode($config));


	echo"reboot";
	sleep(60);	
	exec("sudo reboot");
}



 $descriptorspec = array(
	   0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
	   1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
	   2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);

$env = array('some_option' => 'aeiou');

echo "xmr-stak start\n";

$xmrStakProcess = array(
	"process" => "php ./startXmrStak.php",
	"directory" => "xmr-stak",
	"descriptorspec"  => $descriptorspec,
	"pipes" => NULL,
	"resource" => NULL,
	);
	
$xmrStakProcess["resource"] = proc_open($xmrStakProcess["process"], $xmrStakProcess["descriptorspec"], $xmrStakProcess["pipes"], $xmrStakProcess["directory"], $env );	
//var_dump($xmrStakProcess);	

$minerAliveProcess = array(
	"process" => "php ./MinerAlive.php",
	"directory" => "MinerAlive",
	"descriptorspec"  => $descriptorspec,
	"pipes" => NULL,
	"resource" => NULL,
	);

//$minerAliveProcess["resource"] = proc_open($minerAliveProcess["process"], $minerAliveProcess["descriptorspec"], $minerAliveProcess["pipes"], $minerAliveProcess["directory"], $env );	
//var_dump($minerAliveProcess);	
//echo "Miner Alive Fork Succes\n";


		
while (1) {
	sleep(60);
   
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

	sleep(10);
	echo stream_get_contents($pipes[1]);// read from the pipe 
	unset($pipes);
    
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
	echo stream_get_contents($pipes[1]);// read from the pipe 
	
	fwrite($pipes[0], "h");
	sleep(1);
	$xmrReport["hashreport"] = stream_get_contents($pipes[1]);// read from the pipe 
	fwrite($pipes[0], "c");
	sleep(1);
	$xmrReport["connection"] = stream_get_contents($pipes[1]);// read from the pipe 
	fwrite($pipes[0], "r");
	sleep(1);
	$xmrReport["results"] = stream_get_contents($pipes[1]);// read from the pipe 
	sleep(1);

	//var_dump($xmrReport);

	$xmrReport = ParsexmrReport($xmrReport);

    var_dump($xmrReport["connection"]);

 
    unset($pipes);


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
