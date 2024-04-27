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
 * 返回IYUU客户端主版本号
 * @return string
 */
function iyuu_version(): string
{
    return '8.1.0';
}

/**
 * 是否为IYUU的token格式
 * @param string $token
 * @return bool
 */
function is_iyuu_token(string $token): bool
{
    return strlen($token) < 60 && str_starts_with($token, 'IYUU') && strpos($token, 'T') < 15;
}

/**
 * 粗略验证字符串是否为IYUU的token
 * @param string $token
 * @return bool
 * @throws RuntimeException 如果 IYUU_TOKEN 未配置或格式不正确
 */
function check_iyuu_token(string $token = ''): bool
{
    if (!$token) {
        throw new RuntimeException("未配置IYUU_TOKEN：通用设置->系统设置->爱语飞飞token配置");
    }
    if (!is_iyuu_token($token)) {
        throw new RuntimeException("IYUU_TOKEN格式错误 请重新配置: 通用设置->系统设置->爱语飞飞token配置");
    }
    return true;
}

/**
 * Migration初始化数据库（使用的迁移工具Phinx）
 * @return void
 */
function init_migrate(): void
{
    $rs = shell_exec(implode(' ', [PHP_BINARY, base_path('vendor/bin/phinx'), 'migrate', '-e', 'development']));
    echo is_string($rs) ? $rs : 'Migration初始化数据库，失败！！！';
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

/**
 * 更新 .env 文件中的单个值。
 *
 * @param string $key 要更新的键名
 * @param string $value 新的键值
 * @return string
 */
function update_git_EnvValue(string $key, string $value): string
{
    // 读取 .env 文件的内容
    $envFile = base_path('/.env');
    $contents = file_get_contents($envFile);

    // 解析成键值对数组
    $lines = explode("\n", $contents);
    $envData = [];
    foreach ($lines as $line) {
        if (str_contains($line, '=')) {
            list($envKey, $envValue) = explode('=', $line, 2);
            $envData[$envKey] = $envValue;
        }
    }

    // 更新指定的键值对
    $envData[$key] = $value;

    // 重新构建 .env 文件内容
    $newContents = '';
    foreach ($envData as $envKey => $envValue) {
        $newContents .= "$envKey=$envValue\n";
    }

    // 将更新后的内容写入 .env 文件
    file_put_contents($envFile, $newContents);
    return $value;
}
