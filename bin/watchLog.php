<?php

namespace j\watchLog\bin;

date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

// main, init server and run
use j\watchLog\WatchLogServer;
use j\log\Log;
use Exception;

/**
 * Class WatchLog
 * @package j\watchLog\bin
 */
class WatchLog {

    /**
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    private $vendorPath;

    /**
     * WatchLog constructor.
     * @param array $options
     * @param string $vendorPath
     */
    public function __construct(array $options = [], $vendorPath = ''){
        if(!$options){
            $options = getopt("d", ["config:"]);
        }

        $this->options = $options;
        $this->vendorPath = $vendorPath;
    }

    private function usage(){
        echo <<<STR
php WatchLog.php [options]
Options:
    -h, print this message
    -v, debug mode
    
    -d, run as a daemonize mode
    
    --config value, config file path, 
        default: project_root/config-watchLog.php


STR;
    }

    /**
     * @throws Exception
     */
    private function makeVendorPath(){
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

        $this->vendorPath = $vendorPath;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getConfig(){
        $options = $this->options;

        if(isset($options['config'])){
            $confFile = $options['config'];
        } else {
            $confFile = dirname($this->vendorPath) . '/config-watchLog.php';
        }

        if(!file_exists($confFile)){
            throw new Exception("Config file not found({$confFile})");
        }

        ob_start();
        $conf = include($confFile);
        ob_end_clean();

        if(!is_array($conf)){
            throw new Exception("Config file is not well format(must return array)");
        }

        $host = isset($conf['host']) ? $conf['host'] : '0.0.0.0';
        $port = isset($conf['port']) ? $conf['port'] : 9504;
        $logs = isset($conf['logs']) ? $conf['logs'] : [];
        if(!$logs){
            throw new Exception("Logs not found, please config logs");
        }

        return [$host, $port, $logs];
    }


    function run(){
        if(isset($this->options['h'])){
            $this->usage();
            return;
        }

        try{
            if(!$this->vendorPath){
                $this->makeVendorPath();
            }

            list($host, $port, $logs) = $this->getConfig();
            $server = new WatchLogServer($host, $port);
            $server->documentRoot = dirname(__DIR__) . "/src/client/";
            $server->actionNs = 'j\\watchLog\\cgi\\';
            $server->setLogger(new Log());
            foreach($logs as $log){
                $server->addWatchLog($log);
            }

            if(isset($options['d'])){
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
        } catch(Exception $e){
            echo "Error:\n";
            echo $e->getMessage() . "\n";

            if(isset($this->options['v'])){
                echo $e->getTraceAsString();
            }

            echo "\n";
        }
    }
}

// run
$options = getopt("dhv", ["config:"]);
(new WatchLog($options))->run();