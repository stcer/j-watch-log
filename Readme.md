# 概述

用来监听服务器日志文件的变化

## install

```
composer install stcer/j-watch-log:*
```

## config

创建配置文件设置相关参数, 配置logs监听多个日志文件, 

使用 --config configFilePath 设置配置文件路径,  
默认配置文件 project_root/config-watchLog.php

```php
<?php

return [
    'server' => '0.0.0.0',
    'port' => 9504,
    'logs' => [
        __DIR__ . '/tmp/test.log'
    ]
];
```

## usage

**帮助信息**  
php vendor/bin/WatchLog.php -h

```
php WatchLog.php [options]
Options:
    -h, print this message
    -v, debug mode
    
    -d, run as a daemonize mode
    
    --config value, config file path, 
        default: project_root/config-watchLog.php

```

**启动服务**  
php vendor/bin/WatchLog.php

**以守护进程运行**  
php vendor/bin/WatchLog.php -d

**结速进程**  
1. 自行kill主进程id
2. http://your_server:port/cgi/manager/shutdown


## 访问

http://your_server:port


## todo

1. 增加日志到用户监控