<?php

namespace app\common\amqp\conf;


class Event
{

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Event();
        }
        return self::$instance;
    }

    public function getbuildEvent()
    {
        return [
//            room:
            'RoomCreateEvent' => 'app\event\RoomCreateEvent',
            'RoomUpdateEvent' => 'app\event\RoomUpdateEvent',
            'RoomLockEvent' => 'app\event\RoomLockEvent',
            'RoomUnlockEvent' => 'app\event\RoomUnlockEvent',
            'RoomInfoUpdateNoticeEvent' => 'app\event\RoomInfoUpdateNoticeEvent',

//            user:
            'UserLoginEvent' => 'app\event\UserLoginEvent',
            'UserUpdateProfileEvent' => 'app\event\UserUpdateProfileEvent',
            'PerfectUserInfoEvent' => 'app\domain\user\event\PerfectUserInfoEvent',
            'DukeLevelChangeEvent' => 'app\event\DukeLevelChangeEvent',
            'LevelChangeEvent' => 'app\event\LevelChangeEvent',
            'MemberDetailAuditEvent' => 'app\event\MemberDetailAuditEvent',
            'UserCancelEvent' => 'app\event\UserCancelEvent',
            'UserInfoUpdateNoticeEvent' => 'app\event\UserInfoUpdateNoticeEvent',
        ];
    }

    public function getsubscribeEvent()
    {
        return [
            'app\domain\elastic\event\handler\ElasticSyncConsumerHandler',
        ];
    }

}


