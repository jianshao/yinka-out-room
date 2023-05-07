<?php
namespace app\api\controller\base;

use app\BaseController;
use app\query\site\dao\SiteConfigModelDao;
use app\query\site\service\SiteService;
use app\utils\Aes;
use app\utils\RequestHeaders;

class InitController extends BaseController
{

    /**
     * @info app加密数据配置下发
     * @return \think\response\Json
     */
    public function baseInit()
    {
        $EncryptKey = config('config.EncryptKeySecond');
        $iv = str_repeat("\0", 16);
        $encryptDriver = config('config.EncryptDriver');
        $result['aes'] = [
            'cipherAlgo' => 'aes-128-cbc',
            'passphrase' => $EncryptKey,
            'iv' => $iv,
            'ivBase64' => base64_encode($iv),
            'apiSignAuthKey' => config('config.apiSignAuthKey'),
            'encryptDriver' => $encryptDriver,
            'encryptDriverEnableStatus' => $encryptDriver === "enable" ? true : false,
        ];
        //初始化强更地址
        $siteConf = SiteService::getInstance()->getSiteConf(1);
        if ($this->channel === 'appStore') {
            $result['app']['address'] = $siteConf['iosaddress'] ?? "";
        } else {
            $result['app']['address'] = $siteConf['apkaddress'] ?? "";
        }
        return rjson($result, 200, 'success');
    }


    public function nicai()
    {
        $result = [];
        //初始化强更地址
        $sitConf = SiteService::getInstance()->getSiteConf(1);
        if ($this->channel === 'appStore') {
            $result['app']['address'] = $sitConf['iosaddress'] ?? "";
        } else {
            $result['app']['address'] = $sitConf['apkaddress'] ?? "";
        }
        $headersData = array_change_key_case($this->request->header());
        $requestHeaders = new RequestHeaders();
        $requestHeaders->dataToModel($headersData);
        $Aes = new Aes();
//        监测该版本是否开启aes
        $enable = $Aes->isEnableAes($requestHeaders);
        $enable = false;
        $relink = "!!!";
        if ($enable === false) {
            $relink = "";
        }
        $result['relink'] = $relink;
        return rjson($result, 200, 'success');
    }
}