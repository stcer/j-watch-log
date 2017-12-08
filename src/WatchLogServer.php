<?php

namespace j\watchLog;

use swoole_websocket_server;
use swoole_process;

use j\network\http\Server;

/**
 * Class ImServer
 * @package ws
 */
class WatchLogServer extends Server{

    protected $watchLog = [];

    /**
     * @param $file
     */
    function addWatchLog($file){
        $this->watchLog[] = $file;
    }

    /**
     * @param \swoole_http_server $server
     */
    protected function bindEvent($server) {
        // bind other event
        parent::bindEvent($server);

        // bind web socket event
        $binds = [
            'onOpen' => 'Open',
            'onMessage' => 'message',
            'onClose' => 'Close',
        ];
        foreach($binds as $method => $evt){
            if(method_exists($this, $method)){
                $server->on($evt, array($this, $method));
            }
        }
    }

    /**
     * @return swoole_websocket_server
     */
    protected function createServer() {
        return new swoole_websocket_server($this->ip, $this->port);
    }

	/**
	 * @param \swoole_http_server $server
     * @throws
	 */
	protected function onServerCreate($server){
        if(!$this->watchLog){
            throw new \Exception("Watch log file is empty");
        }

        parent::onServerCreate($server);
		$this->setOption('dispatch_mode', 2);
		$this->setOption('worker_num', 1);
	}

    /**
     * @var swoole_process[]
     */
    protected $works = [];

    /**
     * @param swoole_websocket_server $serv
     */
    function onWorkerStart($serv){
        if ($serv->taskworker) {
            return;
        }

        foreach($this->watchLog as $key => $log){
            $work = new swoole_process(function($process) use($log) {
                $process->exec("/usr/bin/tail", array("-f", $log));
            }, true);

            $pid = $work->start();
            $this->works[$pid] = [$work, $key];
        }

        foreach($this->works as $work){
            list($work, $key) = $work;
            swoole_event_add($work->pipe, function($pipe) use($work, $key){
                $this->log("Read process data");
                $data = $work->read();
                $this->broadcast([
                    'msg' => $data,
                    'index' => $key
                ]);
            });
        }
    }

    /**
     * 广播JSON数据
     * @param $data
     */
    protected function broadcast($data){
        foreach($this->users as $fd){
            $this->send($fd, $data);
        }
    }


    protected $users = [];
    protected $userLogs = [];

    /**
     * @param swoole_websocket_server $server
     * @param $req
     */
    function onOpen($server, $req){
        $this->users[(int)$req->fd] = $req->fd;
        $this->send($req->fd, ['msg' => "welcome\n"]);
        $this->send($req->fd, ['files' => $this->watchLog, 'type' => 'files']);
    }

    /**
     * @param swoole_websocket_server $server
     * @param $fd
     */
    function onClose($server, $fd) {
        unset($this->users[(int)$fd]);
    }

    /**
     * @param swoole_websocket_server $server
     * @param $frame
     */
    function onMessage($server, $frame){
        $data = $this->unpack($frame->data);
        if(!$data){
            return;
        }

        if($data['cmd'] == 'close'){
            // todo close all user's log file
            $this->send($frame->fd, ['msg' => 'close all']);
        } elseif($data['cmd'] == 'add'){
            // todo check unique
            $file = $data['file'];
            if(!file_exists($file)){
                $this->send($frame->fd, ['msg' => 'file not found, cwd:' . getcwd(), 'type' => 'error']);
            } else {
                // todo add user's watch
                $this->userLogs[] = $file;
                $this->send($frame->fd, ['files' => array_merge($this->watchLog, $this->userLogs), 'type' => 'files']);
            }
        }
    }

    /**
     * @param $fd
     * @param $data
     */
    protected function send($fd, $data){
        /** @var \Swoole\WebSocket\Server $server */
        $server = $this->getSwoole();
        $server->push($fd, $this->pack($data));
    }

    /**
     * @param $data
     * @return string
     */
    private function pack($data){
        $data['date'] = date('H:i:s');
        return json_encode($data);
    }

    private function unpack($message) {
        return json_decode($message, true);
    }
}
