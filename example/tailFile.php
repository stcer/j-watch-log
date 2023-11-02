<?php

use j\watchLog\TailFile;
use Swoole\Process;

require __DIR__ . '/../vendor/autoload.php';

$watchFile = __DIR__ . '/../tmp/test.log';
$tailFile = new TailFile($watchFile, 'demo');
$tailFile->onData = function ($message) {
    echo $message['msg'];
};

$timer = Swoole\Timer::tick(1000, function () use ($watchFile) {
    file_put_contents($watchFile, date('Y-m-d H:i:s') . " demo data \n", FILE_APPEND);
});

$stop = function () use ($tailFile, $timer) {
    echo "Process stop\n";
    Swoole\Timer::clear($timer);
    $tailFile->stop();
    Swoole\Event::exit();
};

// 收到15信号关闭服务
Process::signal(SIGTERM, $stop);
Process::signal(SIGINT, $stop);

$tailFile->start();
