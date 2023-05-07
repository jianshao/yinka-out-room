<?php


namespace app\api\controller\inner;


use app\Base2Controller;
use app\domain\exceptions\FQException;
use app\domain\withdraw\service\AgentPayService;
use app\utils\ArrayUtil;
use app\utils\Error;
use think\facade\Request;

class WithdrawController extends Base2Controller
{

    /**
     * @Info   用户增加资产
     * @return \think\response\Json
     * @throws FQException
     */
    public function addAsset()
    {
        $userId = Request::param('userId', 0, 'intval');
        $assetId = Request::param('assetId');
        $count = Request::param('count', 0, 'intval');
        $timestamp = Request::param('timestamp');
        $eventDict = Request::param('eventDict');
        $eventId = Request::param('eventId', 0, 'intval');
        if ($count === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $eventDict = json_decode($eventDict, true);
        $ext1 = ArrayUtil::safeGet($eventDict, 'ext1', '');
        $ext2 = ArrayUtil::safeGet($eventDict, 'ext2', '');
        $ext3 = ArrayUtil::safeGet($eventDict, 'ext3', '');
        $ext4 = ArrayUtil::safeGet($eventDict, 'ext4', '');
        $ext5 = ArrayUtil::safeGet($eventDict, 'ext5', '');

        $asset = AgentPayService::getInstance()->addAsset($userId, $assetId, $count, $timestamp, $eventId, $ext1, $ext2, $ext3, $ext4, $ext5);
        $ret['asset'] = $asset;
        return rjson($ret, 200, 'success');
    }


    /**
     * @info   用户减少资产
     * @return \think\response\Json
     * @throws FQException
     */
    public function consumeAsset()
    {
        $userId = Request::param('userId', 0, 'intval');
        $assetId = Request::param('assetId');
        $count = Request::param('count', 0, 'intval');
        $timestamp = Request::param('timestamp');
        $eventDict = Request::param('eventDict');
        $eventId = Request::param('eventId', 0, 'intval');
        if ($count === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $eventDict = json_decode($eventDict, true);
        $ext1 = ArrayUtil::safeGet($eventDict, 'ext1', '');
        $ext2 = ArrayUtil::safeGet($eventDict, 'ext2', '');
        $ext3 = ArrayUtil::safeGet($eventDict, 'ext3', '');
        $ext4 = ArrayUtil::safeGet($eventDict, 'ext4', '');
        $ext5 = ArrayUtil::safeGet($eventDict, 'ext5', '');

        $asset = AgentPayService::getInstance()->consumeAsset($userId, $assetId, $count, $timestamp, $eventId, $ext1, $ext2, $ext3, $ext4, $ext5);
        $ret['asset'] = $asset;
        return rjson($ret, 200, 'success');
    }

}