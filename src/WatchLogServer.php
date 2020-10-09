<?php

namespace j\watchLog;

use j\tool\Strings;
use swoole_websocket_server;

use j\network\http\Server;

/**
 * Class WatchLogServer
 * @package j\watchLog
 */
class WatchLogServer extends Server
{
    protected $logs = [];

    /**
     * @var TailFile[]
     */
    protected $files = [];

    /**
     * @var array
     */
    protected $users = [];

    /**
     * @param $file
     */
    public function addWatchLog($file)
    {
        if (!file_exists($file)) {
            trigger_error('file not found');
            return;
        }
        $this->logs[] = $file;
    }

    /**
     * @param \swoole_http_server $server
     */
    protected function bindEvent($server)
    {
        // bind other event
        parent::bindEvent($server);

        // bind web socket event
        $this->bindEventOnTarget($server, [
            'onOpen' => 'Open',
            'onMessage' => 'message',
            'onClose' => 'Close',
        ], $this);
    }

    /**
     * @return swoole_websocket_server
     */
    protected function createServer()
    {
        return new swoole_websocket_server($this->ip, $this->port);
    }

    /**
    * @param \swoole_http_server $server
    * @throws
    */
    protected function onServerCreate($server)
    {
        parent::onServerCreate($server);
        $this->setOption('dispatch_mode', 2);
        $this->setOption('worker_num', 1);
    }

    public function onWorkerStart()
    {
        $this->initWatch();
    }

    protected function initWatch()
    {
        if (!$this->logs) {
            throw new \Exception("Watch log file is empty");
        }

        foreach ($this->logs as $key => $file) {
            $tailFile = new TailFile($file, $key);
            $tailFile->onData = function ($data) {
                $this->broadcast($data);
            };
            $tailFile->start();
            $this->files[$key] = $tailFile;
        }
    }

    /**
     * 删除事件循环, kill进程
     */
    public function onWorkerStop()
    {
        $this->log("Stop child process");
        foreach ($this->files as $work) {
            $work->stop();
        }
    }

    /**
     * 广播JSON数据
     * @param $data
     */
    protected function broadcast($data)
    {
        foreach ($this->users as $fd) {
            $this->send($fd, $data);
        }
    }

    /**
     * @param swoole_websocket_server $server
     * @param $req
     */
    public function onOpen($server, $req)
    {
        $this->users[(int)$req->fd] = $req->fd;
        $this->send($req->fd, ['msg' => "welcome"]);
        $this->send($req->fd, ['files' => $this->logs, 'type' => 'files']);
    }

    /**
     * @param swoole_websocket_server $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        unset($this->users[(int)$fd]);
    }

    /**
     * @param swoole_websocket_server $server
     * @param $frame
     */
    public function onMessage($server, $frame)
    {
        $data = $this->unpack($frame->data);
        if (!$data) {
            return;
        }

        if ($data['cmd'] == 'restart') {
            $this->send($frame->fd, ['msg' => "server will reload"]);
            $this->getSwoole()->reload();
        } elseif ($data['cmd'] == 'add') {
            $file = $data['file'];
            if (!file_exists($file)) {
                $this->send($frame->fd, [
                    'msg' => 'file not found, cwd:' . getcwd(),
                    'type' => 'error'
                ]);
            } elseif (in_array($file, $this->logs)) {
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
            }
        }
    }

    /**
     * @param $fd
     * @param $data
     */
    protected function send($fd, $data)
    {
        if (isset($data['msg']) && is_string($data['msg'])) {
            if (!Strings::valid($data['msg'])) {
                $encode = mb_detect_encoding($data['msg'], 'gbk, gb2312');
                $data['msg'] = mb_convert_encoding($data['msg'], 'utf-8', $encode);
            }
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
    private function pack($data)
    {
        $data['date'] = date('H:i:s');
        return json_encode($data);
    }

    private function unpack($message)
    {
        return json_decode($message, true);
    }
}
