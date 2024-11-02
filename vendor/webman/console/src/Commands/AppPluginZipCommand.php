<?php

namespace Webman\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Webman\Console\Util;
use ZipArchive;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class AppPluginZipCommand extends Command
{

    protected static $defaultName = 'app-plugin:zip';
    protected static $defaultDescription = 'App Plugin Zip';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->setName(static::$defaultName)->setDescription(static::$defaultDescription);
        $this->addArgument('name', InputArgument::REQUIRED, 'App plugin name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $output->writeln("Zip App Plugin $name");
        $sourceDir = base_path('plugin' . DIRECTORY_SEPARATOR . $name);
        $zipFilePath = base_path('plugin' . DIRECTORY_SEPARATOR . $name . '.zip');
        if (!is_dir($sourceDir)) {
            $output->writeln("Plugin $name not exists");
            return self::FAILURE;
        }
        if (is_file($zipFilePath)) {
            unlink($zipFilePath);
        }
        $this->zipDirectory($name, $sourceDir, $zipFilePath);
        return self::SUCCESS;
    }

    /**
     * @param $name
     * @param $sourceDir
     * @param $zipFilePath
     * @return bool
     * @throws Exception
     */
    protected function zipDirectory($name, $sourceDir, $zipFilePath) {
        $zip = new ZipArchive();

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception("cannot open <$zipFilePath>\n");
        }

        $sourceDir = realpath($sourceDir);

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $name . DIRECTORY_SEPARATOR . substr($filePath, strlen($sourceDir) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        return $zip->close();
    }

}
