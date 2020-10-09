<?php
# LogFile.php
/**
 * User: Administrator
 * Date: 2019/6/26 0026
 * Time: 下午 12:03
 */
namespace j\watchLog;

use function proc_close;
use function stream_get_contents;
use function swoole_event_add;

class TailFile
{
    protected $file;

    public $onData;

    protected $rs;

    protected $key;

    protected $pid;

    /**
     * LogFile constructor.
     * @param string $key 索引
     * @param string $file
     */
    public function __construct($file, $key)
    {
        $this->key = $key;
        $this->file = $file;
    }

    public function start()
    {
        $desc = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
            );

        $process = $this->rs = proc_open("tail -f {$this->file}", $desc, $pipes);
        if (!is_resource($process)) {
            exit("execute cmd fail\n");
        }

        swoole_event_add($pipes[1], function ($fp) {
            $this->send(stream_get_contents($fp));
        });
    }

    public function stop()
    {
        if (!$this->isStarted()) {
            return;
        }
        swoole_event_del($this->rs->pipe);
        proc_close($this->rs);
        unset($this->rs);
    }

    private function isStarted()
    {
        return isset($this->rs);
    }

    private function send($data)
    {
        if ($this->onData) {
            call_user_func($this->onData, [
                'msg' => $data,
                'index' => $this->key
            ]);
        }
    }
}
