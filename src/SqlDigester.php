<?php /** @noinspection RegExpSimplifiable */

namespace SQLTrace;

use HashContext;

class SqlDigester
{
    private string $buffer = '';
    private ?HashContext $hash = null;

    public function __construct()
    {
        $this->buffer = '';
        $this->hash = hash_init('sha256');
    }

    private function normalize($sql)
    {
        // 搜索 ?, ? 包含多个 ? 替换成 ...
        $this->buffer = preg_replace_callback(
            '/in\s*\((\s*\?,{0,1}\s{0,1}\s*)+/',
            function ($matches) {
                if (count($matches) > 0) {
                    return ' ... ';
                }
                return '';
            },
            $sql
        );
        $this->buffer = $this->replaceLimit($this->buffer);
    }

    protected function replaceLimit($sql)
    {
        // 使用正则表达式匹配并替换 "limit 1" 为 "limit ?"
        $pattern = '/\blimit\s+\d+\b/i';
        $replacement = 'limit ?';
        return preg_replace($pattern, $replacement, $sql);
    }

    public function doDigest($sql): string
    {
        $this->normalize($sql);
        hash_update($this->hash, $this->buffer);
        $this->buffer = '';
        $digest = hash_final($this->hash, true);
        $this->hash = hash_init('sha256');
        return bin2hex($digest);
    }
}
