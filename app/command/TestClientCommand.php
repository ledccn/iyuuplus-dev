<?php

namespace app\command;

use app\admin\services\client\ClientServices;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * 调试客户端命令
 */
class TestClientCommand extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'iyuu:test:client';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：调试下载器客户端';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::REQUIRED, '下载器客户端ID');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');

        try {
            $handler = ClientServices::createBittorrentById($id);
            var_dump($handler->status());
            var_dump($handler->getTorrentList());
            $output->writeln('Hello ClientCommand');
            return self::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln('客户端不存在' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
