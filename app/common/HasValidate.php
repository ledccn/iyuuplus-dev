<?php

namespace app\common;

use think\exception\ValidateException;
use think\Validate;

/**
 * ThinkPHP 验证器
 */
trait HasValidate
{
    /**
     * 验证器助手函数
     * @param array $data 数据
     * @param array|string $validate 验证器类名或者验证规则数组
     * @param array $message 错误提示信息
     * @param bool $batch 是否批量验证
     * @param bool $failException 是否抛出异常
     * @return bool|true
     * @throws ValidateException
     */
    protected static function validate(array $data, array|string $validate = '', array $message = [], bool $batch = false, bool $failException = true): bool
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            if (!class_exists($validate)) {
                throw new ValidateException('验证类不存在:' . $validate);
            }
            /** @var Validate $v */
            $v = new $validate();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        return $v->message($message)->batch($batch)->failException($failException)->check($data);
    }
}
