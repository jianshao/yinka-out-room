<?php

namespace app\domain\withdraw\service;

//三方支付service
use app\common\FileCipher;
use app\core\mysql\Sharding;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\bi\BIConfig;
use app\domain\bi\BIReport;
use app\domain\dao\UserCardModelDao;
use app\domain\dao\UserIdentityModelDao;
use app\domain\exceptions\FQException;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\open\service\EsignService;
use app\domain\user\dao\DiamondModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UnderAgeService;
use app\domain\user\UserRepository;
use app\domain\withdraw\dao\UserWithdrawBankInformationModelDao;
use app\domain\withdraw\dao\UserWithdrawDetailModelDao;
use app\domain\withdraw\dao\UserWithdrawInfoLogModel;
use app\domain\withdraw\dao\UserWithdrawInfoModelDao;
use app\domain\withdraw\dao\WithdrawUserDao;
use app\domain\withdraw\model\UserWithdrawBankInformationModel;
use app\domain\withdraw\model\UserWithdrawBankInformationPayType;
use app\domain\withdraw\model\UserWithdrawDetailModel;
use app\domain\withdraw\model\UserWithdrawDetailOrderStatus;
use app\domain\withdraw\model\UserWithdrawInfoModel;
use app\domain\withdraw\model\UserWithdrawInfoModelStauts;
use app\domain\withdraw\model\WithdrawUser;
use app\query\user\cache\UserModelCache;
use app\service\BlackService;
use app\service\LockService;
use app\utils\CommonUtil;
use think\facade\Log;
use think\file\UploadedFile;


class AgentPayService
{

