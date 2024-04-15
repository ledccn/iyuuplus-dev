<?php

namespace app\common;

/**
 * 创建Eloquent ORM模型观察者
 */
class BuildObserver
{
    /**
     * 创建
     * @param string $class 类名称
     * @param string $namespace 命名空间
     * @param string $file 模型文件的完整路径
     * @param string $table 模型的数据表名称
     * @return void
     */
    public static function create(string $class, string $namespace, string $file, string $table): void
    {
        $observer = $class . 'Observer';
        $file = dirname($file) . DIRECTORY_SEPARATOR . $observer . '.php';
        if (is_file($file)) {
            return;
        }
        $content = <<<EOF
<?php

namespace $namespace;

/**
 * 模型观察者：$table
 * @usage {$class}::observe({$observer}::class);
 */
class $observer
{
   /**
     * 监听数据即将创建的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function creating($class \$model): void
    {
    }

    /**
     * 监听数据创建后的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function created($class \$model): void
    {
    }

    /**
     * 监听数据即将更新的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function updating($class \$model): void
    {
    }

    /**
     * 监听数据更新后的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function updated($class \$model): void
    {
    }

    /**
     * 监听数据即将保存的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function saving($class \$model): void
    {
    }

    /**
     * 监听数据保存后的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function saved($class \$model): void
    {
    }

    /**
     * 监听数据即将删除的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function deleting($class \$model): void
    {
    }

    /**
     * 监听数据删除后的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function deleted($class \$model): void
    {
    }

    /**
     * 监听数据即将从软删除状态恢复的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function restoring($class \$model): void
    {
    }

    /**
     * 监听数据从软删除状态恢复后的事件。
     *
     * @param $class \$model
     * @return void
     */
    public function restored($class \$model): void
    {
    } 
}

EOF;
        file_put_contents($file, $content);
    }
}
