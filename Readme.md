# ����

����������������־�ļ��ı仯

## install

```
composer install stcer/j-watch-log:*
```

## config

���������ļ�������ز���, ����logs���������־�ļ�, 

ʹ�� --config configFilePath ���������ļ�·��,  
Ĭ�������ļ� project_root/config-watchLog.php

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

**������Ϣ**  
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

**��������**  
php vendor/bin/WatchLog.php

**���ػ���������**  
php vendor/bin/WatchLog.php -d

**���ٽ���**  
1. ����kill������id
2. http://your_server:port/cgi/manager/shutdown


## ����

http://your_server:port


## todo

1. ������־���û����