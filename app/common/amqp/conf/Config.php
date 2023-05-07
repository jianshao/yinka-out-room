<?php

namespace app\common\amqp\conf;



/**
 * @info amqpconf
 * Class RoomTag(zb_languageroom.tag_image)
 * @package app\domain\room
 */
class Config
{

    protected static $instance;
    protected $redis = null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }


    public function getUserRegisterRefereeConf(){
        return config("config.ampq_user_register_referee");
    }

    public function getElasticQueueRoomConf()
    {
        return config("config.ampq_elastic_room");
    }

    public function getElasticQueueUserConf()
    {
        return config("config.ampq_elastic_user");
    }

    public function getMessageBusConf()
    {
        return config("config.ampq_message_bus");
    }

}


