<?php

use OSS\Core\OssException;
use OSS\OssClient;
use think\facade\Log;
use think\facade\Request;
use think\file\UploadedFile;
use think\Response;

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件


/**
 * @param array $data
 * @param int $code
 * @param string $msg
 * @return Response
 */
function rjsonToResponse($data = array(), $code = 200, $msg = '')
{
    Log::info(Request::action() . '---' . Request::header('token') . '---返回值 : ' . json_encode($data) . 'msg : ' . $msg);
    Log::record("\n\r", 'debug');
    $out['code'] = $code ?: 0;
    $out['desc'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
    $out['data'] = $data ?: null;
    $response = new Response;
    $response->content(json_encode($out));
    return $response;
}


/**
 * @param array $data
 * @param int $code
 * @param string $msg
 * @param array $sensorsDataParams
 * @return \think\response\Json
 */
function rjson($data = array(), $code = 200, $msg = '',$sensorsDataParams=[])
{
    Log::info(Request::action() . '---' . Request::header('token') . '---返回值 : ' . json_encode($data) . 'msg : ' . $msg);
    Log::record("\n\r", 'debug');
    $out['code'] = $code ?: 0;
    $out['desc'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
    $out['data'] = $data ?: null;
    if(!empty($sensorsDataParams)){
        $out['sensorsData'] = $sensorsDataParams;
    }
    return json($out);
}


function rjsonFit($data = array(), $code = 200, $msg = '')
{
    $data = empty($data) ? (object)array() : $data;
    return rjson($data, $code, $msg);
}


function getTodayStartTime()
{
    return mktime(0, 0, 0, date('m'), date('d'), date('Y'));
}


function game_rjson($data = array(), $code = 200, $msg = '')
{
    Log::info(Request::action() . '---' . Request::header('token') . '---返回值 : ' . json_encode($data) . 'msg : ' . $msg);
    Log::record("\n\r", 'debug');
    $out['code'] = $code ?: 0;
    $out['desc'] = $msg ?: ($out['code'] != 200 ? 'error' : 'success');
    $out['data'] = $data ?: [];
    return json($out);
}

function filter_money($money, $accuracy = 2)
{
    if (is_int($money)) {
        return sprintf('%.2f', $money);
    }
    $str_ret = 0;
    if (empty($money) === false) {
        $str_ret = sprintf("%." . $accuracy . "f", substr(sprintf("%." . ($accuracy + 1) . "f", floatval($money)), 0, -1));
    }

    return floatval($str_ret);
}

//判断字符串包含具体字符
//$str = 12345 $pattern=123
function startwith($str, $pattern)
{
    if (strpos($str, $pattern) === 0)
        return true;
    else
        return false;
}

//hash256
function hash256_encode($str = '')
{
    return hash("sha256", $str);
}

/**
 * 生成签名前的字符串
 *
 * @param $params
 * @return string
 */
function getParamsString($params)
{
    if (!is_array($params))
        $params = array();
    ksort($params);
    $str = '';
    foreach ($params as $k => $v) {
        $str .= $v != '' ? $k . "=" . $v . "&" : '';
    }
    $str = rtrim($str, '&');
    return $str;
}

//当天剩余时间戳
function SurplusTime()
{
    return 86400 - (time() + 8 * 3600) % 86400;
}

function getMonth($date)
{
    $firstDay = date('Y-m-01', strtotime($date));
    $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
    return array($firstDay, $lastDay);
}

/**
 * @Info 是否是测试手机
 * @param $phone
 * @return bool
 */
function authDevPhone($phone)
{
    $test = array('13800000000', '18888888888', '19999999999', '16666666666', '12222222222', '15555555555', '13888888888', '18888556885', '15810501263', '13811258123');
    return (in_array($phone, $test)) ? true : false;
}

/**
 * 生成唯一的订单号
 */
function createOrderNo($uid)
{
    // 生成订单号.
    return $uid . time() . rand(10000, 99999);
}

//本周一
function weekMonday($conf = true)
{
    if ($conf) {
        return date('Y-m-d 00:00:00', (time() - ((date('w', time()) == 0 ? 7 : date('w', time())) - 1) * 24 * 3600));
    }
    return date('Ymd', (time() - ((date('w', time()) == 0 ? 7 : date('w', time())) - 1) * 24 * 3600));
}

//下周一
function getNextMonday($conf = true)
{
    if ($conf) {
        return date('Y-m-d 00:00:00', strtotime('+1 week last monday'));
    }
    return date('Ymd', strtotime('+1 week last monday'));
}

//上周一
function getLastMonday($conf = true)
{
    if ($conf) {
        if (date('l', time()) == 'Monday') return date('y-m-d 00:00:00', strtotime('last monday'));
        return date('Y-m-d 00:00:00', strtotime('-1 week last monday'));
    }
    if (date('l', time()) == 'Monday') return date('Ymd', strtotime('last monday'));
    return date('Ymd', strtotime('-1 week last monday'));
}

//上周日
function getLastSunday($conf = true)
{
    if ($conf) {
        return date('Y-m-d 00:00:00', strtotime('last sunday'));
    }
    return date('Ymd', strtotime('last sunday'));
}

//返回毫秒级时间戳
function msectime()
{
    list($msec, $sec) = explode(' ', microtime());
    $msectime = (int)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    return $msectime;
}

/**
 * 随机生成token.
 * @param $salt
 * @return string
 */
function generateToken($salt)
{
    return md5(md5(generateRandomString(10)) . $salt);
}

function getUnixTimeStamp()
{
    $unixTimeStamp = microtime(true);
    $result = str_replace(".", "", $unixTimeStamp);
    return substr($result, 0, -1);
}

/**
 * 生成指定长度的随机字符串.
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $characters_len = strlen($characters);
    $random_str = '';
    for ($i = 0; $i < $length; ++$i) {
        $random_str .= $characters[rand(0, $characters_len - 1)];
    }

    return $random_str;
}

function generateRandomcode($length = 6)
{
    $characters = '0123456789012345678901234567890123456789';
    $characters_len = strlen($characters);
    $random_str = '';
    for ($i = 0; $i < $length; ++$i) {
        $random_str .= $characters[rand(0, $characters_len - 1)];
    }

    return $random_str;
}


//post json数据
function http_post_json($url, $jsonStr)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr)
        )
    );
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return array($httpCode, $response);
}

/**
 * curl请求
 * @param $url
 * @param $data
 * @param string $method
 * @param string $type
 * @return bool|string
 */
function curlData($url, $data, $method = 'GET', $type = 'json', $head = [], $connnectTimer = 2)
{
    $start_time = msectime();
    Log::info(sprintf('curlData url=%s startTime=%d', $url, $start_time));
    //初始化
    $ch = curl_init();
    $headers_type = [
        'form-data' => ['Content-Type: multipart/form-data'],
        'json' => ['Content-Type: application/json'],
    ];
    $headers = array_merge($headers_type[$type], $head);

    if ($method == 'GET') {
        if ($data) {
            $querystring = http_build_query($data);
            $url = $url . '?' . $querystring;
        }
    }
    // 请求头，可以传数组
    // $headers[]  =  "Authorization: Bearer ". $accessToken;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);         // 执行后不直接打印出来
    if ($method == 'POST') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');     // 请求方式
        curl_setopt($ch, CURLOPT_POST, true);               // post提交
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);              // post的变量
    }
    if ($method == 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    if ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connnectTimer);
    curl_setopt($ch, CURLOPT_TIMEOUT, $connnectTimer);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不从证书中检查SSL加密算法是否存在
    $output = curl_exec($ch); //执行并获取HTML文档内容
    curl_close($ch); //释放curl句柄
    $end_time = msectime();
    Log::info(sprintf('curlData url=%s endTime=%d response=%d', $url, $end_time, $end_time - $start_time));
    return $output;
}


