<?php

namespace plugin\admin\app\controller;

use app\model\Client;
use app\model\Reseed;
use app\model\Site;
use plugin\admin\app\common\Util;
use plugin\admin\app\model\Option;
use plugin\admin\app\model\User;
use plugin\cron\app\model\Crontab;
use support\exception\BusinessException;
use support\Request;
use support\Response;
use Throwable;
use Workerman\Worker;

/**
 * 默认后台控制器
 */
class IndexController
{

    /**
     * 无需登录的方法
     * @var string[]
     */
    protected $noNeedLogin = ['index'];

    /**
     * 不需要鉴权的方法
     * @var string[]
     */
    protected $noNeedAuth = ['dashboard'];

    /**
     * 后台主页
     * @param Request $request
     * @return Response
     * @throws BusinessException|Throwable
     */
    public function index(Request $request): Response
    {
        clearstatcache();
        if (!is_file(base_path('plugin/admin/config/database.php'))) {
            return raw_view('index/install');
        }
        $admin = admin();
        if (!$admin) {
            return raw_view('account/login');
        }

        $services_url = $services_token = '';
        $config = Option::where('name', 'system_config')->value('value');
        if (!empty($config)) {
            $config = json_decode($config, true);
            $services_url = $config['logo']['services_url'] ?? '';
            $services_token = $config['logo']['services_token'] ?? '';
        }
        // 新增功能：引入webman/push 2024年3月4日11:40:45 david
        return raw_view('index/index', [
            'app_key' => config('plugin.webman.push.app.app_key'),
            'websocket_port' => parse_url(config('plugin.webman.push.app.websocket'), PHP_URL_PORT),
            // 服务器地址、用户Token
            'services_url' => $services_url,
            'services_token' => $services_token,
        ]);
    }

    /**
     * 仪表板
     * @param Request $request
     * @return Response
     * @throws Throwable
     */
    public function dashboard(Request $request): Response
    {
        // mysql版本
        $version = Util::db()->select('select VERSION() as version');
        $mysql_version = $version[0]->version ?? 'unknown';

        $day7_detail = [];
        $now = time();
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', $now - 24 * 60 * 60 * $i);
            $day7_detail[substr($date, 5)] = User::where('created_at', '>', "$date 00:00:00")
                ->where('created_at', '<', "$date 23:59:59")->count();
        }

        return raw_view('index/dashboard', [
            'app_filemtime' => current_git_filemtime(),
            'app_commit_id' => current_git_commit(),
            'iyuu_version' => iyuu_version(),
            'count_value1' => Reseed::count(),
            'count_value2' => Client::count(),
            'count_value3' => Site::where('disabled', '=', 0)->count(),
            'count_value4' => Site::count(),
            'count_value5' => 'NaN',
            'count_value6' => Crontab::count(),
            'count_value7' => Crontab::sum('running_count'),
            'count_value8' => 'NaN',
            'php_version' => PHP_VERSION,
            'workerman_version' => Worker::VERSION,
            'webman_version' => Util::getPackageVersion('workerman/webman-framework'),
            'admin_version' => config('plugin.admin.app.version'),
            'mysql_version' => $mysql_version,
            'os' => PHP_OS,
            'day7_detail' => array_reverse($day7_detail),
        ]);
    }

}
