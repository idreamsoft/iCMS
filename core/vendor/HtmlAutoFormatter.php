<?php

/**
 * HTML 自动格式化类
 * 兼容 PHP 7.0 及以上版本
 * 提供安全的HTML内容清理和格式化功能
 */
class HtmlAutoFormatter
{
    /**
     * 配置选项
     * @var array
     */
    private $config = [
        'remove_dangerous_tags' => true,
        'remove_event_attributes' => true,
        'remove_style_attributes' => true,
        'wrap_text_in_paragraphs' => true,
        'wrap_images_in_paragraphs' => true,
        'clean_empty_paragraphs' => true,
        'allowed_tags' => ['p', 'br', 'strong', 'em', 'u', 'i', 'b', 'a', 'img', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'div', 'span'],
        'allowed_attributes' => ['href', 'src', 'alt', 'title', 'class', 'id']
    ];

    /**
     * 构造函数
     * @param array $config 配置选项
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }
    /**
     * 自动格式化 HTML 内容
     * 
     * @param string $html 原始 HTML 内容
     * @param array $config 配置选项
     * @return string 格式化后的 HTML
     */
    public static function autoformat($html, array $config = [])
    {
        $formatter = new self($config);
        return $formatter->format($html);
    }

    /**
     * 格式化 HTML 内容
     * 
     * @param string $html 原始 HTML 内容
     * @return string 格式化后的 HTML
     * @throws InvalidArgumentException 当输入不是字符串时
     */
    public function format($html)
    {
        if (!is_string($html)) {
            throw new InvalidArgumentException('HTML content must be a string');
        }

        // 快速检查空内容
        $trimmedHtml = trim($html);
        if (empty($trimmedHtml)) {
            return '';
        }

        try {
            // 使用 DOMDocument 处理 HTML
            $dom = $this->createDomDocument($trimmedHtml);
            if (!$dom) {
                return $this->fallbackClean($html);
            }
            
            // 清理危险元素和属性
            $this->cleanHtml($dom);

            // 格式化内容
            $formattedHtml = $this->formatHtmlContent($dom);

            // 最终清理和格式化
            return $this->finalClean($formattedHtml);
            
        } catch (Exception $e) {
            // 如果DOM处理失败，使用备用清理方法
            error_log('HtmlAutoFormatter DOM processing failed: ' . $e->getMessage());
            return $this->fallbackClean($html);
        }
    }



    /**
     * 创建 DOMDocument 对象
     * 
     * @param string $html HTML 内容
     * @return DOMDocument|null DOMDocument 对象或 null
     */
    private function createDomDocument($html)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->encoding = 'UTF-8';
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;
        
        // 禁用libxml错误输出
        $previousValue = libxml_use_internal_errors(true);
        