/**获取用户头像
 * @param $avatar   string
 * @return string   返回类型值
 */
function getavatar($avatar)
{
    $avatar_url = config('config.APP_URL_image');
    if (preg_match('/(http:\/\/)|(https:\/\/)/i', $avatar)) {
        $avatar = $avatar;
    } else if (empty($avatar)) {
        $avatar = $avatar_url . getDefaultAvatar();
        // $avatar = $avatar_url . "/image/zwt.png";
    } else {
        $avatar = $avatar_url . $avatar;
    }

    return $avatar;
    if (strrchr($avatar, '.') == '.gif' || strrchr($avatar, '.') == '.svga') {
        return $avatar;
    } else {
        return $avatar . '?x-oss-process=image/auto-orient,1/quality,q_50';
    }
    // return $avatar.'?x-oss-process=image/auto-orient,1/quality,q_50';


    // return str_replace('com//','com/', $avatar);

}

/**处理空数组值
 * @param $arr  数组
 * @return mixed    返回值
 */
function dealnull($arr)
{
    foreach ($arr as $k => $v) {
        if ($v == null) {
            $arr[$k] = "";
        }
    }
    return $arr;
}

/**判断干支、生肖和星座
 * @param $birth        年月日
 * @return array|bool|string    返回类型
 */
