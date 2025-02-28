<?php

namespace Iyuu\SiteManager\Command;

use Error;
use Exception;
use InvalidArgumentException;
use Iyuu\SiteManager\Contracts\ConfigInterface;
use Iyuu\SiteManager\Contracts\ProcessorXml;
use Iyuu\SiteManager\Exception\EmptyListException;
use Iyuu\SiteManager\SiteManager;
use Iyuu\SiteManager\Spider\Context\CurrentPage;
use Iyuu\SiteManager\Spider\Helper;
use Iyuu\SiteManager\Spider\Params;
use Iyuu\SiteManager\Spider\RouteEnum;
use Iyuu\SiteManager\Spider\SpiderWorker;
use Iyuu\SiteManager\Utils;
use Ledc\Container\App;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Workerman\Worker;

/**
 * 爬虫命令
 */
class SpiderCommand extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'spider';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU出品，PT站点页面解析器';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('site', InputArgument::REQUIRED, '站点名称')
            ->addArgument('action', InputArgument::OPTIONAL, implode('|', Params::ACTION_LIST), '')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, '任务类型:' . implode('|', Params::TYPE_LIST), 'cookie')
            ->addOption('uri', null, InputOption::VALUE_OPTIONAL, '单页：统一资源标识符', '')
            ->addOption('route', null, InputOption::VALUE_OPTIONAL, '批量：路由规则名称', '')
            ->addOption('begin', null, InputOption::VALUE_OPTIONAL, '开始页码', '')
            ->addOption('end', null, InputOption::VALUE_OPTIONAL, '结束页码', '')
            ->addOption('count', null, InputOption::VALUE_OPTIONAL, '进程数量', 1)
            ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, '每个种子间休眠的秒数', 0)
            ->addOption('daemon', 'd', InputOption::VALUE_NONE, '守护进程');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws EmptyListException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 接收参数
        $site = $input->getArgument('site');
        $type = $input->getOption('type');
        $uri = $input->getOption('uri');
        $route = $input->getOption('route');
        $end = $input->getOption('end');

        // 验证站点名称
        try {
            $config = App::getInstance()->get(ConfigInterface::class);
            if (empty($config->get($site))) {
                Helper::supportSiteTable($output);
                return self::FAILURE;
            }
        } catch (Error|Exception|Throwable $throwable) {
            $output->writeln($throwable->getMessage());
            Helper::supportSiteTable($output);
            return self::FAILURE;
        }

        // 验证任务类型
        if (!in_array($type, Params::TYPE_LIST)) {
            throw new InvalidArgumentException('未定义的任务类型：' . $type . PHP_EOL . '支持的类型：' . implode(',', Params::TYPE_LIST));
        }

        // 验证uri互斥条件：1.uri不能和route同时设置、2.uri不能和end同时设置
        if (!empty($uri)) {
            if ($route || $end) {
                throw new InvalidArgumentException('互斥条件验证失败：1.uri不能和route同时设置、2.uri不能和end同时设置');
            }
        }

        // 验证路由规则名称
        if ($route && !RouteEnum::hasName($route)) {
            $output->writeln('未定义的路由规则名称：' . $route);
            Helper::supportRouteTable($output);
            return self::FAILURE;
        }

        // 合并入参，实例化爬取参数类，绑定到容器内
        $params = new Params(array_merge($input->getArguments(), $input->getOptions()));
        App::getInstance()->instance(Params::class, $params);
        if ($params->canValidAction()) {
            if (Utils::isWindowsOs()) {
                throw new InvalidArgumentException('常驻内存仅支持Linux');
            }
            // 守护进程模式
            return $this->startSpiderWorker($params);
        }

        /**
         * 单次爬取模式
         */
        $output->writeln("爬取站点 开始 ----->>> $site");
        $this->doSpider($params, $uri, $route);
        $output->writeln("爬取站点 结束 ----->>> $site" . PHP_EOL);
        return self::SUCCESS;
    }

    /**
     * 开始单次爬取模式
     * @param Params $params 爬虫参数
     * @param string $uri 单页：统一资源标识符
     * @param string $route 批量：路由规则名称
     * @return void
     * @throws EmptyListException
     */
    protected function doSpider(Params $params, string $uri, string $route): void
    {
        /** @var SiteManager $siteManager */
        $siteManager = App::pull(SiteManager::class);
        $baseDriver = $siteManager->select($params->site);
        if ($params->isTypeEqCookie()) {
            $baseCookie = $baseDriver->makeBaseCookie();
            // 必须在循环外面获取当前页码（不记录page文件）
            $page = $baseCookie->currentPage();
            if (empty($params->uri)) {
                if ($route) {
                    $route = RouteEnum::{$route};
                }
                // uri为空时，确认结束页码的值
                $endPage = $params->end ?: $baseCookie->crontabEndPage();
            } else {
                // uri有值，置空结束页
                $endPage = '';
            }

            do {
                CurrentPage::set($page);
                if (empty($params->uri)) {
                    $uri = $baseCookie->pageUriBuilder($page, $route);
                }
                echo '当前页面的uri：' . $uri . PHP_EOL;
                $baseCookie->process($uri);
            } while ($endPage && ($page++ < $endPage));
            CurrentPage::delete();
        } else {
            if ($baseDriver instanceof ProcessorXml) {
                $baseDriver->processXml($uri);
            } else {
                throw new RuntimeException('未实现XML解析接口：' . get_class($baseDriver));
            }
        }
    }

    /**
     * 守护进程
     * @param Params $params
     * @return int
     */
    protected function startSpiderWorker(Params $params): int
    {
        SpiderWorker::initMasterStop($params);
        SpiderWorker::initWorker($params->site, $params->daemon);
        $process_config = [
            'count' => $params->count ? max($params->count, 1) : 1,
            'reloadable' => true,
            'handler' => SpiderWorker::class,
            'constructor' => [
                'params' => $params,
            ],
        ];
        worker_start($params->site, $process_config);
        Worker::runAll();
        return self::SUCCESS;
    }
}
