<?php
/**
 * 进程启动时onWorkerStart时运行的回调配置
 */

return [
    support\bootstrap\Session::class,
    support\bootstrap\LaravelDb::class,
    app\Bootstrap::class,
];
