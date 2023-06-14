<?php

declare(strict_types=1);

namespace Sunsgne\WebmanMakeModels;

/**
 * token解析后的结果
 */
final class TokenAnalysis
{
    /**
     * @var string 命名空间
     */
    public string $namespace = '';

    /**
     * @var array 类列表
     */
    public array $classes = [];

    /**
     * @var array use列表
     */
    public array $uses = [];

    private array $_tokenStack = [];

    private int $_level = 0;

    /**
     * 解析tokens
     * @param array $tokens
     */
    public function __construct(array $tokens)
    {
        array_walk($tokens, function (&$token) {
            if (is_array($token)) {
                $token[0] = token_name($token[0]);
            }
        });

        $count = count($tokens);

        for ($index = 0; $index < $count; ++$index) {
            $token = $tokens[$index];
            if ($token === '{') {
                $this->_level++;
            } elseif ($token === '}') {
                $this->_level--;
            }

            if (!is_array($token)) {
                continue;
            }
            /**
             * @var string $type
             * @var string $content
             * @var int $line
             */
            [$type, $content, $line] = $token;
            switch ($type) {
                case 'T_NAMESPACE':
                case 'T_ATTRIBUTE':
                case 'T_DOUBLE_COLON':
                    $this->_tokenStack[] = $token;
                    break;
                case 'T_DOC_COMMENT':
                    if ($this->_level > 0) {
                        break;
                    }
                    $this->_tokenStack[] = $token;
                    break;
                case 'T_CLASS':
                    if ($this->getLastTokenType() === 'T_DOUBLE_COLON') {
                        array_pop($this->_tokenStack);
                    } else {
                        $this->_tokenStack[] = $token;
                    }
                    break;
                case 'T_NAME_QUALIFIED':
                    if ($this->getLastTokenType() === 'T_NAMESPACE') {
                        $this->namespace = $content;
                        array_pop($this->_tokenStack);
                    }
                    break;
                case 'T_STRING':
                    switch ($this->getLastTokenType()) {
                        case 'T_CLASS':
                            $lastToken = $this->getLastToken();
                            $class = [
                                'name'  => $this->namespace . '\\' . $content,
                                'start' => $lastToken[2]
                            ];
                            array_pop($this->_tokenStack);
                            while (!empty($this->_tokenStack)) {
                                switch ($this->getLastTokenType()) {
                                    case 'T_DOC_COMMENT':
                                        $lastToken = $this->getLastToken();
                                        $class['docComment'] = [
                                            'content' => $lastToken[1],
                                            'start'   => $lastToken[2],
                                            'end'     => $class['start'],
                                        ];
                                        $this->_tokenStack = [];
                                        break;
                                    case 'T_ATTRIBUTE':
                                        $lastToken = $this->getLastToken();
                                        $class['start'] = $lastToken[2];
                                        array_pop($this->_tokenStack);
                                        break;
                                    default:
                                        $this->_tokenStack = [];
                                        break;
                                }
                            }
                            $this->classes[] = $class;
                            break;
                        case 'T_DOUBLE_COLON':
                            array_pop($this->_tokenStack);
                            break;
                    }
                    break;
            }
        }
    }

    private function getLastTokenType(): string
    {
        $token = $this->getLastToken();
        return empty($token) ? '' : $token[0];
    }

    private function getLastToken(): mixed
    {
        if (empty($this->_tokenStack)) return null;
        return last($this->_tokenStack);
    }
}
