<?php

namespace app\api\controller\test;

use app\BaseController;
use app\common\GreenCommon;
use app\common\RedisCommon;
use app\domain\activity\giftReturn\GiftReturnUserDao;
use app\domain\asset\AssetKindIds;
use app\domain\chuanglan\service\ChuanglanService;
use app\domain\duke\dao\DukeModelDao;
use app\domain\duke\DukeSystem;
use app\domain\duke\model\DukeModel;
use app\domain\game\box\service\BoxService;
use app\domain\game\taojin\TaojinService;
use app\domain\game\taojin\TaojinSystem;
use app\domain\gift\GiftSystem;
use app\domain\gift\service\GiftService;
use app\domain\prop\dao\PropModelDao;
use app\domain\prop\PropSystem;
use app\domain\room\conf\RoomMode;
use app\domain\room\conf\RoomTag;
use app\domain\vip\dao\VipModelDao;
use app\facade\RequestAes as Request;
use app\form\ReceiveUser;
use app\query\room\service\QueryRoomService;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use Jobby\Exception;
use think\facade\Log;

class TestChuanglanController extends BaseController
{
    /**
     * @info
     * @return \think\response\Json
     */
    public function chuanglansms()
    {
//        $code = mt_rand(100000, 999999);
//        $msg = sprintf('【音恋语音】您好！验证码是:%d 退订回T', $code);
//        $phone = '15810501263';
//        $re = ChuanglanService::getInstance()->sendSMS($phone, $msg);
//        var_dump($re);
//        die;

//
//        $phone = '15810501263';
////        $msg = '【音恋语音】{$var}，您在音恋语音收到新的留言，快去看看吧～点击查看：rongqii.cn 退订回T';
//        $msg = '【音恋语音】{$var}，赠您免费中秋好礼~登录后可在背包查看rongqii.cn/{$var}取关回T';
//        $name = '橙七七';
//        $uid = 1439778;
//        $param = sprintf("%s,%s,%s;", $phone, $name, $uid);
//        $re = ChuanglanService::getInstance()->sendVariableSMS($msg, $param);
//        var_dump($re);

//        $name = "小红红";
//        $phone = '15810501263';
//        $re = ChuanglanService::getInstance()->sendMarketing($phone, $name);
//        var_dump($re);
//        die;

//        $re=ChuanglanService::getInstance()->queryBalance();
//        dd($re);die;


//        $phoneList = ['13080743998', '13347775555', '13130313569', '18733815518', '18811310446'];
        $phoneList = ['15810501263'];
        var_dump($phoneList);
        die;
        $msg = '【音恋语音】{$var},来领动态头像框~已放入您的背包rongqii.cn/{$var}拒D';
        $name = '圣胡安可可以妮';
        foreach ($phoneList as $phone) {
            $uid = 1439778;
            $param = sprintf("%s,%s,%s;", $phone, $name, $uid);
            $re = ChuanglanService::getInstance()->sendVariableSMS($msg, $param);
            echo sprintf("phone %d result:%s", $phone, json_encode($re)) . PHP_EOL;
        }

        return rjsonFit();
    }


}