<?php

// namespace iPHP\core;

/**
 * iPHP - i PHP Framework
 * Copyright (c) iiiPHP.com. All rights reserved.
 *
 * @author iPHPDev <master@iiiphp.com>
 * @website http://www.iiiphp.com
 * @license http://www.iiiphp.com/license
 * @version 2.3.0
 */
// use iPHP;

/**
 * 验证码生成类
 * 支持多种图片格式输出，提供丰富的干扰效果
 * 
 * 主要特性：
 * - 支持TTF字体和GIF字体渲染
 * - 多种干扰元素（线条、像素点、背景字符、正弦曲线）
 * - 自适应图片尺寸和字符间距
 * - 安全的验证码存储和验证机制
 * - 完善的错误处理和异常管理
 * - 支持Token模式和Session模式
 * 
 * @package iPHP.core
 * @since PHP 5.6
 * @requires GD库（推荐）或BMP备用方案
 */
class Captcha
{
    // 图片配置
    public static $width = 0;           // 图片宽度，0为自动计算
    public static $height = 35;         // 图片高度
    public static $fontNum = 4;         // 字符数量
    public static $fontSize = 22;       // 字体大小
    public static $fontShadow = 0;      // 字体阴影色差，0为无阴影
    public static $fontAngle = 0;       // 字体旋转角度，0为无旋转
    public static $fontRand = false;    // 是否随机字体
    public static $spacing = 0;         // 字符间距，0为自动
    
    // 干扰配置
    public static $lineNum = 0;         // 干扰线数量
    public static $pixelNum = 0;        // 干扰点数量
    public static $curveNum = 0;        // 正弦曲线数量
    public static $bgCharNum = 4;       // 背景字符数量组，每组5个
    
    // 颜色配置
    public static $contrast = 125;      // 颜色反差值，越大越好识别，最大200
    public static $randColor = false;   // 是否随机颜色
    
    // 字符集和Token配置
    public static $charset = '2345789ABCDEFHJKLMNPQRTUVWXYZ';
    public static $isToken = false;
    
    // 内部状态变量
    protected static $bgColor = [];
    protected static $textColor = [];
    protected static $code = null;
    protected static $curveColor = null;
    protected static $image = null;
    protected static $mainColor = null;
    
    // 支持的图片格式
    private static $supportedFormats = ['jpeg', 'png', 'gif'];

    /**
     * 检查Token验证码
     * @param string $seccode 用户输入的验证码
     * @param string $token 验证码Token
     * @param string|false $flag 清除标识
     * @return bool
     * @throws sException
     */
    public static function checkToken($seccode, $token, $flag = false)
    {
        if (empty($seccode) || empty($token)) {
            throw new sException('验证码或Token不能为空', 'Captcha');
        }
        
        $cookieSeccode = auth_decode($token);
        
        // 清除标识
        if ($flag) {
            self::cookie($flag, '', -31536000);
        }
        
        if (empty($cookieSeccode) || strtolower($cookieSeccode) !== strtolower($seccode)) {
            throw new sException('验证码错误', 'Captcha');
        }
        
        return true;
    }

    /**
     * 检查验证码
     * @param string|null $captcha 用户输入的验证码
     * @param string $prefix 前缀
     * @param bool $destroy 是否销毁验证码
     * @return bool
     * @throws sException
     */
    public static function check($captcha = null, $prefix = '', $destroy = true)
    {
        // 获取用户输入的验证码
        if ($captcha === null) {
            $captcha = Request::spost('captcha');
        }
        
        if (empty($captcha)) {
            throw new sException('请输入验证码', 'Captcha');
        }
        
        // 构建cookie名称
        $name = $prefix ? $prefix . '_captcha' : 'captcha';
        
        // 获取存储的验证码
        $storedCaptcha = self::cookie($name);
        $cookieCaptcha = $storedCaptcha ? auth_decode($storedCaptcha) : '';
        
        // 销毁验证码
        if ($destroy) {
            self::cookie($name, '', -31536000);
        }
        
        if (empty($cookieCaptcha) || strtolower($cookieCaptcha) !== strtolower($captcha)) {
            throw new sException('验证码错误', 'Captcha');
        }
        
        return true;
    }

