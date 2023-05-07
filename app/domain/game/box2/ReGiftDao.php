<?php


namespace app\domain\game\box2;


use app\core\mysql\ModelDao;


class ReGiftDao extends ModelDao
{
    protected $table = 'yyht_box2_re_user_gift';
    protected $pk = 'id';
    protected $serviceName = 'biMaster';
    protected static $instance;

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new ReGiftDao();
        }
        return self::$instance;
    }

    public function loadAndProcessReGifts($userId, $boxId, $maxCount) {
        $datas = $this->getModel($userId)->field(
            'id,gift_id,state'
        )->where([
            'user_id' => $userId,
            'box_id' => $boxId,
            'state' => ReGiftStates::$NORMAL
        ])->limit(0, $maxCount)->select()->toArray();
        $ret = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $id = $data['id'];
                if ($this->getModel($userId)->where([
                    'id' => $id,
                    'state' => ReGiftStates::$NORMAL
                ])->update([
                    'state' => ReGiftStates::$PROCESS
                ])) {
                    $ret[] = new ReGift($data['id'], $data['gift_id'], $data['state']);
                }
            }
        }
        return $ret;
    }

    public function updateReGiftState($id, $newState) {
        return $this->getModel($id)->where([
            'id' => $id,
        ])->update([
            'state' => $newState
        ]);
    }
}