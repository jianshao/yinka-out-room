<?php

namespace app\utils;

use app\api\view\v1\AppDataView;
use app\domain\exceptions\FQException;
use app\domain\reddot\RedDotItem;
use app\domain\reddot\RedDotModel;
use app\service\GlobalNotifyService;
use think\facade\Log;

class CommonUtil
{
    /**
     * @param $url
     * @return string
     */
    public static function buildImageUrl($url)
    {
        // TODO
        if (empty($url)
            || StringUtil::startsWith($url, 'http://')
            || StringUtil::startsWith($url, 'https://')) {
            return $url;
        }

        $imageUrlPrefix = config('config.APP_URL_image');
        if (StringUtil::endsWith($imageUrlPrefix, '/')) {
            if (StringUtil::startsWith($url, '/')) {
                return $imageUrlPrefix . substr($url, 1);
            }
        } else {
            if (!StringUtil::startsWith($url, '/')) {
                return $imageUrlPrefix . '/' . $url;
            }
        }
        return $imageUrlPrefix . $url;
    }

    public static function validatePassword($password)
    {
        $m = preg_match('/(?!^[0-9]+$)(?!^[A-z]+$)(?!^[^A-z0-9]+$)^.{8,16}$/', $password);
        if (!$m) {
            Log::debug(sprintf('validatePassword: [%s]', $password));
            throw new FQException('密码应为数字字符组合,8-16位', 500);
        }
    }

    public static function validateMobile($mobile)
    {
        $exp = "/^1\d{10}$/";
        if (!preg_match($exp, $mobile)) {
            throw new FQException('手机号格式不正确', 2002);
        }
    }

    public static function validateMobileSecond($mobile)
    {
        $exp = "/^1[3-9]\d{9}$/";
        if (!preg_match($exp, $mobile)) {
            throw new FQException('手机号格式不正确', 2002);
        }
    }

    public static function validateIdCard($idCard)
    {
        $pregCard = '/^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}(\d|x|X)$/';
        if (!preg_match($pregCard, $idCard)) {
            throw new FQException('身份证格式不正确', 500);
        }
    }

    public static function filterMobile($mobile)
    {
        if (!empty($mobile)) {
            return substr_replace($mobile, '****', 3, 4);
        }
        return $mobile;
    }

    /**
     * @info 过滤卡号和邮箱 做*模糊
     * @param $account
     * @return string|string[]
     * 支付宝：手机号模糊第四位到第7位；邮箱模糊第四位到倒数第二位，如果不足七位的，从第四位模糊，到@截止，显示@。
     * 银行卡：模糊第四位到倒数四位
     */
    public static function filterCardAndMail($account)
    {
        if (empty($account)) {
            return "";
        }
        $replaceRe = self::filterMail($account);
        if (!empty($replaceRe)) {
            return $replaceRe;
        }
        return self::filterCardLite($account);
    }

    /**
     * @param $account
     * @return string|string[]
     */
    private static function filterCardLite($account)
    {
        if (empty($account)) {
            return "";
        }
        $len = strlen($account);
        if ($len < 7) {
            $m = floor($len / 2);
            return substr_replace($account, '****', $m, $m);
        }
        if ($len <= 11) {
            return substr_replace($account, '****', 3, 4);
        }
        return substr_replace($account, '****', 4, -4);
    }

    /**
     * @param $account
     * @return string
     */
    public static function filterMail($account)
    {
        if (empty($account)) {
            return "";
        }
        $isMall = strrpos($account, "@");
        if ($isMall === false) {
            return "";
        }
        $mallData = explode("@", $account);
        $mAccount = $mallData[0] ?? "";
        $suffix = $mallData[1] ?? "";
        $len = strlen($mAccount);
        if ($len < 7) {
            $m = floor($len / 2);
            $mAccount = substr_replace($mAccount, '****', $m, $m);
        }
        if ($len <= 11) {
            $mAccount = substr_replace($mAccount, '****', 3, 2);
        }
        $mAccount = substr_replace($mAccount, '****', 3, -2);
        return sprintf("%s@%s", $mAccount, $suffix);
    }

