<?php

namespace db;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Util\Literal;

/**
 * 列
 */
class Column extends \Phinx\Db\Table\Column
{
    /**
     * 是否唯一值
     * @var bool
     */
    protected bool $unique = false;

    /**
     * 【设置】不允许null
     * @return Column
     */
    public function setNotNull(): Column
    {
        return $this->setNull(false);
    }

    /**
     * 【设置】允许null
     * @return Column
     */
    public function setNullable(): Column
    {
        return $this->setNull(true);
    }

    /**
     * 【设置】无符号
     * @return Column
     */
    public function setUnsigned(): Column
    {
        return $this->setSigned(false);
    }

    /**
     * 【设置】唯一值
     * @return $this
     */
    public function setUnique(): static
    {
        $this->unique = true;
        return $this;
    }

    /**
     * 获取是否唯一值
     * @return bool
     */
    public function getUnique(): bool
    {
        return $this->unique;
    }

    /**
     * 获取是否唯一值
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->getUnique();
    }

    /**
     * 构建器
     * @param string $name
     * @param string|Literal $type
     * @param array $options
     * @return Column
     */
    public static function make(string $name, string|Literal $type, array $options = []): Column
    {
        $column = new self();
        $column->setName($name);
        $column->setType($type);
        $column->setOptions($options);
        return $column;
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function bigInteger(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_BIG_INTEGER)->setNull(false);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function binary(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_BLOB);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function boolean(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_BOOLEAN);
    }

    /**
     * 【构建器】
     * @param string $name
     * @param int $length
     * @return Column
     */
    public static function char(string $name, int $length = 255): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_CHAR, compact('length'));
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function date(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_DATE);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function dateTime(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_DATETIME);
    }

    /**
     * 【构建器】
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return Column
     */
    public static function decimal(string $name, int $precision = 8, int $scale = 2): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_DECIMAL, compact('precision', 'scale'));
    }

    /**
     * 【构建器】
     * @param string $name
     * @param array $values
     * @return Column
     */
    public static function enum(string $name, array $values): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_ENUM, compact('values'));
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function float(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_FLOAT);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function integer(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function json(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_JSON);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function jsonb(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_JSONB);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function longText(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TEXT, ['length' => MysqlAdapter::TEXT_LONG]);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function mediumInteger(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER, ['length' => MysqlAdapter::INT_MEDIUM]);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function mediumText(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TEXT, ['length' => MysqlAdapter::TEXT_MEDIUM]);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function smallInteger(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER, ['length' => MysqlAdapter::INT_SMALL]);
    }

    /**
     * 【构建器】
     * @param string $name
     * @param int $length
     * @return Column
     */
    public static function string(string $name, int $length = 255): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_STRING, compact('length'));
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function text(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TEXT);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function time(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TIME);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function tinyInteger(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_INTEGER, ['length' => MysqlAdapter::INT_TINY]);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function unsignedInteger(string $name): Column
    {
        return self::integer($name)->setUnSigned()->setNull(false);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function unsignedTinyInteger(string $name): Column
    {
        return self::tinyInteger($name)->setUnSigned()->setNull(false);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function timestamp(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_TIMESTAMP, ['null' => true, 'default' => null]);
    }

    /**
     * 【构建器】
     * @param string $name
     * @return Column
     */
    public static function uuid(string $name): Column
    {
        return self::make($name, AdapterInterface::PHINX_TYPE_UUID);
    }

    /**
     * 【生成列】创建时间
     * @param string $name
     * @param bool $withTimezone
     * @return Column
     */
    public static function datetimeCreatedAt(string $name = 'created_at', bool $withTimezone = false): Column
    {
        return self::dateTime($name)
            ->setNull(false)
            ->setDefault('CURRENT_TIMESTAMP')
            ->setUpdate('')
            ->setTimezone($withTimezone)
            ->setComment('创建时间');
    }

    /**
     * 【生成列】更新时间
     * @param string $name
     * @param bool $withTimezone
     * @return Column
     */
    public static function datetimeUpdatedAt(string $name = 'updated_at', bool $withTimezone = false): Column
    {
        return self::dateTime($name)
            ->setNull(true)
            ->setDefault(null)
            ->setUpdate('CURRENT_TIMESTAMP')
            ->setTimezone($withTimezone)
            ->setComment('更新时间');
    }

    /**
     * 【生成列】删除时间
     * @param string $name
     * @param bool $withTimezone
     * @return Column
     */
    public static function datetimeDeletedAt(string $name = 'deleted_at', bool $withTimezone = false): Column
    {
        return self::dateTime($name)
            ->setNull(true)
            ->setDefault(null)
            ->setUpdate('')
            ->setTimezone($withTimezone)
            ->setComment('删除时间');
    }
}
