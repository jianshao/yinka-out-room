<?php

namespace app\web\controller\v1;

use app\domain\user\dao\AccountMapDao;
use app\query\music\dao\MusicModelDao;
use app\query\user\cache\UserModelCache;
use app\web\common\WebBaseController;
use think\facade\Request;
use OSS\OssClient;
use OSS\Core\OssException;
use Qcloud\Cos\Client;

class MusicController extends WebBaseController
{

    /**
     * 音乐列表操作
     */
    public function musicList()
    {
        $token = $this->getToken();
        $userinfo = $this->parseToken($token);
        $page = Request::param("page") ?? 1;
        $pagesize = Request::param("pagesize") ?? 10;
        $username = $userinfo['username'] ?? '';
        if (empty($username)) {
            return $this->return_json(500, '', '没有获取到用户名');
        }
        //统计
        $where[] = ['user_id', '=', $userinfo['id']];
        $count = MusicModelDao::getInstance()->userSongCount($userinfo['id']);
        $list = MusicModelDao::getInstance()->userSongList($userinfo['id'], $page, $pagesize);
        // 获取分页显示
        $data = $list->toArray();
        $image_url = config('config.APP_URL_image');
        foreach ($data as $key => $value) {
            $data[$key]['song_url'] = $image_url . '/' . $value['song_url'];
            $data[$key]['type'] = $value['type'] > 1 ? "原唱" : "伴奏";
            $data[$key]['sale'] = $value['sale'] > 0 ? "上架" : "下架";
            $data[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
        }
        return rjson(["page" => $page, "pagesize" => $pagesize,"count"=>$count, "data" => $data, "username" => $username], 200, 'success');
    }

    /**
     * 上传音乐操作
     */
    public function uploadFileMusic()
    {
        $limitSize = 20; // 限制8MB
        $token = $this->getToken();
        $userinfo = $this->parseToken($token);
        $username = $userinfo['username'] ?? '';
        if (empty($username)) {
            return $this->return_json(500, '', '没有获取到用户名');
        }
        //获取数据
        $fileobj = request()->file('song_url');
        if (($fileobj->getSize() / 1024 / 1024) >= $limitSize) {
            return rjson([], 500, '上传的文件太大');
        }
        $file_dir = "/music";
        $song_url = $this->cosFile($fileobj, $file_dir);
        $song_url = parse_url($song_url)['path'];
        $userId = AccountMapDao::getInstance()->getUserIdByMobile($username);
        //插入数据库
        $data = [
            "user_id" => $userId,                  //用户id
            "songer" => Request::param('songer'),       //歌手
            "song_name" => Request::param('song_name'),       //歌名
            "type" => Request::param('type') ? Request::param('type') : 1,           //音乐类型
            "song_url" => $song_url,           //音乐地址
            "upname" => UserModelCache::getInstance()->findNicknameByUserId($userId),  //音乐上传者
            "create_time" => time(),     //上传时间
            "size" => round(Request::param('size') / 1024 / 1024, 1),
        ];
        $result = \app\domain\music\dao\MusicModelDao::getInstance()->saveSong($data);       //插入数据表中

        if ($result) {
            return rjson([], 200, '上传成功');
        }
        return rjson([], 500, '数据异常,重试');
    }

    /**
     * 音乐状态修改
     */
    public function statusMusic()
    {
        //获取数据
        $token = $this->getToken();
        $userinfo = $this->parseToken($token);
        $username = $userinfo['username'] ?? '';
        if (empty($username)) {
            return rjson('', 500, '没有获取到用户名');
        }
        $params = Request::param();
        if ($params) {
            $user_id = $userinfo['id'] ?? 0;
            $music_id = Request::param('ids');
            $data['sale'] = Request::param('sale') ? Request::param('sale') : 0;
            //根据用户查询user_id
            if (!UserModelCache::getInstance()->getUserInfo($user_id)) {
                return rjson('', 500, '此用户不存在');
            }
            $music_id = rtrim($music_id, ',');
            \app\domain\music\dao\MusicModelDao::getInstance()->updateSong($music_id, $user_id, $data);       //插入数据表中
            return rjson([], 200, '操作成功');
        }

    }

    /*
     * 上传图片
     */
    public function ossFile($file_name, $file_dir)
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
        $savename = \think\facade\Filesystem::disk('public')->putFile($file_dir, $file_name);
        $imageObject = str_replace("\\", "/", $savename);
        $imageFile = STORAGE_PATH . str_replace("\\", "/", $savename);
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

    /*
     * 上传图片
     */
    public function cosFile($file_name, $file_dir)
    {
        if (is_file(__DIR__ . '/../autoload.php')) {
            require_once __DIR__ . '/../autoload.php';
        }
        if (is_file(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
        // SECRETID 和 SECRETKEY 请登录访问管理控制台进行查看和管理
        $stsConf = config('cos');
        $secretId = $stsConf['ACCESS_KEY_ID']; //用户的 SecretId，建议使用子账号密钥，授权遵循最小权限指引，降低使用风险。子账号密钥获取可参考https://cloud.tencent.com/document/product/598/37140
        $secretKey = $stsConf['ACCESS_KEY_SECRET']; //用户的 SecretKey，建议使用子账号密钥，授权遵循最小权限指引，降低使用风险。子账号密钥获取可参考https://cloud.tencent.com/document/product/598/37140
        $region = $stsConf['Region']; //用户的 region，已创建桶归属的 region 可以在控制台查看，https://console.cloud.tencent.com/cos5/bucket
        $cosClient = new Client(
            array(
                'region' => $region,
                'schema' => 'https', //协议头部，默认为 http
                'credentials'=> array(
                    'secretId'  => $secretId ,
                    'secretKey' => $secretKey)
            )
        );

        $saveName = \think\facade\Filesystem::disk('public')->putFile($file_dir, $file_name);
        $imageObject = str_replace("\\", "/", $saveName);
        $imageFile = STORAGE_PATH . str_replace("\\", "/", $saveName);

        ## 上传文件流
        try {
            $bucket = $stsConf['BUCKET']; //存储桶名称 格式：BucketName-APPID
            $file = fopen($imageFile, "rb");
            if ($file) {
                $result = $cosClient->putObject(array(
                    'Bucket' => $bucket,
                    'Key' => $imageObject,
                    'Body' => $file));
                $key =$result['Key']?? '';
                if ($key) {
                    $key = '/'.$key;
                }
                return $key;
            }
        } catch (\Exception $e) {
            printf(__FUNCTION__ . ": FAILED\n");
            printf($e->getMessage() . "\n");
            return;
        }
    }

}