function birthext($birth)
{
    if (empty($birth)) {
        return "摩羯座";
    }
    if (strstr($birth, '-') === false && strlen($birth) !== 8) {
        $birth = date("Y-m-d", $birth);
    }
//    if (strlen($birth) === 8) {
//        if (eregi('([0-9]{4})([0-9]{2})([0-9]{2})$', $birth, $bir))
//            $birth = "{$bir[1]}-{$bir[2]}-{$bir[3]}";
//    }
    if (strlen($birth) < 8) {
        return false;
    }
    $tmpstr = explode('-', $birth);
    if (count($tmpstr) !== 3) {
        return false;
    }
    $y = (int)$tmpstr[0];
    $m = (int)$tmpstr[1];
    $d = (int)$tmpstr[2];
    $result = array();
    $xzdict = array('摩羯', '水瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
    $zone = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
    if ((100 * $m + $d) >= $zone[0] || (100 * $m + $d) < $zone[1]) {
        $i = 0;
    } else {
        for ($i = 1; $i < 12; $i++) {
            if ((100 * $m + $d) >= $zone[$i] && (100 * $m + $d) < $zone[$i + 1]) {
                break;
            }
        }
    }
    $result = $xzdict[$i] . '座';
    return $result;
}

/**
 * @info 通过时间日期获取星座
 * @param $birth string
 * @return string
 */
function birthextLite($birth)
{
    if (empty($birth) || !strtotime($birth)) {
        return "";
    }
    $tmpstr = explode('-', $birth);
    if (count($tmpstr) !== 3) {
        return "";
    }
    $y = (int)$tmpstr[0];
    $m = (int)$tmpstr[1];
    $d = (int)$tmpstr[2];
    $result = array();
    $xzdict = array('摩羯', '水瓶', '双鱼', '白羊', '金牛', '双子', '巨蟹', '狮子', '处女', '天秤', '天蝎', '射手');
    $zone = array(1222, 122, 222, 321, 421, 522, 622, 722, 822, 922, 1022, 1122, 1222);
    if ((100 * $m + $d) >= $zone[0] || (100 * $m + $d) < $zone[1]) {
        $i = 0;
    } else {
        for ($i = 1; $i < 12; $i++) {
            if ((100 * $m + $d) >= $zone[$i] && (100 * $m + $d) < $zone[$i + 1]) {
                break;
            }
        }
    }
    $result = $xzdict[$i] . '座';
    return $result;
}

/**
 * 时间格式化处理
 * @param $time 时间
 * @return string   字符类型
 */
function formatTimes($time)
{
    $nowTime = time();
    // 时间差.
    $cut = $nowTime - $time;
    $timeStr = "";
    if ($cut <= 60) { // 小于1分钟.
        $number = floor($cut / 1);
        $timeStr = $number . "秒前";
    } elseif ($cut > 60 && $cut < 3600) { // 1min-10min
        $number = floor($cut / 60);
        $timeStr = $number . "分钟前";
    } elseif (3600 <= $cut && $cut < 86400) {
        $number = floor($cut / 3600);
        $timeStr = $number . "小时前";
    } elseif ($cut >= 86400 && $cut < 259200) {
        $number = floor($cut / 86400);
        $timeStr = $number . "天前";
    } elseif ($cut >= 259200) {
        $number = 3;
        $timeStr = $number . "天前";
    }
    return $timeStr;

}

/**检查是否为数字格式
 * @param $str  字符类型
 * @return bool 返回类型
 */
function isNumber($str)
{
    if (preg_match('/^\d+$/', $str)) {
        return true;
    }
    return false;
}

/**检查是否为中文
 * @param $strchina 字符类型
 * @return bool     批回类型
 */
function checkchinese($str)
{
    if (preg_match("/[\x{4e00}-\x{9fa5}]+/u", $str)) {
        return true;
    }
    return false;
}

/**是给为空字符串
 * @param $str  字符类型
 * @return bool
 */
function isEmpty($str)
{
    $str = trim($str);
    return !empty($str) ? true : false;
}

/**检索给定键的所有值
 * @param $items    数组
 * @param $key      指定的键
 * @return array    返回新的数组
 */
function pluck($items, $key)
{
    return array_map(function ($item) use ($key) {
        return is_object($item) ? $item->$key : $item[$key];
    }, $items);
}

/**
 * 通过图片的远程url，下载到本地
 * @param: $url为图片远程链接
 * @param: $filename为下载图片后保存的文件名
 * @param  $upload 本地地址操作
 */
function GrabImage($url, $filename, $upload)
{
    if ($url == ""):return false;endif;
    ob_start();
    readfile($url);
    $img = ob_get_contents();
    ob_end_clean();
    $size = strlen($img);
    $fp2 = @fopen($upload . $filename, "a");
    fwrite($fp2, $img);
    fclose($fp2);
    return $filename;
}

/**按键对数组或对象的集合排序
 * @param $items    数组
 * @param $attr     指定的键
 * @param $order    排序
 * @return array    返回新的数组
 */
function orderBy($items, $attr, $order)
{
    $sortedItems = [];
    foreach ($items as $item) {
        $key = is_object($item) ? $item->{$attr} : $item[$attr];
        $sortedItems[$key] = $item;
    }
    if ($order === 'desc') {
        krsort($sortedItems);
    } else {
        ksort($sortedItems);
    }
    return array_values($sortedItems);
}

/**获取文件后辍名
 * @param $filename 检测的字符串
 * @return mixed
 */
function getExtension($filename)
{
    $suffix = substr($filename, strrpos($filename, '.'));
    return str_replace('.', '', $suffix);
}

/**过滤数据中的空数组
 * @param $data
 * @return mixed
 */
function filter_data($data)
{
    foreach ($data as $key => $value) {
        if (empty($value)) {
            unset($data[$key]);
        }
    }
    return $data;
}

//检测一个值是否在数组里面
function deep_in_array($value, $array)
{
    foreach ($array as $item) {
        if (!is_array($item)) {
            if ($item == $value) {
                return true;
            } else {
                continue;
            }
        }

        if (in_array($value, $item)) {
            return true;
        } else if (deep_in_array($value, $item)) {
            return true;
        }
    }
    return false;
}


//热度值数据格式化
function formatNumber($number)
{
    $number = (int)$number;
    if ($number <= 9999) {
        $newNumber = $number > 0 ? $number : 0;
        return floor($newNumber);
    } else {
        $newNumber = $number > 9999 ? $number / 10000 : $number;
        if (is_int($newNumber)) {        //整数 15000    1.5w
            return $newNumber . 'w';
        } else {          //有小数点 12457 1.2w
            $newNumber = explode('.', $newNumber);
            if (isset($newNumber[1]) && substr($newNumber[1], 0, 1) < 1) {
                return $newNumber[0] . 'w';
            } else {
                return $newNumber[0] . '.' . substr($newNumber[1], 0, 1) . 'w';
//                return $newNumber[0].'w';
            }
        }
    }
}


/**
 * @Info 热度值数据格式化
 * @param $number
 * @return string
 */
function formatNumberLite($number)
{
    $number = intval($number);
    if ($number <= 9999) {
        $newNumber = $number > 0 ? $number : 0;
        return strval($newNumber);
    } else {
        $newNumber = $number > 9999 ? $number / 10000 : $number;
        if (is_int($newNumber)) {        //整数 15000    1.5w
            return $newNumber . 'w';
        } else {          //有小数点 12457 1.2w
            $newNumber = explode('.', $newNumber);
            if (isset($newNumber[1]) && substr($newNumber[1], 0, 1) < 1) {
                return $newNumber[0] . 'w';
            } else {
                return $newNumber[0] . '.' . substr($newNumber[1], 0, 1) . 'w';
//                return $newNumber[0].'w';
            }
        }
    }
}


/**
 * @info 序列化 number 保留2位小数，省略尾数
 * @param $number
 * @return float
 */
function formatRound($number)
{
    $position = strrpos($number, ".");
    if (empty($position)) {
        return $number;
    }
    return floatval(sprintf("%s.%s", substr($number, 0, $position), substr($number, $position + 1, 2)));
}


/**
 * 计算两个日期的相差天数
 * @param $day1
 * @param $day2
 * @return float|int
 */
function diffBetweenTwoDays($day1, $day2)
{
    $second1 = strtotime($day1);
    $second2 = strtotime($day2);

    if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
    }
    return ($second1 - $second2) / 86400;
}

