<?php

class Html2md
{
    public function convert($html)
    {
        // 移除HTML注释
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // 预处理：统一换行符
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // 转换代码块（保留预处理顺序）
        $html = $this->convertCodeBlocks($html);
        // 转换内联元素
        $html = $this->convertInlineElements($html);

        // 转换标题
        $html = $this->convertHeaders($html);

        // 转换列表
        $html = $this->convertLists($html);

        // 转换块级元素
        $html = $this->convertBlockElements($html);


        $html = trim($this->stripTags($html));
        // 清理多余空行
        $html = preg_replace("/\n\n+/", "\n\n", $html);

        return $html;
    }

    private function convertCodeBlocks($html)
    {
        // 处理pre标签（代码块）
        $html = preg_replace_callback('/<pre>\s*<code[^>]*>(.*?)<\/code>\s*<\/pre>/is', function ($matches) {
            $code = html_entity_decode(trim($matches[1]));
            return "```\n" . $code . "\n```\n\n";
        }, $html);

        // 处理行内code标签
        $html = preg_replace('/<code>(.*?)<\/code>/is', '`$1`', $html);

        return $html;
    }

    private function convertHeaders($html)
    {
        return preg_replace_callback('/<h([1-6])[^>]*>(.*?)<\/h[1-6]>/i', function ($matches) {
            $level = $matches[1];
            $text = trim($this->stripTags($matches[2]));
            return str_repeat('#', $level) . ' ' . $text . "\n\n";
        }, $html);
    }

    private function convertLists($html)
    {
        // 无序列表
        $html = preg_replace_callback('/<ul[^>]*>(.*?)<\/ul>/is', function ($matches) {
            return $this->processListItems($matches[1], '*');
        }, $html);

        // 有序列表
        $html = preg_replace_callback('/<ol[^>]*>(.*?)<\/ol>/is', function ($matches) {
            return $this->processListItems($matches[1], '1.');
        }, $html);

        return $html;
    }

    private function processListItems($content, $prefix) {
        $items = [];
        $index = 1; // 初始化序号
    
        // 提取所有<li>标签内容
        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $content, $matches, PREG_SET_ORDER);
    
        foreach ($matches as $match) {
            $text = trim($this->stripTags($match[1]));
            // 处理多行内容：每行缩进4个空格（Markdown列表项多行内容的规范）
            $text = preg_replace('/\n+/', "\n    ", $text);
    
            // 如果是数字前缀（有序列表），使用递增序号
            if ($prefix === '1.') {
                $items[] = "{$index}. {$text}";
                $index++; // 序号递增
            } else {
                // 无序列表直接使用前缀
                $items[] = "{$prefix} {$text}";
            }
        }
    
        // 处理嵌套列表
        $result = implode("\n", $items);
        if ($prefix === '1.') {
            $result = preg_replace('/\n([*1.])/', "\n  $1", $result); // 嵌套列表缩进
        }

        return "\n" . trim($result) . "\n\n";
    }

    private function convertBlockElements($html)
    {
        // 段落处理
        $html = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "$1\n\n", $html);

        // 水平线
        $html = preg_replace('/<hr\s*\/?>/i', "\n---\n\n", $html);

        // 块引用
        $html = preg_replace_callback('/<blockquote[^>]*>(.*?)<\/blockquote>/is', function ($matches) {
            $text = trim($this->stripTags($matches[1]));
            return preg_replace('/^/m', '> ', $text) . "\n\n";
        }, $html);

        return $html;
    }

    private function convertInlineElements($html)
    {
        // 图片
        $html = preg_replace_callback('/<img\s+[^>]*src=["\'](.*?)["\'][^>]*alt=["\'](.*?)["\'][^>]*>/i', function ($matches) {
            $alt = $matches[2];
            $src = $matches[1];
            return "![{$alt}]({$src})";
        }, $html);

        // 如果alt属性缺失，使用空字符串
        $html = preg_replace_callback('/<img\s+[^>]*src=["\'](.*?)["\'][^>]*>/i', function ($matches) {
            $src = $matches[1];
            return "![]({$src})";
        }, $html);

        // 链接
        $html = preg_replace_callback('/<a\s+.*?href=["\'](.*?)["\'][^>]*>(.*?)<\/a>/is', function ($matches) {
            $text = trim($this->stripTags($matches[2]));
            $href = $matches[1];
            return "[{$text}]({$href})";
        }, $html);

        // 加粗
        $html = preg_replace('/<(b|strong)>(.*?)<\/\1>/is', '**$2**', $html);

        // 斜体
        $html = preg_replace('/<(i|em)>(.*?)<\/\1>/is', '*$2*', $html);

        // 删除线
        $html = preg_replace('/<del>(.*?)<\/del>/is', '~~$1~~', $html);

        // 换行处理
        $html = preg_replace('/<br\s*\/?>/i', "  \n", $html);

        return $html;
    }

    private function stripTags($html)
    {
        // 移除所有HTML标签
        return html_entity_decode(strip_tags($html));
    }
}

// // 使用示例
// $converter = new HtmlToMarkdownConverter();
// $html = <<<'HTML'
// <h1>标题示例</h1>
// <p>这是一个段落，包含 <strong>加粗文本</strong> 和 <em>斜体文本</em>。</p>
// <ul>
//     <li>无序列表项1</li>
//     <li>无序列表项2</li>
// </ul>
// <ol>
//     <li>有序列表项1</li>
//     <li>有序列表项2</li>
// </ol>
// <a href="https://example.com">示例链接</a>
// <img src="image.jpg" alt="示例图片">
// <pre><code>function example() {
//     console.log('Hello World');
// }</code></pre>
// HTML;

// $markdown = $converter->convert($html);
// echo $markdown;
