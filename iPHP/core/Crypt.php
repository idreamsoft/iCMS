<?php

/**
 * Crypt class
 *
 * 加解密
 */
class Crypt
{
    private static $key;
    private static $block_size = 32;
    
    private static $OK = 0;
    private static $ValidateSignatureError = -40001;
    private static $ParseXmlError = -40002;
    private static $ComputeSignatureError = -40003;
    private static $IllegalAesKey = -40004;
    private static $ValidateAppidError = -40005;
    private static $EncryptAESError = -40006;
    private static $DecryptAESError = -40007;
    private static $IllegalBuffer = -40008;
    private static $EncodeBase64Error = -40009;
    private static $DecodeBase64Error = -40010;
    private static $GenReturnXmlError = -40011;

    /**
     * 对需要加密的明文进行填充补位
     * @param $text 需要进行填充补位操作的明文
     * @return string 补齐明文字符串
     */
    private static function encode($text)
    {
        $block_size = self::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = self::$block_size - ($text_length % self::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = self::$block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = '';
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return string 删除填充补位后的明文
     */
    private static function decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    public static function init($key = '')
    {
        self::$key = md5(md5(md5($key)));
    }

    /**
     * 对明文进行加密
     * @param $text 需要加密的明文
     * @return string 加密后的密文
     */
    public static function encrypt($text, $expiry = -1)
    {
        try {
            $expire_time = $expiry < 0 ? $expiry : time() + $expiry;
            $payload = json_encode([
                'e' => $expire_time,
                'c' => $text
            ]);

            //获得16位随机字符串，填充到明文之前
            $random = self::getRandomStr();
            $text = $random . pack('N', strlen($payload)) . $payload;
            //使用自定义的填充方式对明文进行补位填充
            $text = self::encode($text);
            $iv = substr(self::$key, 0, 16);
            $encrypted = openssl_encrypt($text, 'AES-256-CBC', substr(self::$key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
            //使用BASE64对加密后的字符串进行编码
            return base64_encode($encrypted);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * 对密文进行解密
     * @param string $encrypted 需要解密的密文
     * @return string 解密得到的明文
     */
    public static function decrypt($encrypted)
    {
        if (is_null($encrypted)) {
            return null;
        }

        try {
            //使用BASE64对需要解密的字符串进行解码
            $iv = substr(self::$key, 0, 16);
            $encrypted = base64_decode($encrypted);
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', substr(self::$key, 0, 32), OPENSSL_ZERO_PADDING, $iv);
        } catch (Exception $ex) {
            throw $ex;
        }

        try {
            //去除补位字符
            $result = self::decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16) {
                return null;
            }

            $content = substr($result, 16, strlen($result));
            $len_list = unpack('N', substr($content, 0, 4));
            $de_len = $len_list[1];
            $payload = substr($content, 4, $de_len);
            $payload = json_decode($payload, true);
            if (!$payload) {
                return null;
            }
            $expire_time = $payload['e'];
            if ($expire_time > 0 && time() > $expire_time) {
                return null;
            }
        } catch (Exception $ex) {
            throw $ex;
        }
        return $payload['c'];
        // $array = json_decode($payload['c'],true);
        // return is_array($array)?$array:$payload['c'];
    }

    /**
     * 随机生成16位字符串
     * @return string 生成的字符串
     */
    private static function getRandomStr()
    {
        $str = '';
        $str_pol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}
