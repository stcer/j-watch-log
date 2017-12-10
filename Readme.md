# ����

����������������־�ļ��ı仯, web����ļ�ͬ���� tail -f fileЧ��,  
��Ҫ��������һ��ϲ���������Ի�����־�ļ�

![](example.png)

ע��: ���������κ��ļ�����, Ϊ�����İ�ȫ�����ڲ��Ի���������, �����������ӷ���ǽ����ȫ����

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

php vendor/bin/WatchLog.php -h

```
php WatchLog.php [options]

Options:
    -h, print this message
    -v, debug mode
    
    -d, run as a daemonize mode
    -a <action>, 
        stop: stop the server 
        restart: restart the server
    
    --config value, config file path, 
        default: project_root/config-watchLog.php

```

## ����

http://your_server:port


## todo

1. ������־���û����
1. ���ӿͻ����ռ�Զ��������־