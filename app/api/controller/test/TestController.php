<?php

namespace app\api\controller\test;

use app\BaseController;
use app\common\GreenCommon;
use app\common\RedisCommon;
use app\common\TextcanimgCommon;
use app\common\YunxinCommon;
use app\domain\activity\giftReturn\GiftReturnUserDao;
use app\domain\activity\halloween\HalloweenService;
use app\domain\activity\halloween\HalloweenSystem;
use app\domain\asset\AssetKindIds;
use app\domain\dao\UserIdentityModelDao;
use app\domain\duke\dao\DukeModelDao;
use app\domain\duke\DukeSystem;
use app\domain\duke\model\DukeModel;
use app\domain\exceptions\FQException;
use app\domain\game\box\service\BoxService;
use app\domain\game\taojin\TaojinSystem;
use app\domain\gift\GiftSystem;
use app\domain\gift\service\GiftService;
use app\domain\guild\cache\GuildRoomCache;
use app\domain\guild\cache\PopularGuildRoomCache;
use app\domain\models\UserIdentityStatusModel;
use app\domain\promote\event\handler\PromoteHandler;
use app\domain\prop\dao\PropModelDao;
use app\domain\queue\producer\YunXinMsg;
use app\domain\room\conf\RoomTag;
use app\domain\room\dao\RoomBlackModelDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\vip\dao\VipModelDao;
use app\form\ReceiveUser;
use app\query\room\service\QueryRoomService;
use app\query\search\service\SearchService;
use app\query\user\elastic\UserModelElasticDao;
use app\query\user\QueryUserService;
use app\utils\Aes;
use app\utils\ApiAuth;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\Error;
use app\utils\RequestOrm;
use app\utils\TimeUtil;
use Jobby\Exception;
use think\facade\Log;
use think\facade\Request;

class TestController extends BaseController
{
    protected $user_info_key = "userinfo_";

//    private $jsonTest='';


    public function updatePropTime()
    {
        $userId = intval($this->request->param('userId'));
        $kindId = $this->request->param('kindId');
        $woreTime = $this->request->param('woreTime');
        $expiresTime = $this->request->param('expiresTime');
        $model = PropModelDao::getInstance()->loadPropByKindId($userId, $kindId);
        if ($model == null) {
            return rjson(["not kindId"]);
        }
        Log::info(sprintf('updatePropTime userId=%d info=%s', $userId, json_encode(PropModelDao::getInstance()->modelToData($model))));

        if ($woreTime) {
            $model->woreTime = $woreTime;
        }
        if ($expiresTime) {
            $model->expiresTime = $expiresTime;
        }
        PropModelDao::getInstance()->updateProp($userId, $model);
        Log::info(sprintf('updatePropTime userId=%d newInfo=%s', $userId, json_encode(PropModelDao::getInstance()->modelToData($model))));

        return rjson(PropModelDao::getInstance()->modelToData($model));
    }

    public function testConf()
    {
        $redis = RedisCommon::getInstance()->getRedis(["select" => 3]);
//        $conf = Config::getInstance()->getWeekCheckInConf();
        //        $redis->del("weekcheckin_conf");
        //        $redis->set("weekcheckin_conf", json_encode($conf));
        //
        //        $conf = Config::getInstance()->getDailyConfig();
        //        $redis->del("daily_conf");
        //        $redis->set("daily_conf", json_encode($conf));
        //
        //        $conf = Config::getInstance()->getActiveBoxConfig();
        //        $redis->del("activebox_conf");
        //        $redis->set("activebox_conf", json_encode($conf));
        //
        //        $conf = Config::getInstance()->getNewerConfig();
        //        $redis->del("newer_conf");
        //        $redis->set("newer_conf", json_encode($conf));

//        $conf = \app\domain\game\box\Config::getInstance()->getBoxConf();
        //        $redis->set("box_conf", json_encode($conf));
        //
        //        $conf = \app\domain\game\taojin\Config::getInstance()->getTaoJibConf();
        //        $redis->set("taojin_conf", json_encode($conf));
        //
        //        $conf = \app\domain\level\Config::getInstance()->getLevelConf();
        //        $redis->set("level_conf", json_encode($conf));
        //
        //        $conf = \app\domain\lottery\Config::getInstance()->getLotteryConf();
        //        $redis->del("lottery_conf");
        //        $redis->set("lottery_conf", json_encode($conf));

//        $conf = \app\domain\Config::getInstance()->getVipConf();
        //        $redis->set("vip_conf", json_encode($conf));
        //
        //        $conf = \app\domain\Config::getInstance()->getDukeConf();
        //        $redis->set("duke_conf", json_encode($conf));
        //
        //        $conf = \app\domain\Config::getInstance()->getChargeConf();
        //        $redis->set("charge_conf", json_encode($conf));
        //
        //        $conf = \app\domain\Config::getInstance()->getChargeMallConf();
        //        $redis->set("chargemall_conf", json_encode($conf));

        return rjson(["ok"]);
    }

    public function testKwfilter()
    {
        $text = $this->request->param('text');
        $filteredText = GreenCommon::getInstance()->filterText($text);
        $isGreen = GreenCommon::getInstance()->checkText($text);
        return rjson(['text' => $text, 'filteredText' => $filteredText, 'isGreen' => $isGreen]);
    }

    public function testSetDuke()
    {
        $userId = intval($this->request->param('userId'));
        $dukeLevel = intval($this->request->param('dukeLevel'));
        $dukeValue = intval($this->request->param('dukeValue'));
        $expiresTime = TimeUtil::strToTime($this->request->param('expiresTime'));

        $dukeModel = new DukeModel();
        $dukeModel->dukeLevel = $dukeLevel;
        $dukeModel->dukeValue = $dukeValue;
        $dukeModel->dukeExpiresTime = $expiresTime;

        DukeModelDao::getInstance()->saveDuke($userId, $dukeModel);

        return rjson([
            'dukeLevel' => $dukeModel->dukeLevel,
            'dukeValue' => $dukeModel->dukeValue,
            'dukeExpiresTime' => $dukeModel->dukeExpiresTime,
            'dukeExpiresTimeStr' => TimeUtil::timeToStr($dukeModel->dukeExpiresTime),
            'expiresTime' => $this->request->param('expiresTime'),
        ]);
    }

    public function testGetDuke()
    {
        $userId = intval($this->request->param('userId'));
        if ($userId <= 0) {
            return rjson(null, 500, 'userId参数错误');
        }
        $curTime = TimeUtil::strToTime($this->request->param('curTime'));
        if ($curTime <= 0) {
            return rjson(null, 500, 'curTime参数错误');
        }
        $dukeModel = DukeModelDao::getInstance()->loadDuke($userId);
        $newDukeModel = new DukeModel();
        $newDukeModel->dukeLevel = $dukeModel->dukeLevel;
        $newDukeModel->dukeValue = $dukeModel->dukeValue;
        $newDukeModel->dukeExpiresTime = $dukeModel->dukeExpiresTime;

        DukeSystem::getInstance()->adjustDuke($newDukeModel, $curTime);

        return rjson([
            'curTime' => TimeUtil::timeToStr($curTime),
            'db' => [
                'dukeLevel' => $dukeModel->dukeLevel,
                'dukeValue' => $dukeModel->dukeValue,
                'dukeExpiresTime' => $dukeModel->dukeExpiresTime,
                'dukeExpiresTimeStr' => TimeUtil::timeToStr($dukeModel->dukeExpiresTime),
            ],
            'calc' => [
                'dukeLevel' => $newDukeModel->dukeLevel,
                'dukeValue' => $newDukeModel->dukeValue,
                'dukeExpiresTime' => $newDukeModel->dukeExpiresTime,
                'dukeExpiresTimeStr' => TimeUtil::timeToStr($newDukeModel->dukeExpiresTime),
            ],
        ]);
    }

