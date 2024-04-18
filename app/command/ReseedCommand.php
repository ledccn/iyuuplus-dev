<?php

namespace app\command;

use app\admin\services\reseed\ReseedServices;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * IYUU自动辅种
 */
class ReseedCommand extends Command
{
    /**
     * 命令名称
     */
    public const string COMMAND_NAME = 'iyuu:reseed';

    /**
     * 命令名称
     * @var string
     */
    protected static string $defaultName = self::COMMAND_NAME;
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：自动辅种';

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
            $output->writeln(date('Y-m-d H:i:s') . "即将执行自动辅种，任务id：{$crontab_id}");
            $reseedServices = new ReseedServices(iyuu_token(), (int)$crontab_id);
            $reseedServices->run();
        } catch (\Error|\Exception|\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        } finally {
            $output->writeln('辅种完毕，感谢使用IYUU自动辅种！');
        }

        return self::SUCCESS;
    }
}
