<?php


namespace app\domain\promote;


use app\domain\exceptions\FQException;
use app\domain\open\dao\PromoteReportDao;
use app\domain\open\model\PromoteFactoryTypeModel;
use app\domain\user\dao\UserModelDao;

//归因 sservice 工具 ervice
class PromoteUtilService
{
    protected static $instance;
    private $callbackForUserId = "ProblemService_callback_filter";

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * @param $userId
     * @return string
     * @throws FQException
     */
    public function loadChannleForUserId($userId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException("用户信息异常请重试", 500);
        }
        if ($userModel->registerChannel === "appStore") {
            $registerChannel = $this->findIosRegisterChannel($userModel->idfa);
        } else {
            $registerChannel = $userModel->registerChannel;
        }
        return $this->LoadChannelMap($registerChannel);
    }

    /**
     * @param $userModel
     * @return string
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function findIosRegisterChannel($idfa)
    {
        if ($idfa === '00000000-0000-0000-0000-000000000000') {
            return "";
        }
        return PromoteReportDao::getInstance()->LoadChannelForIdfaColumn(md5($idfa));
    }


    /**
     * @Info 通过渠道名获取渠道类型
     * @param $channle
     * @return string
     */
    private function LoadChannelMap($channle)
    {
        $channleMap = [
            'TouTiaoXY_CP' => PromoteFactoryTypeModel::$TOUTIAO,
            'HuaWei' => PromoteFactoryTypeModel::$HUAWEI,
            'Oppo' => PromoteFactoryTypeModel::$OPPO,
            'BiZhan' => PromoteFactoryTypeModel::$BIZHAN,
        ];
        return isset($channleMap[$channle]) ? $channleMap[$channle] : "";
    }
}