# ����

����������������־�ļ��ı仯

## install

```
composer install stcer/j-watch-log:*
```

## config

��Ŀ��Ŀ¼���������ļ� config-watchLog.php, ����ʵ������޸���ز���, ����logs���������־�ļ�

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

# ���ػ���������
php vendor/bin/watchLogServer.php -d

# ���ٽ���
# ����kill������id
```

## ����

http://host:port
