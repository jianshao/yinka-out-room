<?php

namespace app\web\controller\wechat;

use app\BaseController;
use app\domain\exceptions\FQException;
use app\domain\user\service\UnderAgeService;
use app\domain\user\service\UserLoginService;
use app\domain\withdraw\dao\UserWithdrawBankInformationModelDao;
use app\domain\withdraw\service\AgentPayService;
use app\facade\RequestAes as Request;
use app\form\ClientInfo;
use app\utils\Error;
use app\view\WithDrawView;
use think\facade\Log;

class UserLoginController extends BaseController
{
    public function initialize()
    {
        parent::initialize();
    }


    /**
     * @info  根据用户账号id获取用户头像和昵称
     * @data 1注册 2更改手机号（登录） 4申请家族 5注销账号 6 other 9 提现登录
     * @return \think\response\Json
     * @throws \app\domain\exceptions\FQException
     */
    public function sendsms()
    {
        $phone = Request::param('phone');
        $type = Request::param('type', 9, "intval");
        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($this->request);
        $ttlSecond = UserLoginService::getInstance()->serviceSendSms($clientInfo, $this->headUid, $phone, $type);
        return rjson(['ttl' => (int)$ttlSecond], 200, '验证码发送成功');
    }

    /**
     * @info 登录
     * 0 未提交 1认证 2未通过 3审核中
     * @return \think\response\Json
     * @throws \think\Exception
     */
    public function login()
    {
        $params = Request::param();
        if (empty($params['username']) || empty($params['verify'])) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        try {
            $clientInfo = new ClientInfo();
            $clientInfo->fromRequest($this->request);
            $clientInfo->simulatorInfo = isset($params['simulator_info']) ? $params['simulator_info'] : '';
            $user = UserLoginService::getInstance()->loginByMobileForWithdraw($params['username'], $params['verify'], $clientInfo);
            if ($user === null) {
                throw new FQException("登录失败 用户信息异常", 500);
            }
            // 已实名并且未成年限制操作
            $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($user->getUserId());
            $userBankModel = UserWithdrawBankInformationModelDao::getInstance()->loadModelHoverForUserId($user->getUserId());
            list($diamond, $banlance) = AgentPayService::getInstance()->loadUserAssetsForUid($user->getUserId());
            $result = [
//                用户信息
                'userInfo' => WithDrawView::viewUserInfo($user->getUserModel(), $user->getToken()),
//                用户余额
                'assets' => WithDrawView::viewUserAssets($diamond, $banlance),
//                提现银行信息
                'withDrawBankInfo' => WithDrawView::viewWithDrawBank($userBankModel),
//                实名状态
                'isUnderAge' => $isUnderAge,
            ];
            return rjson($result, 200, "success");
        } catch (FQException $e) {
            Log::warning(sprintf('UserLoginController::login ex=%d:%s', $e->getCode(), $e->getMessage()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

}
