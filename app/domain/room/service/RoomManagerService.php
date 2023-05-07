<?php


namespace app\domain\room\service;


use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\manager\ManagerTypes;
use app\domain\room\model\RoomManagerModel;
use app\domain\user\dao\UserModelDao;
use app\event\RoomManagerAddEvent;
use app\event\RoomManagerRemoveEvent;
use think\facade\Log;

class RoomManagerService
{
    protected static $instance;

    private $typeCove;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RoomManagerService();
            self::$instance->typeCove = [
                0 => ManagerTypes::$GENERAL,
                1 => ManagerTypes::$OWNER,
                2 => ManagerTypes::$SUPER,
            ];
        }
        return self::$instance;
    }

    /**
     * @info 添加管理员
     * @param $roomId
     * @param $userId
     * @param $opUserId
     * @param int $managerType
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addManager($roomId, $userId, $opUserId, $managerType = 0)
    {
        $roleType = $this->getOpUserType($opUserId, $roomId);

        // 超管只能操作管理员，不能操作超级管理或者房主
        if ($roleType == ManagerTypes::$SUPER && $managerType == 2) {
            throw new FQException("房主才能添加超级管理员");
        }

        if ($userId == $opUserId) {
            throw new FQException('不能添加自己为管理员', 500);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if (empty($userModel)) {
            throw new FQException('添加用户不存在', 500);
        }

        $userManager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
        if (!empty($userManager) && $userManager->type == $managerType) {
            throw new FQException('该用户是房间管理员不能添加', 500);
        }

        //派对房间10个管理, C端房间2个管理员
        $roomCount = RoomManagerModelDao::getInstance()->getTotalForRoomId($roomId);

        if ($roomCount >= 30) {
            throw new FQException('管理员数量已达上限', 500);
        }

        $timestamp = time();
        $this->updateManager($roomId, $userId, $timestamp, $managerType, $userManager==null);

        Log::info(sprintf('RoomService::addManager ok userId=%d roomId=%d opUserId=%d managerType=%d',
            $userId, $roomId, $opUserId, $managerType));

        event(new RoomManagerAddEvent($userId, $roomId, $opUserId, $managerType, $timestamp));
    }

    /**
     * @info 修改管理员
     * @param $roomId  int  房间id
     * @param $userId  int  用户id
     * @param $timestamp  int   时间
     * @param $managerType  int  用户类型
     * @param $isNew  bool  是否是新添加的
     */
    private function updateManager($roomId, $userId, $unixTime, $managerType, $isNew)
    {
        $model = new RoomManagerModel;
        $model->roomId = $roomId;
        $model->userId = $userId;
        $model->createTime = $unixTime;
        $model->type = $managerType;
        if ($isNew){
            RoomManagerModelDao::getInstance()->saveModel($model);
        }else{
            RoomManagerModelDao::getInstance()->updateModel($model);
        }
    }


    //    初始化用户权限
    public function getOpUserType($opUserId, $roomId)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null) {
            throw new FQException('此房间不存在', 500);
        }

        $roleType = $roomModel->userId == $opUserId ? ManagerTypes::$OWNER : null;
        if ($roleType == null) {
            $managerModel = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $opUserId);
            $roleType = $managerModel === null ? $roleType : $this->typeCove[$managerModel->type];
        }

        if (empty($roleType) || $roleType == ManagerTypes::$GENERAL) {
            throw new FQException("没有权限", 500);
        }

        return $roleType;
    }


    //    初始化用户权限
    public function selectOpUserType($opUserId, $roomId)
    {
        $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
        if ($roomModel == null) {
            throw new FQException('此房间不存在', 500);
        }
        $roleType = $roomModel->userId == $opUserId ? ManagerTypes::$OWNER : null;
        if ($roleType == null) {
            $managerModel = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $opUserId);
            $roleType = $managerModel === null ? $roleType : $this->typeCove[$managerModel->type];
        }
        return $roleType;
    }

    /**
     * @param $roomId
     * @param $userId
     * @param $opUserId
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function removeManager($roomId, $userId, $opUserId)
    {
        $roleType = ManagerTypes::$GENERAL;
//        如果是放弃自己的房间管理员不需验证权限
        if ($userId !== $opUserId) {
            $roleType = $this->getOpUserType($opUserId, $roomId);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel === null) {
            throw new FQException('此用户不存在', 500);
        }

        $userManager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $userId);
        if ($userManager === null) {
            throw new FQException('该用户不是房间管理员', 500);
        }

//        只能删除管理员，不能操作超级管理或者房主
        if ($roleType == ManagerTypes::$SUPER && $userManager->type == 2) {
            throw new FQException("房主才能删除超级管理员");
        }

        $timestamp = time();
        $this->deleteManager($roomId, $userId);
        Log::info(sprintf('RoomService::removeManager ok userId=%d roomId=%d opUserId=%d',
            $userId, $roomId, $opUserId));
        event(new RoomManagerRemoveEvent($userId, $roomId, $opUserId, $timestamp));
    }

    /**
     * @info 放弃自己的房间管理员权限
     * @param $roomId
     * @param $opUserId
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function leaveManager($roomId, $opUserId)
    {
        $roleType = $this->selectOpUserType($opUserId, $roomId);
        //房主不能放弃房间管理员
        if ($roleType == ManagerTypes::$OWNER) {
            throw new FQException("房主不能放弃自己的管理权限");
        }
        $userManager = RoomManagerModelDao::getInstance()->findManagerByUserId($roomId, $opUserId);
        if ($userManager == null) {
            throw new FQException('不是房间管理员', 500);
        }
        $timestamp = time();
        $this->deleteManager($roomId, $opUserId);
        Log::info(sprintf('RoomService::removeManager ok userId=%d roomId=%d opUserId=%d',
            $opUserId, $roomId, $opUserId));
        event(new RoomManagerRemoveEvent($opUserId, $roomId, $opUserId, $timestamp));
    }


    /**
     * @param $roomId
     * @param $userId
     * @throws FQException
     */
    private function deleteManager($roomId, $userId)
    {
        $delete = RoomManagerModelDao::getInstance()->removeManager($roomId, $userId);
        if (empty($delete)) {
            throw new FQException('移除管理员异常', 500);
        }
    }

}