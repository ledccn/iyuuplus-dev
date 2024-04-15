<?php

use plugin\cron\app\support\PushNotify;
use Random\RandomException;

/**
 * 向长连接发送计划任务输出信息
 * - 原理：向私有频道private-webman-admin，发布事件shell_output
 * @param int $crontab_id
 * @param string $msg
 * @return bool
 * @author david <367013672@qq.com>
 */
function send_shell_output(int $crontab_id, string $msg): bool
{
    return PushNotify::shellOutput("crontab_id:{$crontab_id}", $msg);
}

/**
 * 获取所有命令
 * @return array
 */
function webman_commands(): array
{
    $webman_commands = json_decode(shell_exec(PHP_BINARY . ' webman list --format=json'), true);
    $commands = array_column($webman_commands['commands'], null, 'name');
    $namespaces = [];
    foreach ($webman_commands['namespaces'] as $item) {
        $namespace = [
            'name' => $item['id'],
            'children' => array_map(function ($v) use ($commands) {
                return [
                    'name' => $v,
                    'value' => $v,
                    'description' => $commands[$v]['description'] ?? '',
                    'usage' => $commands[$v]['usage'] ?? []
                ];
            }, $item['commands']),
        ];
        $namespaces[] = $namespace;
    }
    //$webman_commands['commands'] = $commands;
    $webman_commands['namespaces'] = $namespaces;
    return $webman_commands;
}

/**
 * 执行计划任务
 * @param string $command 完整的命令
 * @return void
 */
function run_crontab_command(string $command = ''): void
{
    if (DIRECTORY_SEPARATOR === '\\') {
        pclose(popen('start /B ' . $command, 'r'));
    } else {
        pclose(popen($command, 'r'));
    }
}

/**
 *
 * @return string
 */
function generate_unique_id(): string
{
    try {
        return \bin2hex(\pack('d', \microtime(true)) . \random_bytes(16));
    } catch (RandomException $exception) {
        return \sha1(\microtime(true) . \uniqid('', true) . \mt_rand());
    }
}
