<?php

date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

$vendorPath = null;
$composerAutoload = [
    __DIR__ . '/../vendor/autoload.php', // in dev repo
    __DIR__ . '/../../../autoload.php', // installed as a composer binary
    ];
foreach ($composerAutoload as $autoload) {
    if (file_exists($autoload)) {
        require($autoload);
        $vendorPath = realpath(dirname($autoload));
        break;
    }
}

if(!$vendorPath){
    throw new \Exception("Not found autoload.php");
}

$file = dirname($vendorPath) . '/config-watchLog.php';
if(!file_exists($file)){
    throw new Exception("Config file not found({$file})");
}

$conf = include($file);
if(!is_array($conf)){
    throw new Exception("Config file not found({$file})");
}

$host = isset($conf['host']) ? $conf['host'] : '0.0.0.0';
$port = isset($conf['port']) ? $conf['port'] : 9504;
$logs = isset($conf['logs']) ? $conf['logs'] : [];
if(!$logs){
    throw new Exception("Logs not found, please config logs");
}

use j\watchLog\WatchLogServer;
use j\log\Log;

$server = new WatchLogServer($host, $port);
$server->documentRoot = dirname(__DIR__) . "/src/client/";
//$server->actionNs = 'ws\\cgi\\';
$server->setLogger(new Log());
foreach($logs as $log){
    $server->addWatchLog($log);
}

if(getopt('d')){
    $server->daemonize();
}

$server->setOption([
//	'heartbeat_check_interval' => 5,
//	'heartbeat_idle_time' => 10,
    'worker_num' => 1,
	'open_tcp_keepalive' => 1,
	'tcp_keepidle' => 10,
	'tcp_keepcount' => 2,
	'tcp_keepinterval' => 5,
	]);

$server->run();