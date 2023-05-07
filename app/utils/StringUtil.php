<?php

namespace app\utils;

class StringUtil
{
    /**
     * @param $s
     * @param $prefix
     * @return bool
     */
    public static function startsWith($s, $prefix) {
        $sLen = $s === null ? 0 : strlen($s);
        $prefixLen = $prefix == null ? 0 : strlen($prefix);

        if ($prefixLen === 0 || $sLen < $prefixLen) {
            return false;
        }
        return $s[0] === $prefix[0]  ? strncmp($s, $prefix, $prefixLen) === 0 : false;
    }

    /**
     * @param $s
     * @param $suffix
     * @return false
     */
    public static function endsWith($s, $suffix) {
        $sLen = $s === null ? 0 : strlen($s);
        $suffixLen = $suffix == null ? 0 : strlen($suffix);
        $pos = $sLen - $suffixLen;
        return $pos >= 0 && strpos($s, $suffix, $pos) !== false;
    }

    public static function ReplaceUrl($url, $newUrl = 'image.muayuyin.com') {
        if (config('config.appDev') === "dev") {
            return $url;
        }
        $reg = '/((http)|(https)):\/\/([^\/]+)/i';
        preg_match($reg, $url,$res);
        $oldUrl = '';
        if (!empty($res) && isset($res[4])) {
            $oldUrl = $res[4];
        }
        if ($oldUrl == 'activity.muayuyin.com') {
            return $url;
        }
        if ($oldUrl != 'newmapi2.fqparty.com') {
            return preg_replace($reg, 'https://'.'image.muayuyin.com', $url);
        } else {
            return preg_replace($reg, 'https://'.$newUrl, $url);
        }

    }
}
