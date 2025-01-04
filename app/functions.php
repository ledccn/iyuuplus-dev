<?php
/**
 * 用户自定义函数（支持热重载）
 * Here is your custom functions.
 */

use Iyuu\BittorrentClient\ClientDownloader;
use Iyuu\ReseedClient\Client;
use Iyuu\SiteManager\SiteManager;
use Ledc\Container\App;
use plugin\admin\app\model\Base;
use support\Log;
use support\Model;

if (is_file(runtime_path('Bencode.php'))) {
    require_once runtime_path('Bencode.php');
}

/**
 * 显示系统信息
 * @return void
 */
function echo_system_info(): void
{
    echo implode(PHP_EOL, [
        ' 当前时间：' . date('Y-m-d H:i:s'),
        ' IYUU版本：' . iyuu_version(),
        ' Docker：' . (isDockerEnvironment() ? '是' : '否'),
        ' 操作系统：' . PHP_OS,
        ' 运行环境：' . PHP_OS_FAMILY,
        ' PHP二进制路径：' . PHP_BINARY,
        ' PHP版本号：' . PHP_VERSION . PHP_EOL
    ]);
}

/**
 * 清理缓存的驱动实例：防止变更配置后常驻内存未更新
 * @return void
 */
function clear_instance_cache(): void
{
    try {
        /** @var SiteManager $siteManager */
        $siteManager = App::pull(SiteManager::class);
        $siteManager->clearDriver();
        /** @var ClientDownloader $clientDownloader */
        $clientDownloader = App::pull(ClientDownloader::class);
        $clientDownloader->clearDriver();
    } catch (Error|Exception|Throwable $throwable) {
        Log::error('清理缓存驱动实例异常：' . $throwable->getMessage());
    }
}

/**
 * 创建IYUU辅种客户端实例
 * @return Client
 */
function iyuu_reseed_client(): Client
{
    return new Client(iyuu_token());
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
    clearstatcache();

    $version_file = base_path('.version');
    $version = trim(file_get_contents($version_file));
    $dir = base_path() . '/.git/refs';
    if (!is_dir($dir)) {
        return $version;
    }

    try {
        $process = new Symfony\Component\Process\Process(['git', 'tag'], base_path(), null, null, 5);
        $process->run();
        $tags = explode("\n", $process->getOutput());
        foreach ($tags as $tag) {
            if (str_starts_with($tag, 'v')) {
                $tag_version = trim(substr($tag, 1));
                if (version_compare($tag_version, $version, '>=')) {
                    $version = $tag_version;
                }
            }
        }
    } catch (Error|Exception|Throwable $throwable) {
        echo '获取IYUU版本号异常：' . $throwable->getMessage() . PHP_EOL;
    } finally {
        return $version;
    }
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
    $command = implode(' ', [PHP_BINARY, base_path('vendor/bin/phinx'), 'migrate', '-e', 'development']);
    $process = Symfony\Component\Process\Process::fromShellCommandline($command, base_path(), null, null, 30);
    $process->run();
    echo $process->getOutput();
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
 * 检查docker环境是否存在nginx
 * - 2024年5月10日12:00后 镜像整合nginx
 * @return bool
 */
function is_docker_exists_nginx(): bool
{
    clearstatcache();
    $res1 = is_file('/etc/s6-overlay/s6-rc.d/svc-iyuu/run');
    $res2 = is_file('/etc/s6-overlay/s6-rc.d/nginx/run');
    return $res1 && $res2;
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
 * 验证密码复杂度
 * @param string $password
 * @return string|true
 */
function validate_password(string $password): string|true
{
    $errors = [];

    // 检查长度
    if (strlen($password) < 8) {
        $errors[] = "密码长度不能小于8个字符。";
    }

    // 检查数字
    if (!preg_match("#[0-9]+#", $password)) {
        $errors[] = "密码必须包含至少一个数字。";
    }

    // 检查大写字母
    if (!preg_match("#[A-Z]+#", $password)) {
        $errors[] = "密码必须包含至少一个大写字母。";
    }

    // 检查小写字母
    if (!preg_match("#[a-z]+#", $password)) {
        $errors[] = "密码必须包含至少一个小写字母。";
    }

    // 检查特殊字符
    if (!preg_match("#[\W_]+#", $password)) {
        $errors[] = "密码必须包含至少一个特殊字符。";
    }

    // 返回错误信息
    return $errors ? implode("\n", $errors) : true;
}

/**
 * 清理git产生的锁文件
 * @return void
 */
function clear_git_lock(): void
{
    $map = [
        base_path() . '/.git/index.lock',
        base_path() . '/.git/refs/remotes/origin/master.lock',
    ];

    if (current_git_commit()) {
        foreach ($map as $file) {
            if (is_file($file) && is_readable($file)) {
                unlink($file);
            }
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
 * @param string $key 要更新的键名
 * @param string $value 新的键值
 * @return string
 */
function update_env_value(string $key, string $value): string
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
            $envData[$envKey] = $envValue ?? '';
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

/**
 * 安全的调用匿名函数
 * @param Closure $fn
 * @return mixed
 */
function safe_run(Closure $fn): mixed
{
    try {
        return $fn();
    } catch (Error|Exception|Throwable $throwable) {
        Log::error('safe_run 异常：' . $throwable->getMessage());
    }
    return null;
}
