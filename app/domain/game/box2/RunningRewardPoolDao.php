<?php


namespace app\domain\game\box2;
use app\core\mysql\ModelDao;


class RunningRewardPoolDao extends ModelDao
{
    protected static $instance;
    protected $table = 'zb_box2_pools';
    protected $serviceName = 'commonMaster';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new RunningRewardPoolDao();
        }
        return self::$instance;
    }


    public function isRewardPoolExists($boxId, $poolId) {
        $where = [
            'box_id' => $boxId,
            'pool_id' => $poolId
        ];
        $found = $this->getModel($boxId)->where($where)->field('box_id')->find();
        return !empty($found);
    }

    public function loadRewardPoolWithoutLock($boxId, $poolId) {
        return $this->loadRewardPoolImpl($boxId, $poolId, false);
    }

    public function loadRewardPool($boxId, $poolId) {
        return $this->loadRewardPoolImpl($boxId, $poolId, true);
    }

    public function insertRewardPool($runningRewardPool) {
        $data = [
            'box_id' => $runningRewardPool->boxId,
            'pool_id' => $runningRewardPool->poolId,
            'gifts' => json_encode($runningRewardPool->giftMap)
        ];
        $this->getModel($runningRewardPool->boxId)->insert($data);
    }

    public function updateRewardPool($runningRewardPool) {
        $where = [
            'box_id' => $runningRewardPool->boxId,
            'pool_id' => $runningRewardPool->poolId,
        ];
        $this->getModel($runningRewardPool->boxId)->where($where)->update([
            'gifts' => json_encode($runningRewardPool->giftMap)
        ]);
    }

    private function loadRewardPoolImpl($boxId, $poolId, $withLock) {
        $where = [
            'box_id' => $boxId,
            'pool_id' => $poolId
        ];
        if ($withLock) {
            $data = $this->getModel($boxId)->lock(true)->field('gifts')->where($where)->find();
        } else {
            $data = $this->getModel($boxId)->field('gifts')->where($where)->find();
        }
        if (!empty($data)) {
            $data = $data->toArray();
            $ret = new RunningRewardPool($boxId, $poolId);
            $ret->giftMap = json_decode($data['gifts'], true);
            return $ret;
        }
        return null;
    }
}
