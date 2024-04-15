<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 【开发工具】导出备份
 */
class DevelopExportCommand extends Command
{
    /**
     * 命令
     * @var string
     */
    protected static string $defaultName = 'iyuu:develop';

    /**
     * 简介
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：开发者工具链';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        while ($name--) {
            echo '剩余：' . $name . '次 | ' . static::class . PHP_EOL;
            echo '休眠3秒...' . PHP_EOL;
            //NotifyAdmin::success(date('Y-m-d H:i:s') . ' | 剩余：' . $name . '次 | ' . static::class);
            //NotifyAdmin::success('休眠3秒...');
            sleep(3);
        }
        $output->writeln('Hello ExportCommand');
        return self::SUCCESS;
    }
}
