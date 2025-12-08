<?php

class PluginMakePic
{
    public static $cache = false;

    public static function card($pic, $title, $content, $url)
    {
    }
    public static function make($text, $size = 26)
    {
        //配置部分
        $basedir = __DIR__ . '/makepic';

        //随机字体
        $font_array = glob($basedir . '/font/*.ttf');
        $fontKey = array_rand($font_array);
        $font = $font_array[$fontKey];

        //随机图片
        $img_array = glob($basedir . '/bg/*.{gif,jpg,png}', GLOB_BRACE);
        $img = array_rand($img_array);
        $bg_src = $img_array[$img];

        $dst_path = $bg_src;
        $smdst_path = $basedir . '/sm.png';
        $mubu_height = '160';

        $text = trim($text);
        self::isUtf8($text) or $text = iconv("GBk", "UTF-8", $text);

        $text_length = mb_strlen($text, 'UTF-8');

        $textarr = self::utf8_str_split($text, 13); //切割文字

        if ($text_length >= 12) {
            $mubu_height = '215'; //拉升文字幕布高度
            //print_r($textarr); die;
        }

        if ($text_length >= 24) {
            $textarr = array_slice($textarr, 0, 2);
            $textarr[2] = '...';
            //print_r($textarr); die;
        }

        //创建图片的实例
        $dst = imagecreatefromstring(file_get_contents($dst_path));
        $smdst = imagecreatefromstring(file_get_contents($smdst_path));

        //$slimg = imagecreatetruecolor(100,20);//建立一个画板，尺寸可以自行修改
        //$bg = imagecolorallocatealpha($slimg,0,0,0,127);     
        $color = imagecolorallocate($dst, 255, 255, 255); //字体拾色   
        //imagealphablending($slimg, false);//关闭混合模式，以便透明颜色能覆盖原画板
        //imagefill($slimg,0,0,$bg);//填充

        $mubu_colorid = mt_rand(1, 3);
        //$mubu_color = ImageColorAllocate($dst, 79,11,12);

        if ($mubu_colorid == 1) {
            $mubu_color = ImageColorAllocate($dst, 255, 0, 0);
        }
        if ($mubu_colorid == 2) {
            $mubu_color = ImageColorAllocate($dst, 0, 0, 0);
        }
        if ($mubu_colorid == 3) {
            $mubu_color = ImageColorAllocate($dst, 79, 11, 12);
        }

        //ImageRectangle($dst, 50, 20, 200, 15, $white); //矩形框子
        //ImageFilledRectangle($dst, 0, 0, 100, 200, $white); //矩形填充背景
        self::arcRec($dst, 30, 76, 520, $mubu_height, 5, $mubu_color); //文字幕布

        //人物图片定位
        imagecopyresized($dst, $smdst, 500, 60, 0, 0, 149, 226, imagesx($smdst), imagesy($smdst));

        // 文字显示部分
        //imagefttext($dst,$size,0,50,132,$color,$font,$textarr[0]);
        //imagefttext($dst,$size,0,50,177,$color,$font,$textarr[1]);

        $text_margintop = 80;
        $text_i = 0;
        foreach ($textarr as $title) {
            $text_i++;
            if ($text_i <= 2) {
                $step_num = 50;
            } else {
                $step_num = 20;
            }

            $text_margintop = $text_margintop + $step_num;
            //$title = preg_replace("/(\\w)/i", " $1 ", $title);
            imagefttext($dst, $size, 0, 50, $text_margintop, $color, $font, $title);
        }

        imagesavealpha($dst, true);
        if(self::$cache){
            $name = md5($text);
            $dir1 = substr($name, 0, 3);
            $dir2 = substr($name, 3, 2);
            $path = sprintf('makepic/%s/%s/%s.png',$dir1,$dir2,$name);
            $rootpath = FilesClient::getRoot($path);
            if(!is_file($rootpath)){
                File::mkdir(dirname($rootpath));
                imagepng($dst,$rootpath);
                imagedestroy($dst);
            }
            $url = FilesClient::getUrl($path);
            return $url;
        }
        ob_start();
        imagepng($dst);
        imagedestroy($dst);
        $image = ob_get_contents();
        ob_clean();
        return 'data:image/png;base64,' . base64_encode($image);
    }
    public static function utf8_str_split($str, $split_len = 1)
    {
        if (!preg_match('/^[0-9]+$/', $split_len) || $split_len < 1)
            return FALSE;

        $len = mb_strlen($str, 'UTF-8');
        //var_dump($len); var_dump($str); die;
        //$len =(strlen($str) + mb_strlen($str,'UTF-8')) / 2;

        if ($len <= $split_len)
            return array($str);

        preg_match_all('/.{' . $split_len . '}|[^\x00]{1,' . $split_len . '}$/us', $str, $ar);

        return $ar[0];
    }
    public static function isUtf8($string) //函数二
    {
        $encode = mb_detect_encoding($string, array("ASCII", "GB2312", "GBK", "CP936", "UTF-8"));
        if ($encode == "UTF-8" || $encode == "CP936") {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 圆角矩形
     * @param $imageObj
     * @param $arcRec_SX 开始点X坐标
     * @param $arcRec_SY 开始点Y坐标
     * @param $arcRec_EX 结束点X坐标
     * @param $arcRec_EY 结束点Y坐标
     * @param $redius 圆角半径
     * @param $color 颜色
     */
    public static function arcRec($imageObj, $arcRec_SX, $arcRec_SY, $arcRec_EX, $arcRec_EY, $redius, $color)
    {
        $arcRec_W = $arcRec_EX - $arcRec_SX;
        $arcRec_H = $arcRec_EY - $arcRec_SY;
        imagefilledrectangle($imageObj, $arcRec_SX + $redius, $arcRec_SY, $arcRec_SX + ($arcRec_W - $redius), $arcRec_SY + $redius, $color);        //矩形一
        imagefilledrectangle($imageObj, $arcRec_SX, $arcRec_SY + $redius, $arcRec_SX + $arcRec_W, $arcRec_SY + ($arcRec_H - ($redius * 1)), $color); //矩形二
        imagefilledrectangle($imageObj, $arcRec_SX + $redius, $arcRec_SY + ($arcRec_H - ($redius * 1)), $arcRec_SX + ($arcRec_W - ($redius * 1)), $arcRec_SY + $arcRec_H, $color); //矩形三
        imagefilledarc($imageObj, $arcRec_SX + $redius, $arcRec_SY + $redius, $redius * 2, $redius * 2, 180, 270, $color, IMG_ARC_PIE);   //四分之一圆 - 左上
        imagefilledarc($imageObj, $arcRec_SX + ($arcRec_W - $redius), $arcRec_SY + $redius, $redius * 2, $redius * 2, 270, 360, $color, IMG_ARC_PIE);   //四分之一圆 - 右上
        imagefilledarc($imageObj, $arcRec_SX + $redius, $arcRec_SY + ($arcRec_H - $redius), $redius * 2, $redius * 2, 90, 180, $color, IMG_ARC_PIE);   //四分之一圆 - 左下
        imagefilledarc($imageObj, $arcRec_SX + ($arcRec_W - $redius), $arcRec_SY + ($arcRec_H - $redius), $redius * 2, $redius * 2, 0, 90, $color, IMG_ARC_PIE);   //四分之一圆 - 右下
    }
}
