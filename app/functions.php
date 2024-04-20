<?php
/**
 * 用户自定义函数（支持热重载）
 * Here is your custom functions.
 */

use plugin\admin\app\model\Base;
use support\Model;

if (is_file(runtime_path('Bencode.php'))) {
    require_once runtime_path('Bencode.php');
}

/**
 * 获取IYUU请求token
 * @return string
 */
function iyuu_token(): string
{
    return getenv('IYUU_TOKEN') ?: '';
}

/**
 * 返回IYUU客户端版本号
 * - 要求PHP版本^8.3
 * @return string
 */
function iyuu_version(): string
{
    return '8.0.1';
}

/**
 * 返回项目名称
 * @return string
 */
function iyuu_project_name(): string
{
    return 'IYUUPlus-dev';
}

/**
 * 检测是否运行在docker环境
 * @return bool
 */
function isDockerEnvironment(): bool
{
    clearstatcache();
    $rs1 = is_file('/etc/php83/conf.d/99-overrides.ini');
    $rs2 = is_file('/etc/s6-overlay/s6-rc.d/svc-iyuu/run');
    return $rs1 && $rs2;
}

/**
 * 在docker环境内，停止自身，等待S6重新拉起进程
 * @return void
 */
function safe_webman_stop(): void
{
    if (isDockerEnvironment()) {
        $cmd = implode(' ', ['php', base_path('start.php'), 'stop']);
        exec($cmd);
        sleep(3);
    }
}

/**
 * 开发专用函数，打印变量exit
 * @param mixed $v
 * @param bool $format
 * @return void
 */
function var_dump_exit(mixed $v, bool $format = true): void
{
    if ($format) {
        var_dump($v);
    } else {
        print_r($v);
    }
    exit;
}

/**
 * 检查只读字段，禁止变更
 * @param array $readonly
 * @param Model|Base $model
 * @return void
 */
function verify_readonly_field(array $readonly, Model|Base $model): void
{
    $dirty = $model->getDirty();
    foreach ($readonly as $field) {
        if (array_key_exists($field, $dirty)) {
            throw new RuntimeException("禁止修改只读字段{$field}");
        }
    }
}

/**
 * 获取当前版本commit
 * @param string $branch
 * @param bool $short
 * @return string
 */
function current_git_commit(string $branch = 'master', bool $short = true): string
{
    $filename = sprintf(base_path() . '/.git/refs/heads/%s', $branch);
    clearstatcache();
    if (is_file($filename)) {
        $hash = file_get_contents($filename);
        $hash = trim($hash);

        return $short ? substr($hash, 0, 7) : $hash;
    }
    return '';
}

/**
 * 获取当前版本时间
 * @param string $branch
 * @param string $format
 * @return string
 */
function current_git_filemtime(string $branch = 'master', string $format = 'Y-m-d H:i:s'): string
{
    $filename = sprintf(base_path() . '/.git/refs/heads/%s', $branch);
    clearstatcache();
    if (is_file($filename)) {
        $time = filemtime($filename);
        return date($format, $time);
    }
    return '';
}
