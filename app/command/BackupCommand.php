<?php

namespace app\command;

use app\model\Client;
use app\model\Site;
use Illuminate\Database\Eloquent\Collection;
use Iyuu\SiteManager\Contracts\RecoveryInterface;
use Iyuu\SiteManager\Utils;
use plugin\admin\app\model\Option;
use plugin\cron\app\model\Crontab;
use support\Model;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 备份与恢复命令
 */
class BackupCommand extends Command
{
    /**
     * 需要备份的模型
     */
    public const BACKUP = [
        Site::class,
        Client::class,
        Crontab::class,
        Option::class,
    ];

    /**
     * @var string
     */
    protected static string $defaultName = 'iyuu:backup';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：备份数据库表';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '操作名字', 'backup');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $code = match ($name) {
            'backup' => $this->backup($output),
            'recovery' => $this->recovery($output),
            default => self::SUCCESS
        };

        $output->writeln('Done!');
        return $code;
    }

    /**
     * 备份
     * @param OutputInterface $output
     * @return int
     */
    private function backup(OutputInterface $output): int
    {
        $date = date('Ymd');
        $dir = runtime_path('backup');
        Utils::createDir($dir);

        /** @var Model $class */
        foreach (self::BACKUP as $class) {
            $model = new $class();
            /** @var Collection $list */
            $list = $class::get();
            if (!$list->isEmpty()) {
                file_put_contents($dir . DIRECTORY_SEPARATOR . $model->getTable() . '.json', json_encode($list->toArray(), JSON_UNESCAPED_UNICODE));
                file_put_contents($dir . DIRECTORY_SEPARATOR . $date . $model->getTable() . '.json', json_encode($list->toArray(), JSON_UNESCAPED_UNICODE));
            }
        }

        $output->writeln('备份成功，Success!' . PHP_EOL . '存放在：' . $dir);
        return self::SUCCESS;
    }

    /**
     * 恢复
     * @param OutputInterface $output
     * @return int
     */
    private function recovery(OutputInterface $output): int
    {
        $dir = runtime_path('backup');
        $count = 0;
        /** @var Model $class */
        foreach (self::BACKUP as $class) {
            $model = new $class();
            if (!$model instanceof RecoveryInterface) {
                $output->writeln('未实现数据恢复接口，已忽略类：' . $class);
                continue;
            }

            $filename = $dir . DIRECTORY_SEPARATOR . $model->getTable() . '.json';
            if (is_file($filename)) {
                $list = json_decode(file_get_contents($filename), true);
                $model->recoveryHandle($list) and $count++;
            }
        }

        $output->writeln('恢复成功Success!' . PHP_EOL . " -----共计{$count}张表");
        return self::SUCCESS;
    }
}
