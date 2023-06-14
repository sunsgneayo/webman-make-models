<?php

declare(strict_types=1);
namespace Sunsgne\WebmanMakeModels;



/**
 * 包含文件内容的类
 */
class FileContext
{
    /**
     * @var string 代码内容
     */
    public string $content = '';

    /**
     * @var string[] 行列表
     */
    public array $lines = [];

    /**
     * @var TokenAnalysis token解析后的内容
     */
    public TokenAnalysis $analysis;

    /**
     * @var bool 文件内容是否已改变
     */
    public bool $updated = false;

    public function __construct(string $file)
    {
        $this->content = file_get_contents($file);
        $this->lines = preg_split('/(\\r\\n)|\\n|\\r/', $this->content);
        $tokens = token_get_all($this->content);
        $this->analysis = new TokenAnalysis($tokens);
    }
}
