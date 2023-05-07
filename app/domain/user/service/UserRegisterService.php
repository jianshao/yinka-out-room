<?php


namespace app\domain\user\service;


use app\common\RedisCommon;
use app\domain\adserving\AdServingModelDao;
use app\domain\dao\MemberInvitcodeLogModelDao;
use app\domain\models\MemberInvitcodeLogModel;
use app\web\service\OpenInstallService;
use think\facade\Log;

class UserRegisterService
{
    protected static $instance;
    protected $redis = null;
    protected $asoListKey = 'aso_list';

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {

            self::$instance = new UserRegisterService();
            self::$instance->redis = RedisCommon::getInstance()->getRedis();
        }
        return self::$instance;
    }

    public function asoTmpl($clientInfo) {
        try {
            $res = OpenInstallService::getInstance()->getRefereeInfo($this->buildParams($clientInfo));
        } catch (\Exception $e) {
            Log::record('aso_error---'.$e->getCode().$e->getMessage());
            $res = [];
        }
        Log::record('asoResponse---'.json_encode($res));
        if ($res) {
            $res = json_decode($res, true);
            return empty($res['promoteCode']) ? 0 : $res['promoteCode'];
        }
        return 0;
    }

    public function extend($clientInfo) {
        try {
            $res = OpenInstallService::getInstance()->getBindCode($this->buildParams($clientInfo));
            Log::record('extendResponse---'.json_encode($res));
            if ($res) {
                return empty($res['promoteCode'])||!isset($res['promoteCode']) ? 0 : $res['promoteCode'];
            }
        } catch (\Exception $e) {
            Log::record('extend_error---'.$e->getCode().$e->getMessage());
        }
        return 0;
    }

    public function buildParams($clientInfo) {
        $simulatorInfo = $clientInfo->simulatorInfo;
        $height = '';
        $width = '';
        $dpi = '';
        $key = '';
        if (!empty($simulatorInfo)) {
            $simulatorInfo = json_decode($simulatorInfo,true);
            $screen_param = $simulatorInfo['screen_param'] ?? '';
            if ($screen_param) {
                $screen_param = json_decode($screen_param,true);
                $height = $screen_param['height'];
                $width = $screen_param['width'];
                $dpi = $screen_param['dpi'];
            }
            if (isset($simulatorInfo['squeeze_sign'])) {
                $key  = $simulatorInfo['squeeze_sign'];
            }
        }
        return [
            'ip' => $clientInfo->clientIp,
            'key' => $key,
            'scWidth' => $height,
            'scHeight' => $width,
            'pixelRatio' => $dpi,
            'version' => $clientInfo->version,
            'renderer' => '',
            'promoteCode' => $clientInfo->promoteCode
        ];
    }

    public function adServingCallBack($clientInfo) {
        try {
            if ($clientInfo->idfa) {
                $this->requestCallBack($clientInfo->idfa);
            }
        } catch (\Exception $e) {
            Log::error(sprintf('adServingCallBack idfa=%s error=%s', $clientInfo->idfa, $e->getTraceAsString()));
        }
    }

    private function requestCallBack($idfa) {
        $where = ['idfa' => $idfa, 'status' => 0];
        $data = AdServingModelDao::getInstance()->findOne($where);
        if (!empty($data)) {
            $res = curlData($data['callbackaddress'], [],'GET');
            Log::info(sprintf('adServingCallBack idfa=%s response=%s:',$idfa, $res));
            if ($res) {
                $resData = json_decode($res,true);
                $status = 0;
                //兼容不同渠道的返回值
                if ((isset($resData['status']) && $resData['status'] == 'true') || $resData['code'] == 200) {
                    $status = 1;
                }

                AdServingModelDao::getInstance()->updateDataByWhere(['id' => $data['id']], ['status' => $status, 'responsemsg' => $res]);
            }
        }
    }

    /**
     * @desc 记录用户邀请码/推广码绑定
     * @param $invitcode
     * @param $uid
     * @param $room_id
     * @return int|string
     */
    public function recordMemberInvitcodeLog($invitcode,$uid,$room_id)
    {
        $invitcodeInfo = MemberInvitcodeLogModelDao::getInstance()->getUserinvitInfo($uid);
        if (!empty($invitcodeInfo)) {
            Log::error(sprintf('UserRegisterService::recordMemberInvitcodeLog info%s', json_encode($invitcodeInfo)));
            return false;
        }
        $model = new MemberInvitcodeLogModel();
        $model->invitcode = $invitcode;
        $model->uid = $uid;
        $model->roomId = $room_id;
        $model->created = time();
        return MemberInvitcodeLogModelDao::getInstance()->insertModel($model);
    }



}