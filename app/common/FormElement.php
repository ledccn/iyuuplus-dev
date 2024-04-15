<?php

namespace app\common;

use JsonSerializable;

/**
 * 表单元素
 * @link https://www.workerman.net/doc/webman-admin/base/control.html
 */
class FormElement implements JsonSerializable
{
    /**
     * 字段元素ID
     * @var string
     */
    public string $field_id = '';

    /**
     * 字段名称
     * @var string
     */
    public string $field = '';

    /**
     * 字段备注
     * @var string
     */
    public string $comment = '';

    /**
     * 控件类型
     * @var string
     */
    public string $control = 'input';

    /**
     * 控件参数
     * @var string
     */
    public string $control_args = '';

    /**
     * 默认值
     * @var string
     */
    public string $default = '';

    /**
     * 字段长度值
     * @var int|null
     */
    public int|null $length = null;

    /**
     * （设置）字段元素ID
     * @param string $field_id
     * @return FormElement
     */
    public function setFieldId(string $field_id): self
    {
        $this->field_id = $field_id;
        return $this;
    }

    /**
     * （设置）字段名称
     * @param string $field
     * @return FormElement
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }

    /**
     * （设置）字段备注
     * @param string $comment
     * @return FormElement
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * （设置）控件类型
     * @param string $control
     * @return FormElement
     */
    public function setControl(string $control): self
    {
        $this->control = $control;
        return $this;
    }

    /**
     * （设置）控件参数
     * @param string $control_args
     * @return FormElement
     */
    public function setControlArgs(string $control_args): self
    {
        $this->control_args = $control_args;
        return $this;
    }

    /**
     * （设置）默认值
     * @param string $default
     * @return FormElement
     */
    public function setDefault(string $default): self
    {
        $this->default = $default;
        return $this;
    }

    /**
     * （设置）字段长度值
     * @param int $length
     * @return FormElement
     */
    public function setLength(int $length): self
    {
        $this->length = $length;
        return $this;
    }

    /**
     * 对象转数组
     * @return array
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
