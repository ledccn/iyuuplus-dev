<?php
/**
 * 异常配置
 */

return [
    '' => support\exception\Handler::class,
    'admin' => app\exception\AdminHandler::class,
];
