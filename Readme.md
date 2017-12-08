# 概述

用来监听服务器日志文件的变化

## install

```
composer install stcer/j-watch-log:*
```

## config

项目根目录创建配置文件 config-watchLog.php, 根据实际情况修改相关参数, 配置logs监听多个日志文件

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

## run
```
php vendor/bin/watchLogServer.php

# 以守护进程运行
php vendor/bin/watchLogServer.php -d

# 结速进程
# 自行kill主进程id
```

## 访问

http://host:port
