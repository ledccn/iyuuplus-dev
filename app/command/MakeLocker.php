<?php

namespace app\command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Webman\Console\Util;

/**
 * 命令：创建Locker
 */
class MakeLocker extends Command
{
    /**
     * @var string
     */
    protected static string $defaultName = 'make:locker';

    /**
     * @var string
     */
    protected static string $defaultDescription = 'Make Locker';

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, '锁类名');
        $this->addArgument('key', InputArgument::OPTIONAL, '锁key', 'key');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = trim($input->getArgument('name'));
        $key = $input->getArgument('key');
        $class = Util::nameToClass($name);
        $file = app_path() . "/locker/{$class}Locker.php";
        if (is_file($file)) {
            $output->writeln("存在文件：" . $file);
        } else {
            $this->createTimer($file, $class, $key);
            $output->writeln("创建成功：" . $file);
        }

        return self::SUCCESS;
    }

    /**
     * @param string $file
     * @param string $class
     * @param string $key
     * @return void
     */
    protected function createTimer(string $file, string $class, string $key): void
    {
        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $content = <<<EOF
<?php

namespace app\\locker;

use app\\common\\Locker;
use Symfony\\Component\\Lock\\SharedLockInterface;

/**
 * 业务锁：{$class}Locker
 * @method static SharedLockInterface lock(?string \${$key} = null, ?float \$ttl = null, ?bool \$autoRelease = null, ?string \$prefix = null) 创建锁
 */
class {$class}Locker extends Locker
{
}

EOF;

        file_put_contents($file, $content);
    }
}
