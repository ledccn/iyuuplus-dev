<?php

namespace app\command;

use app\admin\services\rss\RssServices;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * IYUU下载之RSS订阅
 */
class RssCommand extends Command
{
    /**
     * 命令名称
     */
    public const string COMMAND_NAME = 'iyuu:rss';
    /**
     * @var string
     */
    protected static string $defaultName = self::COMMAND_NAME;
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：RSS订阅';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('crontab_id', InputArgument::REQUIRED, '计划任务ID');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $crontab_id = $input->getArgument('crontab_id');
        try {
            $output->writeln(date('Y-m-d H:i:s') . "即将执行RSS订阅，任务id：{$crontab_id}");
            $service = new RssServices((int)$crontab_id);
            $service->run();
        } catch (\Error|\Exception|\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        } finally {
            echo_system_info();
            $output->writeln('RSS订阅执行完毕，感谢使用IYUU！');
        }
        return self::SUCCESS;
    }
}
