# 加密解密、加签验签

## 安装
`composer require ledc/crypt`

## 使用

开箱即用，只需要传入一个配置，初始化一个实例即可：

```php
use Ledc\Crypt;
$crypt = new AesCrypt($aesKey, 'aes-128-cbc', 'sha256', 30);
```

在创建实例后，所有的方法都可以有IDE自动补全；例如：

```php
// 加密
$crypt->encrypt($data);

// 解密
$crypt->decrypt($payload, $signature);
```

## 捐赠

![reward](reward.png)
