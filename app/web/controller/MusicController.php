<?php
namespace app\web\controller;

use app\domain\user\dao\AccountMapDao;
use app\query\music\dao\MusicModelDao;
use app\query\user\cache\UserModelCache;
use app\web\common\WebBaseController;
use think\facade\View;
use think\facade\Request;
use OSS\OssClient;
use OSS\Core\OssException;

class MusicController extends WebBaseController
{

    /**
     * 音乐列表操作
     */
    public function musicList()
    {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        if(empty($username)){
            return redirect($web_url.'/web/index.html');
        }
        //统计
        $user_id = AccountMapDao::getInstance()->getUserIdByMobile($username);
        $count = MusicModelDao::getInstance()->userSongCount($user_id);
        $list = MusicModelDao::getInstance()->userSongList($user_id, 1, 20);
        // 获取分页显示
        $data = $list->toArray();
        $image_url = config('config.APP_URL_image');
        foreach ($data as $key => $value) {
            $data[$key]['song_url'] = $image_url . '/' . $value['song_url'];
            $data[$key]['type'] = $value['type'] > 1 ? "原唱" : "伴奏";
            $data[$key]['sale'] = $value['sale'] > 0 ? "上架" : "下架";
            $data[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }

        View::assign('page', 1);
        View::assign('data', $data);
        View::assign('count', $count);
        View::assign('username', $username);
        View::assign('user_id', $user_id);
        View::assign('web_url', $web_url);
        return View::fetch('../view/web/musicList.html');
    }
    /**
     * 上传音乐操作
     */
    public function uploadFileMusic()
    {
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        if(empty($username)){
            return redirect($web_url.'/web/index.html');
        }
        $params = Request::param();
        if($params){
            //获取数据
            $url = request()->file('song_url');
            $file_dir = "/music";
            $song_url  = $this->ossFile($url,$file_dir);
            /*$UploadOssFileCommon = new UploadOssFileCommon();
            $song_url = $UploadOssFileCommon->ossFile($url,$file_dir);*/
            $song_url  = parse_url($song_url)['path'];
            $userId = AccountMapDao::getInstance()->getUserIdByMobile($username);
            //插入数据库
            $data = [
                "user_id" => $userId,                  //用户id
                "songer" => Request::param('songer'),       //歌手
                "song_name" => Request::param('song_name'),       //歌名
                "type" => Request::param('type')?Request::param('type'):1,           //音乐类型
                "song_url" => $song_url,           //音乐地址
                "upname" => UserModelCache::getInstance()->findNicknameByUserId($userId),           //音乐上传者
                "create_time" => time(),                         //上传时间
                "size" => round(Request::param('size')/1024/1024,1),                         //上传时间
            ];
            $result = \app\domain\music\dao\MusicModelDao::getInstance()->saveSong($data);       //插入数据表中
            if($result){
                return redirect($web_url.'/web/musicList');
                //echo '<script>window.location.href="http://newmtestapi.muayuyin.com/web/musicList";</script>';
                //die;
            }else{
                echo $this->return_json(500, '', '数据异常,重试');
                die;
            }
        }else{
            View::assign('web_url', $web_url);
            View::assign('username', $username);
            return View::fetch('../view/web/music.html');
        }

    }
    /**
     * 音乐状态修改
     */
    public function statusMusic()
    {
        //获取数据
        if (!session_id()) session_start();
        $username = !empty($_SESSION)?$_SESSION['username']:'';
        $web_url = config('config.WEB_URL');
        if(empty($username)){
            return redirect($web_url.'/web/index.html');
        }
        $params = Request::param();
        if($params){
            $user_id = Request::param('user_id');
            $music_id = Request::param('ids');
            $data['sale'] = Request::param('sale')?Request::param('sale'):0;
            //根据用户查询user_id
            if(!UserModelCache::getInstance()->getUserInfo($user_id)){
                echo $this->return_json(500, '', '此用户不存在');
                die;
            }
            $music_id = rtrim($music_id, ',');
            \app\domain\music\dao\MusicModelDao::getInstance()->updateSong($music_id, $user_id, $data);       //插入数据表中
            echo $this->return_json(200, '', '操作成功');
            die;
        }else{
            $data = [];
            $count = 0;
            View::assign('data', $data);
            View::assign('count', $count);
            View::assign('username', $username);
            return View::fetch('../view/web/musicList.html');
        }

    }

    /*
     * 上传图片
     */
    public function ossFile($file_name,$file_dir)
    {
        if (is_file(__DIR__ . '/../autoload.php')) {
            require_once __DIR__ . '/../autoload.php';
        }
        if (is_file(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        //OSS第三方配置
        $ossConfig = config('config.OSS');
        $accessKeyId = $ossConfig['ACCESS_KEY_ID'];//阿里云OSS  ID
        $accessKeySecret = $ossConfig['ACCESS_KEY_SECRET'];//阿里云OSS 秘钥
        $endpoint = $ossConfig['ENDPOINT'];//阿里云OSS 地址
        $bucket = $ossConfig['BUCKET']; //oss中的文件上传空间
        $savename = \think\facade\Filesystem::disk('public')->putFile( $file_dir, $file_name);
        $imageObject =str_replace("\\", "/", $savename);
        $imageFile = STORAGE_PATH.str_replace("\\", "/", $savename);
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $result = $ossClient->uploadFile($bucket, $imageObject, $imageFile);//上传成功
            return $result['info']['url'];
        } catch (OssException $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

}
