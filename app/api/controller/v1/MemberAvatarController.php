<?php

namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\domain\user\service\UserService;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use constant\CodeConstant as coder;
use app\common\TextcanimgCommon;
use app\common\UploadOssFileCommon;
use app\domain\shumei\ShuMeiCheck;
use app\domain\shumei\ShuMeiCheckType;

class MemberAvatarController extends ApiBaseController
{

    /**
     * 设置头像
     * @param string $value [description]
     */
    public function setAvatar()
    {
        $userId = intval($this->headUid);
        $avatar = Request::param('avatar');
        try {
            //todo 图片检测
            $checkStatus = ShuMeiCheck::getInstance()->imageCheck($avatar,ShuMeiCheckType::$IMAGE_HEAD_EVENT,$this->headUid);
            if(!$checkStatus){
                return rjson([], 500, '头像图片违反平台规定');
            }
//            $isSafes = TextcanimgCommon::getInstance()->checkImg([$avatar]);
//            if ($isSafes != -1) {
//                throw new FQException("图片不合规", 403);
//            }
            UserService::getInstance()->setUserAvatar($userId, $avatar);
            return rjson([], 200, '更新成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**相册
     * @param $token    用户token值
     * @param $album    相册
     * @param $photo_id     相册id
     */
    public function setAlbum()
    {
        $album = Request::param('album');
        $userId = intval($this->headUid);
        try {
            $memberAvatar = UserService::getInstance()->setAlbum($userId, $album);
            return rjson($memberAvatar);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**删除OSS图片
     * @return mixed
     */
    public function delAlbum() {
        $token = Request::param('token');
        $album = Request::param('album');
        if (!$token) {
            return rjson([], coder::CODE_参数错误, coder::CODE_PARAMETER_ERR_MAP[coder::CODE_参数错误]);
        }
        $photo = explode(',', $album);
        $UploadOssFileCommon = new UploadOssFileCommon();
        $result = $UploadOssFileCommon->delossFile($photo);
        return rjson([],200,'操作成功');
    }
}