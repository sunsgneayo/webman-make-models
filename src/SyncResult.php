<?php

declare(strict_types=1);

namespace Sunsgne\WebmanMakeModels;

use Throwable;

/**
 * 模型同步结果
 */
class SyncResult
{
    /**
     * 模型同步结果
     * @param string $file 源文件路径
     * @param string $class 模型类
     * @param Throwable|null $exception 异常
     */
    public function __construct(
        public string     $file,
        public string     $class,
        public ?Throwable $exception = null
    )
    {

    }
}