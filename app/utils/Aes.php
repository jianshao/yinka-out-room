<?php

namespace app\utils;

use app\common\RedisCommon;
use think\facade\Log;

class Aes
{
    private $key = "";
    private $iv = "";
    private $AesVersionCache = "aes_version_cache_hash";
    private $AesVersionEnableCache = "aes_version_enable_hash";

    public function __construct($key = null, $iv = null)
    {
        if (is_null($key)) {
            $this->key = config('config.EncryptKey');
        }
        if (is_null($iv)) {
            $this->iv = str_repeat("\0", 16);
        }
    }

    /**
     * @param $cacheKey
     * @return false|string
     */
    private function getVersionKey($cacheKey)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->hGet($this->AesVersionCache, $cacheKey);
    }

    /**
     * @param $cacheKey
     * @return false|string
     */
    private function getVersionIsEnable($cacheKey){
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->hGet($this->AesVersionEnableCache, $cacheKey);
    }


    /**
     * @param RequestHeaders $requestHeaders
     * @return bool
     */
    public function isEnableAes(RequestHeaders $requestHeaders){
        $platForm = $requestHeaders->getPlatFormOs();
        $source = $requestHeaders->source;
        $version = $requestHeaders->version;

        $cacheKey = sprintf("%s:%s:%s", $platForm, $source, $version);
//        string(21) "Android:source:3.2.11"
        $enable = $this->getVersionIsEnable($cacheKey);
        if ($enable==="off"){
            return false;
        }
        return true;
    }

    /**
     * @info 根据{os+source+version}获取不同的aeskey
     * @param RequestHeaders $requestHeaders
     * @return mixed|string
     */
    public function resetAesKey(RequestHeaders $requestHeaders)
    {
        $platForm = $requestHeaders->getPlatFormOs();
        $source = $requestHeaders->source;
        $version = $requestHeaders->version;

        $cacheKey = sprintf("%s:%s:%s", $platForm, $source, $version);
//        string(21) "Android:source:3.2.11"
        $aesKey = $this->getVersionKey($cacheKey);
        if ($aesKey) {
            $this->key = $aesKey;
        }
        Log::info(sprintf('Aes::resetAesKey aeskey=%s', $this->key));
        return $this->key;
    }

    /**
     * AES加密
     *
     * @param $plainText  String 明文
     * @param $key        String 对称密钥
     * @return string
     * @throws \Exception
     */
    public function aesEncrypt($plainText)
    {
        try {
            $key=$this->disruptedDecode($this->key);
            if (strlen($key) == 0) {
                throw new \Exception("AES加密失败，plainText=" . $plainText . "，AES密钥为空。");
            }
            //AES, 128 模式加密数据 CBC
            $screct_key = base64_decode($key);
//            $str = trim($plainText);
//            $str = $this->addPKCS7Padding($str);
            $iv = $this->iv;
            $encrypt_str = openssl_encrypt($plainText, 'aes-128-cbc', $screct_key, 0, $iv);
            return $encrypt_str;
        } catch (\Exception $e) {
            throw new \Exception("AES加密失败，plainText=" . $plainText . "，keySize=" . strlen($key) . "。" . $e->getMessage());
        }
    }

    /**
     * AES加密
     *
     * @param $plainText  String 明文
     * @param $key        String 对称密钥
     * @return string
     * @throws \Exception
     */
    public function aesEncryptOrigin($plainText, $key, $cipher_algo = "aes-128-cbc", $iv = "")
    {
        try {
            if (strlen($key) == 0) {
                throw new \Exception("AES加密失败，plainText=" . $plainText . "，AES密钥为空。");
            }
            //AES, 128 模式加密数据 CBC
            $screct_key = base64_decode($key);
            $encrypt_str = openssl_encrypt($plainText, $cipher_algo, $screct_key, 0, $iv);
            return $encrypt_str;
        } catch (\Exception $e) {
            throw new \Exception("AES加密失败，plainText=" . $plainText . "，keySize=" . strlen($key) . "。" . $e->getMessage());
        }
    }

    /**
     * @param $screct_key
     * @return string
     */
    public function disruptedDecode($screct_key)
    {
        $screct_key = $this->disForPosDecode($screct_key, 2);
        $screct_key = $this->disForPosDecode($screct_key, 4);
        $screct_key = $this->disForPosDecode($screct_key, 7);
        return $screct_key;
    }

    /**
     * @param $screct_key
     * @param $pos
     * @return string
     */
    private function disForPosDecode($screct_key,$pos){
        if (empty($screct_key)) {
            return "";
        }
        $offset = (int)$pos * 2;
        if (strlen($screct_key) < $offset) {
            return $screct_key;
        }
        $firstStr = substr($screct_key, 0, $pos);
        $secondStr = substr($screct_key, $pos, $pos);
        $offset = $pos * 2;
        $moreStr = substr($screct_key, $offset);
        return sprintf("%s%s%s", $secondStr, $firstStr, $moreStr);
    }

    /**
     * @param $screct_key
     * @return string
     */
    public function disruptedEncode($screct_key)
    {
        $screct_key = $this->disForPos($screct_key, 7);
        $screct_key = $this->disForPos($screct_key, 4);
        $screct_key = $this->disForPos($screct_key, 2);
        return $screct_key;
    }

    private function disForPos($screct_key, $pos)
    {
        if (empty($screct_key)) {
            return "";
        }
        $offset = (int)$pos * 2;
        if (strlen($screct_key) < $offset) {
            return $screct_key;
        }
        $firstStr = substr($screct_key, 0, $pos);
        $secondStr = substr($screct_key, $pos, $pos);
        $moreStr = substr($screct_key, $offset);
        return sprintf("%s%s%s", $secondStr, $firstStr, $moreStr);
    }

    /**
     * AES解密
     *
     * @param $cipherText String 密文
     * @param $key        String 对称密钥
     * @return false|string
     * @throws \Exception
     */
    public function aesDecrypt($cipherText)
    {
        try {
            $key=$this->disruptedDecode($this->key);
            if (strlen($key) == 0) {
                throw new \Exception("AES加密失败，plainText=" . $cipherText . "，AES密钥为空。");
            }
            //AES, 128 模式加密数据 CBC
            $str = $cipherText;
            $screct_key = base64_decode($key);
            $iv = $this->iv;
            $decrypt_str = openssl_decrypt($str, 'aes-128-cbc', $screct_key, 0, $iv);
//            $decrypt_str = $this->stripPKSC7Padding($decrypt_str);
            return $decrypt_str;
        } catch (\Exception $e) {
            throw new \Exception("AES解密失败，cipherText=" . $cipherText . "，keySize=" . strlen($key) . "。" . $e->getMessage());
        }
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    private function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = 16;

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    private function stripPKSC7Padding($source)
    {
        $char = substr($source, -1);
        $num = ord($char);
        if ($num == 62) return $source;
        $source = substr($source, 0, -$num);
        return $source;
    }

}