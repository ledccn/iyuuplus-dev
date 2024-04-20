<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 生成nginx配置文件
 */
class NginxConfig extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'iyuu:nginx:config';
    /**
     * @var string
     */
    protected static string $defaultDescription = 'IYUU：生成nginx配置文件';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description', 'proxy');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $server_port = parse_url(config('server.listen'), PHP_URL_PORT);
        $push_app_key = config('plugin.webman.push.app.app_key');
        $push_websocket_port = parse_url(config('plugin.webman.push.app.websocket'), PHP_URL_PORT);
        $conf = <<<EOF
location ^~ / {
  proxy_set_header X-Real-IP \$remote_addr;
  proxy_set_header Host \$host;
  proxy_set_header X-Forwarded-Proto \$scheme;
  proxy_http_version 1.1;
  proxy_set_header Connection "";
  if (!-f \$request_filename){
    proxy_pass http://127.0.0.1:$server_port;
  }
}

location /app/$push_app_key
{
  proxy_pass http://127.0.0.1:$push_websocket_port;
  proxy_http_version 1.1;
  proxy_set_header Upgrade \$http_upgrade;
  proxy_set_header Connection "Upgrade";
  proxy_set_header X-Real-IP \$remote_addr;
}
EOF;
        $filename = base_path($name . '.conf');
        file_put_contents($filename, $conf);
        $output->writeln('成功生成nginx配置文件：' . $filename);
        return self::SUCCESS;
    }
}
