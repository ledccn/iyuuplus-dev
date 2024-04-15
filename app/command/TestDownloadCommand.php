<?php

namespace app\command;

use app\admin\services\client\ClientServices;
use app\admin\services\download\DownloaderServices;
use Ledc\Container\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * 测试向下载器投递种子
 */
class TestDownloadCommand extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'iyuu:test:download';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：测试向下载器投递种子';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('sid', InputArgument::REQUIRED, '站点ID');
        $this->addArgument('torrent_id', InputArgument::REQUIRED, '站点内种子ID');
        $this->addArgument('group_id', InputArgument::OPTIONAL, '站点内种子分组ID', 0);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sid = $input->getArgument('sid');
        $torrent_id = $input->getArgument('torrent_id');
        $group_id = $input->getArgument('group_id');
        $data = [
            'sid' => $sid,
            'torrent_id' => $torrent_id,
            'group_id' => $group_id,
        ];
        try {
            /** @var DownloaderServices $downloadServices */
            $downloadServices = App::pull(DownloaderServices::class);
            $response = $downloadServices->download($data);
            $model = ClientServices::getDefaultClient();
            $result = ClientServices::sendClientDownloader($response, $model);
            var_dump($result);
            $output->writeln('success');
            return self::SUCCESS;
        } catch (Throwable $throwable) {
            $output->writeln($throwable->getMessage());
            return self::FAILURE;
        }
    }
}
