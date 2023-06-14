<?php

declare(strict_types=1);

namespace Sunsgne\WebmanMakeModels;


/**
 * 包含文件内容的类
 */
class MakeFile
{

    /** @var string|false  文件内容 */
    public string|bool $content = '';

    /** @var array|false|string[] 行列表 */
    public array $lines = [];

    /** @var TokenAnalysis token解析后的内容 */
    public TokenAnalysis $analysis;

    /** @var bool 文件内容变更状态 */
    public bool $updated = false;


    /**
     * @param string $file 文件路径
     */
    public function __construct(string $file)
    {
        $this->content = file_get_contents($file);
        $this->lines = preg_split('/(\\r\\n)|\\n|\\r/', $this->content);
        $tokens = token_get_all($this->content);
        $this->analysis = new TokenAnalysis($tokens);
    }
}