/**
 * PHP实现识别带emoji表情的字符串
 * @param $str
 * @return bool
 */
function have_special_char($str)
{
    $length = mb_strlen($str);
    $array = [];
    for ($i = 0; $i < $length; $i++) {
        $array[] = mb_substr($str, $i, 1, 'utf-8');
        if (strlen($array[$i]) >= 4) {
            return true;

        }
    }
    return false;
}

/**
 * 获取真实ip
 * @return array|false|mixed|string
 */
function getIP()
{
    if (isset($_SERVER)) {
        if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
            $realip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $realip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if (getenv("HTTP_X_FORWARDED_FOR")) {
            $realip = getenv("HTTP_X_FORWARDED_FOR");
        } else if (getenv("HTTP_CLIENT_IP")) {
            $realip = getenv("HTTP_CLIENT_IP");
        } else {
            $realip = getenv("REMOTE_ADDR");
        }
    }
    return $realip;
}

/**
 * @Info 用户的默认头像
 * @return string
 */
function initMemberAvatar($avatar)
{
    if (empty($avatar)) {
        return getDefaultAvatar();
    }
    return $avatar;
}

function getDefaultAvatar()
{
    return "/images/defaultHead.png";
}

function filterEmoji($str)
{
    $str = preg_replace_callback('/./u',
        function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        },
        $str);
    return $str;
}

