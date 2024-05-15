<?php

namespace Iyuu\SiteManager\Observers;

use Iyuu\SiteManager\BaseDriver;
use Iyuu\SiteManager\Contracts\Observer;
use Iyuu\SiteManager\Exception\BadRequestException;
use Iyuu\SiteManager\Exception\DownloadHtmlException;
use Iyuu\SiteManager\Exception\DownloadTorrentException;
use Iyuu\SiteManager\Pipeline\Report\EchoTitle;
use Iyuu\SiteManager\Pipeline\Report\RequestCreate;
use Iyuu\SiteManager\Pipeline\Report\RequestFind;
use Iyuu\SiteManager\Spider\Payload;
use Iyuu\SiteManager\Spider\SpiderTorrents;
use Iyuu\SiteManager\Spider\SpiderWorker;
use Iyuu\SiteManager\Utils;
use Ledc\Container\App;
use Ledc\Pipeline\Pipeline;
use support\Log;
use Throwable;

/**
 * 示例：一个观察者
 *  - 计算种子特征码并上报
 */
class ReportObserver implements Observer
{
    /**
     * 流水线业务逻辑
     * @var array|array[]
     */
    protected static array $pipelines = [
        [EchoTitle::class, 'process'],
        [RequestFind::class, 'process'],
        [RequestCreate::class, 'process'],
    ];

    public static function update(SpiderTorrents $spiderTorrents, BaseDriver $baseDriver, int $index): void
    {
        //存在解码器 && 实现契约
        if (!SpiderTorrents::existsDecoder()) {
            if (!SpiderWorker::getParams()->daemon) {
                print_r($spiderTorrents->toArray());
                echo '不存在解码器 || 未实现契约' . PHP_EOL;
            }
            return;
        }
        //print_r($torrent->toArray());
        try {
            ob_start();
            $worker_id = SpiderWorker::getParams()->canValidAction() ? SpiderWorker::getWorker()->id : 0;
            $currentPage = SpiderWorker::getParams()->isTypeEqRss() ? 'RSS模式' : $baseDriver->makeBaseCookie()->currentPage();
            Utils::echo(sprintf('站点：%s | Worker进程ID：%d | 当前页码：%s', SpiderWorker::getParams()->site, $worker_id, $currentPage));
            $pipeline = new Pipeline(App::getInstance());
            $pipeline->send(new Payload($spiderTorrents, $baseDriver))
                ->through(static::$pipelines)
                ->thenReturn();
        } catch (Throwable $throwable) {
            $message = SpiderWorker::getParams()->site . '[种子观察者]异常 ----->>> ' . $throwable->getMessage();
            echo $message . PHP_EOL;

            //记录日志
            if ($throwable instanceof BadRequestException ||
                $throwable instanceof DownloadTorrentException ||
                $throwable instanceof DownloadHtmlException
            ) {
                unset($spiderTorrents->metadata);
                Log::error($message, $spiderTorrents->toArray());
                echo get_class($throwable) . ' 发生异常，休眠中...' . PHP_EOL;
                sleep(mt_rand(5, 10));
            }
        } finally {
            $content = ob_get_clean();
            if ($content && !SpiderWorker::getParams()->daemon) {
                echo $content;
            }
            if (0 < SpiderWorker::getParams()->sleep) {
                echo '安全休眠中...' . PHP_EOL;
                sleep(min(SpiderWorker::getParams()->sleep, 60));
            }
        }
    }
}
