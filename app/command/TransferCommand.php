<?php

namespace app\command;

use app\admin\services\transfer\TransferServices;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * IYUU自动转移做种客户端
 */
class TransferCommand extends Command
{
    /**
     * 命令名称
     */
    public const COMMAND_NAME = 'iyuu:transfer';
    /**
     * 命令名称
     * @var string
     */
    protected static string $defaultName = self::COMMAND_NAME;
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：自动转移做种客户端';

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
            $output->writeln(date('Y-m-d H:i:s') . "即将执行自动转移做种客户端，任务id：{$crontab_id}");
            $transferServices = new TransferServices((int)$crontab_id);
            $transferServices->run();
        } catch (\Error|\Exception|\Throwable $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
        } finally {
            $output->writeln('转移完毕，感谢使用IYUU！');
        }
        return self::SUCCESS;
    }
}
