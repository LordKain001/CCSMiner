<?php

//include 'ConfigManager/ConfigManager.php';


/*
$ConfigManager = new configManager;
//var_dump($ConfigManager);
echo "end of Configmanager";
	sleep(5);
*/

$childPids = array();

$pid = pcntl_fork();
if ($pid == -1) {   //fork failed. May be extreme OOM condition
		die('pcntl_fork failed');
	} elseif ($pid) {   //parent process                
		$childPids[] = $pid;
		chdir("MinerAlive");
	} else {            //child process                
		echo "xmr-stak Fork start\n";
		chdir("xmr-stak");
		passthru("sudo php ./startXmrStak.php");
		echo "xmr-stak Fork Succes\n";
		
	}
/*
$pid = pcntl_fork();
if ($pid == -1) {   //fork failed. May be extreme OOM condition
		die('pcntl_fork failed');
	} elseif ($pid) {   //parent process                
		$childPids[] = $pid;
	} else {            //child process                
		echo "MinerAlive Fork start $minerAlivePid\n";	
		chdir("MinerAlive");
		passthru("php ./MinerAlive.php");
		echo "Miner Alive Fork Succes\n";	
		
	}
*/

		
while (1) {
   
   echo "\n------------------------------------------\n";
 echo "---------------Allive---------------------\n";
 echo "------------------------------------------\n";
  exec("sudo php ./MinerAlive.php");
  sleep(60);
}

?>
