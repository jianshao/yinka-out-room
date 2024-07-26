<?php

namespace app\api\controller\v1;


use app\common\RedisCommon;
use app\domain\sound\dao\SoundRecordModel;
use app\domain\sound\service\SoundService;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use constant\SoundConstant;


class SoundController extends ApiBaseController
{
    /**
     * 随机获取一个录音
     */
    public function randRecord()
    {
        $type = Request::param('type');
        if (!$type) {
            return rjson([], 500, '参数错误');
        }
        $info = SoundRecordModel::getInstance()->getModel()->where([
            "is_delete" => 1,
            "type" => $type,
        ])->orderRand()->find();
        if (!empty($info)) {
            $info = $info->toArray();
        }

        $result = [
            'sound_record' => $info,
        ];
        return rjson($result);
    }

    public function soundList()
    {
        $nextSoundId = Request::param('next_sound_id', 0);
        $size = Request::param('size', 10);

        $userId = intval($this->headUid);

        try {
            // 获取当前日期
            $today = date("Y-m-d");

            // 获取当前用户今天已经匹配了几次
            $matchedTimes = SoundService::getInstance()->getMatchedTimes($userId, $today);
            $leaveSoundIDs = SoundService::getInstance()->getSoundMatchUserLeave($userId, $today);

            if (empty($leaveSoundIDs)){
                $soundList = SoundService::getInstance()->getSoundListByCache($userId, $nextSoundId, $size);
                // 更新当前用户今天已经匹配的次数
                SoundService::getInstance()->updateMatchedTimes($userId, $today, $matchedTimes + 1);
                $matchedTimes = SoundService::getInstance()->getMatchedTimes($userId, $today);
            } else {
                $soundList = SoundService::getInstance()->getSoundListByIDs($leaveSoundIDs);
            }
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

        $result = [
            'sound_list' => $soundList,
            'next_sound_id' => (int)$nextSoundId,
            'has_more' => false,
            'current_match_times' => SoundConstant::MATCH_TIMES_TOTAL - $matchedTimes,
        ];
        return rjson($result);
    }

    public function soundMatch()
    {
        $userId = intval($this->headUid);
        $nextSoundId = Request::param('next_sound_id', 0);
        $size = Request::param('size', 10);

        try {
            // 获取当前日期
            $today = date("Y-m-d");

            // 获取当前用户今天已经匹配了几次
            $matchedTimes = SoundService::getInstance()->getMatchedTimes($userId, $today);
            // 判断当前用户是否还有匹配机会
            if ($matchedTimes >= SoundConstant::MATCH_TIMES_TOTAL) {
                return rjson([], 500, "您今天已经用完了三次匹配机会，请明天再来吧！");
            }
            $soundList = SoundService::getInstance()->getSoundListByCache($userId, $nextSoundId, $size);
            if (empty($soundList)) {
                return rjson([], 500, "没有更多的录音，请稍后再来吧！");
            }
            // 更新当前用户今天已经匹配的次数
            SoundService::getInstance()->updateMatchedTimes($userId, $today, $matchedTimes + 1);

            $result = [
                'sound_list' => $soundList,
                'next_sound_id' => (int)$nextSoundId,
                'has_more' => false,
                'current_match_times' => SoundConstant::MATCH_TIMES_TOTAL - $matchedTimes - 1,
            ];
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

        return rjson($result);
    }

    public function soundLike()
    {
        $soundID = Request::param('sound_id');
        if (!$soundID) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            SoundService::getInstance()->soundLike($userId, $soundID);
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

        return rjson();
    }

    public function soundCancel()
    {
        $soundID = Request::param('sound_id');
        if (!$soundID) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            SoundService::getInstance()->soundCancel($userId, $soundID);
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }

        return rjson();
    }

    public function checkOperationCode()
    {
        $userId = intval($this->headUid);
        $operatingCode = Request::param('operating_code');
        if (!$operatingCode) {
            return rjson([], 500, '参数错误');
        }
        try {
            $cachekey = "soundOpcodeConf";
            $cache = RedisCommon::getInstance()->getRedis();
            $data = $cache->get($cachekey);
            $list = json_decode($data, true);
            $isPass = false;
            if (in_array($operatingCode, $list)) {
                $isPass = true;
            }
        } catch (\Exception $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
        $result = [
            "is_pass" => $isPass
        ];

        return rjson($result);
    }
}