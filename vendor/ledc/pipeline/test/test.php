<?php

use Ledc\Pipeline\Pipeline;

require_once dirname(__DIR__) . '/vendor/autoload.php';

//初始数据
class Request
{
    public int $number = 1;
}

//管道数组
$pipes = [];
foreach (range(1, 3) as $row) {
    $pipes[] = function ($request, $next) use ($row) {
        echo 'pipe-before' . $row . PHP_EOL;
        $request->number += $row;
        $request = $next($request);
        echo 'pipe-after' . $row . PHP_EOL;
        return $request;
    };
}

//核心逻辑
$init = function ($request) {
    echo 'init start' . PHP_EOL;
    var_dump($request);
    echo 'init end' . PHP_EOL;
    return 'init';
};

$pipeline = new Pipeline();
$result = $pipeline->send(new Request())
    ->through($pipes)
    ->then($init);

var_dump($result);