/**
 * @param UploadedFile $uploadFile
 * @param string $file_dir
 * @return string
 * @throws OssException
 */
function uploadOssFile($uploadFile, $file_dir)
{
    if (\app\utils\CommonUtil::getAppDev()) {
        return "http://muayuyin.oss-cn-beijing.aliyuncs.com/background_image/20220510/af62f687ae7c34d71264d2c3abfbd14c.jpeg";
    }
    //OSS第三方配置
    $ossConfig = config('config.OSS');
    $accessKeyId = $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
    $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
    $endpoint = $ossConfig['ENDPOINT'];//阿里云OSS 地址
    $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
    $savename = \think\facade\Filesystem::disk('public')->putFile($file_dir, $uploadFile);
    $imageObject = str_replace("\\", "/", $savename);
    $imageFile = STORAGE_PATH . str_replace("\\", "/", $savename);
    $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
    $result = $ossClient->uploadFile($bucket, $imageObject, $imageFile);//上传成功
    return $result['info']['url'] ?? "";
}


/*
 * 上传图片
 */
function uploadOssFileSecond($file_name, $file_dir)
{
    $file_dir = rtrim($file_dir, "/");
    //OSS第三方配置
    $ossConfig = config('config.OSS');
    $accessKeyId = $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
    $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
    $endpoint = $ossConfig['ENDPOINT'];//阿里云OSS 地址
    $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
    $savename = sprintf("%s/%s", $file_dir, basename($file_name));
    $imageObject = str_replace("\\", "/", $savename);
    $imageFile = STORAGE_PATH . str_replace("\\", "/", $savename);
    try {
        $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $result = $ossClient->uploadFile($bucket, $imageObject, $imageFile);//上传成功
        return $result['info']['url'];
    } catch (OssException $e) {
        throw $e;
    }


}

// 定义一个函数，可以把数字转换成带有单位的字符串
function format_number_string($number) {
    // 如果数字大于等于10000，则除以10000，并在后面加上"w"单位
    if ($number >= 10000) {
        return round($number / 10000, 1) . "w";
    }
    // 如果数字大于等于1000，则除以1000，并在后面加上"k"单位
    elseif ($number >= 1000) {
        return round($number / 1000, 1) . "k";
    }
    // 否则，直接返回数字本身
    else {
        return $number;
    }
}

