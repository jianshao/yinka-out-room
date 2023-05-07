<?php


namespace app\api\controller\inner;

use app\Base2Controller;
use app\domain\game\gashapon\GashaponService;
use app\domain\game\gashapon\GashaponSystem;
use think\facade\Log;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;

class GMGashaponController extends Base2Controller
{
    private function checkAuth() {
        $operatorId = $this->request->param('operatorId');
        $token = $this->request->param('token');

        $redis = RedisCommon::getInstance()->getRedis();
        $adminToken = $redis->get('admin_token_'.$operatorId);
        if ($token != $adminToken) {
            throw new FQException('鉴权失败', 500);
        }

        return $operatorId;
    }

    public function setConf() {
        $operatorId = $this->checkAuth();
        $confStr = $this->request->param('conf');

        if (empty($confStr)) {
            return rjson([], 500, '配置参数错误');
        }
        Log::info(sprintf('GMGashaponController::setConf operatorId=%d conf=%s', $operatorId, $confStr));
        try {
            $conf = json_decode($confStr, true);
            GashaponSystem::setConf($conf);
            return rjson($conf);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function refreshPool() {
        $operatorId = $this->checkAuth();
        $userId = intval($this->request->param('userId'));

        Log::info(sprintf('GMGashaponController::refreshPool operatorId=%d userId=%d', $operatorId, $userId));

        $curPoolStr = GashaponService::getInstance()->refreshPool();
        $curPoolStr = json_decode($curPoolStr, true);

        return rjson([
            'lotterys' => $curPoolStr['gifts']
        ]);
    }

    public function getRunningPool() {
        $operatorId = $this->checkAuth();
        try {
            Log::info(sprintf('GMTurntableController::getRunningPool operatorId=%d', $operatorId));

            $curPoolStr = GashaponService::getInstance()->ensurePoolExists();
            $curPoolStr = json_decode($curPoolStr, true);

            return rjson([
                'lotterys' => $curPoolStr['gifts']
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}