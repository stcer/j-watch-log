<?php

namespace j\watchLog\bin;

date_default_timezone_set('Asia/Shanghai');
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0);

// main, init server and run
use j\watchLog\WatchLogCommand;
use Exception;

// run
try{
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
    if(!isset($vendorPath)){
        throw new \Exception("Not found autoload.php");
    }

    $options = getopt("a:dhv", ["config:"]);
    (new WatchLogCommand($options, $vendorPath))->run();
}
catch(Exception $e)
{
    echo "Error:\n";
    echo $e->getMessage() . "\n";

    if(isset($options['v'])){
        echo $e->getTraceAsString();
    }
}