    public function testSendGift()
    {
//        $fromUserId = Request::param('userId');
        //        $toUserIds = Request::param('toUserIds');
        //        $toMicIds = Request::param('toMicIds');
        //        $giftId = Request::param('giftId');
        //
        //        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
        //        if ($giftKind == null) {
        //            return rjson([], 500, '礼物不存在');
        //        }
        //$receiveUsers = ReceiveUser::fromUserMicIdArray(toUserIds, toMicIds);
        $start = microtime(true);
        $giftId = 376;
        $count = 2;
        $fromUserId = 1366882;
        $toUsersIds = [1101042, 1366922];
        $toMicIds = [1, 2];
        $receiveUsers = ReceiveUser::fromUserMicIdArray($toUsersIds, $toMicIds);
        $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
        GiftService::getInstance()->sendGift(120847, $fromUserId, $receiveUsers, $giftKind, $count);

        $end = microtime(true);
        echo sprintf('used %.4f', $end - $start);
    }

    public function testSetVip()
    {
        $userId = intval($this->request->param('userId'));
        $vipLevel = intval($this->request->param('vipLevel'));
        $vipExpiresTime = $this->request->param('vipExpiresTime');
        $svipExpiresTime = $this->request->param('svipExpiresTime');

        if ($userId <= 0) {
            return rjson(null, 500, 'userId参数错误');
        }

        if (!in_array($vipLevel, [0, 1, 2])) {
            return rjson(null, 500, 'vipLevel参数错误');
        }

        $vipModel = VipModelDao::getInstance()->loadVip($userId);
        $vipModel->level = $vipLevel;

        if (!empty($vipExpiresTime)) {
            $vipModel->vipExpiresTime = TimeUtil::strToTime($vipExpiresTime);
        }
        if (!empty($svipExpiresTime)) {
            $vipModel->svipExpiresTime = TimeUtil::strToTime($svipExpiresTime);
        }

        VipModelDao::getInstance()->saveVip($userId, $vipModel);

        return rjson([
            'vipLevel' => $vipModel->level,
            'vipExpiresTime' => $vipModel->vipExpiresTime,
            'vipExpiresTimeStr' => TimeUtil::timeToStr($vipModel->vipExpiresTime),
            'svipExpiresTime' => $vipModel->svipExpiresTime,
            'svipExpiresTimeStr' => TimeUtil::timeToStr($vipModel->svipExpiresTime),
        ]);
    }

    public function encodeQueryRooms($queryRooms)
    {
        $ret = [];
        foreach ($queryRooms as $room) {
            $ret[] = [
                'roomId' => $room->roomId,
                'roomName' => $room->roomName,
                'roomTypeName' => $room->roomTypeName,
                'lock' => $room->lock,
                'visitorNumber' => $room->visitorNumber,
                'isLive' => $room->isLive,
                'image' => $room->image,
                'ownerUserId' => $room->ownerUserId,
                'ownerAvatar' => $room->ownerAvatar,
                'ownerNickname' => $room->ownerNickname,
            ];
        }
        return $ret;
    }

    public function testSearchRoom()
    {
        $search = $this->request->param('search');
        $offset = $this->request->param('offset');
        $count = $this->request->param('count');
        $offset = empty($offset) ? 0 : intval($offset);
        $count = empty($count) ? 0 : intval($count);
        list($rooms, $count) = QueryRoomService::getInstance()->searchRoom($search, $offset, $count);
        return rjson([
            'count' => $count,
            'rooms' => $this->encodeQueryRooms($rooms),
        ]);
    }

    public function encodeQueryUsers($queryUsers)
    {
        $ret = [];
        foreach ($queryUsers as $queryUser) {
            $ret[] = [
                'user_id' => $queryUser->userId,
                'nickname' => $queryUser->nickname,
                'avatar' => CommonUtil::buildImageUrl($queryUser->avatar),
                'sex' => $queryUser->sex,
                'pretty_id' => $queryUser->prettyId,
                'pretty_avatar' => CommonUtil::buildImageUrl($queryUser->prettyAvatar),
                'is_vip' => $queryUser->vipLevel,
                'lv_dengji' => $queryUser->lvDengji,
            ];
        }
        return $ret;
    }

    public function testSearchUser()
    {
        $search = $this->request->param('search');
        $offset = $this->request->param('offset');
        $count = $this->request->param('count');
        $offset = empty($offset) ? 0 : intval($offset);
        $count = empty($count) ? 0 : intval($count);
        list($users, $count) = QueryUserService::getInstance()->searchUsers($search, $offset, $count);
        return rjson([
            'count' => $count,
            'users' => $this->encodeQueryUsers($users),
        ]);
    }


    /**
     * 【Http接口参数】
     * 提交地址:122.112.230.64:8001
     * 用户名:642799
     * 密码:3mAfhpmj
     * string(35) "20211227153709,0,202112271105179579"
     * string(35) "20211227154202,0,202112271105179582"
     * string(35) "20211227155223,0,202112271105179585"
     * string(35) "20211227161555,0,202112271105179661"
     * string(35) "20211227162508,0,202112271105179667"
     * string(35) "20211227162914,0,202112271105179668"
     * string(35) "20211227163912,0,202112271105179680"
     * string(35) "20211227163931,0,202112271105179681"
     *
     */
    private function testRTD()
    {
        $tpl = "【音恋 语音】kkk,来领动态头像框~已放入您的背包rongqii.cn/1456391退T";
        $tpl = urlencode($tpl);
        $phone = "13080743998";
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        $data['url'] = sprintf('http://122.112.230.64:8001/mt.ashx?account=642799&pswd=3mAfhpmj&msg=%s&pn=%d', $tpl, $phone);
        return $requestObj->get($data['url']);
    }


    private function testRTDreport()
    {
        $url = sprintf("http://122.112.230.64:8001/rpt.ashx?account=642799&pswd=3mAfhpmj");
        $headers = array('Content-Type' => 'application/json');
        $requestObj = new RequestOrm($headers);
        return $requestObj->get($url);
    }

    public function testpost()
    {
        $param = \think\facade\Request::param('');
        return rjson($param, 200, 'success');
    }



//$headers = [
////                'VERSION' => '3.2.11',
////                'CHANNEL' => 'GW',
////                'DEVICEID' => 'a412aba6-294b-458b-802d-090b979e2e23',
////                'PLATFORM' => 'Android,10',
////                'IMEI' => '4EA7981072A00E364B647FCE4D087BABC2DFF397',
////                'id' => '3.2.11',
////                'source' => 'fanqie',
////            ];

