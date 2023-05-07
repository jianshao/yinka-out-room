<?php

namespace app\service;

use app\domain\dao\UserIdentityModelDao;
use app\domain\exceptions\FQException;
use app\domain\user\dao\UserBlackModelDao;
use app\utils\CommonUtil;
use app\utils\Error;
use Exception;
use think\facade\Log;

class BlackService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BlackService();
        }
        return self::$instance;
    }


    public function checkBlack($clientInfo, $userId)
    {
        try {
            //账号黑名单
            $idBlack = UserBlackModelDao::getInstance()->isBlockWithUser($userId);
            if ($idBlack != null) {
                $content = null;
                if ($idBlack->time == -1) {   //永封
                    $content = "您的账号因严重违反平台规范，已被永久封禁";
                } else if ($idBlack->endTime >= time()) {
                    $strTime = CommonUtil::time2string($idBlack->time);
                    $unsealTime = date("Y年m月d日H:i", $idBlack->updateTime + $idBlack->time);
                    $content = "因违反平台规范，您账号被禁止登录" . $strTime . "，于" . $unsealTime . "解禁。";
                }
                if (!is_null($content)) {
                    throw new FQException($content, Error::ERROR_CHECK_BLACK_FAIL);
                }
            }
            //ip黑名单
            $ipBlack = UserBlackModelDao::getInstance()->isBlockWithIp($clientInfo->clientIp);
            if ($ipBlack != null) {
                throw new FQException('很抱歉！由于该IP存在违规行为，已被永久拉入黑名单，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378', 500);
            }
            //设备黑名单
            $deviceBlack = UserBlackModelDao::getInstance()->isBlockWithDeviceId($clientInfo->deviceId);
            if ($deviceBlack != null) {
                throw new FQException('很抱歉！由于该设备存在违规行为，已被永久拉入黑名单，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378', 500);
            }
            //设备唯一标志黑名单
            $imeiBlack = UserBlackModelDao::getInstance()->isBlockWithImei($clientInfo->imei);
            if ($imeiBlack != null) {
                throw new FQException('很抱歉！由于该设备存在违规行为，已被永久拉入黑名单，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378', 500);
            }
            //身份证黑名单
            $userIdentityModel = UserIdentityModelDao::getInstance()->loadByWhere(['uid' => $userId, 'status' => 1]);
            if ($userIdentityModel != null) {
                $certBlack = UserBlackModelDao::getInstance()->isBlockWithCertNo($userIdentityModel->certno);
                if ($certBlack != null) {
                    throw new FQException('很抱歉！由于该账号存在违规行为，已被永久拉入黑名单，由此给您带来的不便请谅解，如有异议，请联系我们的客服QQ：3425184378', 500);
                }
            }
        } catch (Exception $e) {
            Log::error(sprintf('BlackService::checkBlack userId=%d clientInfo=%s ex=%d:%s',
                $userId, json_encode($clientInfo), $e->getCode(), $e->getMessage()));
            throw $e;
        }

    }

    public function checkCertNo($certNo)
    {
        try {
            $certBlack = UserBlackModelDao::getInstance()->isBlockWithCertNo($certNo);
            if ($certBlack != null) {
                throw new FQException('该身份信息存在违规，已被禁止实名', 500);
            }
        } catch (FQException $e) {
            Log::error(sprintf('BlackService::checkBlack certNo=%d ex=%d:%s',
                $certNo, $e->getCode(), $e->getMessage()));
            throw $e;
        }
    }
}