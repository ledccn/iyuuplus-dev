# 安装

```
composer require ledc/macroable
```



# 使用

```
<?php

use Ledc\Macroable\Macro;
use Ledc\Macroable\Macroable;

require_once __DIR__ . '/vendor/autoload.php';
class Tests
{
    //use Macroable;
    use Macro;
}

class Request
{
    //use Macroable;
    use Macro;
}

$ts = new Tests();
$ts->macro('hello', function () {
    echo 'hello Tests' . PHP_EOL;
});
$ts->hello();

$req = new Request();
$req->macro('hello', function () {
    echo 'hello Request' . PHP_EOL;
});
$req->hello();
```

## Usage

You can add a new method to a class using `macro`:

```php
$macroableClass = new class() {
    use Ledc\Macroable\Macroable;
};

$macroableClass::macro('concatenate', function(... $strings) {
   return implode('-', $strings);
});

$macroableClass->concatenate('one', 'two', 'three'); // returns 'one-two-three'
```

Callables passed to the `macro` function will be bound to the `class`

```php
$macroableClass = new class() {
    protected $name = 'myName';
    use Ledc\Macroable\Macroable;
};

$macroableClass::macro('getName', function() {
   return $this->name;
};

$macroableClass->getName(); // returns 'myName'
```

You can also add multiple methods in one go by using a mixin class. A mixin class contains methods that return callables. Each method from the mixin will be registered on the macroable class.

```php
$mixin = new class() {
    public function mixinMethod()
    {
       return function() {
          return 'mixinMethod';
       };
    }
    
    public function anotherMixinMethod()
    {
       return function() {
          return 'anotherMixinMethod';
       };
    }
};

$macroableClass->mixin($mixin);

$macroableClass->mixinMethod() // returns 'mixinMethod';

$macroableClass->anotherMixinMethod() // returns 'anotherMixinMethod';
```



# 最佳实践

- 宏指令方法不存在状态：用`Ledc\Macroable\Macroable`
- 宏指令方法存在状态或依赖上下文时，用：`Ledc\Macroable\Macro`



# 使用场景

| 特性trait                  | 使用场景                                          |
| -------------------------- | ------------------------------------------------- |
| `Ledc\Macroable\Macroable` | PHP-FPM，或宏指令方法无状态                       |
| `Ledc\Macroable\Macro`     | PHP-CLI，中间件内对请求对象注入方法(请求结束销毁) |