    /**
     * @param $params array 请求的数据参数
     * @param $platForm string 平台信息
     * @param $source string 源数据
     * @param $version string 版本信息
     * @return string
     * @throws \Exception
     */
    private function makeTestAesString($params, $platForm, $source, $version)
    {
//        拼接时间戳
        $params['timestamp'] = time();
//        过滤并拼接请求数据
        $resultStr = "";
        foreach ($params as $key => $value) {
            $left = "";
            if ($resultStr !== "") {
                $left = "&";
            }
            $resultStr .= sprintf("%s%s=%s", $left, urlencode($key), urlencode($value));
        }
        $platForm = $this->getPlatFormOs($platForm);
//        用原始数据计算sign
        $sign = ApiAuth::getInstance()->createSign($params, $platForm, $source, $version);
//        将sign拼接进原始请求数据
        $resultStr .= sprintf("&sign=%s", $sign);
        $Aes = new Aes();
//        将请求数据加密
        return $Aes->aesEncrypt($resultStr);
    }


    /**
     * @return string
     */
    public function getPlatFormOs($platForm)
    {
        $pos = strpos($platForm, "Android");
        if ($pos !== false) {
            return "Android";
        }
        $pos = strpos($platForm, "iOS");
        if ($pos !== false) {
            return "iOS";
        }
        return "";
    }


    private function makeBase64Image($file)
    {
        //        dd($file['img']);
        $stream = fopen($file['img']->getRealPath(), 'r');
        dd($stream);
    }

    private function imgToBase64($img_file, $type = 1)
    {
        $img_base64 = '';
        if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等
            $fp = fopen($app_img_file, "r"); // 图片是否可读权限
            if ($fp) {
                $filesize = filesize($app_img_file);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) { //判读图片类型
                    case 1:
                        $img_type = "gif";
                        break;
                    case 2:
                        $img_type = "jpg";
                        break;
                    case 3:
                        $img_type = "png";
                        break;
                }
//合成图片的base64编码
                if ($type) {
                    $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;
                } else {
                    $img_base64 = $file_content;
                }
            }
            fclose($fp);
        }
        return array($img_base64, $img_type); //返回图片的base64及图片类型

    }

    private function isPrettyNumber($number)
    {
        if (!CommonUtil::isPrettyNumber($number)) {
            return true;
        }
        return false;
    }