    /**
     * 生成验证码图片
     * @param string|null $prefix 前缀
     * @return void
     * @throws Exception 当图片生成失败时抛出异常
     */
    public static function get($prefix = null)
    {
        try {
            // 清理输出缓冲区
            self::clearOutputBuffer();
            
            // 设置防缓存头
            self::setNoCacheHeaders();

            // 构建cookie名称
            $name = $prefix ? $prefix . '_captcha' : 'captcha';

            // 生成验证码
            if (!self::$code) {
                self::$code = self::generateCode();
            }
            
            // 编码并存储验证码
            $authcode = auth_encode(self::$code, 180);
            if (self::$isToken) {
                header('Captcha-Token: ' . $authcode);
            }
            self::cookie($name, $authcode);

            // 检查GD库支持并生成图片
            if (self::isGdSupported()) {
                if (!self::generateImage()) {
                    throw new Exception('验证码图片生成失败');
                }
            } else {
                self::generateBmp();
            }
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Captcha generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * 清理输出缓冲区
     */
    private static function clearOutputBuffer()
    {
        while (ob_get_level()) {
            ob_end_clean();
        }
    }
    
    /**
     * 设置防缓存头
     */
    private static function setNoCacheHeaders()
    {
        header('Expires: -1');
        header('Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0', false);
        header('Pragma: no-cache');
    }
    
    /**
     * 检查GD库支持
     * @return bool 如果GD库完全支持返回true，否则返回false
     */
    private static function isGdSupported()
    {
        // 检查GD扩展是否加载
        if (!extension_loaded('gd')) {
            return false;
        }
        
        $requiredFunctions = [
            'imagecreate', 'imagecolorset', 'imagecopyresized',
            'imagecreatetruecolor', 'imagecolorallocate', 'imagearc',
            'imageline', 'imagesetpixel'
        ];
        
        foreach ($requiredFunctions as $function) {
            if (!function_exists($function)) {
                return false;
            }
        }
        
        // 检查字体支持
        if (!function_exists('imagettftext') && !function_exists('imagechar')) {
            return false;
        }
        
        // 检查图片格式支持
        $formatSupported = false;
        foreach (self::$supportedFormats as $format) {
            if (function_exists('image' . $format)) {
                $formatSupported = true;
                break;
            }
        }
        
        return $formatSupported;
    }

    /**
     * Cookie操作封装
     * @param string $key 键名
     * @param string|null $value 值
     * @param int $expires 过期时间
     * @return mixed
     */
    private static function cookie($key, $value = null, $expires = 0)
    {
        if ($value === null) {
            return Session::get($key);
        }
        return Session::set($key, $value, $expires);
    }

    /**
     * 生成图片验证码
     * @return bool 成功返回true，失败返回false
     * @throws Exception 当画布创建失败时抛出异常
     */
    private static function generateImage()
    {
        try {
            // 初始化画布
            self::initCanvas();
            
            if (!self::$image) {
                throw new Exception('画布创建失败');
            }
            
            // 添加背景字符
            self::addBackgroundChars();

            // 绘制主要文字
            if (function_exists('imagettftext')) {
                self::drawTtfText();
            } else {
                self::drawGifText();
            }
            
            // 添加干扰曲线
            for ($i = 0; $i < self::$curveNum; $i++) {
                self::drawCurve();
            }
            
            // 添加干扰元素
            self::addInterference();

            // 输出图片
            return self::outputImage();
        } catch (Exception $e) {
            if (self::$image) {
                imagedestroy(self::$image);
            }
            throw $e;
        }
    }
    
    /**
     * 输出图片
     * @return bool
     */
    private static function outputImage()
    {
        $success = false;
        
        if (function_exists('imagejpeg')) {
            header('Content-Type: image/jpeg');
            $success = imagejpeg(self::$image);
        } elseif (function_exists('imagepng')) {
            header('Content-Type: image/png');
            $success = imagepng(self::$image);
        } elseif (function_exists('imagegif')) {
            header('Content-Type: image/gif');
            $success = imagegif(self::$image);
        }
        
        if (self::$image) {
            imagedestroy(self::$image);
        }
        
        return $success;
    }

    /**
     * 生成随机验证码
     * @return string
     */
    private static function generateCode()
    {
        $charset = self::$charset;
        $charsetLength = strlen($charset) - 1;
        $code = '';
        
        for ($i = 0; $i < self::$fontNum; $i++) {
            $code .= $charset[mt_rand(0, $charsetLength)];
        }
        
        return $code;
    }

    /**
     * 初始化画布
     * @throws Exception 当画布创建失败时抛出异常
     */
    private static function initCanvas()
    {
        // 计算图片尺寸
        if (!self::$width) {
            self::$width = self::$fontNum * (self::$fontSize * 1.25);
        }
        if (!self::$height) {
            self::$height = self::$fontSize * 1.85;
        }
        
        // 验证尺寸合理性
        if (self::$width <= 0 || self::$height <= 0) {
            throw new Exception('图片尺寸无效');
        }
        
        // 创建画布
        self::$image = imagecreate(self::$width, self::$height);
        
        if (!self::$image) {
            throw new Exception('画布创建失败，可能是内存不足');
        }
        
        // 生成背景色和文字色
        $bgColor = [];
        $textColor = [];
        
        for ($i = 0; $i < 3; $i++) {
            $bgColor[$i] = mt_rand(220, 240);
            $textColor[$i] = abs($bgColor[$i] - self::$contrast);
        }
        
        self::$bgColor = $bgColor;
        self::$textColor = $textColor;
        
        // 设置背景色
        imagecolorallocate(self::$image, $bgColor[0], $bgColor[1], $bgColor[2]);
        
        // 设置主要文字颜色
        self::$mainColor = imagecolorallocate(self::$image, $textColor[0], $textColor[1], $textColor[2]);
        self::$curveColor = self::$mainColor;
    }

    /**
     * 添加干扰元素（线条和像素点）
     */
    private static function addInterference()
    {
        $color = self::$mainColor;
        
        // 添加干扰线
        for ($i = 0; $i < self::$lineNum; $i++) {
            if ($i % 2) {
                // 绘制弧线
                $x = mt_rand(0, self::$width);
                $y = 0;
                $x2 = mt_rand(0, self::$width);
                $y2 = self::$height;
                $s = 0;
                $e = mt_rand(0, 360);
                imagearc(self::$image, $x, $y, $x2, $y2, $s, $e, $color);
                imagearc(self::$image, $x + 1, $y, $x2 + 1, $y2, $s, $e, $color);
            } else {
                // 绘制直线
                $x = 0;
                $y = mt_rand(0, self::$height);
                $x2 = self::$width;
                $y2 = mt_rand(0, self::$height);
                imageline(self::$image, $x, $y, $x2, $y2, $color);
                imageline(self::$image, $x, $y + 1, $x2, $y2 + 1, $color);
            }
        }
        
        // 添加干扰像素点
        for ($i = 0; $i < self::$pixelNum; $i++) {
            $x = mt_rand(0, self::$width);
            $y = mt_rand(0, self::$height);
            $pixelColor = self::getRandomColor(false, true, -50);
            
            // 绘制像素点群
            imagesetpixel(self::$image, $x, $y, $pixelColor);
            imagesetpixel(self::$image, $x + 1, $y, $pixelColor);
            imagesetpixel(self::$image, $x - 1, $y, $pixelColor);
            imagesetpixel(self::$image, $x, $y + 1, $pixelColor);
            imagesetpixel(self::$image, $x, $y - 1, $pixelColor);
        }
    }

    /**
     * 添加背景字符
     */
    private static function addBackgroundChars()
    {
        $charsetLength = strlen(self::$charset) - 1;
        
        for ($i = 0; $i < self::$bgCharNum; $i++) {
            $color = self::getRandomColor(false, true, -50);
            
            for ($j = 0; $j < 5; $j++) {
                imagestring(
                    self::$image,
                    5,
                    mt_rand(-5, self::$width),
                    mt_rand(-5, self::$height),
                    self::$charset[mt_rand(0, $charsetLength)],
                    $color
                );
            }
        }
    }

    /**
     * 获取随机颜色
     * @param bool $returnArray 是否返回数组格式
     * @param bool $forceRandom 强制随机
     * @param int $fix 颜色修正值
     * @return mixed
     */
    private static function getRandomColor($returnArray = false, $forceRandom = false, $fix = 0)
    {
        if (self::$randColor || $forceRandom) {
            $R = mt_rand(self::$textColor[0], self::$bgColor[0] + $fix);
            $G = mt_rand(self::$textColor[1], self::$bgColor[1] + $fix);
            $B = mt_rand(self::$textColor[2], self::$bgColor[2] + $fix);
            $color = imagecolorallocate(self::$image, $R, $G, $B);
        } else {
            $R = self::$textColor[0];
            $G = self::$textColor[1];
            $B = self::$textColor[2];
            $color = self::$mainColor;
        }
        
        return $returnArray ? [$color, $R, $G, $B] : $color;
    }

    /**
     * 获取TTF字体文件路径
     * @param int $fontNumber 字体编号引用
     * @return string
     */
    private static function getTtfPath(&$fontNumber = 0)
    {
        $fontNumber = mt_rand(1, 10);
        return iPHP_CORE . '/captcha/ttf/t' . $fontNumber . '.ttf';
    }

    /**
     * 使用TTF字体绘制验证码文字
     */
    private static function drawTtfText()
    {
        $x = 3;
        $y = round(self::$height * 0.8);
        $ttfPath = self::getTtfPath($fontNumber);
        $fontSize = self::$fontSize;
        
        // 特殊字体大小调整
        if ($fontNumber == 9) {
            $fontSize = self::$fontSize * 0.8;
        }
        
        $spacing = self::$width / self::$fontNum;
        
        for ($i = 0; $i < self::$fontNum; $i++) {
            // 随机字体
            if (self::$fontRand) {
                $ttfPath = self::getTtfPath();
            }
            
            // 随机角度
            $angle = self::$fontAngle ? mt_rand(-self::$fontAngle, self::$fontAngle) : 0;
            $char = self::$code[$i];
            
            // 获取字符颜色
            list($color, $R, $G, $B) = self::getRandomColor(true);
            
            // 绘制阴影
            if (self::$fontShadow) {
                $shadowR = min(abs($R + self::$fontShadow), 255);
                $shadowG = min(abs($G + self::$fontShadow), 255);
                $shadowB = min(abs($B + self::$fontShadow), 255);
                $shadowColor = imagecolorallocate(self::$image, $shadowR, $shadowG, $shadowB);
                imagettftext(self::$image, $fontSize, $angle, $x + 1, $y + 1, $shadowColor, $ttfPath, $char);
            }
            
            // 绘制主要文字
            imagettftext(self::$image, $fontSize, $angle, $x, $y, $color, $ttfPath, $char);
            self::$curveColor = $color;
            
            // 计算下一个字符位置
            if (self::$spacing) {
                $bbox = imagettfbbox($fontSize, $angle, $ttfPath, $char);
                $charWidth = abs($bbox[4] - $bbox[0]);
                $x += $charWidth + self::$spacing;
            } else {
                $x += mt_rand(round($spacing * 0.85), round($spacing));
            }
        }
    }

    /**
     * 绘制正弦曲线干扰线
     * 使用正弦函数 y=A*sin(ωx+φ)+b 绘制两段连接的曲线
     * 
     * @param A 振幅 - 决定峰值（纵向拉伸压缩倍数）
     * @param b Y轴偏移 - 波形在Y轴的位置关系（上加下减）
     * @param φ 相位 - 波形与X轴位置关系（左加右减）
     * @param ω 角频率 - 决定周期（T=2π/|ω|）
     */
    protected static function drawCurve()
    {
        $color = self::$curveColor;
        $halfHeight = self::$height / 2;
        $quarterHeight = self::$height / 4;
        
        // 第一段曲线参数
        $amplitude1 = mt_rand(1, $halfHeight);                    // 振幅
        $yOffset1 = mt_rand(-$quarterHeight, $quarterHeight);     // Y轴偏移
        $phase1 = mt_rand(-$quarterHeight, $quarterHeight);       // 相位
        $period1 = mt_rand(self::$height * 1.5, self::$width * 2); // 周期
        $omega1 = (2 * M_PI) / $period1;                         // 角频率
        
        $startX = 0;
        $endX = mt_rand(self::$width / 2, self::$width * 0.667);
        
        // 绘制第一段曲线
        for ($x = $startX; $x <= $endX; $x += 0.9) {
            if ($omega1 != 0) {
                $y = $amplitude1 * sin($omega1 * $x + $phase1) + $yOffset1 + $halfHeight;
                $pixelSize = max(1, (int)((self::$fontSize - 6) / 4));
                
                for ($i = 0; $i < $pixelSize; $i++) {
                    imagesetpixel(self::$image, $x + $i, $y + $i, $color);
                }
            }
        }

        // 第二段曲线参数
        $amplitude2 = mt_rand(1, $halfHeight);
        $phase2 = mt_rand(-$quarterHeight, $quarterHeight);
        $period2 = mt_rand(self::$height * 1.5, self::$width * 2);
        $omega2 = (2 * M_PI) / $period2;
        
        // 计算连接点的Y偏移，使两段曲线平滑连接
        $yOffset2 = $y - $amplitude2 * sin($omega2 * $x + $phase2) - $halfHeight;
        
        $startX2 = $endX;
        $endX2 = self::$width;
        
        // 绘制第二段曲线
        for ($x = $startX2; $x <= $endX2; $x += 0.9) {
            if ($omega2 != 0) {
                $y = $amplitude2 * sin($omega2 * $x + $phase2) + $yOffset2 + $halfHeight;
                $pixelSize = max(1, (int)((self::$fontSize - 8) / 4));
                
                for ($i = 0; $i < $pixelSize; $i++) {
                    imagesetpixel(self::$image, $x + $i, $y + $i, $color);
                }
            }
        }
    }

    /**
     * 使用GIF字体绘制验证码文字
     */
    private static function drawGifText()
    {
        $gifDirs = [];
        
        // 查找可用的GIF字体目录
        if (function_exists('imagecreatefromgif')) {
            $gifRoot = dirname(strtr(__FILE__, '\\', '/')) . '/captcha/';
            if (is_dir($gifRoot)) {
                $handle = opendir($gifRoot);
                while (($dir = readdir($handle)) !== false) {
                    if ($dir !== '.' && $dir !== '..' && file_exists($gifRoot . $dir . '/9.gif')) {
                        $gifDirs[] = $dir;
                    }
                }
                closedir($handle);
            }
        }

        // 计算字符宽度
        $fonts = [];
        $totalWidth = 0;
        
        for ($i = 0; $i < self::$fontNum; $i++) {
            $gifPath = '';
            if ($gifDirs) {
                $randomDir = $gifDirs[array_rand($gifDirs)];
                $gifPath = $gifRoot . $randomDir . '/' . strtolower(self::$code[$i]) . '.gif';
            }
            
            if ($gifPath && file_exists($gifPath)) {
                $fonts[$i]['file'] = $gifPath;
                $fonts[$i]['data'] = getimagesize($gifPath);
                $fonts[$i]['width'] = $fonts[$i]['data'][0] + mt_rand(-4, 2);
                $fonts[$i]['height'] = $fonts[$i]['data'][1] + mt_rand(-4, 2);
                $fonts[$i]['width'] += mt_rand(0, max(0, self::$width / 5 - $fonts[$i]['width']));
            } else {
                $fonts[$i]['file'] = '';
                $fonts[$i]['width'] = 8 + mt_rand(0, max(0, self::$width / 5 - 5));
            }
            $totalWidth += $fonts[$i]['width'];
        }
        
        // 计算起始位置
        $x = mt_rand(1, max(1, self::$width - $totalWidth));
        
        // 绘制字符
        for ($i = 0; $i < self::$fontNum; $i++) {
            if (!empty($fonts[$i]['file'])) {
                // 使用GIF字体文件
                $charImage = imagecreatefromgif($fonts[$i]['file']);
                $y = mt_rand(0, max(0, self::$height - $fonts[$i]['height']));
                
                // 绘制阴影
                if (self::$fontShadow) {
                    $shadowImage = $charImage;
                    imagecolorset($shadowImage, 0, 
                        255 - self::$textColor[0], 
                        255 - self::$textColor[1], 
                        255 - self::$textColor[2]
                    );
                    imagecopyresized(self::$image, $shadowImage, 
                        $x + 1, $y + 1, 0, 0, 
                        $fonts[$i]['width'], $fonts[$i]['height'], 
                        $fonts[$i]['data'][0], $fonts[$i]['data'][1]
                    );
                }
                
                // 绘制主要字符
                imagecolorset($charImage, 0, 
                    self::$textColor[0], 
                    self::$textColor[1], 
                    self::$textColor[2]
                );
                imagecopyresized(self::$image, $charImage, 
                    $x, $y, 0, 0, 
                    $fonts[$i]['width'], $fonts[$i]['height'], 
                    $fonts[$i]['data'][0], $fonts[$i]['data'][1]
                );
            } else {
                // 使用内置字体
                $y = mt_rand(0, max(0, self::$height - 20));
                
                // 绘制阴影
                if (self::$fontShadow) {
                    $shadowColor = imagecolorallocate(self::$image, 
                        255 - self::$textColor[0], 
                        255 - self::$textColor[1], 
                        255 - self::$textColor[2]
                    );
                    imagechar(self::$image, 5, $x + 1, $y + 1, self::$code[$i], $shadowColor);
                }
                
                // 绘制主要字符
                $textColor = imagecolorallocate(self::$image, 
                    self::$textColor[0], 
                    self::$textColor[1], 
                    self::$textColor[2]
                );
                imagechar(self::$image, 5, $x, $y, self::$code[$i], $textColor);
            }
            
            $x += $fonts[$i]['width'];
        }
    }

    /**
     * 生成BMP格式的验证码图片
     * 当GD库不可用时的备用方案
     */
    private static function generateBmp()
    {
        $numbers = [
            'B' => ['00', 'fc', '66', '66', '66', '7c', '66', '66', 'fc', '00'],
            'C' => ['00', '38', '64', 'c0', 'c0', 'c0', 'c4', '64', '3c', '00'],
            'E' => ['00', 'fe', '62', '62', '68', '78', '6a', '62', 'fe', '00'],
            'F' => ['00', 'f8', '60', '60', '68', '78', '6a', '62', 'fe', '00'],
            'G' => ['00', '78', 'cc', 'cc', 'de', 'c0', 'c4', 'c4', '7c', '00'],
            'H' => ['00', 'e7', '66', '66', '66', '7e', '66', '66', 'e7', '00'],
            'J' => ['00', 'f8', 'cc', 'cc', 'cc', '0c', '0c', '0c', '7f', '00'],
            'K' => ['00', 'f3', '66', '66', '7c', '78', '6c', '66', 'f7', '00'],
            'M' => ['00', 'f7', '63', '6b', '6b', '77', '77', '77', 'e3', '00'],
            'P' => ['00', 'f8', '60', '60', '7c', '66', '66', '66', 'fc', '00'],
            'Q' => ['00', '78', 'cc', 'cc', 'cc', 'cc', 'cc', 'cc', '78', '00'],
            'R' => ['00', 'f3', '66', '6c', '7c', '66', '66', '66', 'fc', '00'],
            'T' => ['00', '78', '30', '30', '30', '30', 'b4', 'b4', 'fc', '00'],
            'V' => ['00', '1c', '1c', '36', '36', '36', '63', '63', 'f7', '00'],
            'W' => ['00', '36', '36', '36', '77', '7f', '6b', '63', 'f7', '00'],
            'X' => ['00', 'f7', '66', '3c', '18', '18', '3c', '66', 'ef', '00'],
            'Y' => ['00', '7e', '18', '18', '18', '3c', '24', '66', 'ef', '00'],
            'A' => ['00', 'c3', '81', '81', '81', '00', '81', '81', '81', '00'],
            'D' => ['00', '07', '83', '81', '81', '81', '81', '83', '07', '00'],
            'L' => ['00', '9f', '9f', '9f', '9f', '9f', '9f', '9f', '81', '00'],
            'N' => ['00', '81', '01', '03', '87', 'cf', 'e1', 'f0', '81', '00'],
            'U' => ['00', '81', '81', '81', '81', '81', '81', '81', 'c3', '00'],
            'Z' => ['00', '81', 'f9', 'f3', 'e7', 'cf', '9f', '81', '81', '00'],
            '2' => ['fc', 'c0', '60', '30', '18', '0c', 'cc', 'cc', '78', '00'],
            '3' => ['78', '8c', '0c', '0c', '38', '0c', '0c', '8c', '78', '00'],
            '4' => ['00', '3e', '0c', 'fe', '4c', '6c', '2c', '3c', '1c', '1c'],
            '5' => ['00', '81', '9f', '9f', '83', 'f1', 'f9', '81', 'c3', '00'],
            '6' => ['78', 'cc', 'cc', 'cc', 'ec', 'd8', 'c0', '60', '3c', '00'],
            '7' => ['30', '30', '38', '18', '18', '18', '1c', '8c', 'fc', '00'],
            '8' => ['78', 'cc', 'cc', 'cc', '78', 'cc', 'cc', 'cc', '78', '00'],
            '9' => ['f0', '18', '0c', '6c', 'dc', 'cc', 'cc', 'cc', '78', '00']
        ];
        
        // 为每个字符添加随机干扰数据
        foreach ($numbers as $i => $number) {
            for ($j = 0; $j < 6; $j++) {
                $a1 = substr('012', mt_rand(0, 2), 1) . substr('012345', mt_rand(0, 5), 1);
                $a2 = substr('012345', mt_rand(0, 5), 1) . substr('0123', mt_rand(0, 3), 1);
                mt_rand(0, 1) == 1 ? array_push($numbers[$i], $a1) : array_unshift($numbers[$i], $a1);
                mt_rand(0, 1) == 0 ? array_push($numbers[$i], $a1) : array_unshift($numbers[$i], $a2);
            }
        }
        
        // 构建位图数据
        $bitmap = [];
        for ($i = 0; $i < 20; $i++) {
            for ($j = 0; $j < self::$fontNum; $j++) {
                $char = substr(self::$code, $j, 1);
                $bytes = $numbers[$char][$i];
                array_push($bitmap, $bytes);
            }
        }
        
        // 添加边框干扰
        for ($i = 0; $i < 8; $i++) {
            $randomBytes = substr('012345', mt_rand(0, 2), 1) . substr('012345', mt_rand(0, 5), 1);
            array_unshift($bitmap, $randomBytes);
            array_push($bitmap, $randomBytes);
        }
        
        // 生成BMP图像数据
        $bmpData = pack('H*', '424d9e000000000000003e000000280000002000000018000000010001000000' .
            '0000600000000000000000000000000000000000000000000000FFFFFF00' . implode('', $bitmap));
        
        header('Content-Type: image/bmp');
        echo $bmpData;
    }
    
    /**
     * 将32位整数转换为小端字节序
     * @param int $n 整数值
     * @return string 4字节字符串
     */
    private static function dword($n)
    {
        return pack('V', $n);
    }
    
    /**
     * 将16位整数转换为小端字节序
     * @param int $n 整数值
     * @return string 2字节字符串
     */
    private static function word($n)
    {
        return pack('v', $n);
    }
}