    protected static $instance;
    public $maxWithDrawAmount = 98000;
    private $userIdFlag = 1400000;  //老用户认证表最大用户uid标识

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AgentPayService();
        }
        return self::$instance;
    }

    private function markDestPath($fileDir, UploadedFile $uploadObject, $headUid)
    {
        $originName = $uploadObject->getOriginalName();
        $pathInfo = pathinfo($originName);
        if (empty($pathInfo)) {
            throw new FQException("无法识别图片，请重试", 500);
        }
        $mtRand = mt_rand(1, 999);
        $extension = $pathInfo['extension'] ?? "";
        $filename = sprintf("%s%s%d", $pathInfo['filename'] ?? "", $headUid, $mtRand);
        $pathDir = sprintf("%s/%s", "./storage", $fileDir);
        if (!is_dir($pathDir)) {
            mkdir($pathDir, 0777, true);
        }
        return sprintf("%s/%s.%s", $pathDir, md5($filename), $extension);
    }

    /**
     * @param UploadedFile $identityCardFrontSrc
     * @param UploadedFile $identityCardOppositeSrc
     * @param $headUid
     * @return array
     * @throws FQException
     * @throws \OSS\Core\OssException
     * @throws \Throwable
     */
    public function encryptIdentityCardUpload(UploadedFile $identityCardFrontSrc, UploadedFile $identityCardOppositeSrc, $headUid)
    {
        $fileCipher = new FileCipher(base64_decode('aa4BtZ4tspm2wnXLb1ThQA=='), '1234567890123456', 1024 * 1024, "0001");
        $fileDir = "identity";
        $identityCardFrontDest = $this->markDestPath($fileDir, $identityCardFrontSrc, $headUid);
        $identityCardOppositeDest = $this->markDestPath($fileDir, $identityCardOppositeSrc, $headUid);
//        加密，
        $fileCipher->encryptFile($identityCardFrontSrc->getPathname(), $identityCardFrontDest);
        $fileCipher->encryptFile($identityCardOppositeSrc->getPathname(), $identityCardOppositeDest);

        $frontDestUrl = uploadOssFileSecond($identityCardFrontDest, $fileDir);
        $oppositeDestUrl = uploadOssFileSecond($identityCardOppositeDest, $fileDir);
        return [$frontDestUrl, $oppositeDestUrl];
    }


    /**
     * @info 查看用户提现信息列表，并且返回默认选中的条目
     * @param $userId
     * @return array|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withDrawBankShow($userId)
    {
        if (empty($userId)) {
            return null;
        }
        $modelList = UserWithdrawBankInformationModelDao::getInstance()->loadModelListForUserId($userId);
        $result = [];
//        默认选中的放在第一个位置
        foreach ($modelList as $itemModel) {
            if ($itemModel->defaultHover === 1) {
                array_unshift($result, $itemModel);
            } else {
                $result[] = $itemModel;
            }
        }
        return $result;
    }


    /**
     * @param $bankCardNumber
     * @return bool
     * @throws FQException
     */
    public function filterBankCardName($bankCardNumber)
    {
        $pattern = "/^[A-Za-z0-9]*$/";
        if (preg_match($pattern, $bankCardNumber)) {
            return true;
        }
        $pattern='/^[_A-Za-z0-9-]+(\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*(\.[A-Za-z]{2,})$/';
        if (preg_match($pattern, $bankCardNumber)) {
            return true;
        }

        throw new FQException("账号非法,请检查重试", 500);
    }

    /**
     * @info 添加打款的银行信息
     * @param $userId
     * @param $bankName
     * @param $bankCardNumber
     * @param $payType
     * @return int|string|null
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withDrawBankStore($userId, $username, $bankName, $bankCardNumber, $payType)
    {
        if (empty($username) || empty($bankCardNumber)) {
            return null;
        }

        $this->filterBankCardName($bankCardNumber);

//        检查 提交的用户姓名和用户身份认证的用户姓名是否一致
        $userWithdrawInfoModel = UserWithdrawInfoModelDao::getInstance()->loadDataForSuccess($userId);
        if (empty($userWithdrawInfoModel->realName)) {
            throw new FQException("认证信息错误，请联系客服", 500);
        }
        if ($userWithdrawInfoModel->realName !== $username) {
            throw new FQException("真实姓名必须和认证身份一致，请检查重试", 409);
        }
        $payTypeList = [UserWithdrawBankInformationPayType::$ZHIFUBAO, UserWithdrawBankInformationPayType::$YINHANGKA];
        if (!in_array($payType, $payTypeList)) {
            throw new FQException("支付方式错误，请检查重试", 409);
        }
//        存库
        $unixTime = time();
        $model = new UserWithdrawBankInformationModel();
        $model->userId = $userId;
        $model->username = $username;
        $model->bankName = $bankName;
        $model->bankCardNumber = $bankCardNumber;
        $model->payType = $payType;
        $model->defaultHover = 0;   //  默认选中状态 default:0 [0假,1真]
        $model->createTime = $unixTime;
        $model->updateTime = 0;
        try {
            return UserWithdrawBankInformationModelDao::getInstance()->storeModel($model);
        } catch (\Exception $e) {
            if ($e->getCode() === 10501) {
                throw new FQException("操作失败 重复添加", 500);
            }
            throw $e;
        }
    }

    /**
     * @param $userId
     * @param $id
     * @return bool
     */
    public function withDrawBankDelete($userId, $id)
    {
        if (empty($userId) || empty($id)) {
            return false;
        }
//        删除条目
        return UserWithdrawBankInformationModelDao::getInstance()->deleteModel($userId, $id);
    }

    /**
     * @param $userId
     * @param $id
     * @param $setEmpty
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withDrawBankSetDefault($userId, $id, $setEmpty)
    {
        if (empty($userId) || empty($id)) {
            return false;
        }
//        设置条目默认值
        return UserWithdrawBankInformationModelDao::getInstance()->changeDefaultForId($userId, $id, $setEmpty);
    }

    /**
     * @param $headUid
     * @param $phone
     * @param $identityCardFrontSrc
     * @param $identityCardOppositeSrc
     * @return int|string
     * @throws FQException
     * @throws \OSS\Core\OssException
     * @throws \Throwable
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function storeWithDrawUserInfo($headUid, $phone, UploadedFile $identityCardFrontSrc, UploadedFile $identityCardOppositeSrc)
    {
        if (empty($headUid)) {
            throw new FQException("用户信息错误", 500);
        }
        //        监测用户数据是否存在，如果存在报错
        $UserWithdrawInfoModel = UserWithdrawInfoModelDao::getInstance()->loadModel($headUid);
        if ($UserWithdrawInfoModel !== null && $UserWithdrawInfoModel->status == UserWithdrawInfoModelStauts::$AUDIT) {
            throw new FQException("添加失败不能重复添加", 500);
        }
        if ($UserWithdrawInfoModel !== null && $UserWithdrawInfoModel->status == UserWithdrawInfoModelStauts::$SUCCESS) {
            throw new FQException("您已认证成功了不能重复提交", 500);
        }

        $userIdentityModel = $this->loadAuditWithUserIdentityOrUserCard($headUid);
        if ($userIdentityModel === null) {
            throw new FQException("用户认证信息异常", 500);
        }

        $userWithdrawInfoModel = new UserWithdrawInfoModel();
        $userWithdrawInfoModel->userId = $headUid;
        $userWithdrawInfoModel->realPhone = $phone;
//        处理用户身份证 加密，上传oss
        list($userWithdrawInfoModel->identityCardFront, $userWithdrawInfoModel->identityCardOpposite) = AgentPayService::getInstance()->encryptIdentityCardUpload($identityCardFrontSrc, $identityCardOppositeSrc, $headUid);

        $unixTime = time();
        $userWithdrawInfoModel->identityNumber = $userIdentityModel->certno;
        $userWithdrawInfoModel->realName = $userIdentityModel->certName;
        $userWithdrawInfoModel->status = UserWithdrawInfoModelStauts::$AUDIT;
        $userWithdrawInfoModel->snsUserId = 0;
        $userWithdrawInfoModel->createTime = $unixTime;
        $userWithdrawInfoModel->updateTime = 0;
        return UserWithdrawInfoModelDao::getInstance()->storeModel($userWithdrawInfoModel);
    }


    /**
     * @info 获取用户实名认证信息，从identity表或usercard老表
     * @param $headUid
     * @return \app\domain\models\UserIdentityModel|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function loadAuditWithUserIdentityOrUserCard($headUid)
    {
        $userIdentityModel = UserIdentityModelDao::getInstance()->loadModelForUserId($headUid);
        if ($userIdentityModel === null && $headUid < $this->userIdFlag) {
            $userIdentityModel = UserCardModelDao::getInstance()->loadModelForUserId($headUid);
        }
        return $userIdentityModel;
    }

    private function buildWithDrawApplyLockKey($userId)
    {
        return sprintf("withDrawApplyUserLockKey:%d", $userId);
    }

    /**
     * @param $userId
     * @return \app\domain\user\model\UserModel
     * @throws FQException
     */
    public function loadWithdrawUserModel($userId)
    {
        if (empty($userId)) {
            throw new FQException("用户信息异常", 500);
        }
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户信息异常", 500);
        }