//{"\u8bf7\u6c42\u5730\u5740: ":"HTTP\/1.0 POST : http:\/\/test.php.fqparty.com\/api\/v1\/perfectuserinfo","\u8bf7\u6c42\u53c2\u6570: ":{"s":"\/\/api\/v1\/perfectuserinfo","birthday":"2003-11-10","sex":"1","avatar":"http:\/\/image2.fqparty.com\/images\/manhead\/17m3x.jpg","nickname":"\ud83c\udf2e\u9ad8\u8fdc\u7684\u6a80\u73cd\ud83e\udd69","invitcode":"1100","simulator":"false","token":"4143566d1e5565de3b99c5af55a142d3","device":"NTH-AN00","version":"11"},"\u670d\u52a1\u5668\u4fe1\u606f: ":{"USER":"nobody","HOME":"\/","HTTP_USER_AGENT":"okhttp\/3.11.0","HTTP_ACCEPT_ENCODING":"gzip","HTTP_CONTENT_TYPE":"application\/x-www-form-urlencoded","HTTP_SIMULATOR":"false","HTTP_BUILDTIME":"2111081831","HTTP_SOURCE":"yinlian","HTTP_TOKEN":"4143566d1e5565de3b99c5af55a142d3","HTTP_VERSION":"3.1.7","HTTP_IMEI":"871C2D6A754F85D45BFF67B309D28CB08B919970","HTTP_DEVICEID":"61714e43-ec46-469a-80a9-5fcc5880f3db","HTTP_ID":"com.party.fq","HTTP_DEVICE":"NTH-AN00","HTTP_CHANNEL":"Beta","HTTP_PLATFORM":"Android,11","HTTP_CONTENT_LENGTH":"271","HTTP_CONNECTION":"close","HTTP_X_FORWARDED_FOR":"43.224.44.215","HTTP_X_REAL_IP":"43.224.44.215","HTTP_HOST":"test.php.fqparty.com","REDIRECT_STATUS":"200","SERVER_NAME":"test.php.fqparty.com","SERVER_PORT":"8082","SERVER_ADDR":"127.0.0.1","REMOTE_PORT":"46836","REMOTE_ADDR":"127.0.0.1","SERVER_SOFTWARE":"nginx\/1.14.1","GATEWAY_INTERFACE":"CGI\/1.1","REQUEST_SCHEME":"http","SERVER_PROTOCOL":"HTTP\/1.0","DOCUMENT_ROOT":"\/www\/wwwroot\/mua\/public","DOCUMENT_URI":"\/index.php","REQUEST_URI":"\/api\/v1\/perfectuserinfo","SCRIPT_NAME":"\/index.php","CONTENT_LENGTH":"271","CONTENT_TYPE":"application\/x-www-form-urlencoded","REQUEST_METHOD":"POST","QUERY_STRING":"s=\/\/api\/v1\/perfectuserinfo","SCRIPT_FILENAME":"\/www\/wwwroot\/mua\/public\/index.php","PATH_INFO":"","FCGI_ROLE":"RESPONDER","PHP_SELF":"\/index.php","REQUEST_TIME_FLOAT":1636536362.663977,"REQUEST_TIME":1636536362}}
//"\ud83c\udf51\u6069\u591a\u62c9\u6a80\u73cd\ud83e\udd69"
    public function test()
    {

//        $key = HalloweenSystem::getInstance()->getActivityType();
//        $re=HalloweenService::getInstance()->TestonUserPayEvent();
//        var_dump($re);

        echo 'success';
        die;
        $model = new PromoteHandler;
        $result = $model->testUserLoginEvent($this->request);
        var_dump($result);
        die;
        echo 'success';
        die;

////        load 提现记录表 看用户是否存在待提现提现记录， 返回id主键 find one
////        $re=UserWithdrawDetailModelDao::getInstance()->existsAuditOrderForUserId($userId);
//
////        清理公众号用户提现 zb_user_withdraw_info 表，将用户数据存入zb_user_withdraw_info_log 清理原表中数据
////        $userId=2313902;
//        $re=AgentPayService::getInstance()->cleanwithdrawStoreLog($userId);
////        var_dump($re);die;
////        清理用户提现 zb_user_withdraw_bank_information 表
//        $delRe=UserWithdrawBankInformationModelDao::getInstance()->deleteForUserId($userId);


//        echo 555;die;

        $userId = 1813941;
        $re = UserIdentityModelDao::getInstance()->updateIdentityStatus($userId, UserIdentityStatusModel::$ERROR);
        var_dump($re);
        die;


        $number = 12121212;  //true
//        $strList=<<<str
//33345567
//12412433
//11234566
//45222133
//15151234
//11334466
//13691369
//str;

        $strList = <<<str
111333
333444
11133345
11331133
str;
        $listData = explode("\n", $strList);
        $successList = [];
        $errorList = [];
        foreach ($listData as $prettyNumber) {
            $result = $this->isPrettyNumber((int)$prettyNumber);
            if ($result) {
                $successList[] = $prettyNumber;
            } else {
                $errorList[] = $prettyNumber;
            }
        }
        echo '<pre>';
        echo 'successList' . PHP_EOL;
        print_r($successList);
        echo 'errorList' . PHP_EOL;
        print_r($errorList);


//        $number = 12001255;  //false
//        $number=1313; //true
//        var_dump(preg_match('#(\d)(\d)\1((?!\1)\2)$#', $number));die;
//        $re=CommonUtil::isPrettyNumber($number);
//        var_dump($re);die;

//        $cacheData="[]";
//        $result = json_decode($cacheData, true);
//        var_dump($result);die;


//        热搜主播
        $result = SearchService::getInstance()->loadHotAnchorUserList();

//        猜你喜欢
//        $result=SearchService::getInstance()->loadGuessLike();
        die;

//        $userId=1439778;
//        $model = UserModelDao::getInstance()->loadUserModel($userId);
//        if ($model === null) {
//            return null;
//        }
//        $re=UserModelElasticDao::getInstance()->storeData($userId, $model);
//        dd($re);

        $userId = 1439778;
        $re = UserModelElasticDao::getInstance()->searchUserForId($userId, 0, 20);
        dd($re);


        $userModels = UserModelElasticDao::getInstance()->searchUserByIdfa('9D7DB601-4020-42D7-9A3E-F4E65385ADA2', 1);
        print_r($userModels);
        die;

//        echo __FUNCTION__;die;

//        $search = 1439778;
//        list($roomModelList, $total) = UserModelElasticDao::getInstance()->searchUserForId($search, 0, 50);
//        var_dump($total);
//        dd($roomModelList);


//        $search = "容";
//        list($roomModelList, $total) = UserModelElasticDao::getInstance()->searchUserForNickname($search, 0, 50);
//        var_dump($total);
//        dd($roomModelList);


//        $search = "好人";
//        list($roomModelList, $total) = RoomModelElasticDao::getInstance()->searchRoomForRoomName($search, 0, 50);
//        var_dump($total);
//        dd($roomModelList);


//        $search = 123775;
//        list($roomModelList, $total) = RoomModelElasticDao::getInstance()->searchRoomForId($search, 0, 50);
//        var_dump($total);
//        dd($roomModelList);


//        $search="kk";
//        $offset=0;
//        $count=50;
//        list($modelList,$total)=QueryUserService::getInstance()->searchUsersForIndexSecond($search,$offset,$count);
//        var_dump($total);
//        dd($modelList);


//        $userId = 1439778;
//        $roomId = 123775;
//        $updateProfile['room_name'] = "kkkroom";
//        event(new RoomUpdateEvent($userId, (int)$roomId, $updateProfile, time()));
//        echo 'first success';die;

//        $userId = 1439778;
//        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
//        if ($userModel === null) {
//            throw new FQException("error", 500);
//        }
//        $status = 1;
//        event(new MemberDetailAuditEvent($userModel->userId, null, false, $userModel, $status, time()));


        echo 'success';
        die;
        $recommendIds = [];

        $userId = 1439778;
        $filterArr = [101, 102, 103, 104, $userId];
        $recommendIds = array_merge($recommendIds, $filterArr);
        list($list, $total) = UserModelElasticDao::getInstance()->loadUserModelForNotMatch($recommendIds);

        var_dump($total);
        dd($list);


        $limit = 3;
        $userModels[] = ['id' => 1];
        $userModels[] = ['id' => 2];
        $userModels[] = ['id' => 3];
        $userModels[] = ['id' => 4];
        $userModels[] = ['id' => 5];
        $userModels[] = ['id' => 6];

        $re = array_slice($userModels, 0, $limit);
        dd($re);


        $roomId = 122059;
        $userId = 1524297;
        $type = 1;
        $offset = 0;
        $pageNum = 20;

//        $adminUser = RoomManagerModelDao::getInstance()->where([
//            'rooms_id' => $roomId,
//            'user_id' => $userId
//        ])->find();

        $re = RoomManagerModelDao::getInstance()->where([
            'rooms_id' => $roomId,
            'user_id' => $userId
        ])->find();
        dd($re);


////        list($blackUsers, $total) = QueryBlackService::getInstance()->listBlackUser($roomId, $type, $offset, $pageNum);
//        list($blackUsers, $total) = QueryBlackService::getInstance()->searchBlackUser($userId, $roomId, $userId);
//        var_dump($total);
//        dd($blackUsers);

//        echo 444;die;
//        $dbMap = Sharding::getInstance()->getModelsMap("roomSlave");
//        foreach ($dbMap as $dbName) {
//
//        }

//        $data=RoomBlackModelDao::getInstance()->loadAllTempData();
//        dd($data);


        $id = 12495;
//        $re=RoomBlackModelDao::getInstance()->where(array("id"=>$id))->delete();

        $re = RoomBlackModelDao::getInstance()->removeForRoomUser($roomId, $userId);
        dd($re);
        $roomId = 123775;
        $userId = 1439778;
//        $list = LanguageroomModel::getInstance()->field('id,user_id')->select()->toArray();
//        $roomRes = LanguageroomModel::getInstance()->field('id,pretty_room_id,user_id,room_name')->where(['id'=> $roomId])->find();
//        $field='id,pretty_room_id,user_id,room_name';
//        $roomRes=RoomModelDao::getInstance()->loadRoomDataField($roomId,$field);
//        dd($roomRes);die;

//        $is_guild_id = LanguageroomModel::getInstance()->where('id',$event->roomId)->value('room_type');
//        $is_guild_id=RoomModelDao::getInstance()->loadRoomTypeForId($roomId);
//        dd($is_guild_id);

//        $roomInfo = LanguageroomModel::getInstance()->where(['id' => $room_id])->find();
//        $roomInfo=RoomModelDao::getInstance()->loadRoomData($roomId);
//        dd($roomInfo);

        $roomId = 123775;
        $userId = 1439550;
//        $blackUser = RoomBlackModelDao::getInstance()->where(array('user_id' => $uid,'room_id'=>$room_id))->find();
//        $blackUser = RoomBlackModelDao::getInstance()->getModel($roomId)->where(array('user_id' => $userId,'room_id'=>$roomId))->find()->toArray();
//        echo RoomBlackModelDao::getInstance()->getModel($roomId)->getLastSql();die;//SELECT * FROM `zb_room_black` WHERE `user_id` = 1439778 AND `room_id` = 123775 LIMIT 1

//        $blackUser=RoomBlackModelDao::getInstance()->loadDataForUserIdRoomId($userId,$roomId);


//        $where = [
//            "room_id" => $roomId,
//            "user_id" => $userId,
//        ];
//        $re=RoomBlackModelDao::getInstance()->where($where)->delete();

        $re = RoomBlackModelDao::getInstance()->removeForRoomUser($roomId, $userId);

        dd($re);


        echo 'success';
        die;

//        $roomId=RoomService::getInstance()->findRoomidForUserId($userId);
//        $result= RoomModelDao::getInstance()->getOnlineGuildRoomIds();

        $d = QueryRoomService::getInstance()->queryRoomsImplSecond($roomId);
        dd($d);

//        $result = RoomModelDao::getInstance()->getOnlineGuildRoomIdsForRoomType(26);
//        dd($result);
//        $userId=1700588;
//        $userId=16997802222;
//        $models=QeuryRoomManagerDao::getInstance()->loadRoomIdsByUserId($userId);
//        dd($models);

//        echo 'success';die;
//        $result = RoomTypeModelDao::getInstance()->loadRoomType($roomType);
//        $result=RoomTypeModelDao::getInstance()->getIndexhallData();
//        $result=RoomTypeModelDao::getInstance()->roomtypeForPid($roomType);
//        $result=RoomTypeModelDao::getInstance()->getNavList();

//        $result = RoomTypeModelDao::getInstance()->getGuildTypeIds();
//        $result = RoomTypeModelDao::getInstance()->getPersonTypeIds();
//        $roomInfoModel=RoomModelDao::getInstance()->loadRoom($roomId);
////        dd($roomInfo);
//        $roomInfoData=RoomModelDao::getInstance()->modelToData($roomInfoModel);
//        $result = RoomTypeModelDao::getInstance()->RoomTypeGuild($roomInfoData);
//
//        dd($result);

//        ---
//        $managerRooms = QueryRoomService::getInstance()->queryMyManagerRoom($userId);
//        dd($managerRooms);


//        $hotRooms = QueryRoomService::getInstance()->queryHotRooms(0, 20);
//        dd($hotRooms);

//        $offset=0;
//        $pageNum=20;

//        $hotRooms = QueryRoomService::getInstance()->queryFollowRooms($userId,0, 20);
//        list($datas,$count) = QueryRoomService::getInstance()->queryFollowRooms($userId, $offset, $pageNum);

//        $hotRooms = QueryRoomService::getInstance()->queryHotRooms(0, 20);
//        dd($hotRooms);

//        $search = 123775;
//        $offset = 0;
//        $count = 20;
//        list($rooms, $count) = QueryRoomService::getInstance()->searchRoom($search, $offset, $count);
//        var_dump($count);
//        dd($rooms);

//        $roomModelList = RoomElasticDao::getInstance()->queryHotRooms($offset, $count);
//        dd($roomModelList);
//        dd($rooms);


//        $followRooms=QueryRoomFollowDao::getInstance()->getRoomIdsForUserId($userId,0,50);
//        dd($followRooms);


//        list($followRooms, $_) = QueryRoomService::getInstance()->queryFollowRooms($userId, 0, 50);

//        dd($followRooms);

//        $userId=1439778;
//        $myRoom = QueryRoomService::getInstance()->queryMyRoom($userId);
//        dd($myRoom);

//        $roomIds = [123775, 154297, 135131];
//        $roomIdsResult = RoomModelDao::getInstance()->getShowRoomids($roomIds);
//        dd($roomIdsResult);

//        $search = 123775;
//        $re = QueryRoomService::getInstance()->searchVersionRoom($search);
//        dd($re);


        echo 'success';
        die;
        $userId = 1439778;
        $roomModel = RoomModelDao::getInstance()->loadRoomByUserId($userId);
        dd($roomModel);

        $roomId = 123775;
        $roomType = QueryRoomService::getInstance()->findRoomTypeByRoomIdForCache($roomId);
        dd($roomType->roomType);


        echo 222;
        die;

//        $guildId=493;
//        $re=RoomInfoMapDao::getInstance()->getRoomIdByGuildId($guildId);

        $roomIds = [123775];
        $roomData = RoomModelDao::getInstance()->loadModelForRoomIds($roomIds);
        dd($roomData);

        $roomId = 123776;
        $model = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($model === null) {
            throw new FQException("load Room data error", 500);
        }
    }

    public function testGameResult()
    {
        $taskId = $this->request->param('taskId');
        $key = 'taojin_task_res';
        $redis = RedisCommon::getInstance()->getRedis();
        $taskStatus = $redis->hGet($key, $taskId);
        if (!empty($taskStatus)) {
            $taskRes = json_decode($taskStatus, true);
            if ($taskRes['status']['status'] == 2) {
                $taskRes['url'] = "https://recodetest.fqparty.com/static/$taskId.csv";
            }
            return rjson($taskRes);
        } else {
            return rjson([], 200, '没有该任务');
        }
    }

    public function setTaskStatus($task, $status)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $resKey = 'taojin_task_res';
        $taskData = [
            'task' => $task,
            'status' => $status,
        ];
        $redis->hSet($resKey, $task['taskId'], json_encode($taskData));
        echo 'set task status' . $task['taskId'] . ':' . json_encode($taskData);
    }

    //testgame
    public function testGame()
    {
        $loopCount = $this->request->param('cishu');
        $num = $this->request->param('num');
        $gameId = $this->request->param('gameid');
        $count = $this->request->param('count');
        $taskId = '' . getmypid() . TimeUtil::timeToStr(time(), '%Y%m%d%H%M%S');

        $subKey = 'taojin_task_sub';
        $redis = RedisCommon::getInstance()->getRedis();
        $task = [
            'taskId' => $taskId,
            'loopCount' => $loopCount,
            'num' => $num,
            'gameId' => $gameId,
            'count' => $count,
            'userId' => 1178493,
        ];
        $taskData = json_encode($task);
        Log::info('testGame=====>' . $taskData);
        $redis->rPush($subKey, $taskData);
        $this->setTaskStatus($task, [
            'status' => 0,
            'progress' => 0,
        ]);

        return header("Location:https://recodetest.fqparty.com/api/test/testgames?taskId=$taskId");

        // return rjson(['taskId' => $taskId]);

//        set_time_limit(0);
        //        $params = $this->request->param();
        //
        //        $data = [];
        //        $params['token'] = '30599e40c7a35d532241ba097f6fe5e6';
        ////        $userId = intval($params['userId']);
        ////        $gameId = intval($params['gameId']);
        ////        $times = intval($params['times']);
        ////        $count = intval($params['count']);
        //        if ($params['csv'] == 1) {
        //            $res = [];
        ////            $taojinGame = TaojinSystem::getInstance()->findTaojinByGameId($gameId);
        ////            if ($taojinGame == null) {
        ////                throw new FQException('当前游戏已关闭', 500);
        ////            }
        //
        //            for ($i=1; $i <= $params['cishu']; $i++) {
        //                $price = [];
        ////                $taojinRewards = TaojinService::getInstance()->rollDice($userId, $times, $taojinGame);
        ////                print_r($taojinRewards);die;
        //                $tmp = curlData('http://recodetest.fqparty.com/api/v1/gameaction', $params , 'POST', 'form-data');
        //                $tmp = json_decode($tmp,true);
        ////
        //                foreach ($tmp['data']['gift'] as $key => $value) {
        //                    @$res[$i]['num'] += $value['giftnum'] * $value['gift_coin'];
        //                    $data[] = [$value['gift_name'] => $value['gift_name']];
        //                }
        //            }
        //            $this->putcsv($data,$res);
        //        } else {
        //            for ($i=1; $i <= $params['cishu']; $i++) {
        //                $res = [];
        //                $price = [];
        //                echo '次数：'.$i;
        //                echo "<br>";
        //                $tmp = curlData('http://recodetest.fqparty.com/api/v1/gameaction', $params , 'POST', 'form-data');
        //                $tmp = json_decode($tmp,true);
        //
        //                foreach ($tmp['data']['gift'] as $key => $value) {
        //                    echo '个数：'.$value['giftnum'];
        //                    echo "<br>";
        //                    echo '礼物：'.$value['gift_name'];
        //                    echo "<br>";
        //                    @$res[$value['gift_name']] += $value['giftnum'];
        //                    @$price[$value['gift_name']] = $value['gift_coin'];
        //                }
        //                echo '===总数===';
        //                echo "<br>";
        //                foreach ($res as $key => $value) {
        //                    echo $key.':'.$value.'--价值：'.$value*$price[$key];
        //                    echo "<br>";
        //                }
        //                echo "<br>";
        //                echo "<br>";
        //                echo '--------------';
        //                echo "<br>";
        //
        //            }
        //        }
    }

    public function putcsv($data, $res)
    {
        foreach ($res as $key => $value) {
            foreach ($value as $k => $v) {
                $outArray['gift_name'] = '总数';
                // $outArray['giftnum'] = $value['giftnum'];
                $outArray['gift_coin'] = $v;
                @$string .= implode(",", $outArray) . "\n";
            }
            @$string .= implode(",", ['-', '-']) . "\n";
        }
        $filename = date('YmdHis') . '.csv'; //设置文件名
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        exit(mb_convert_encoding($string, "GBK", "UTF-8"));
    }

    public function combinGiftMap(&$toGiftMap, &$giftMap)
    {
        foreach ($giftMap as $giftId => $count) {
            if (array_key_exists($giftId, $toGiftMap)) {
                $toGiftMap[$giftId] += $count;
            } else {
                $toGiftMap[$giftId] = 1;
            }
        }
    }

    private function getNextRewards($step, $rewardList)
    {
        $ret = [];
        for ($n = 0; $n < 6; $n++) {
            $i = ($step + $n) % count($rewardList);
            $ret[] = $rewardList[$i];
        }
        return $ret;
    }

    public function getAssetValue($assetId, $count)
    {
        $VALUE_MAP = [
            AssetKindIds::$BEAN => 1,
            AssetKindIds::$TAOJIN_ORE_IRON => 222,
            AssetKindIds::$TAOJIN_ORE_SILVER => 1737,
            AssetKindIds::$TAOJIN_ORE_GOLD => 4380,
            AssetKindIds::$TAOJIN_ORE_FOSSIL => 11147,
        ];

        $value = ArrayUtil::safeGet($VALUE_MAP, $assetId, 0);
        return $value * $count;
    }

    public function testTaojinBaolv()
    {
        $gameId = $this->request->param('gameId');
        $taojin = TaojinSystem::getInstance()->findTaojinByGameId($gameId);
        $ret = [];
        $conf = [];
        $allTotalWeight = 0;
        $allTotalReward = 0;
        $allTotalConsume = 0;
        $totalBaolv = 0;
        for ($i = 0; $i < count($taojin->rewardList); $i++) {
            $rewardList = $this->getNextRewards($i, $taojin->rewardList);
            $totalReward = 0;
            $totalConsume = 0;
            $consumeOnce = $taojin->energy * 2;
            $totalWeight = 0;
            $stepReward = $taojin->rewardList[$i];
            $conf[] = [
                $stepReward->reward->assetId, $stepReward->reward->count, $stepReward->weight
            ];
            foreach ($rewardList as $reward) {
                $totalWeight += $reward->weight;
                $totalConsume += $reward->weight * $consumeOnce;
                $totalReward += $this->getAssetValue($reward->reward->assetId, $reward->reward->count) * $reward->weight;
            }

            $allTotalConsume += $totalConsume;
            $allTotalReward += $totalReward;
            $allTotalWeight += $totalWeight;

            $baolv = round($totalReward * 100.0 / $totalConsume, 2);
            $ret[] = [
                $i, $totalReward, $totalConsume, $totalWeight, $baolv
            ];
            $totalBaolv += $baolv;
        }

        return rjson([
            'gameId' => $gameId,
            'conf' => $conf,
            'baolv' => [
                'setps' => $ret,
                'total' => [$allTotalReward, $allTotalConsume, $allTotalWeight, round($allTotalReward * 100.0 / $allTotalConsume, 2), round($totalBaolv / 20.0, 2)]
            ]
        ]);
    }

    public function testBoxBaolv()
    {
        $userId = 1101042;
        $roomId = 120088;
        $boxId = $this->request->param('boxId');
        $count = $this->request->param('count');
        $loopCount = $this->request->param('loopCount');
        $totalGiftMap = [];
        for ($i = 0; $i < $loopCount; $i++) {
            list($giftMap, $selfSpecialGiftId, $globalSpecialGiftId) = BoxService::getInstance()->breakBox($userId, $roomId, $boxId, $count);
            $this->combinGiftMap($totalGiftMap, $giftMap);
        }
        $totalConsume = 0;
        $totalReward = 0;
        return rjson([
            'loopCount' => $loopCount,
            'count' => $count,
            'totalConsume' => $totalConsume,
            'totalReward' => $totalReward,
            'giftMap' => $totalGiftMap
        ]);
    }

    public function testRoomModelConf()
    {
        return;
//        echo 'testRoomMOdelConf';
//        $kindMap = PropSystem::getInstance()->getKindMap();
//        var_dump($kindMap);


//        $this->initRoomModeData();
//        $modeMap = RoomMode::getInstance()->getDataMap();
//        $modeMap = RoomMode::getInstance()->findRoomMode(222);
//        dd($modeMap);


//        $data=$this->initRoomTagData();
//        echo json_encode($data);
//        $modeMap = RoomTag::getInstance()->getDataMap();
        $modeMap = RoomTag::getInstance()->findRoomTag(2);
        dd($modeMap);
    }

    private function initRoomTagData()
    {
        $id = 1;
        $tag_name = "新人推荐";
        $tag_img_mua = "/images/tagcoin/xinren_mua.png";
        $tag_img_yinlian = "/images/tagcoin/xinren_yinlian.png";
        $data[] = $this->initItemRoomTagMode($id, $tag_name, $tag_img_mua, $tag_img_yinlian);

        $id = 2;
        $tag_name = "官方推荐";
        $tag_img_mua = "/images/tagcoin/guanfang_mua.png";
        $tag_img_yinlian = "/images/tagcoin/guanfang_yinlian.png";
        $data[] = $this->initItemRoomTagMode($id, $tag_name, $tag_img_mua, $tag_img_yinlian);

        $id = 3;
        $tag_name = "新人";
        $tag_img_mua = "/images/tagcoin/new_mua.png";
        $tag_img_yinlian = "/images/tagcoin/new_yinlian.png";
        $data[] = $this->initItemRoomTagMode($id, $tag_name, $tag_img_mua, $tag_img_yinlian);

        $id = 4;
        $tag_name = "为你推荐";
        $tag_img_mua = "/images/tagcoin/weinituijian_mua.png";
        $tag_img_yinlian = "/images/tagcoin/weinituijian_yinlian.png";
        $data[] = $this->initItemRoomTagMode($id, $tag_name, $tag_img_mua, $tag_img_yinlian);
        return $data;
    }

    private function initItemRoomTagMode($id, $tag_name, $tag_img_mua, $tag_img_yinlian)
    {
        return [
            'id' => $id,
            'tag_name' => $tag_name,
            'tag_img_mua' => $tag_img_mua,
            'tag_img_yinlian' => $tag_img_yinlian,
        ];
    }


    private function initRoomModeData()
    {
        $id = 1;
        $pid = 0;
        $tag_name = '聊天';
        $tag_img_mua = "/images/tagcoin/liaotianmua.png";
        $tag_img_yinlian = "/images/tagcoin/liaotianyinlian.png";
        $data[] = $this->initItemMode($id, $pid, $tag_name, $tag_img_mua, $tag_img_yinlian);

        $id = 2;
        $pid = 0;
        $tag_name = '游戏';
        $tag_img_mua = "/images/tagcoin/youximua.png";
        $tag_img_yinlian = "/images/tagcoin/youxiyinlian.png";
        $data[] = $this->initItemMode($id, $pid, $tag_name, $tag_img_mua, $tag_img_yinlian);

        $id = 3;
        $pid = 0;
        $tag_name = '狼人杀';
        $tag_img_mua = "/images/tagcoin/langrenshamua.png";
        $tag_img_yinlian = "/images/tagcoin/langrenshayinlian.png";
        $data[] = $this->initItemMode($id, $pid, $tag_name, $tag_img_mua, $tag_img_yinlian);
        return $data;
    }

    private function initItemMode($id, $pid, $tag_name, $tag_img_mua, $tag_img_yinlian)
    {
        return [
            'id' => $id,
            'pid' => $pid,
            'tag_name' => $tag_name,
            'tag_img_mua' => $tag_img_mua,
            'tag_img_yinlian' => $tag_img_yinlian,
        ];
    }

    public function giftReturnSetUser()
    {
        $userId = intval($this->request->param('userId'));
        $updateTime = $this->request->param('updateTime');
        $gotReward = $this->request->param('gotReward');
        try {
            $redis = RedisCommon::getInstance()->getRedis();
            $jstr = $redis->hget('giftreturn_user', $userId);
            $jsonObj = json_decode($jstr, true);
            if (!empty($updateTime)) {
                $jsonObj['updateTime'] = TimeUtil::strToTime($updateTime);
            }

            if (!empty($gotReward)) {
                $jsonObj['gotReward'] = intval($gotReward);
            }

            $redis->hSet('giftreturn_user', $userId, json_encode($jsonObj));

            return rjson($jsonObj);
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function giftReturnClearUser()
    {
        $userId = intval($this->request->param('userId'));
        try {
            GiftReturnUserDao::getInstance()->removeUser($userId);

            return rjson([]);
        } catch (Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    public function testYunxin()
    {
//        $a1=array("a"=>"red","b"=>"green","c"=>"blue","d"=>"yellow");
//        $a2=array("e"=>"red","f"=>"green","g"=>"blue");
//
//        $result=array_intersect($a1,$a2);
//        print_r($result);

//
//        $old = array('jpg', 'png', 'gif', 'bmp');
////        $new = array('JPG','txt','docx','bmp');
////        $new = array('png', 'gif', 'bmp');
//        $new = array('png', 'gif', 'bmp');
////        $difference = array_diff($old, $new);
//        $difference = array_diff($new, $old);
//        var_dump($difference);


//            发小秘书消息
//        $logInfo = ["中秋佳节头像框*7天"];
//        $msg = sprintf("感谢你回来！中秋将至送您%s，祝您玩的开心！", implode("", $logInfo));
//        YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => 1454687, 'type' => 0, 'msg' => $msg]);

//        新增用户的更改信息审核记录，发送小秘书消息通知
//        $unixTime = time();
//        $model = new MemberDetailAuditModel();
//        $model->userId = 1454733;
//        $model->content = "audit intro info...";
//        $model->status = 0;
//        $model->action = MemberDetailAuditActionModel::$intro;
//        $model->updateTime = $unixTime;
//        $model->createTime = $unixTime;
//        $re = MemberDetailAuditDao::getInstance()->store($model);
//        var_dump($re);

//        $re = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache(1454733,MemberDetailAuditActionModel::$wall);
//        dd($re);

//        $typeStr = MemberDetailAuditActionModel::typeToMsg(MemberDetailAuditActionModel::$wall);
//        $msg = sprintf("我们正在审核%s，请耐⼼等待！", $typeStr);
//        $re = YunXinMsg::getInstance()->sendAssistantMsg(1454687, $msg);
//        var_dump($re);
//        $userId = '1454733';
////        $album = "/Album/1439778/1616384374791.jpg,Album/1439778/1619526122.552890.jpg";
//
////        Album/1454733/1632905828.018037.jpg,Album/1454733/1633674417.794070.jpg
//        $album = "Album/1454733/1633674417.794070.jpg";
//        $re = $this->isDeletePhoto($album, $userId);
//        var_dump($re);

//        $userModel=UserModelDao::getInstance()->loadUserModel(1454733);
//        if ($userModel===null) {
//            throw new FQException("error");
//        }
//        $yunxinRe = YunxinCommon::getInstance()->updateUinfo($userModel->accId, $userModel->nickname);
//        var_dump($yunxinRe);die;


        //                更新云信
        $userId = 1021100;
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        try {
            $yunResult = YunxinCommon::getInstance()->getUinfos([$userId]);
            if (isset($yunResult['code']) && $yunResult['code'] === 200) {
                $yunxinRe = YunxinCommon::getInstance()->updateUinfo($userModel->accId, $userModel->nickname);
            } else {
                $yunxinRe = "getUinfos error";
            }
            echo sprintf('app\command\UserCancellationCheckCommand exceed updateUinfo info yunxinResult:%s', json_encode($yunxinRe));
            die;
        } catch (Exception $e) {
            echo sprintf('app\command\UserCancellationCheckCommand exceed updateUinfo Exception info userId:%d ex=%s strace:%s', $userId, $e->getMessage(), $e->getTraceAsString());
            die;
        }


    }


    /**
     * @info 是否是删除图片,新上传的背景墙是否包含在旧图中
     * @param $album string  背景墙数据
     * @param $userId int 用户id
     * @return bool
     */
    private function isDeletePhoto($album, $userId)
    {
        if (empty($album)) {
            return true;
        }
        if (empty($userId)) {
            return false;
        }
        $redis = RedisCommon::getInstance()->getRedis();
        $userKey = $this->user_info_key . $userId;
        $member_album = $redis->hget($userKey, 'album');
        if (empty($member_album)) {
            return false;
        }
        $oldAlbum = explode(",", $member_album);
        $newAlbum = explode(",", $album);
        $oldCount = count($oldAlbum);
        $newCount = count($newAlbum);
        if ($oldCount === 0 || $newCount === 0) {
            return false;
        }
        if ($newCount >= $oldCount) {
            return false;
        }
//        如果旧图和新图有差集，新图不是包含在旧图中则不是删除行为
        $difference = array_diff($newAlbum, $oldAlbum);
        if (!empty($difference)) {
            return false;
        }
        return true;
    }


    /**
     * @info 万圣节活动加豆
     */
    public function halloweenBean()
    {
        $userId = Request::param('user_id', 0, 'intval');
        $bean = Request::param('bean', 0, 'intval');
        if (empty($userId) || empty($bean)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $timestamp = HalloweenService::getInstance()->getUnixTime();
        $result = HalloweenService::getInstance()->innerAddBeanForUserId($userId, $bean, $timestamp);
        return rjson(['result' => $result], 200, 'success');
    }


    /**
     * @info 万圣节活动加豆
     */
    public function halloweenAddCandy()
    {
        $userId = Request::param('user_id', 0, 'intval');
        $candy = Request::param('candy', 0, 'intval');
        if (empty($userId) || empty($candy)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
//        $timestamp = time();
        $timestamp=HalloweenService::getInstance()->getUnixTime();
        $number = HalloweenService::getInstance()->addCandy($userId, $candy, $timestamp);
        return rjson(['data' => $number], 200, 'success');
    }


    //爆率查询接口
    public function halloweenPoolDetail()
    {
//        $key = "halloween";
        $key = HalloweenSystem::getInstance()->getRewardPoolKey();
        $redis = RedisCommon::getInstance()->getRedis();
        $data = $redis->hGetAll($key);
        $result = json_decode($data[1], true);
        return rjson($result, 200, 'success');
    }

    public function checkImg()
    {
        $userId = 1439778;
//        $imgstr = "https://image.fqparty.com/test/WechatIMG108.jpeg";

//        $imgstr='http://image2.fqparty.com/Album/1304453/1600364479017.jpg,http://image2.fqparty.com/Album/1304453/1600364479531.jpg';

//        $imgstr = "https://image.fqparty.com/attire/20201010/623b1002f4b6800a118dcc7d6fb1a1dc.png";

        $imgstr = "https://image.fqparty.com/test/WechatIMG108.jpeg,https://image.fqparty.com/test/WechatIMG108.jpeg";

        $album = $imgstr;
        $url = config('config.APP_URL_image');
        $imgstr = str_replace($url, '', $imgstr);
        $imgstr = str_replace('https://image.fqparty.com/', '', $imgstr);


        $albumArray = explode(",", $imgstr);
        list($is_safes, $albumArray) = TextcanimgCommon::getInstance()->checkImgReset($albumArray);

        if (!empty($albumArray)) {
            $album = implode(",", $albumArray);
        } else {
            $album = "";
        }
        if ($is_safes !== -1) {
//            发错误消息小秘书通知
            $msg = '您的背景墙中有违规图片无法展示';
            YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
        }

        var_dump($album);
        die;
    }


    public function loadRoomHot()
    {
        $roomId = Request::param('roomId', 0, 'intval');
        if ($roomId === 0) {
            throw new FQException("param error", 500);
        }

//        获取房间热度值
        $roomHotmodel = new GuildRoomCache($roomId);
        $sumHot = $roomHotmodel->getHotSumTpl();

//        获取房间活跃值
        $popularModel = new PopularGuildRoomCache($roomId);
        $popular = $popularModel->getHotSumTpl();

        $result['roomId'] = $roomId;
        $result['hotNumber'] = $sumHot;
        $result['popularNumber'] = $popular;
        return rjson($result, 200, 'success');


    }

    public function encryptTest()
    {
        return rjson([], 200, 'success123');
    }

    /**
     * @return \think\response\Json
     * @example djBsbiHMqRVpXQpVbYN2HGUixZUu5aGtU0AQBgdf7udBiAlDBp2gTbrDckn4cimDm8HTEnrOFJMhAbTal4vpeGgELET5fqGUVnngZYJvKkjQA4300vLh7RBjGzTG+/tZjM6sSv61JTn67GPWqyDgTM+U2tx/q7ozv6H5vOuk0euuJHRDZMc5CMPJi8mZVjpMfXFmhDYS0OHkH3Rx38vsY56ms6s/IILtGmxWCO/yS8X1BsYUXrVqUAhKlwIXKPpqhG9h5W/LffqhffYLQCWfgdqR+flVz6gtg+wCzNHOXwV356vm5O5RvrHpeaHTnwS40ktkGe0EAySiZEeLA/CjnqS8LOG/rYjrZBsfCJ0Gp4RYzdTWhwwCV5/Tem1djKxElEfbclwsTIrZHuYg0w+HhFFel4LrymFLU4uC2jvs6Bfc8TEH5Qosmbv8+82uBl3Ikel9gli11VdprJoI1F6RtUiEFYKBqed37fv6UsAKj2MRLEfKfzkntPNyZ8qFwiMAXGZjaNNUUgJmf7bV1scBF6K4Dv7vBl1OxKC26Ysi7yRBl8YCtn87/Cmpu8bnb1hb0mIJGNZrS6+59b+axGKZ5g==
     * @example djBsbiHMqRVpXQpVbYN2HGUixZUu5aGtU0AQBgdf7udBiAlDBp2gTbrDckn4cimDm8HTEnrOFJMhAbTal4vpeGgELET5fqGUVnngZYJvKkjQA4300vLh7RBjGzTG%2B%2FtZjM6sSv61JTn67GPWqyDgTM%2BU2tx%2Fq7ozv6H5vOuk0euuJHRDZMc5CMPJi8mZVjpMfXFmhDYS0OHkH3Rx38vsY56ms6s%2FIILtGmxWCO%2FyS8X1BsYUXrVqUAhKlwIXKPpqdk1du8fQlpWaaWAuv1FWBZ1SA%2FSaBkZG1lC94zdEqSUEmzzt9shDA4a5bPOGhrOP
     */
    public function requestEncryptTest()
    {
        $requestParam = \app\facade\RequestAes::param('token');
//        $requestParam = \app\facade\Requestaes::param('phone');
        return rjson(['data' => $requestParam], 200, 'success123');
    }

    public function AuthSign()
    {
        $strData = Request::param('strData', 0, '');
        $Aes = new Aes();
        $origin = $Aes->aesDecrypt($strData);
        return rjson(['strData' => $origin], 200, 'success');
    }


    public function encryptDecode()
    {
        $strData = Request::param('strData', 0, '');
        $Aes = new Aes();
        $reStr = $Aes->aesDecrypt($strData);
        $result['paramStr'] = $strData;
        $result['reStr'] = $reStr;
        return rjson($result, 200, 'success');
    }

    public function encryptEncode()
    {
        $strData = Request::param('strData', 0, '');
        $Aes = new Aes();
        $reStr = $Aes->aesEncrypt($strData);
        $result['paramStr'] = $strData;
        $result['reStr'] = $reStr;
        return rjson($result, 200, 'success');
    }

    public function testParam()
    {
        $requestParam = \app\facade\RequestAes::param('token');
//        $requestParam = \app\facade\Requestaes::param('token');
//        $requestParam = Request::param('token');
        var_dump($requestParam);
        die;
    }
}