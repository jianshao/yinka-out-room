<?php


namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\domain\dao\UserIdentityModelDao;
use app\domain\exceptions\FQException;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserService;
use app\service\BlackService;
use \app\facade\RequestAes as Request;
use app\common\AliPayEasyCommon;
use app\utils\CommonUtil;
use Exception;

class MemberIdentity extends ApiBaseController
{
    public function memberIdentityInit()
    {
        try {
            $uid = $this->headUid;
            $channel = $this->channel;
            //接收客户端的身份信息
            $certName = Request::param('certName');
            $certNo = Request::param('certNo');
            CommonUtil::validateIdCard($certNo);
            $biz_code = Request::param('biz_code');
            //校验该身份证是否在黑名单库
            BlackService::getInstance()->checkCertNo($certNo);
            $userModel = UserModelDao::getInstance()->loadUserModel($uid);
            $count = UserIdentityModelDao::getInstance()->getCountByCertno($certNo);
            if ($count >= 5) {
                return rjson([], 500, '同一证件最多只能认证5个账号');
            }
            if (empty($userModel)) {
                return rjson([], 500, '用户不存在');
            }
            if ($userModel->attestation == 1) {
                return rjson([], 500, '您已完成认证');
            }

            $certifyId = UserService::getInstance()->memberIdentity($uid, $certName, $certNo, $channel, $biz_code, $this->config);
            $url = AliPayEasyCommon::getInstance()->certify($certifyId);

            return rjson(['certifyId' => $certifyId, 'url' => $url],200,'认证成功',[
                'function'  => 'faceIdAuth',
                'extra'     => [
                    'userId'    => $uid,
                    'certNo'    => $certNo,
                    'certName'  => $certName,
                ]
            ]);
        } catch (FQException $e) {
            return rjson([],$e->getCode(), $e->getMessage(),[
                'function'  => 'faceIdAuth',
                'extra'     => [
                    'userId'    => $uid,
                    'certNo'    => $certNo,
                    'certName'  => $certName,
                ]
            ]);
        }
    }

    /**
     * 查询认证结果
     * @return mixed
     */
    public function queryIdentity()
    {
        try {
            $uid = $this->headUid;
            $certifyId = Request::param('certifyId');
            list($isOk, $isTeen) = UserService::getInstance()->queryIdentity($uid, $certifyId, $this->deviceId);
            if ($isOk) {
                return rjson(['isTeen' => $isTeen], 200, '认证通过',[
                    'function'  => 'queryIdentity',
                    'extra'     => [
                        'userId'    => $uid,
                        'certifyId' => $certifyId,
                    ]
                ]);
            } else {
                return rjson([], 500, '认证失败了',[
                    'function'  => 'queryIdentity',
                    'extra'     => [
                        'userId'    => $uid,
                        'certifyId' => $certifyId,
                    ]
                ]);
            }

        }catch (FQException $e) {
            return rjson([],$e->getCode(), $e->getMessage(),[
                'function'  => 'queryIdentity',
                'extra'     => [
                    'userId'    => $uid,
                    'certifyId' => $certifyId,
                ]
            ]);
        }
    }
}
