<?php

namespace j\watchLog;

use swoole_websocket_server;
use swoole_process;

use j\network\http\Server;

/**
 * Class WatchLogServer
 * @package j\watchLog
 */
class WatchLogServer extends Server{

    protected $logs = [];

    /**
     * @param $file
     */
    function addWatchLog($file){
        $this->logs[] = $file;
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
        if(!$this->logs){
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

        foreach($this->logs as $key => $log){
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
     * @param $serv
     */
    function onWorkerStop($serv){
        $this->log("Stop child process");
        foreach($this->works as $pid => $work){
            list($process, $log) = $work;
            $process->kill($pid);
        }
    }

    private function createWatch($log, $key){
        $work = new swoole_process(function($process) use($log) {
            $process->exec("/usr/bin/tail", array("-f", $log));
        }, true);

        $pid = $work->start();
        $this->works[$pid] = [$work, $key];

        swoole_event_add($work->pipe, function($pipe) use($work, $key){
            $this->log("Read user process data");
            $data = $work->read();
            $this->broadcast([
                'msg' => $data,
                'index' => $key
            ]);
        });
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

    /**
     * @param swoole_websocket_server $server
     * @param $req
     */
    function onOpen($server, $req){
        $this->users[(int)$req->fd] = $req->fd;
        $this->send($req->fd, ['msg' => "welcome"]);
        $this->send($req->fd, ['files' => $this->logs, 'type' => 'files']);
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

        if($data['cmd'] == 'restart')
        {
            $this->send($frame->fd, ['msg' => "server will reload"]);
            $this->getSwoole()->reload();
        }
        elseif($data['cmd'] == 'add')
        {
            $file = $data['file'];
            if(!file_exists($file)){
                $this->send($frame->fd, [
                    'msg' => 'file not found, cwd:' . getcwd(),
                    'type' => 'error'
                ]);
            } elseif(in_array($file, $this->logs)){
                $this->send($frame->fd, [
                    'msg' => 'file was exist',
                    'type' => 'error'
                ]);
            } else {
                // todo add file to user watch logs
                $this->logs[] = $file;
                $this->send($frame->fd, [
                    'files' => $this->logs,
                    'type' => 'files'
                ]);

                // create user's watch
                $key = count($this->logs) - 1;
                $this->createWatch($file, $key);
            }
        }
    }

    /**
     * @param $fd
     * @param $data
     */
    protected function send($fd, $data){
        if(isset($data['msg'])){
            $data['msg'] .= "\n";
        }
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
