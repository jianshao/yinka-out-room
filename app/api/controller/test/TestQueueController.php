<?php


namespace app\api\controller\test;


use app\BaseController;
use app\domain\queue\producer\NotifyMessage;
use app\domain\queue\producer\YunXinMsg;
use app\utils\AesUtil;
use think\facade\Request;

class TestQueueController extends BaseController
{
    public function index() {
        $msg = ['fromUid' => 1178493, 'fromName' => 'test1', 'toUid' => 1021100, 'toName' => 'test2', 'describe' => '你好', 'type' => 'takeShot'];
        YunXinMsg::getInstance()->sendMsg(['from' => 1178493, 'ope' => 0, 'toUid' => 1021100, 'type' => 100, 'msg' => $msg]);

//        $url = 'www.baidu.com';
//        $msgData = ['aa' => 1];
//        NotifyMessage::getInstance()->notify(['url' => $url, 'data' => $msgData, 'method' => 'POST', 'type' => 'json']);
    }
}