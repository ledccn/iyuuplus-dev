<?php

namespace plugin\cron\app\services\generates;

use Error;
use Exception;
use InvalidArgumentException;
use Ledc\Element\Concrete;
use Ledc\Element\Decorator;
use Ledc\Element\GenerateInterface;
use plugin\cron\api\CrontabExtend;
use support\exception\BusinessException;
use Throwable;

/**
 * 生成Layui计划任务配置模板
 */
class LayuiTemplate
{
    /**
     * 支持的计划任务类型
     * @return array
     */
    public function select(): array
    {
        $select = CrontabTaskTypeEnums::select();
        $extend = CrontabExtend::getInstance();
        foreach ($extend->getTypeEnums() as $typeEnum) {
            $current = $typeEnum::select();
            $key_intersect = array_intersect(array_keys($select), array_keys($current));
            $value_intersect = array_intersect(array_values($select), array_values($current));
            if ($key_intersect) {
                throw new InvalidArgumentException('类型的键名重复：' . json_encode($key_intersect, JSON_UNESCAPED_UNICODE));
            }
            if ($value_intersect) {
                throw new InvalidArgumentException('类型的键值重复：' . json_encode($value_intersect, JSON_UNESCAPED_UNICODE));
            }

            $select = array_merge($select, $current);
        }

        return $select;
    }

    /**
     * 生成Layui计划任务配置模板
     * @param int $type
     * @return GenerateInterface
     * @throws BusinessException
     */
    public function generate(int $type): GenerateInterface
    {
        $default = new Concrete();
        try {
            return match ($type) {
                CrontabTaskTypeEnums::command->value => Decorator::make([GenerateCommand::class], $default),
                CrontabTaskTypeEnums::classMethod->value => Decorator::make([GenerateClassMethod::class], $default),
                CrontabTaskTypeEnums::urlRequest->value => Decorator::make([GenerateUrlRequest::class], $default),
                CrontabTaskTypeEnums::evalCode->value => Decorator::make([GenerateEvalCode::class], $default),
                CrontabTaskTypeEnums::shellExec->value => Decorator::make([GenerateShellExec::class], $default),
                default => $this->extend($type)
            };
        } catch (Error|Exception|Throwable $throwable) {
            throw new BusinessException($throwable->getMessage(), $throwable->getCode());
        }
    }

    /**
     * 扩展支持更多的计划任务类型
     * @param int $type
     * @return GenerateInterface
     * @throws BusinessException
     */
    protected function extend(int $type): GenerateInterface
    {
        $extend = CrontabExtend::getInstance();
        foreach ($extend->getTemplates() as $layuiTemplate) {
            try {
                if ($template = $layuiTemplate->generate($type)) {
                    return $template;
                }
            } catch (Throwable $throwable) {
            }
        }

        throw new BusinessException('未找到计划任务配置模板');
    }
}
