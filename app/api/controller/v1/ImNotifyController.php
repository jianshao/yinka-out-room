<?php


namespace app\api\controller\v1;


use app\BaseController;
use app\domain\bi\BIUserAssetModelDao;
use app\domain\gift\GiftSystem;
use \app\facade\RequestAes as Request;
use think\facade\Log;

class ImNotifyController extends BaseController
{
    private $unSendMsgModel = [
        'iPhone8,1', //6s
    ];
    public function imMessageNotify() {
        $data = Request::param();
        $header = Request::header();
        $checkSum = $this->checkSign($header);
        $startTime = time()-60;
        $endTime = time();
        if($checkSum == $header['checksum'] && isset($data['attach'])) { //验签成功
            if($data['fromClientType'] == 'AOS' && $data['msgType'] == 'CUSTOM') { //安卓
                $attach = json_decode($data['attach'], true);
                if (isset($attach['data']) && array_key_exists('giftName', $attach['data'])) {
                    $giftKind = GiftSystem::getInstance()->findGiftKindByName($attach['data']['giftName']);
                    if ($giftKind) {
                        $where[] = ['success_time', '>=', $startTime];
                        $where[] = ['success_time', '<=', $endTime];
                        $where[] = ['uid', '=', $data['fromAccount']];
                        $where[] = ['touid', '=', $data['to']];
                        $where[] = ['event_id', '=', 10002];
//                        $where[] = ['ext_1', '=', $giftKind->kindId];
                        $isExits = BIUserAssetModelDao::getInstance()->getModel($data['fromAccount'])->where($where)->find();
                        if ($isExits) {
                            $res['errCode'] = 0;
                            $res['responseCode'] = 0;
                            $res['modifyResponse'] = json_encode([]);
                            $res['callbackExt'] = '';
                        } else {
                            $res['errCode'] = 1;
                            $res['responseCode'] = 20000;
                            $res['modifyResponse'] = json_encode([]);
                            $res['callbackExt'] = '';
                        }
                    } else {
                        $res['errCode'] = 1;
                        $res['responseCode'] = 20000;
                        $res['modifyResponse'] = json_encode([]);
                        $res['callbackExt'] = '';
                    }
                    Log::record('imNotifyReturn----'.json_encode($res));
                    return json_encode($res);
                }
            } elseif($data['fromClientType'] == 'IOS' && $data['msgType'] == 'CUSTOM') {    //ios
                $attach = json_decode($data['attach'], true);
                if(array_key_exists('giftName', $attach)) {
                    $giftKind = GiftSystem::getInstance()->findGiftKindByName($attach['giftName']);
                    if ($giftKind) {
                        $where[] = ['success_time', '>=', $startTime];
                        $where[] = ['success_time', '<=', $endTime];
                        $where[] = ['uid', '=', $data['fromAccount']];
                        $where[] = ['touid', '=', $data['to']];
                        $where[] = ['event_id', '=', 10002];
//                        $where[] = ['ext_1', '=', $giftKind->kindId];
                        $isExits = BIUserAssetModelDao::getInstance()->getModel($data['fromAccount'])->where($where)->find();
                        if ($isExits) {
                            $res['errCode'] = 0;
                            $res['responseCode'] = 0;
                            $res['modifyResponse'] = json_encode([]);
                            $res['callbackExt'] = '';
                        } else {
                            $res['errCode'] = 1;
                            $res['responseCode'] = 20000;
                            $res['modifyResponse'] = json_encode([]);
                            $res['callbackExt'] = '';
                        }
                    } else {
                        $res['errCode'] = 1;
                        $res['responseCode'] = 20000;
                        $res['modifyResponse'] = json_encode([]);
                        $res['callbackExt'] = '';
                    }

                    Log::record('imNotifyReturn----'.json_encode($res));
                    return json_encode($res);
                }
            }
        }
        $res['errCode'] = 0;
        $res['responseCode'] = 0;
        $res['modifyResponse'] = json_encode([]);
        $res['callbackExt'] = '';
        return json_encode($res);
    }

    protected function checkSign($header) {
        $checkSum = '';
        $AppSecret   = config('config.yunxin.Appsecret');       //appsecret
        $time = $header['curtime'];
        $requestBody = $header['md5'];
        $join_string    = $AppSecret . $requestBody . $time;
        return sha1($join_string);
    }
}