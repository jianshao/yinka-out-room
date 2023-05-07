<?php


namespace app\domain\im\event\handler;


use app\domain\dao\ImChatPointDao;
use app\domain\user\dao\BeanModelDao;
use app\event\ImChatPointEvent;

class ImChatPointEventHandler
{
    public function onImChatPointEvent(ImChatPointEvent $event) {
        $data['user_id'] = $event->userModel->userId;
        $data['code']  = $event->code;
        $data['user_level'] = $event->userModel->lvDengji;
        $bean = BeanModelDao::getInstance()->loadBean($data['user_id']);
        $data['charge_coin'] = $bean->total;
        $data['register_time'] = $event->userModel->registerTime;
        $data['create_time'] = $event->timestamp;
        ImChatPointDao::getInstance()->insertData($data);
    }

}