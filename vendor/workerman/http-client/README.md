# Http client
Asynchronous http client for PHP based on workerman.

-  Asynchronous requests.

-  Uses PSR-7 interfaces for requests, responses.

-  Build-in connection pool.

-  Support Http proxy.

-  Parallel Request. (use 'revolt/event-loop')

# Installation
`composer require workerman/http-client`

# Examples
**example.php**
```php
<?php
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker();
$worker->onWorkerStart = function () {
    $http = new Workerman\Http\Client();

    $http->get('https://example.com/', function ($response) {
        var_dump($response->getStatusCode());
        echo $response->getBody();
    }, function ($exception) {
        echo $exception;
    });

    $http->post('https://example.com/', ['key1' => 'value1', 'key2' => 'value2'], function ($response) {
        var_dump($response->getStatusCode());
        echo $response->getBody();
    }, function ($exception) {
        echo $exception;
    });

    $http->request('https://example.com/', [
        'method' => 'POST',
        'version' => '1.1',
        'headers' => ['Connection' => 'keep-alive'],
        'data' => ['key1' => 'value1', 'key2' => 'value2'],
        'success' => function ($response) {
            echo $response->getBody();
        },
        'error' => function ($exception) {
            echo $exception;
        }
    ]);
};
Worker::runAll();
```

Run with commands `php example.php start` or `php example.php start -d`

# Optinons
```php
<?php
require __DIR__ . '/vendor/autoload.php';
use Workerman\Worker;
$worker = new Worker();
$worker->onWorkerStart = function(){
    $options = [
        'max_conn_per_addr' => 128,
        'keepalive_timeout' => 15,
        'connect_timeout'   => 30,
        'timeout'           => 30,
    ];
    $http = new Workerman\Http\Client($options);

    $http->get('http://example.com/', function($response){
        var_dump($response->getStatusCode());
        echo $response->getBody();
    }, function($exception){
        echo $exception;
    });
};
Worker::runAll();
```

# Proxy
```php
require __DIR__ . '/vendor/autoload.php';
use Workerman\Worker;
$worker = new Worker();
$worker->onWorkerStart = function(){
    $options = [
        'max_conn_per_addr' => 128,
        'keepalive_timeout' => 15,
        'connect_timeout'   => 30,
        'timeout'           => 30,
        // 'context' => [
        //     'http' => [
        //         // All use '$http' will cross proxy.  The highest priority here. !!!
        //         'proxy' => 'http://127.0.0.1:1080',
        //     ],
        // ],
    ];
    $http = new Workerman\Http\Client($options);

    $http->request('https://example.com/', [
        'method' => 'GET',
        'proxy' => 'http://127.0.0.1:1080',
         // 'proxy' => 'socks5://127.0.0.1:1081',
        'success' => function ($response) {
            echo $response->getBody();
        },
        'error' => function ($exception) {
            echo $exception;
        }
    ]);
};
Worker::runAll();

```

# Parallel
```php
use Workerman\Worker;

require_once __DIR__ . '/vendor/autoload.php';

$worker = new Worker();
$worker->onWorkerStart = function () {
    $http = new Workerman\Http\ParallelClient();

    $http->batch([
        ['http://example.com', ['method' => 'POST', 'data' => ['key1' => 'value1', 'key2' => 'value2']]],
        ['https://example2.com', ['method' => 'GET']],
    ]);
    $http->push('http://example3.com');

    $result = $http->await(false);
    // $result:
    // [
    //     [bool $isSuccess = true, Workerman\Http\Response $response],
    //     [bool $isSuccess = false, Throwable $error],
    //     [bool $isSuccess, Workerman\Http\Response $response],
    // ]

};
Worker::runAll();
```

# License

MIT
