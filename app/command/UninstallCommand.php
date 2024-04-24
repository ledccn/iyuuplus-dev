<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 重置系统
 */
class UninstallCommand extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'iyuu:uninstall';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：重置系统到未安装状态';

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

        clearstatcache();
        // 删除数据表

        // 删除文件
        $unlinks = [
            base_path() . '/.env',
            base_path() . '/config/crontab.php',
            base_path() . '/plugin/admin/config/database.php',
            base_path() . '/plugin/admin/config/thinkorm.php',
        ];
        foreach ($unlinks as $filename) {
            is_file($filename) and unlink($filename);
        }

        $output->writeln('重置完成！');
        return self::SUCCESS;
    }
}