    public static function isPrettyNumber($number)
    {
        //-----AAA类型判断
        // if(preg_match('#([\d])\1{2}$#', $num)){
        //     return true;
        // }
        //-----AAAA类型判断
        if (preg_match('#([\d])\1{3}$#', $number)) {
            return true;
        }
        //-----ABC类型判断
        // if(preg_match('#(123|234|345|456|567|678|789|012)$#', $num)){
        //     return true;
        // }
        //-----ABCD类型判断
        if (preg_match('#(1234|2345|3456|4567|5678|6789|0123)$#', $number)) {
            return true;
        }

        //-----AAAB类型判断
        // if(preg_match('#(\d)\1\1((?!\1)\d)$#', $num)){
        //     return true;
        // }
        //-----ABAB类型判断
        if (preg_match('#(\d)(\d)\1((?!\1)\2)$#', $number)) {
            return true;
        }
        //-----AABB类型判断
        if (preg_match('#(\d)\1(\d)((?!\1)\2)$#', $number)) {
            return true;
        }

        //-----AAABBB类型判断
        if (preg_match('#(\d)\1{2}(?!\1)(\d)\2{2}$#', $number)) {
            return true;
        }

        //-----ABCDABCD类型判断
        if (preg_match('#([\d]{4})\1$#', $number)) {
            return true;
        }
        return false;
    }

    public static function checkImgIsGif($img)
    {
        $content = file_get_contents($img);
        return preg_match("/" . chr(0x21) . chr(0xff) . chr(0x0b) . 'NETSCAPE2.0' . "/", $content);
    }


    /**
     * @param $imgSrc
     * @throws FQException
     */
    public static function checkImgIsGifLite($imgSrc)
    {
        if (empty($imgSrc)) {
            return;
        }
        $imgArr = pathinfo($imgSrc);
        if (isset($imgArr['extension']) && $imgArr['extension'] == 'gif') {
            throw new FQException('图片不能为gif', 200);
        }
    }

    /**
     * 生成唯一的订单号
     */
    public static function createOrderNo($uid)
    {
        // 生成订单号.
        return $uid . time() . rand(10000, 99999);
    }

    //根据经纬度获取location
    public static function getLocation($longitudes, $latitudes)
    {
        $latitude = $longitudes . ',' . $latitudes;
        $place_url = 'https://restapi.amap.com/v3/geocode/regeo?output=json&location=' . $latitude . '&key=62a862a4d8d6bf56e6e4bd5a13e73ddd&radius=1000&extensions=all';
        $json_place = file_get_contents($place_url);
        $place_arr = json_decode($json_place, true);
        $location = $place_arr['regeocode']['addressComponent']['province'] ? $place_arr['regeocode']['addressComponent']['province'] : '';
        return $location;
    }

    public static function buildUrl($url, $params): string
    {
        $paramStr = http_build_query($params);
        return $url . '?' . $paramStr;
    }


    public static function createUuID($prefix = "")
    {
        $chars = md5(uniqid(mt_rand(), true));
        $uuid = substr($chars, 0, 8) . '-'
            . substr($chars, 8, 4) . '-'
            . substr($chars, 12, 4) . '-'
            . substr($chars, 16, 4) . '-'
            . substr($chars, 20, 12);
        return $prefix . ':' . $uuid;
    }

    /**
     * @param string $prefix
     * @return string
     */
    public static function createUuIDShort($prefix = "")
    {
        $chars = md5(uniqid(mt_rand(), true));
        return md5(sprintf("%s%s%s%s%s%s", $prefix, substr($chars, 0, 8), substr($chars, 8, 4), substr($chars, 12, 4), substr($chars, 16, 4), substr($chars, 20, 12)));
    }

