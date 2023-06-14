<?php

declare(strict_types=1);

namespace Sunsgne\WebmanMakeModels;

use support\Db;

/**
 * 数据表信息
 * @Time 2023/6/14 10:40
 * @author sunsgne
 */
class TableInfo
{

    /** @var string|null 数据库连接配置 */
    public ?string $connection = null;

    /** @var string 数据表名 */
    public string $table = '';

    /** @var array 连接配置项 */
    public array $config = [];


    /** @var string|null 表注释 */
    public ?string $comment = null;

    /** @var array 列信息 */
    public array $columns = [];

    /**
     * 读入表注释
     * @return void
     * @throws MakeException
     */
    public function readTableComment(): void
    {
        match ($this->config['driver']) {
            'mysql' => $this->readMysqlTableComment(),
            'pgsql' => $this->readPgsqlTableComment()
        };
    }

    /**
     * 读入mysql表注释
     * @return void
     * @throws MakeException
     */
    protected function readMysqlTableComment(): void
    {
        $rows = Db::connection($this->connection)
                  ->select(
                  /**
                   * @dialect mysql
                   */
                      'SELECT table_comment FROM information_schema.`TABLES` WHERE table_schema = ? AND table_name = ?',
                      [$this->config['database'], $this->getTableName()]
                  );
        if (empty($rows)) {
            throw new MakeException('表不存在');
        }
        $this->comment = $rows[0]->table_comment ?? $rows[0]->TABLE_COMMENT;
    }

    /**
     * 获取完整的表名
     * @return string
     */
    protected function getTableName(): string
    {
        return ($this->config['prefix'] ?? '') . $this->table;
    }

    /**
     * 读取pgsql表注释
     * @return void
     * @throws MakeException
     */
    protected function readPgsqlTableComment(): void
    {
        $rows = Db::connection($this->connection)->select(
            "
        SELECT
            CAST ( obj_description ( c.relfilenode, 'pg_class' ) AS VARCHAR ) AS comment 
        FROM
            pg_class as c,
            pg_namespace as nsp
        WHERE
            c.relnamespace = nsp.oid
            AND
            nsp.nspname = ?
            AND
            c.relname = ?
        ", [$this->config['schema'] ?? 'public', $this->getTableName()]);
        if (empty($rows)) {
            throw new MakeException('表不存在');
        }
        $this->comment = $rows[0]->comment ?? $rows[0]->COMMENT;
    }

    /**
     * 从数据库中读入列信息
     * @return void
     */
    public function readColumns(): void
    {
        match ($this->config['driver']) {
            'mysql' => $this->readMysqlColumns(),
            'pgsql' => $this->readPgsqlColumns()
        };
    }

    /**
     * 读取mysql列信息
     * @return void
     */
    protected function readMysqlColumns(): void
    {
        $rows = Db::connection($this->connection)->select(
            "
            SELECT
                column_name,
                is_nullable,
                data_type,
                column_type,
                extra,
                column_comment
            FROM
                information_schema.`COLUMNS`
            WHERE
                table_schema = ? AND table_name = ?
            ORDER BY
                ordinal_position
            ",
            [$this->config['database'], $this->getTableName()]
        );
        $this->columns = array_map([ColumnInfo::class, 'fromMysql'], $rows);
    }

    /**
     * 读取pgsql列信息
     */
    protected function readPgsqlColumns(): void
    {
        $rows = Db::connection($this->connection)->select(
            "
            SELECT
                a.attname as column_name,
                d.description as column_comment,
                t.typname as column_type,
	            a.attnotnull as is_notnull
            FROM
                pg_class AS c,
                pg_namespace as nsp,
                pg_type AS t,
                pg_attribute AS a
            LEFT JOIN
                pg_description AS d ON d.objoid = a.attrelid AND d.objsubid = a.attnum
            WHERE
                c.relnamespace = nsp.oid
                AND nsp.nspname = ?
                AND c.relname = ?
                AND a.attrelid = c.oid
                AND a.attnum > 0
                AND t.oid = a.atttypid
            ORDER BY
                a.attnum
            ",
            [$this->config['schema'] ?? 'public', $this->getTableName()]
        );
        $this->columns = array_map([ColumnInfo::class, 'fromPgsql'], $rows);
    }
}