//        是否实名
        if ((int)$userModel->attestation !== 1) {
            throw new FQException("登录失败 实名认证异常请检查", 500);
        }

//        是否为公会用户
        $guildId = MemberSocityModelDao::getInstance()->getGuidIdByUserId($userId);
        if ($userModel->guildId === 0 && $guildId === 0) {
            throw new FQException("您不是公会用户不能提现", 500);
        }

//        是否满18周岁
        $isUnderAge = UnderAgeService::getInstance()->underAgeProhibit($userId);
        if ($isUnderAge) {
            throw new FQException("未满18周岁用户暂不支持此功能", 500);
        }
        return $userModel;
    }


    /**
     * @param $userId int 用户id
     * @param $amount string 提现金额
     * @param $bid int 提现账号id
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function withDrawApply($userId, $amount, $bid, $clientInfo)
    {
        if (empty($userId) || empty($amount) || empty($bid)) {
            return false;
        }
        $withDrawInfoModel = UserWithdrawInfoModelDao::getInstance()->loadDataForSuccess($userId);
        if (empty($withDrawInfoModel->userId)) {
            throw new FQException("用户认证信息异常请检查", 500);
        }
        $withDrawBankInfoModel = UserWithdrawBankInformationModelDao::getInstance()->loadModel($bid);
        if ($withDrawBankInfoModel === null) {
            throw new FQException("银行打款信息异常请检查", 500);
        }
        if ($withDrawBankInfoModel->userId !== $userId) {
            throw new FQException("认证信息错误请检查", 500);
        }
//        已实名并且未成年限制操作
        AgentPayService::getInstance()->loadWithdrawUserModel($userId);
        //黑名单检测
        BlackService::getInstance()->checkBlack($clientInfo, $userId);

        $timestamp = time();
        $userRole = WithdrawUserDao::getInstance()->loadUserRole($userId);
        if ($userRole === WithdrawUser::$NormalUser) {
            list($UserWithdrawDetailModel, $diamondAmount) = $this->withdrawApplyNormalUser($userId, $amount, $withDrawInfoModel, $withDrawBankInfoModel, $timestamp, $userRole);
        } else {
            list($UserWithdrawDetailModel, $diamondAmount) = $this->withdrawApplySpecialUser($userId, $amount, $withDrawInfoModel, $withDrawBankInfoModel, $timestamp, $userRole);
        }
        $lockKey = $this->buildWithDrawApplyLockKey($userId);
        LockService::getInstance()->lock($lockKey);
//        用户钻石 提现预扣除
        try {
            $consumeRe = Sharding::getInstance()->getConnectModel('userMaster', $userId)
                ->transaction(function () use ($userId, $timestamp, $diamondAmount, $UserWithdrawDetailModel) {
                    $user = UserRepository::getInstance()->loadUser($userId);
                    if ($user === null) {
                        throw new FQException("用户不存在", 500);
                    }
                    //注销检测
                    if ($user->getUserModel()->isCancel) {
                        throw new FQException('用户已注销', 500);
                    }
                    if ($user->getAssets()->balance(AssetKindIds::$DIAMOND, $timestamp) < $diamondAmount) {
                        throw new FQException("钻石数量不足", 500);
                    }
                    $biEvent = BIReport::getInstance()->makeWithdrawPretakeoffBIEvent($UserWithdrawDetailModel->orderNumber, $UserWithdrawDetailModel->orderPrice);
                    return $user->getAssets()->consume(AssetKindIds::$DIAMOND, $diamondAmount, $timestamp, $biEvent);
                });
            $storeRe = Sharding::getInstance()->getConnectModel('commonMaster', $userId)->transaction(function () use ($UserWithdrawDetailModel) {
                return UserWithdrawDetailModelDao::getInstance()->storeModel($UserWithdrawDetailModel);
            });
            Log::info(sprintf('AgentPayService.withDrawApply ok userId=%d orderId=%d timestamp:%d price:%d diamondAmount:%d storeRe:%d consumeRe:%s', $userId, $UserWithdrawDetailModel->orderNumber, $timestamp, $UserWithdrawDetailModel->orderPrice, $diamondAmount, $storeRe, json_encode($consumeRe)));
            return $storeRe;
        } catch (\Exception $e) {
            Log::error(sprintf('AgentPayService withDrawApply userId=%d ex=%d:%s', $userId, $e->getCode(), $e->getMessage()));
            throw $e;
        } finally {
            LockService::getInstance()->unlock($lockKey);
        }
    }

    /**
     * @param $userId
     * @return string
     */
    private function loadOrderNumber($userId)
    {
        return CommonUtil::createUuIDShort(sprintf("withdraw:%d", $userId));
    }

    /**
     * @info 白名单用户信息提现
     * @param $userId
     * @param $amount
     * @param UserWithdrawInfoModel $withDrawInfoModel
     * @param UserWithdrawBankInformationModel $withDrawBankInfoModel
     * @param $timestamp
     * @param $userRole
     * @return array
     * @throws FQException
     */
    public function withdrawApplySpecialUser($userId, $amount, $withDrawInfoModel, $withDrawBankInfoModel, $timestamp, $userRole)
    {
//        白名单用户 银行卡提现需走esign 提现记录认证
        if ($withDrawBankInfoModel->payType === UserWithdrawBankInformationPayType::$YINHANGKA) {
            $signRe = $this->esignHandler($withDrawInfoModel, $withDrawBankInfoModel);
//            验证失败return
            if ($signRe === false) {
                throw new FQException("提现信息银行3要素认证失败,请重试", 500);
            }
        }
        //        创建提现数据，配置
        $strDate = date("Y-m");
        $rate = config("config.khd_scale") ?? 10000;
        $diamondAmount = $amount * $rate;
        $UserWithdrawDetailModel = new UserWithdrawDetailModel();
        $UserWithdrawDetailModel->userId = $userId;
        $UserWithdrawDetailModel->orderNumber = $this->loadOrderNumber($userId);
        $UserWithdrawDetailModel->orderPrice = $amount;
        $UserWithdrawDetailModel->snsOrderNumber = "";
        $UserWithdrawDetailModel->username = $withDrawBankInfoModel->username;
        $UserWithdrawDetailModel->bankName = $withDrawBankInfoModel->bankName;
        $UserWithdrawDetailModel->bankCardNumber = $withDrawBankInfoModel->bankCardNumber;
//        支付类型
        $UserWithdrawDetailModel->payType = $withDrawBankInfoModel->payType;
        $UserWithdrawDetailModel->userRole = $userRole;
        $UserWithdrawDetailModel->snsAgentName = "";
        $UserWithdrawDetailModel->snsAgentResponse = "";
        $UserWithdrawDetailModel->orderStatus = UserWithdrawDetailOrderStatus::$AUDIT;
        $UserWithdrawDetailModel->messageDetail = "";
        $UserWithdrawDetailModel->createTime = $timestamp;
        $UserWithdrawDetailModel->updateTime = 0;
        $UserWithdrawDetailModel->callbackTime = 0;
        $UserWithdrawDetailModel->dateStrMonth = $strDate;
        $UserWithdrawDetailModel->diamond = $diamondAmount;
        $UserWithdrawDetailModel->identityNumber = $withDrawInfoModel->identityNumber;
        $UserWithdrawDetailModel->realPhone = $withDrawInfoModel->realPhone;
        return [$UserWithdrawDetailModel, $diamondAmount];
    }


    /**
     * @info e融的验证 ,获取bankinfo的银行信息验证状态 （5次 超过5次失败，不再认证，直接失败） 0 1 2   [0 未认证 ,1 认证成功,2 认证失败]
     * @param UserWithdrawInfoModel $withDrawInfoModel
     * @param UserWithdrawBankInformationModel $withDrawBankInfoModel
     * @return bool  标记验证的结果状态  【成功，失败】
     */
    private function esignHandler(UserWithdrawInfoModel $withDrawInfoModel, UserWithdrawBankInformationModel $withDrawBankInfoModel)
    {
//        如果已经验证过了，返回验证结果
        if ($withDrawBankInfoModel->verifyStatus === UserWithdrawBankInformationModelDao::$VERIFY_STATUS_SUCCESS) {
            return true;
        }
//        没有验证过则查看验证次数 ，超过5次 return
        if ($withDrawBankInfoModel->verifyCount > 5) {
            throw new FQException("提现信息银行3要素认证失败,验证超过次数", 500);
        }
//        调用三方接口验证银行三要素
        $result = $this->doBank3Factors($withDrawInfoModel->identityNumber, $withDrawBankInfoModel->username, $withDrawBankInfoModel->bankCardNumber);
//        验证失败标记状态
        if ($result === false) {
            UserWithdrawBankInformationModelDao::getInstance()->incrVerifyCountForId($withDrawBankInfoModel->id, UserWithdrawBankInformationModelDao::$VERIFY_STATUS_ERROR, 1);
        } else {
            UserWithdrawBankInformationModelDao::getInstance()->updateStatusForId($withDrawBankInfoModel->id, UserWithdrawBankInformationModelDao::$VERIFY_STATUS_SUCCESS);
        }
        return $result;
    }

    /**
     * @param $identityNumber
     * @param $username
     * @param $bankCardNumber
     * @return bool   成功 true 失败 false
     * @example  EsignService::getInstance()->bank3Factors("510321199106120019","胡洋","6210676862306686101");
     */
    private function doBank3Factors($identityNumber, $username, $bankCardNumber)
    {
        $reStr = EsignService::getInstance()->bank3Factors($identityNumber, $username, $bankCardNumber);
        if (empty($reStr)) {
            return false;
        }
        $reArr = json_decode($reStr, true);
        $code = $reArr['code'] ?? null;
        if ($code !== 0) {
            return false;
        }
        return true;
    }

    /**
     * @info 普通用户提现
     * @param $userId
     * @param $amount
     * @param UserWithdrawInfoModel $withDrawInfoModel
     * @param UserWithdrawBankInformationModel $withDrawBankInfoModel
     * @param $timestamp
     * @param $userRole
     * @return array
     * @throws FQException
     */
    private function withdrawApplyNormalUser($userId, $amount, $withDrawInfoModel, $withDrawBankInfoModel, $timestamp, $userRole)
    {
//        验证用户身份证提交的金额当月够不够9.8万
        $history_amount = UserWithdrawDetailModelDao::getInstance()->loadUserMonthAmountSum($withDrawInfoModel->identityNumber);
        $history_amount_all = $history_amount + (int)$amount;
        if ($history_amount_all > $this->maxWithDrawAmount) {
            throw new FQException("额度超限，请联系客服", 500);
        }
//        创建提现数据，配置
        $strDate = date("Y-m");
        $rate = config("config.khd_scale") ?? 10000;
        $diamondAmount = $amount * $rate;
        $UserWithdrawDetailModel = new UserWithdrawDetailModel();
        $UserWithdrawDetailModel->userId = $userId;
        $UserWithdrawDetailModel->orderNumber = $this->loadOrderNumber($userId);
        $UserWithdrawDetailModel->orderPrice = $amount;
        $UserWithdrawDetailModel->snsOrderNumber = "";
        $UserWithdrawDetailModel->username = $withDrawBankInfoModel->username;
        $UserWithdrawDetailModel->bankName = $withDrawBankInfoModel->bankName;
        $UserWithdrawDetailModel->bankCardNumber = $withDrawBankInfoModel->bankCardNumber;
//        支付类型
        $UserWithdrawDetailModel->payType = $withDrawBankInfoModel->payType;
        $UserWithdrawDetailModel->userRole = $userRole;
        $UserWithdrawDetailModel->snsAgentName = "";
        $UserWithdrawDetailModel->snsAgentResponse = "";
        $UserWithdrawDetailModel->orderStatus = UserWithdrawDetailOrderStatus::$AUDIT;
        $UserWithdrawDetailModel->messageDetail = "";
        $UserWithdrawDetailModel->createTime = $timestamp;
        $UserWithdrawDetailModel->updateTime = 0;
        $UserWithdrawDetailModel->callbackTime = 0;
        $UserWithdrawDetailModel->dateStrMonth = $strDate;
        $UserWithdrawDetailModel->diamond = $diamondAmount;
        $UserWithdrawDetailModel->identityNumber = $withDrawInfoModel->identityNumber;
        $UserWithdrawDetailModel->realPhone = $withDrawInfoModel->realPhone;
        return [$UserWithdrawDetailModel, $diamondAmount];
    }

    /**
     * @param int $userId
     * @return array [钻石余额,人民币余额]
     */
    public function loadUserAssetsForUid($userId)
    {
        $diamondBalance = DiamondModelDao::getInstance()->loadDiamond($userId)->balance();
        $rate = config("config.khd_scale") ?? 10000;
        if (empty($diamondBalance)) {
            $amount = 0;
            $diamondBalance = 0;
        } else {
            $amount = $diamondBalance / $rate;
            $diamondBalance = $diamondBalance / $rate;
        }
        return [$diamondBalance, $amount];
    }

    public function withDrawOrderList($userId, $strDate, $page, $pageNum)
    {
        if (empty($userId)) {
            return [];
        }
        return UserWithdrawDetailModelDao::getInstance()->loadModelForUserId($userId, $strDate, $page, $pageNum);
    }

    public function withDrawOrderTotalPrice($userId, $strDate)
    {
        return UserWithdrawDetailModelDao::getInstance()->loadOrderTotalPrice($userId, $strDate);
    }

    /**
     * @info 初始化用户提现认证信息表
     * Front opposite
     */
    public function makeUserWithdrawInfo()
    {
        $unixTime = time();
        $data = [];
        $data['id'] = 1;
        $data['user_id'] = 1;
        $data['sns_user_id'] = 1;
        $data['identity_card_front'] = "http://www.baidu.com";
        $data['identity_card_opposite'] = "http://www.baidu.com";
        $data['real_phone'] = "158105012";
        $data['real_name'] = "kkk";
        $data['identity_number'] = "51031231230120312";
        $data['status'] = 1;
        $data['create_time'] = $unixTime;
        $data['update_time'] = $unixTime;

//        echo json_encode($data);die;
//        {"id":1,"user_id":1,"sns_user_id":1,"identity_card_front":"http:\/\/www.baidu.com","identity_card_opposite":"http:\/\/www.baidu.com","real_phone":"158105012","real_name":"kkk","identity_number":"51031231230120312","create_time":1648018910,"update_time":1648018910}
    }


    /**
     * @info 初始化用户提现认证信息表
     * Front opposite  UserWithdrawBankInformation
     * zb_user_withdraw_bank_information
     */
    public function makeUserWithdrawBankInformation()
    {
        $unixTime = time();
        $data = [];
        $data['id'] = 1;
        $data['user_id'] = 1;
        $data['bank_phone'] = "158105012";
        $data['bank_name'] = "kkk";
        $data['bank_card_number'] = "51031231230120312";
        $data['pay_type'] = 1;
        $data['default_hover'] = 0;
        $data['create_time'] = $unixTime;
        $data['update_time'] = $unixTime;

//        echo json_encode($data);die;s
//        {"id":1,"user_id":1,"bank_phone":"158105012","bank_name":"kkk","bank_card_number":"51031231230120312","pay_type":1,"default_have":0,"create_time":1648022246,"update_time":1648022246}
    }


    /**
     * @info 初始化用户提现认证信息表
     * Front opposite  UserWithdrawBankInformation
     * zb_make_user_withdraw_detail
     */
    public function makeUserWithdrawDetail()
    {
        $unixTime = time();
        $data = [];
        $data['id'] = 1;
        $data['order_number'] = "23489238490";
        $data['user_id'] = 1439778;
        $data['sns_order_number'] = "100029302";
        $data['order_price'] = "23.55";

        $data['bank_phone'] = "158105012";
        $data['bank_name'] = "kkk";
        $data['bank_card_number'] = "12343242333222";
        $data['pay_type'] = 1;
        $data['sns_agent_name'] = "dalong";

        $data['sns_agent_response'] = "";
        $data['order_status'] = 1;
        $data['message_detail'] = "打款成功";
        $data['create_time'] = $unixTime;
        $data['update_time'] = $unixTime;

        $data['reject_time'] = $unixTime;
        $data['refund_time'] = $unixTime;
        $data['success_time'] = $unixTime;
        $data['settle_time'] = $unixTime;

        echo json_encode($data);
        die;
//        {"id":1,"order_number":1,"user_id":1439778,"sns_order_number":"100029302","order_price":"23.55","bank_phone":"158105012","bank_name":"kkk","bank_card_number":"12343242333222","pay_type":1,"sns_agent_name":"dalong","sns_agent_response":"","order_status":1,"message_detail":"\u6253\u6b3e\u6210\u529f","create_time":1648025062,"update_time":1648025062,"reject_time":1648025062,"refund_time":1648025062,"success_time":1648025062,"settle_time":1648025062}
    }

    /**
     * @info 审核通过的身份信息计算剩余提现总额，没审核通过的不统计
     * @param $userRole
     * @param UserWithdrawInfoModel $userWithdrawInfoModel //用户身份提现信息
     * @return float|int
     */
    public function withDrawBalanceWithSuccess($userRole, UserWithdrawInfoModel $userWithdrawInfoModel)
    {
        $identityNumber = 0;
        if ($userWithdrawInfoModel->status === UserWithdrawInfoModelStauts::$SUCCESS) {
            $identityNumber = $userWithdrawInfoModel->identityNumber;
        }
        return $this->withDrawBalance($userRole, $identityNumber);
    }

    /**
     * @info 根据用户角色和身份证获取可提现余额
     * @param $userRole
     * @param $identityNumber
     * @return int
     * @throws FQException
     */
    public function withDrawBalance($userRole, $identityNumber)
    {
        $withDrawBalance = 0;
        if ($userRole === WithdrawUser::$NormalUser) {
            $maxWithDrawAmount = (int)AgentPayService::getInstance()->maxWithDrawAmount;
            $historyBalance = UserWithdrawDetailModelDao::getInstance()->loadUserMonthAmountSum($identityNumber);
            if (empty($historyBalance)) {
                return $maxWithDrawAmount;
            }
            $withDrawBalance = $maxWithDrawAmount - $historyBalance;
            if ($withDrawBalance < 0) {
                $withDrawBalance = 0;
            }
        }
        return $withDrawBalance;
    }


    /**
     * @param $eventId
     * @param $ext1
     * @param $ext2
     * @param $ext3
     * @param $ext4
     * @param $ext5
     * @return array|null
     */
    private function loadBiEvent($eventId, $ext1, $ext2, $ext3, $ext4, $ext5)
    {
        if ($eventId === BIConfig::$WITHDRAW_PRETAKEOFF_EVENTID) {
            return BIReport::getInstance()->makeWithdrawPretakeoffBIEvent($ext1, $ext3, $ext2, $ext4, $ext5);
        }
        if ($eventId === BIConfig::$WITHDRAW_REFUSE_EVENTID) {
            return BIReport::getInstance()->makeWithdrawRefuseBIEvent($ext1, $ext3, $ext2, $ext4, $ext5);
        }
        return null;
    }


    /**
     * @param $userId
     * @param $assetId
     * @param $count
     * @param $timestamp
     * @param $roomId
     * @param $activityType
     * @param $ext2
     * @param $ext3
     * @param $ext4
     * @param $ext5
     * @return array
     * @throws FQException
     */
    public function consumeAsset($userId, $assetId, $count, $timestamp, $eventId, $ext1, $ext2, $ext3, $ext4, $ext5)
    {
        $biEvent = $this->loadBiEvent($eventId, $ext1, $ext2, $ext3, $ext4, $ext5);
        if ($biEvent === null) {
            throw new FQException("eventId error", 500);
        }
        list($consume, $balance) = AssetUtils::consumeAsset($userId, $assetId, $count, $timestamp, $biEvent);

        // 扣费失败
        if ($consume < $count) {
            throw new FQException("扣费失败账户余额不足", 500);
        }
        return [$consume, $balance];
    }


    /**
     * @param $userId
     * @param $assetId
     * @param $count
     * @param $timestamp
     * @param $eventId
     * @param $ext1
     * @param $ext2
     * @param $ext3
     * @param $ext4
     * @param $ext5
     * @throws FQException
     */
    public function addAsset($userId, $assetId, $count, $timestamp, $eventId, $ext1, $ext2, $ext3, $ext4, $ext5)
    {
        $biEvent = $this->loadBiEvent($eventId, $ext1, $ext2, $ext3, $ext4, $ext5);
        if ($biEvent === null) {
            throw new FQException("eventId error", 500);
        }
        list($consume, $balance) = AssetUtils::addAsset($userId, $assetId, $count, $timestamp, $biEvent);
        return [$consume, $balance];
    }

    /**
     * @param $userIds
     * @return array
     * @throws FQException
     */
    public function cleanGuildUserCache($userIds)
    {
        if (empty($userIds)) {
            throw new FQException("userids error", 500);
        }
        $result = [];
        foreach ($userIds as $userId) {
            $cleanRe = UserModelCache::getInstance()->cleanUserCache($userId);
            $result[$userId] = $cleanRe;
        }
        return $result;
    }

    /**
     * @param $userId
     * @return false|mixed
     */
    public function cleanwithdrawStoreLog($userId)
    {
        if (empty($userId)) {
            return false;
        }
        return Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use ($userId) {
            $userWithdrawDetailModel = UserWithdrawInfoModelDao::getInstance()->loadModel($userId);
            if ($userWithdrawDetailModel === null) {
                return 0;
            }
            $pkId = $userWithdrawDetailModel->id;
            $userWithdrawDetailModel->id = 0;
            (int)UserWithdrawInfoLogModel::getInstance()->storeModel($userWithdrawDetailModel);
            UserWithdrawInfoModelDao::getInstance()->delModelForId($pkId);
            return true;
        });
    }

}
