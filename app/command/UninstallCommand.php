<?php

namespace app\command;

use Error;
use Exception;
use plugin\admin\app\common\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

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
        $this->addArgument('code', InputArgument::OPTIONAL, 'Code description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('code');
        if (date('Ymd') !== $name) {
            $output->writeln('<error>Code错误（当前的年月日）</error>');
            return self::INVALID;
        }

        clearstatcache();
        // 删除数据表
        $database = getenv('DB_DATABASE');

        try {
            $tables = Util::db()->select("SELECT TABLE_NAME FROM  information_schema.`TABLES` WHERE  TABLE_SCHEMA='$database'");
            $table_names = array_column($tables, 'TABLE_NAME');
            Util::db()->statement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ($table_names as $table) {
                Util::db()->statement('DROP TABLE IF EXISTS ' . $table);
            }
        } catch (Error|Exception|Throwable $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        } finally {
            Util::db()->statement('SET FOREIGN_KEY_CHECKS = 1');
        }

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
