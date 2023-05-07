<?php


namespace app\utils;


class AesUtil
{
    private $hex_iv = '878zxc321smn7d9s0wkjuytrvbnd6gaq';
    private $key = '2j8s2jjugyuhgdnfijmwy36b7n8kmf8p';

    function __construct()
    {
        $key = $this->key;
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt($input)
    {
        $data = openssl_encrypt($input, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
        $data = base64_encode($data);
        return $data;
    }

    public function decrypt($input)
    {
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
        parse_str($decrypted,$decrypted);
        return $decrypted;
    }

    function hexToStr($hex)
    {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        }
        return $string;
    }


}