    /**
     * @info 设置指定小红点并推送
     * @param $userId
     * @param $hotType
     * @param $number
     * @throws FQException
     */
    public static function redHeadSet($userId, $hotType, $number)
    {
        if (empty($userId) || empty($hotType)) {
            throw new FQException("param error", 409);
        }
        $hotType = (new RedDotModel($userId))->checkRedTypes($hotType);
        if (empty($hotType)) {
            throw new FQException("redType errors");
        }
        try {
            $reddotItem = new RedDotItem($userId, $hotType);
            $reddotItem->hset($number, "count");
            $mode = $reddotItem->getItem();
            $redDotData = AppDataView::viewReddot($mode);
            $data = [
                $hotType => $redDotData
            ];
            $re = GlobalNotifyService::getInstance()->redDotNotifyForUser($userId, $data);
            Log::info(sprintf('commondUtil::redHeadSet success replaymsg=%s userId:%d data:%s', $re, $userId, json_encode($data)));
        } catch (FQException $e) {
            Log::warning(sprintf('commondUtil::redHeadSet error userId=%d ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
        }
    }


    /**
     * @info 增加指定小红点并推送
     * @param $userId
     * @param $hotType
     * @param $number
     * @throws FQException
     */
    public static function redHeadIncr($userId, $hotType, $number)
    {
        if (empty($userId) || empty($hotType)) {
            throw new FQException("param error", 409);
        }
        $hotType = (new RedDotModel($userId))->checkRedTypes($hotType);
        if (empty($hotType)) {
            throw new FQException("redType errors");
        }
        try {
            $reddotItem = new RedDotItem($userId, $hotType);
            $reddotItem->incr($number, "count");
            $mode = $reddotItem->getItem();
            $redDotData = AppDataView::viewReddot($mode);
            $data = [
                $hotType => $redDotData
            ];
            $re = GlobalNotifyService::getInstance()->redDotNotifyForUser($userId, $data);
            Log::info(sprintf('commondUtil::redHeadIncr success replaymsg=%s userId:%d data:%s', $re, $userId, json_encode($data)));
        } catch (FQException $e) {
            Log::warning(sprintf('commondUtil::redHeadIncr error userId=%d ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
        }
    }

    /**
     * @info 增加指定小红点并推送
     * @param $userId
     * @param $hotType
     * @param $number
     * @throws FQException
     */
    public static function redHeadDecr($userId, $hotType, $number)
    {
        if (empty($userId) || empty($hotType)) {
            throw new FQException("param error", 409);
        }
        $hotType = (new RedDotModel($userId))->checkRedTypes($hotType);
        if (empty($hotType)) {
            throw new FQException("redType errors");
        }
        try {
            $reddotItem = new RedDotItem($userId, $hotType);
            $reddotItem->decr($number, "count");
            $mode = $reddotItem->getItem();
            $redDotData = AppDataView::viewReddot($mode);
            $data = [
                $hotType => $redDotData
            ];
            $re = GlobalNotifyService::getInstance()->redDotNotifyForUser($userId, $data);
            Log::info(sprintf('commondUtil::redHeadDecr success replaymsg=%s userId:%d data:%s', $re, $userId, json_encode($data)));
        } catch (FQException $e) {
            Log::warning(sprintf('commondUtil::redHeadDecr error userId=%d ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
        }
    }

    public static function getAge($id)
    {
        if (empty($id)) {
            return 0;
        }
        $id = trim($id);
        # 1.从身份证中获取出生日期
        $birth_Date = strtotime(substr($id, 6, 8));//截取日期并转为时间戳

        # 2.格式化[出生日期]
        $Year = date('Y', $birth_Date);//yyyy
        $Month = date('m', $birth_Date);//mm
        $Day = date('d', $birth_Date);//dd

        # 3.格式化[当前日期]
        $current_Y = date('Y');//yyyy
        $current_M = date('m');//mm
        $current_D = date('d');//dd

        # 4.计算年龄()
        $age = $current_Y - $Year;//今年减去生日年
        if ($Month > $current_M || $Month == $current_M && $Day > $current_D) {//深层判断(日)
            $age--;//如果出生月大于当前月或出生月等于当前月但出生日大于当前日则减一岁
        }
        # 返回
        return $age;
    }

    /**
     * 是否为appdev
     * @return bool
     */
    public static function getAppDev()
    {
        return config("config.appDev") === "dev";
    }

    /**
     * @info get microtime
     * @return int
     */
    public static function getCurrentMilis()
    {
        $mill_time = microtime();
        $timeInfo = explode(' ', $mill_time);
        $milis_time = sprintf('%d%03d', $timeInfo[1], $timeInfo[0] * 1000);
        return (int)$milis_time;
    }

    public static function time2string($second)
    {
        $day = floor($second / (3600 * 24));
        $second = $second % (3600 * 24);//除去整天之后剩余的时间
        $hour = floor($second / 3600);
        $second = $second % 3600;//除去整⼩时之后剩余的时间
        $minute = floor($second / 60);
        //返回字符串
        $reStr = '';
        if ($day) {
            $reStr .= $day . '天';
        }
        if ($hour) {
            $reStr .= $hour . '⼩时';
        }
        if ($minute) {
            $reStr .= $minute . '分钟';
        }
        return $reStr;
    }


    /**
     * 生成13位时间戳
     * @return float
     */
    public static function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

}