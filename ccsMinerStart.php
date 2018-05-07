<?php

//include 'ConfigManager/ConfigManager.php';


/*
$ConfigManager = new configManager;
//var_dump($ConfigManager);
echo "end of Configmanager";
	sleep(5);
*/



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






switch ($config["installStatus"]) {
	case '1':
		if (!file_exists("/etc/systemd/system/ccsMiner.service"))
		{
			passthru("sudo cp -v ccsMiner.service /etc/systemd/system");
		}

		if (!file_exists("/etc/systemd/system/multi-user.target.wants/ccsMiner.service"))
		{
			passthru("sudo cp -v ccsMiner.service /etc/systemd/system/multi-user.target.wants");
		}
		break;
		
	case '2':
		passthru("sudo apt install -y libmicrohttpd-dev libssl-dev cmake build-essential libhwloc-dev lm-sensors git ssh php php7.0-curl clinfo");
		passthru("sudo apt dist-upgrade -y");
		passthru("sudo apt update -y");
		passthru("sudo apt upgrade -y");

		exec('echo "vm.nr_hugepages=128" >> /etc/sysctl.conf');
		exec('echo "kernel.panic = 1" >> /etc/sysctl.conf');
		exec('echo "kernel.sysrq = 1" >> /etc/sysctl.conf');
		passthru('sysctl -p');

		exec('echo "soft memlock 262144" >> /etc/security/limits.conf');
		exec('echo "hard memlock 262144" >> /etc/security/limits.conf');
		break;
	
	case '3':
		passthru("wget --referer=http://support.amd.com https://www2.ati.com/drivers/linux/beta/ubuntu/amdgpu-pro-17.40.2712-510357.tar.xz");
		exec("tar -Jxvf amdgpu-pro-17.40.2712-510357.tar.xz");
		exec("sudo chmod 777 -R amdgpu-pro-17.40.2712-510357");
		passthru("amdgpu-pro-17.40.2712-510357/amdgpu-pro-install -y --compute");
	
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
	sleep(10);
   
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

	sleep(5);
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

	fwrite($pipes[0], "h");
	fwrite($pipes[0], "c");
	fwrite($pipes[0], "r");
	sleep(1);
    echo stream_get_contents($pipes[1]);// read from the pipe 

    unset($pipes);


}else
 {
 echo "No Data\n";
 }
 



	//exec("sudo php ./MinerAlive.php");


 	echo "\n------------------------------------------\n";
	echo "---------------sleep----------------------\n";
	echo "------------------------------------------\n";
  	sleep(50);
}

?>
