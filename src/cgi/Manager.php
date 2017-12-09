<?php

namespace j\watchLog\cgi;

use j\network\http\AbstractAction;

/**
 * Class Manager
 * @package j\watchLog\cgi
 */
class Manager extends AbstractAction{

    /**
     * 关闭服务
     */
    function shutdownAction(){
        $this->responseJson([
            'code' => 200
        ]);
        $this->server->getSwoole()->shutdown();
    }

    /**
     * 重启服务
     */
    function restartAction(){
        $this->responseJson([
            'code' => 200
        ]);
        $this->server->getSwoole()->reload();
    }
}