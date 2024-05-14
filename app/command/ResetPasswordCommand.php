<?php

namespace app\command;

use plugin\admin\app\common\Util;
use plugin\admin\app\model\Admin;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 重置密码
 */
class ResetPasswordCommand extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'iyuu:reset:password';
    /**
     * @var string
     */
    protected static string $defaultDescription = '重置系统超级管理员密码';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('password', InputArgument::REQUIRED, '请输入新密码');
        $this->addArgument('admin_id', InputArgument::OPTIONAL, '请输入管理员ID（默认首位用户）');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $password = $input->getArgument('password');
        $admin_id = $input->getArgument('admin_id');
        if (true !== validate_password($password)) {
            $output->writeln($password);
            return self::FAILURE;
        }

        $update_data = [
            'password' => Util::passwordHash($password)
        ];
        if (ctype_digit((string)$admin_id)) {
            Admin::where('id', $admin_id)->update($update_data);
        } else {
            Admin::first()->update($update_data);
        }
        $output->writeln('密码重置成功');
        return self::SUCCESS;
    }
}
