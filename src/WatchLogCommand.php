<?php

namespace j\watchLog;

use j\log\Log;
use Exception;
use j\log\LogInterface;

/**
 * Class WatchLogServer
 * @package j\watchLog
 */
class WatchLogCommand {
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
    public function __construct(array $options = [], $vendorPath){
        if(!$options){
            $options = getopt("d", ["config:"]);
        }

        $this->options = $options;
        $this->vendorPath = $vendorPath;
    }


    public static function usage(){
        echo <<<STR
php WatchLog.php [options]

Options:
    -h, print this message
    -v, debug mode
    
    -d, run as a daemonize mode
    -a <action>, 
        stop: stop the server 
        restart: restart the server
    
    --config value, config file path, 
        default: project_root/config-watchLog.php


STR;
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


    /**
     * @param LogInterface $log
     * @throws Exception
     */
    function run($log = null){
        if(isset($this->options['h'])){
            $this->usage();
            return;
        }

        list($host, $port, $logs) = $this->getConfig();

        if(isset($this->options['a'])){
            $serverName = "{$host}:{$port}";
            $this->manager($serverName, $this->options['a']);
            return;
        }

        $server = new WatchLogServer($host, $port);
        $server->documentRoot = dirname(__DIR__) . "/src/client/";
        $server->actionNs = 'j\\watchLog\\cgi\\';
        $server->setLogger($log ?: new Log());
        foreach($logs as $log){
            $server->addWatchLog($log);
        }

        if(isset($this->options['d'])){
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
    }

    /**
     * @param string $serverName
     * @param string $action
     * @throws Exception
     */
    protected function manager($serverName, $action = 'shutdown')
    {
        if($action == 'stop'){
            $action = 'shutdown';
        }

        $url = "http://{$serverName}/cgi/manager/{$action}";
        $rs = file_get_contents($url);
        if(!$rs || !json_decode($rs, true)){
            throw new Exception("$action fail, empty return on this server");
        }
        echo $rs. "\n";
    }
}