<?php

namespace Iyuu\SiteManager\Command;

use Iyuu\SiteManager\BaseCookie;
use Iyuu\SiteManager\SiteManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 生成站点支持类
 */
class MakeSpider extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'make:spider';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU出品，生成解析器类';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('site', InputArgument::OPTIONAL, '站点标识')
            ->addArgument('frame', InputArgument::OPTIONAL, '框架类型', 'NexusPhp')
            ->addOption('cookie', null, InputOption::VALUE_OPTIONAL, '支持凭cookie解析HTML', true)
            ->addOption('rss', null, InputOption::VALUE_OPTIONAL, '支持RSS解析XML', true);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $site = $input->getArgument('site');
        $frame = $input->getArgument('frame');
        $cookie = $input->getOption('cookie');
        $rss = $input->getOption('rss');

        $this->createDriver($site, $cookie, $rss, $frame);
        $this->createCookie($site, $cookie, $frame);

        $output->writeln('Success');
        return self::SUCCESS;
    }

    /**
     * 创建驱动
     * @param string $site 站点标识
     * @param bool $cookie 支持凭cookie解析HTML
     * @param bool $rss 支持RSS解析XML
     * @param string $frame 框架类型
     * @return void
     */
    protected function createDriver(string $site, bool $cookie, bool $rss, string $frame): void
    {
        $className = SiteManager::siteToClassname($site);
        $file = SiteManager::getDirname() . DIRECTORY_SEPARATOR . SiteManager::DRIVER_PREFIX . DIRECTORY_SEPARATOR . $className . '.php';
        if (!is_file($file)) {
            $path = pathinfo($file, PATHINFO_DIRNAME);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $implements = [];
            $import = $used = '';
            if ($cookie) {
                $implements[] = 'Processor';
            }
            if ($rss) {
                $used = 'use HasRss;';
                $implements[] = 'ProcessorXml';
            }

            if ($implements) {
                $import = 'implements ' . implode(', ', $implements);
            }

            $content = <<<EOF
<?php

namespace Iyuu\\SiteManager\\Driver;

use Iyuu\\SiteManager\\BaseDriver;
use Iyuu\\SiteManager\\Contracts\\Processor;
use Iyuu\\SiteManager\\Contracts\\ProcessorXml;
use Iyuu\\SiteManager\\Frameworks\\NexusPhp\\HasRss;

/**
 * $site
 */
class $className extends BaseDriver $import
{
    $used
    /**
     * 站点名称
     */
    public const string SITE_NAME = '$site';
}

EOF;
            file_put_contents($file, $content);
        } else {
            echo '存在驱动文件：' . $file . PHP_EOL;
        }
    }

    /**
     * 创建HTML解析类
     * @param string $site 站点标识
     * @param bool $cookie 支持凭cookie解析HTML
     * @param string $frame 框架类型
     * @return void
     */
    protected function createCookie(string $site, bool $cookie, string $frame): void
    {
        $className = BaseCookie::siteToClassname($site);
        $file = SiteManager::getDirname() . DIRECTORY_SEPARATOR . BaseCookie::CLASS_PREFIX . DIRECTORY_SEPARATOR . $className . '.php';
        if (!is_file($file)) {
            $path = pathinfo($file, PATHINFO_DIRNAME);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }

            $content = <<<EOF
<?php

namespace Iyuu\\SiteManager\\Cookie;

use Iyuu\\SiteManager\\BaseCookie;
use Iyuu\\SiteManager\\Frameworks\\NexusPhp\\HasCookie;
use Iyuu\\SiteManager\\Spider\\Pagination;

/**
 * $site
 * - 凭cookie解析HTML列表页
 */
class $className extends BaseCookie
{
    use HasCookie, Pagination;
    /**
     * 站点名称
     */
    public const SITE_NAME = '$site';

    /**
     * 是否调试当前站点
     * @return bool
     */
    protected function isDebugCurrent(): bool
    {
        return true;
    }
}

EOF;
            file_put_contents($file, $content);
        } else {
            echo '存在HTML解析类文件：' . $file . PHP_EOL;
        }
    }
}
