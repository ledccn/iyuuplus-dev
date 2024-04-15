<?php

namespace app\command;

use app\admin\services\SitesServices;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * 初始化站点
 */
class SyncSitesCommand extends Command
{
    /**
     * 命令名称
     * @var string
     */
    protected static string $defaultName = 'iyuu:sync:sites';
    /**
     * 简介
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：同步站点列表';

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
        try {
            SitesServices::sync();
            $output->writeln('ok');
            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $output->writeln(__METHOD__ . ' 异常：' . $throwable->getMessage());
            return self::FAILURE;
        }
    }
}
