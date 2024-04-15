<?php

namespace Iyuu\SiteManager\Spider;

use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\SiteManager;
use Iyuu\SiteManager\Utils;
use Ledc\Container\App;
use support\Log;
use Throwable;
use Workerman\Worker;

/**
 *  爬虫应用Worker
 */
class SpiderWorker
{
    /**
     * worker容器
     * @var Worker|null
     */
    protected static ?Worker $worker = null;

    /**
     * 爬取参数
     * @var Params|null
     */
    protected static ?Params $params = null;

    /**
     * 构造函数
     * @param Params $params 爬取参数
     */
    public function __construct(Params $params)
    {
        if (Utils::isWindowsOs()) {
            throw new InvalidArgumentException('常驻内存仅支持Linux');
        }
        static::$params = $params;
    }

    /**
     * 子进程启动时回调函数
     * @param Worker $worker
     * @return void
     */
    public function onWorkerStart(Worker $worker): void
    {
        static::$worker = $worker;
        echo 'onWorkerStart 启动子进程，WorkerId：' . $worker->id . PHP_EOL;

        $params = self::getParams();
        /** @var SiteManager $siteManager */
        $siteManager = App::pull(SiteManager::class);
        $baseDriver = $siteManager->select($params->site);
        $baseCookie = $baseDriver->makeBaseCookie();

        $endPage = (int)$params->end ?: 0;
        if ($route = $params->route ?: '') {
            //根据路由规则名称，获取路由的枚举值
            $route = RouteEnum::getValue($route);
        }

        do {
            $page = $baseCookie->currentPage();
            try {
                $uri = $baseCookie->pageUriBuilder($page, $route);
                $baseCookie->process($uri);
            } catch (EmptyListException $throwable) {
                $this->incrEmptyList(7);
                sleep(mt_rand(5, 10));
            } catch (Error|Exception|Throwable $throwable) {
                Log::error('爬取页面时异常：' . $throwable->getMessage());
            } finally {
                $baseCookie->nextPage();
            }
        } while ($page < $endPage);

        if ($endPage && ($page > $endPage)) {
            // 停止主进程
            $this->stopMasterProcess(static::$worker);
        } else {
            echo '==================退出当前子进程，WorkerId:' . $worker->id . PHP_EOL . PHP_EOL;
            self::stopAll();
        }
    }

    /**
     * 子进程退出时回调函数
     * @return void
     */
    public function onWorkerStop(): void
    {
    }

    /**
     * 累加空列表的次数
     * - 超过X次后停止主进程
     * @param int $maxEmptyNumber
     * @return void
     */
    protected function incrEmptyList(int $maxEmptyNumber = 5): void
    {
        clearstatcache();
        $site = static::getParams()->site;
        $filename = Helper::siteEmptyListFilename($site);
        if (is_file($filename)) {
            $number = (int)file_get_contents($filename);
        } else {
            $number = 0;
        }
        $number++;

        if ($maxEmptyNumber < $number) {
            $this->stopMasterProcess(static::$worker);
        } else {
            file_put_contents($filename, $number);
        }
    }

    /**
     * 停止master进程
     * @param Worker $worker
     */
    public function stopMasterProcess(Worker $worker): void
    {
        if ($worker->id) {
            return;
        }
        $start_file = static::getParams()->site;
        $master_pid = is_file($worker::$pidFile) ? (int)file_get_contents($worker::$pidFile) : 0;
        $master_pid && posix_kill($master_pid, SIGINT);
        // Timeout.
        $timeout = $worker::$stopTimeout + 3;
        $start_time = time();
        // Check master process is still alive?
        while (1) {
            $master_is_alive = $master_pid && posix_kill($master_pid, 0);
            if ($master_is_alive) {
                // Timeout?
                if (time() - $start_time >= $timeout) {
                    $worker::log("Workerman Spider [$start_file] stop fail");
                    exit;
                }
                // Waiting moment.
                usleep(10000);
                continue;
            }
            // Stop success.
            $worker::log("Workerman Spider [$start_file] stop success");
            exit(0);
        }
    }

    /**
     * 当前进程worker实例
     * @return Worker|null
     */
    public static function getWorker(): ?Worker
    {
        return static::$worker;
    }

    /**
     * 爬取参数
     * @return Params
     */
    public static function getParams(): Params
    {
        if (null === self::$params) {
            return App::getInstance()->get(Params::class);
        }
        return self::$params;
    }

    /**
     * 初始化worker容器
     * @param string $site 站点名称
     * @param bool $daemon 常驻守护进程
     * @return void
     */
    public static function initWorker(string $site, bool $daemon = false): void
    {
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);
        date_default_timezone_set('Asia/Shanghai');

        Worker::$onMasterReload = function () {
            if (function_exists('opcache_get_status')) {
                if ($status = opcache_get_status()) {
                    if (isset($status['scripts']) && $scripts = $status['scripts']) {
                        foreach (array_keys($scripts) as $file) {
                            opcache_invalidate($file, true);
                        }
                    }
                }
            }
        };

        Worker::$pidFile = rtrim(runtime_path(), PHP_EOL) . "/app_$site.pid";
        Worker::$stdoutFile = rtrim(runtime_path(), PHP_EOL) . '/logs/stdout_spider.log';
        Worker::$logFile = rtrim(runtime_path(), PHP_EOL) . '/logs/workerman_spider.log';
        if (property_exists(Worker::class, 'statusFile')) {
            Worker::$statusFile = rtrim(runtime_path(), PHP_EOL) . "/app_$site.status";
        }
        if (property_exists(Worker::class, 'stopTimeout')) {
            Worker::$stopTimeout = 7;
        }

        Worker::$daemonize = $daemon;
    }

    /**
     * 主进程停止时执行的回调
     * @param Params $params
     * @return void
     */
    public static function initMasterStop(Params $params): void
    {
        $site = $params->site;
        if ($params->isActionEqStart()) {
            Helper::clearRuntimeSpiderCache($site);
        }

        Worker::$onMasterStop = function () use ($site) {
            Helper::clearRuntimeSpiderCache($site);
        };
    }

    /**
     * 退出进程
     * @param int $code
     * @param string $log
     * @return void
     */
    public static function stopAll(int $code = 0, string $log = ''): void
    {
        Worker::stopAll($code, $log);
    }
}
