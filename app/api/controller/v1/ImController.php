<?php

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\common\RedisCommon;
use app\domain\Config;
use app\domain\dao\ImCheckMessageModelDao;
use app\domain\im\service\ImService;
use app\domain\models\ImCheckMessageModel;
use app\domain\riskwarn\RishWarnService;
use app\domain\riskwarn\RiskWarnSystem;
use app\domain\shumei\ShuMeiCheck;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\user\dao\FriendModelDao;
use app\event\PrivateChatEvent;
use app\facade\RequestAes as Request;
use app\query\site\service\SiteService;
use app\query\user\cache\UserModelCache;
use app\query\weshine\model\WeShineModel;
use app\query\weshine\service\WeShineService;
use think\facade\Log;

class ImController extends ApiBaseController
{
    public function talkBreakIce()
    {
        $redis = $this->getRedis();
        $pokeWords = $redis->get('greetmessage_cache');
        $greetMessage = json_decode($pokeWords, true);
        if (empty($greetMessage)) {
            $siteConf = SiteService::getInstance()->getSiteConf(1);
            $greetMessage = json_decode($siteConf['greet_message'], true);
            $redis->set('greetmessage_cache', $siteConf['greet_message']);
        }
        $randArr = array_rand($greetMessage, 5);
        $res = [];
        foreach ($randArr as $val) {
            $res[] = $greetMessage[$val];
        }
        return rjson($res);
    }

    /**
     * @info im消息检测第二版
     * @throws \app\domain\exceptions\FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DbException
     */
    public function imCheckSecond()
    {
        $image = Request::param('image');
        $fromUid = Request::param('fromUid');
        $toUid = Request::param('toUid');
        $textContent = Request::param('textContent');
        $voice = Request::param('voice');
        $width = Request::param('width', 0, 'intval');
        $height = Request::param('height', 0, 'intval');
        if (!$fromUid || !$toUid) {
            return rjson([], 500, '参数错误');
        }
        $time = time();
        $type = 0;
        $message = '';
        if ($image != '') {
            $type = 1;
            $message = $image;
        }
        if ($textContent != '') {
            $type = 0;
            $message = $textContent;
        }
        if ($voice != '' && $image == '') {
            $type = 2;
            $message = $voice;
        }

        //检测用户
        list($resCode, $resMessage, $apiResponse) = ImService::getInstance()->imCheckUserSecond($fromUid, $toUid, $this->deviceId);
        $resData = ['textContent' => $message, 'type' => 1];
        $machineResponse = null;
        $status = 1;
        if ($resCode == 200) {
            //检测消息
            list($resCode, $resMessage, $newMessage, $machineResponse, $apiResponse, $resData) = $this->detectionMessage($type, $message, $fromUid, $width, $height, $toUid);
//            是否发送闪萌的版本太低的提示消息
            ImService::getInstance()->checkVersionMessage($this->channel, $this->version, $toUid, $message);

            if ($resCode == 500) {
                $status = 2;
            }
        } else if ($resCode == 201) {
            //官方账户
            $resCode = 200;
        } else {
            $status = 3;
        }

        //记录消息
        $messageId = $this->recordIm($fromUid, $toUid, $type, $message, $machineResponse, $apiResponse, $status, $time);
        $resData['messageId'] = (int)$messageId;
        return rjson($resData, $resCode, $resMessage);
    }

    public function detectionMessage($type, $message, $fromUid, $width, $height, $toUid)
    {
        $res = true;
        $text = $message;

        $redis = RedisCommon::getInstance()->getRedis(["select" => 3]);
        $bannedList = $redis->sMembers("banned_cache_set");
        $replacement = "**";
        foreach ($bannedList as $v) {
            $text = str_replace($v, $replacement, $text);
            $message = str_replace($v, $replacement, $message);
        }
        $response = null;
        switch ($type) {
            case 0:
                //用户等级大于等于5级，双方为好友关系
                $userInfo = UserModelCache::getInstance()->getUserInfo($fromUid);
                $userLevel = $userInfo->lvDengji;
                $riskPrompt = '';
                if (RishWarnService::getInstance()->isRiskWarn($message)) {
                    $riskPrompt = RiskWarnSystem::getInstance()->getPrompt();
                }
                if (empty(FriendModelDao::getInstance()->loadFriendModel($fromUid, $toUid)) || $userLevel < 5) {
                    list($res, $text, $response) = ShuMeiCheck::getInstance()->imCheckText($message, ShuMeiCheckType::$TEXT_MESSAGE_EVENT, $fromUid);
                }
                $returnMsg = $res ? '返回成功' : '聊天内容包含敏感字符';
                $resData = ['textContent' => $message, 'riskPrompt' => $riskPrompt, 'type' => 1];
                break;
            case 1:
//                是否为免验证图片
                if (ShuMeiCheck::getInstance()->checkNotAuthImage($message)) {
                    $WeShineModel = new WeShineModel();
                    $WeShineModel->src = $message;
                    $WeShineModel->width = $width;
                    $WeShineModel->height = $height;
                    WeShineService::getInstance()->setHistoryShineForUser($this->headUid, $WeShineModel);
                } else {
                    list ($res, $text, $response) = ShuMeiCheck::getInstance()->imImageCheck($message, ShuMeiCheckType::$IMAGE_MESSAGE_EVENT, $fromUid);
                }
                $returnMsg = $res ? '返回成功' : '图片不合规';
                $resData = ['textContent' => $message, 'type' => 1];
                break;
            case 2:
                list ($res, $text, $response) = ShuMeiCheck::getInstance()->imAliAudioCheck($message);
                $returnMsg = $res ? '返回成功' : '语音违反平台规定';
                $resData = ['textContent' => $message, 'type' => 1];
                break;
            default :
                $returnMsg = '返回成功';
                $resData = ['textContent' => $text, 'type' => 1];
                break;
        }
        $returnCode = ($res == true) ? 200 : 500;
        return [
            $returnCode,
            $returnMsg,
            $text,
            $response,
            $returnMsg,
            $resData
        ];
    }

    public function recordIm($fromUid, $toUid, $type, $message, $machineResponse, $apiResponse, $status, $time)
    {
        $model = new ImCheckMessageModel($fromUid, $toUid, $type, $message, $machineResponse, $apiResponse, $status, $time, $time);
        event(new PrivateChatEvent($fromUid, $toUid, $time));
        $messageId = ImCheckMessageModelDao::getInstance()->addRecord($model);
        try {
            // 写入队列
            ImService::getInstance()->createMessageQueue($model, $messageId);
        } catch (\Exception $e) {
            Log::error(sprintf("recordIm ampQueue publisher error error:%s error trice:%s", $e->getMessage(), $e->getTraceAsString()));
            throw $e;
        }
        return $messageId;
    }

    /**
     * @desc 消息撤回
     * @return \think\response\Json
     */
    public function imMessageWithdraw()
    {
        $messageId = Request::param('message_id');
        if (!$messageId) {
            return rjson([], 500, '参数错误');
        }

        $userId = $this->headUid;

        try {
            ImService::getInstance()->updateRecordStatus($userId, $messageId, 4);
        } catch (\Exception $e) {
            Log::error(sprintf('ImController imMessageWithdraw Failed userId=%d message_id=%d errmsg=%d',
                $userId, $messageId, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

}