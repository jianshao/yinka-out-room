<?php


namespace app\web\controller;


use app\BaseController;
use app\domain\room\dao\RoomModelDao;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;
use think\facade\Request;
use think\facade\View;

class AppAwakenController extends BaseController
{
    public function indexPlatform(){
        $room_id = Request::param('room_id');
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false || strpos($_SERVER['HTTP_USER_AGENT'],'QQ') !== false){
            return View::fetch('../view/web/guide.html');//蒙层
        }
        $roomInfo=RoomModelDao::getInstance()->loadRoomData($room_id);

        $room_name = $roomInfo['room_name'];
        $user_avatar = CommonUtil::buildImageUrl(UserModelCache::getInstance()->findAvatarByUserId($roomInfo['user_id']));
        View::assign('room_id', $room_id);
        View::assign('room_name', $room_name);
        View::assign('avatar', $user_avatar);
        return View::fetch('../view/web/share.html');
    }

    public function indexPlatformMua(){
        $room_id = Request::param('room_id');
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){
            return View::fetch('../view/web/guide.html');//蒙层
        }
        $roomInfo=RoomModelDao::getInstance()->loadRoomData($room_id);
        $room_name = $roomInfo['room_name'];
        $user_avatar = CommonUtil::buildImageUrl(UserModelCache::getInstance()->findAvatarByUserId($roomInfo['user_id']));
        View::assign('room_id', $room_id);
        View::assign('room_name', $room_name);
        View::assign('avatar', $user_avatar);
        return View::fetch('../view/web/sharemua.html');
    }

    public function indexPlatformzh(){
        $room_id = Request::param('room_id');
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $get = isset(explode('?', $_SERVER['REQUEST_URI'])[1]) ? '?'.explode('?', $_SERVER['REQUEST_URI'])[1] : '';
            View::assign('room_id', $room_id);
            return View::fetch('../view/web/indexIoszh.html');
        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
            $get = isset(explode('?', $_SERVER['REQUEST_URI'])[1]) ? explode('?', $_SERVER['REQUEST_URI'])[1] : '';
            View::assign('room_id', $room_id);
            return View::fetch('../view/web/indexAndroidzh.html');
        }else{
            echo '非法来源';die;
        }
    }
}