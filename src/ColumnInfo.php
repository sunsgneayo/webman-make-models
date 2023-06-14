<?php

declare(strict_types=1);

namespace Sunsgne\WebmanMakeModels;

use stdClass;


/**
 * 数据表列信息
 * @Time 2023/6/14 18:43
 * @author sunsgne
 */
class ColumnInfo
{
    /** @var string 名称 */
    public string $name = '';


    /** @var string 类型 */
    public string $type = '';

    /** @var string|null 注释文本 */
    public ?string $comment = null;

    /**
     * 从mysql行数据解析列类型
     * @param stdClass $info
     * @return ColumnInfo
     * @Time 2023/6/14 18:44
     * @author sunsgne
     */
    public static function fromMysql(stdClass $info): ColumnInfo
    {
        $column = new ColumnInfo();
        $column->name = $info->column_name ?? $info->COLUMN_NAME;
        $column->type = match ($info->data_type ?? $info->DATA_TYPE) {
            'bigint',
            'enum',
            'int',
            'mediumint',
            'smallint',
            'timestamp',
            'tinyint'   => 'int',
            'char',
            'date',
            'datetime',
            'longtext',
            'mediumtext',
            'text',
            'varchar',
            'varbinary' => 'string',
            'decimal'   => 'number',
            'double',
            'float'     => 'float',
            'json'      => 'stdClass',
            default     => 'mixed'
        };
        if ($info->IS_NULLABLE === 'YES' && $column->type !== 'mixed') {
            $column->type .= '|null';
        }
        $column->comment = $info->column_comment ?? $info->COLUMN_COMMENT;
        return $column;
    }

    /**
     * 从pgsql获取列信息
     * @param stdClass $info
     * @return ColumnInfo
     * @Time 2023/6/14 18:44
     * @author sunsgne
     */
    public static function fromPgsql(stdClass $info): ColumnInfo
    {
        $column = new ColumnInfo();
        $column->name = $info->column_name;
        $type = rtrim($info->column_type, "0123456789");
        $column->type = match ($type) {
            'timestamptz',
            'varchar',
            'bytea',
            'date',
            'time',
            'timestamp',
            'text',
            'bpchar'  => 'string',
            'float',
            'numeric' => 'float',
            'int'     => 'int',
            'json',
            'jsonb'   => 'stdClass',
            'bool'    => 'bool',
            default   => 'mixed'
        };
        if (!$info->is_notnull && $column->type !== 'mixed') {
            $column->type .= '|null';
        }
        $column->comment = $info->column_comment;
        return $column;
    }
}
