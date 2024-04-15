<?php

namespace Iyuu\SiteManager\Command;

use Iyuu\SiteManager\Config;
use Iyuu\SiteManager\Contracts\ConfigInterface;
use Iyuu\SiteManager\SiteManager;
use Ledc\Container\App;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * 生成爬虫脚本
 */
class MakeSpiderShell extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'make:spider:shell';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU出品，生成解析器shell脚本';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('type', InputArgument::OPTIONAL, '爬虫类型:cookie,rss', 'cookie');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $type = $input->getArgument('type');
        if (!in_array($type, ['cookie', 'rss'])) {
            throw new RuntimeException('未定义的爬虫类型：' . $type);
        }
        $this->createShell($type);
        $output->writeln('Create Done!');
        return self::SUCCESS;
    }

    /**
     * @param string $type 爬虫类型:cookie,rss
     * @return void
     */
    protected function createShell(string $type): void
    {
        $rows = SiteManager::supportList(true);
        /** @var ConfigInterface $config */
        $config = App::getInstance()->get(ConfigInterface::class);
        $cookies_list = [];
        $rss_list = [];
        foreach ($rows as $site => $row) {
            try {
                $siteConfig = new Config($config->get($site));
            } catch (Throwable $throwable) {
                continue;
            }
            $hasProcessor = $row[1] && $siteConfig->cookie;
            $hasProcessorXml = $row[2];
            if ($hasProcessor) {
                $cookies_list[] = "\$PHP_BINARY webman spider $site";
            }
            if ($hasProcessorXml) {
                $rss_list[] = "\$PHP_BINARY webman spider $site --type=rss";
            }
        }

        $php = PHP_BINARY;
        $content = <<<EOF
#!/bin/sh
current_dir=$(cd $(dirname $0); pwd)
echo \$current_dir
cd \$current_dir
if [ $1 ]; then
  PHP_BINARY=$1
else
  PHP_BINARY="{$php}"
fi

{{CONTENT}}

EOF;

        file_put_contents(base_path('/cookie.sh'), str_replace('{{CONTENT}}', implode(PHP_EOL, $cookies_list), $content));
        file_put_contents(base_path('/rss.sh'), str_replace('{{CONTENT}}', implode(PHP_EOL, $rss_list), $content));
    }
}
