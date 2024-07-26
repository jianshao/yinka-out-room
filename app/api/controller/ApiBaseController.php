<?php


namespace app\api\controller;

use app\BaseController;
use app\domain\exceptions\FQFatalException;
use app\query\site\service\SiteService;
use think\facade\Log;

class ApiBaseController extends BaseController
{

    protected $headUid;
    protected $headToken;
    protected $actionArr = [];
    protected $source;

    public function initialize()
    {
        //判断强更
        $redis = $this->getRedis();
        if ($this->source == 'chuchu') {
            $siteConf = SiteService::getInstance()->getSiteConf(3, ['ipaversion', 'iosaddress', 'apkversion', 'apkaddress']);
        } else {
            $siteConf = SiteService::getInstance()->getSiteConf(1, ['ipaversion', 'iosaddress', 'apkversion', 'apkaddress']);
        }

        if ($this->channel == 'appStore') {
            if (version_compare($this->version, $siteConf['ipaversion'], '<')) {
                throw new FQFatalException(json_encode(['code' => 3000, 'desc' => '该用户不是最新版本', 'appStore' => $siteConf['iosaddress']]), 3000);
            }
        } else {
            if (version_compare($this->version, $siteConf['apkversion'], '<')) {
                Log::info(sprintf("3000 result version=%s request=%s", $this->version, json_encode($this->request)));
                throw new FQFatalException(json_encode(['code' => 3000, 'desc' => '该用户不是最新版本', 'apk_url' => $siteConf['apkaddress']]), 3000);
            }
        }
        //api过滤登录
        $this->actionArr = ['login', 'regist', 'option'];
        $this->headToken = $this->request->header('token');
        if (empty($this->headToken)) {//兼容老版
            $this->headToken = $this->request->param('token');
        }
        if (!empty($this->headToken)) {
            $this->headUid = (int)$redis->get($this->headToken);
            if (empty($this->headUid)) {
                throw new FQFatalException(json_encode(['code' => 5000, 'desc' => '非法请求', 'data' => null]), 3000);
            }
        }
    }
}