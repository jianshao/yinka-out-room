<?php

namespace app\api\controller\inner;

use app\Base2Controller;
use app\domain\exceptions\FQException;
use app\domain\user\dao\RecallSmsInfoDao;
use app\utils\Error;
use think\facade\Request;

// 回归活动控制器
class RecallController extends Base2Controller
{

    //用户召回短信活动的短信下载链接数据上报
    public function recallSms()
    {
        $userId = Request::param('userid', 0, 'intval');//用户id
        $platform = Request::param('platform', '');//平台
        $action = Request::param('action', '');//行为
        if (empty($platform) || empty($action)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        RecallSmsInfoDao::getInstance()->storeData($userId, $platform, $action);
        return rjsonFit([], 200, 'success');
    }

}
