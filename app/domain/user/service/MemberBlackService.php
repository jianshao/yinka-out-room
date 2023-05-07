<?php

namespace app\domain\user\service;

use app\common\CurlApiService;
use app\common\GetuiV2Common;
use app\common\RedisCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\user\dao\UserBlackModelDao;
use app\query\user\dao\UserLastInfoDao;
use think\facade\Db;
use think\facade\Log;

class MemberBlackService
{

    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberBlackService();
        }
        return self::$instance;
    }

    //封禁类型
    public static $BLACKTYPE_IP = 1;
    public static $BLACKTYPE_DEviCEID = 2;
    public static $BLACKTYPE_CERTNO = 3;
    public static $BLACKTYPE_UID = 4;

    public static $avatar = '/useravatar/20210609/0db5690075cb34ddcc190698ac1444ec.png';

    public static $BLACKDESC = '很抱歉！由于您违反了平台相关规定已被永久拉入黑名单，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378';

    //封禁状态
    public static $BLACKSTATUS_YES = 1;
    public static $BLACKSTATUS_NO = 0;

    //封账号
    public function memberBlacks($uid, $time, $desc, $admin_id)
    {
        $curtime = time();
        if ($time != -1) {
            $end_time = $time * 86400;
            $celcEndTime = $this->celcEndTime($time, $curtime);
        } else {
            $celcEndTime = $end_time = -1;
        }
        $this->perform($uid, 4, $desc, $end_time, $uid, $curtime); //个推
        //查询此用户是否存在封号记录
        $blackModel = UserBlackModelDao::getInstance()->isBlockWithUser($uid);
        if (!empty($blackModel)){
            throw new FQException('封禁已存在', 500);
        }

        $data = [
            'user_id' => $uid,
            'type' => self::$BLACKTYPE_UID,
            'blackinfo' => $uid,
            'create_time' => $curtime,
            'update_time' => $curtime,
            'time' => $end_time,
            'status' => self::$BLACKSTATUS_YES,
            'reason' => $desc,
            'admin_id' => $admin_id,
            'end_time' => $celcEndTime,
            'blacks_time' => $curtime,
        ];

        $forbid_data = $data;
        $forbid_data['forbid_type'] = 0;
        try {
            Sharding::getInstance()->getConnectModel('commonMaster',0)->transaction(function () use ($data, $forbid_data) {
                UserBlackModelDao::getInstance()->addData($data);
                $dnName = Sharding::getInstance()->getDbName('commonMaster', 0);
                return Db::connect($dnName)->table('zb_black_log')->insert($forbid_data);
            });
        } catch (\Exception $e) {
            Log::info(sprintf('MemberBlackService memberBlacks userId=%d ex=%d:%s',
                $uid, $e->getCode(), $e->getMessage()));
        }

    }

    //到期时间
    public function celcEndTime($time, $curtime)
    {
        return $curtime + $time * 86400;
    }

    //封禁是否到期
    public function isBlacksEnd($data)
    {
        return $data['end_time'] < time() ? true : false;
    }

    //组合信息放入redis
    public function perform($uid, $type, $reason, $time, $blackinfo, $curtime)
    {
        $date = date("Y年m月d日 H:i:s");
        if ($type == 1) {
            return true;
        } elseif ($type == 2) {
            return true;
        } elseif ($type == 3) {
            return true;
        } elseif ($type == 4) {
            //组装封禁消息
            if ($time == -1) { //永封
                $content = "很抱歉！由于您" . $reason . "，违反了平台相关规定，您的账号已于" . $date . "被永久封禁，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378";
            } else {
                $unsealTime = date("Y年m月d日H:i:s", $curtime + $time);
                $content = "很抱歉！由于您" . $reason . "，违反了平台相关规定，您的账号已于" . $date . "被封禁，解封时间" . $unsealTime . "。由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378";
            }
            $uidSource = UserLastInfoDao::getInstance()->getFieldBuUserId('user_id,source', $uid);
            if ($uidSource) {
                $this->kickedOut($uidSource, $content); //踢出
            }
        }
    }

    //个推
    public function kickedOut($uidSource, $content)
    {
        $redis = RedisCommon::getInstance()->getRedis(['select' => 0]);
        foreach ($uidSource as $k => $v) {
            $token = $redis->get($v['user_id']);
            $redis->del($v['user_id']);
            $redis->del($token);
            $source = $v['source'] === 'mua' ? 'muaconfig' : 'config';
            GetuiV2Common::getInstance($source)->toSingleTransmission($v['user_id'], 1, $content);
            GetuiV2Common::getInstance($source)->toSingleTransmission2($v['user_id'], $content);
            //通知API
            CurlApiService::getInstance()->blockUserNotice($v['user_id'], 1);
//            GetuiCommon::getInstance()->pushMessageToSingle($v['id'], 1, $content,$v['source']);
        }
    }

}