        try {
            // 确保HTML使用UTF-8编码 - PHP8兼容性修复
            if (!mb_check_encoding($html, 'UTF-8')) {
                $html = mb_convert_encoding($html, 'UTF-8', 'auto');
            }
            // 处理HTML实体，替代已弃用的mb_convert_encoding HTML-ENTITIES模式
            $html = htmlspecialchars_decode($html, ENT_QUOTES | ENT_HTML5);
            
            // 包装 HTML 以确保正确解析
            $wrappedHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
            
            $loaded = $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
            
            return $loaded ? $dom : null;
            
        } finally {
            // 恢复libxml错误设置
            libxml_use_internal_errors($previousValue);
        }
    }

    /**
     * 清理危险元素和属性
     * 
     * @param DOMDocument $dom DOMDocument 对象
     * @return void
     */
    private function cleanHtml($dom)
    {
        $xpath = new DOMXPath($dom);
        
        // 移除危险标签
        if ($this->config['remove_dangerous_tags']) {
            $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'button', 'select'];
            foreach ($dangerousTags as $tag) {
                $elements = $xpath->query("//{$tag}");
                $this->removeElements($elements);
            }
        }
        
        // 移除不允许的标签
        if (!empty($this->config['allowed_tags'])) {
            $allElements = $xpath->query('//body//*');
            foreach ($allElements as $element) {
                if (!in_array(strtolower($element->nodeName), $this->config['allowed_tags'], true)) {
                    $this->unwrapElement($element);
                }
            }
        }
        
        // 移除事件属性
        if ($this->config['remove_event_attributes']) {
            $this->removeEventAttributes($xpath);
        }
        
        // 移除style属性
        if ($this->config['remove_style_attributes']) {
            $styledElements = $xpath->query('//*[@style]');
            foreach ($styledElements as $element) {
                if($element instanceof DOMElement) {
                    $element->removeAttribute('style');
                }
            }
        }
        
        // 清理属性
        $this->cleanAttributes($xpath);
        
        // 处理特殊元素
        $this->cleanSpecialElements($xpath);
    }
    
    /**
     * 移除元素集合
     * 
     * @param DOMNodeList $elements 元素集合
     * @return void
     */
    private function removeElements($elements)
    {
        $elementsArray = [];
        foreach ($elements as $element) {
            $elementsArray[] = $element;
        }
        
        foreach ($elementsArray as $element) {
            if ($element->parentNode) {
                $element->parentNode->removeChild($element);
            }
        }
    }
    
    /**
     * 解包元素（保留内容，移除标签）
     * 
     * @param DOMElement $element 要解包的元素
     * @return void
     */
    private function unwrapElement($element)
    {
        if (!$element->parentNode) {
            return;
        }
        
        $parent = $element->parentNode;
        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }
    
    /**
     * 移除事件属性
     * 
     * @param DOMXPath $xpath XPath对象
     * @return void
     */
    private function removeEventAttributes($xpath)
    {
        $elements = $xpath->query('//*[@*[starts-with(name(), "on")]]');
        foreach ($elements as $element) {
            $attrsToRemove = [];
            foreach ($element->attributes as $attr) {
                if (strpos($attr->name, 'on') === 0) {
                    $attrsToRemove[] = $attr->name;
                }
            }
            foreach ($attrsToRemove as $attrName) {
                if($element instanceof DOMElement) {
                    $element->removeAttribute($attrName);
                }
            }
        }
    }
    
    /**
     * 清理元素属性
     * 
     * @param DOMXPath $xpath XPath对象
     * @return void
     */
    private function cleanAttributes($xpath)
    {
        if (empty($this->config['allowed_attributes'])) {
            return;
        }
        
        $allElements = $xpath->query('//body//*');
        foreach ($allElements as $element) {
            $attrsToRemove = [];
            foreach ($element->attributes as $attr) {
                if (!in_array($attr->name, $this->config['allowed_attributes'], true)) {
                    $attrsToRemove[] = $attr->name;
                }
            }
            foreach ($attrsToRemove as $attrName) {
                if($element instanceof DOMElement) {
                    $element->removeAttribute($attrName);
                }
            }
        }
    }
    
    /**
     * 清理特殊元素
     * 
     * @param DOMXPath $xpath XPath对象
     * @return void
     */
    private function cleanSpecialElements($xpath)
    {
        // 移除空src的图片
        $emptyImgs = $xpath->query('//img[not(@src) or @src=""]');
        $this->removeElements($emptyImgs);
        
        // 移除空href的链接
        $emptyLinks = $xpath->query('//a[not(@href) or @href=""]');
        foreach ($emptyLinks as $link) {
            $this->unwrapElement($link);
        }
        
        // 清理空的块级元素
        $emptyBlocks = $xpath->query('//div[not(node())] | //p[not(node())] | //h1[not(node())] | //h2[not(node())] | //h3[not(node())] | //h4[not(node())] | //h5[not(node())] | //h6[not(node())]');
        $this->removeElements($emptyBlocks);
    }

    /**
     * 格式化 HTML 内容
     * 
     * @param DOMDocument $dom DOMDocument 对象
     * @return string 格式化后的内容
     */
    private function formatHtmlContent($dom)
    {
        $xpath = new DOMXPath($dom);
        $body = $dom->getElementsByTagName('body')->item(0);
        
        if (!$body) {
            return '';
        }
        
        // 处理文本节点，为纯文本添加段落标签
        if ($this->config['wrap_text_in_paragraphs']) {
            $this->wrapTextInParagraphs($body);
        }
        
        // 处理图片，确保图片在段落中
        if ($this->config['wrap_images_in_paragraphs']) {
            $this->wrapImagesInParagraphs($xpath);
        }
        
        // 清理空的段落标签
        if ($this->config['clean_empty_paragraphs']) {
            $this->cleanEmptyParagraphs($xpath);
        }
        
        // 获取处理后的内容
        $content = '';
        foreach ($body->childNodes as $node) {
            $content .= $dom->saveHTML($node);
        }
        
        return $content;
    }

    /**
     * 为纯文本节点添加段落标签
     * 
     * @param DOMElement $element DOM 元素
     * @return void
     */
    private function wrapTextInParagraphs($element)
    {
        $nodesToProcess = [];
        $blockElements = ['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'blockquote', 'ul', 'ol', 'li', 'table', 'tr', 'td', 'th'];
        
        // 收集需要处理的文本节点
        foreach ($element->childNodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE) {
                $text = trim($node->nodeValue);
                if (!empty($text) && $text !== "\n" && $text !== "\r\n") {
                    $nodesToProcess[] = $node;
                }
            } elseif ($node->nodeType === XML_ELEMENT_NODE) {
                // 递归处理子元素（非块级元素）
                $nodeName = strtolower($node->nodeName);
                if (!in_array($nodeName, $blockElements, true)) {
                    $this->wrapTextInParagraphs($node);
                }
            }
        }
        
        // 为文本节点添加段落标签
        foreach ($nodesToProcess as $textNode) {
            $text = trim($textNode->nodeValue);
            if (!empty($text)) {
                $p = $textNode->ownerDocument->createElement('p');
                $p->appendChild($textNode->ownerDocument->createTextNode($text));
                if ($textNode->parentNode) {
                    $textNode->parentNode->replaceChild($p, $textNode);
                }
            } else {
                if ($textNode->parentNode) {
                    $textNode->parentNode->removeChild($textNode);
                }
            }
        }
    }

    /**
     * 确保图片在段落标签中
     * 
     * @param DOMXPath $xpath DOMXPath 对象
     * @return void
     */
    private function wrapImagesInParagraphs($xpath)
    {
        $imgTags = $xpath->query('//img[not(parent::p)]');
        foreach ($imgTags as $img) {
            $p = $img->ownerDocument->createElement('p');
            $img->parentNode->insertBefore($p, $img);
            $p->appendChild($img);
        }
    }

    /**
     * 清理空的段落标签
     * 
     * @param DOMXPath $xpath DOMXPath 对象
     * @return void
     */
    private function cleanEmptyParagraphs($xpath)
    {
        $emptyParagraphs = $xpath->query('//p[not(node())]');
        foreach ($emptyParagraphs as $p) {
            $p->parentNode->removeChild($p);
        }
    }

    /**
     * 最终清理和格式化
     * 
     * @param string $html HTML 内容
     * @return string 清理后的 HTML
     */
    private function finalClean($html)
    {
        if (empty($html)) {
            return '';
        }
        
        // 标准化换行符
        $html = str_replace(["\r\n", "\r"], "\n", $html);
        
        // 清理多余的空白字符（保留必要的空格）
        $html = preg_replace('/[ \t]+/', ' ', $html);
        $html = preg_replace('/\n\s*\n/', "\n", $html);
        
        // 清理多余的段落嵌套
        $html = preg_replace('/<p>\s*<p>(.*?)<\/p>\s*<\/p>/is', '<p>$1</p>', $html);
        
        // 确保自闭合标签格式正确
        $html = preg_replace('/<(br|hr|img|input|meta|link)([^>]*?)\s*\/?>/i', '<$1$2 />', $html);
        
        // 清理空标签（除了自闭合标签）
        $selfClosingTags = 'br|hr|img|input|meta|link|area|base|col|embed|source|track|wbr';
        $html = preg_replace('/<(?!' . $selfClosingTags . ')([a-zA-Z][a-zA-Z0-9]*)([^>]*)>\s*<\/\1>/i', '', $html);
        
        // 清理连续的空段落
        $html = preg_replace('/(<p>\s*<\/p>\s*)+/', '', $html);
        
        // 清理段落中只有空白字符的情况
        $html = preg_replace('/<p>\s*(&nbsp;|\s)*\s*<\/p>/', '', $html);
        
        // 清理多余的空白
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/> </', '><', $html);
        
        return trim($html);
    }

    /**
     * 备用清理方法（当DOM处理失败时使用）
     * 
     * @param string $html HTML 内容
     * @return string 清理后的 HTML
     */
    private function fallbackClean($html)
    {
        if (empty($html)) {
            return '';
        }
        
        // 移除危险标签
        $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'button', 'select'];
        foreach ($dangerousTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $html);
            $html = preg_replace('/<' . $tag . '\b[^>]*\/?>/i', '', $html);
        }
        
        // 移除事件属性
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\'>]*["\']/', '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/', '', $html);
        
        // 移除style属性
        $html = preg_replace('/\s+style\s*=\s*["\'][^"\'>]*["\']/', '', $html);
        
        // 基本清理
        $html = strip_tags($html, '<p><br><strong><em><u><i><b><a><img><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote><pre><code><div><span>');
        $html = preg_replace('/\s+/', ' ', $html);
        
        return trim($html);
    }
    
    /**
     * 获取配置选项
     * 
     * @param string $key 配置键名
     * @return mixed 配置值
     */
    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }
    
    /**
     * 设置配置选项
     * 
     * @param string|array $key 配置键名或配置数组
     * @param mixed $value 配置值
     * @return self
     */
    public function setConfig($key, $value = null)
    {
        if (is_array($key)) {
            $this->config = array_merge($this->config, $key);
        } else {
            $this->config[$key] = $value;
        }
        return $this;
    }

    /**
     * 简化版本的格式化方法
     * 
     * @param string $html HTML 内容
     * @return string 清理后的 HTML
     */
    public function simpleFormat($html)
    {
        if (!is_string($html) || empty(trim($html))) {
            return '';
        }
        
        try {
            // 创建临时配置，只进行基本清理
            $tempConfig = [
                'remove_dangerous_tags' => true,
                'remove_event_attributes' => true,
                'remove_style_attributes' => true,
                'wrap_text_in_paragraphs' => false,
                'wrap_images_in_paragraphs' => false,
                'clean_empty_paragraphs' => true
            ];
            
            $originalConfig = $this->config;
            $this->config = array_merge($this->config, $tempConfig);
            
            // 使用DOM处理
            $dom = $this->createDomDocument($html);
            if (!$dom) {
                $this->config = $originalConfig;
                return $this->fallbackClean($html);
            }
            
            // 基本清理
            $this->cleanHtml($dom);
            
            // 获取内容
            $body = $dom->getElementsByTagName('body')->item(0);
            if (!$body) {
                $this->config = $originalConfig;
                return '';
            }
            
            $content = '';
            foreach ($body->childNodes as $node) {
                $content .= $dom->saveHTML($node);
            }
            
            // 恢复原配置
            $this->config = $originalConfig;
            
            // 基本清理
            $content = str_replace(['&nbsp;', '　'], ' ', $content);
            $content = preg_replace('/\s+/', ' ', $content);
            
            return trim($content);
            
        } catch (Exception $e) {
            error_log('HtmlAutoFormatter simpleFormat failed: ' . $e->getMessage());
            return $this->fallbackClean($html);
        }
    }
}

// 兼容函数 - 如果需要静态调用
if (!function_exists('autoformat_html')) {
    /**
     * 兼容函数 - 静态调用格式化方法
     * 
     * @param string $html HTML 内容
     * @param array $config 配置选项
     * @return string 格式化后的 HTML
     */
    function autoformat_html($html, array $config = [])
    {
        return HtmlAutoFormatter::autoformat($html, $config);
    }
}

if (!function_exists('simple_format_html')) {
    /**
     * 兼容函数 - 简化格式化方法
     * 
     * @param string $html HTML 内容
     * @param array $config 配置选项
     * @return string 格式化后的 HTML
     */
    function simple_format_html($html, array $config = [])
    {
        $formatter = new HtmlAutoFormatter($config);
        return $formatter->simpleFormat($html);
    }
}