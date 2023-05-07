<?php


namespace app\api\controller\v1;


use app\api\controller\ApiBaseController;
use app\domain\duke\dao\DukeModelDao;
use app\domain\duke\DukeSystem;
use app\utils\CommonUtil;

class DukeController extends ApiBaseController
{
    public function buildPrivilege($dukeLevelList, $curLevel) {
        $ret = [];
        $sort = 1;
        foreach ($dukeLevelList as $dukeLevel) {
            foreach ($dukeLevel->privilegeDescList as $dukePrivilegeDesc) {
                $ret[] = [
                    'sort' => $sort++,
                    'picture' => CommonUtil::buildImageUrl($dukePrivilegeDesc->picture),
                    'state' => $curLevel >= $dukeLevel->level ? 1 : 0,
                    'title' => $dukePrivilegeDesc->title
                ];
            }
        }
        return $ret;
    }

    public function dukeInfo() {
        $userId = intval($this->headUid);

        // 获取所有dukelevel
        $dukeLevelList = DukeSystem::getInstance()->getDukeLevelList();
        $dukeInfo = [];

        foreach ($dukeLevelList as $dukeLevel) {
            $dukeInfo[] = [
                'duke_id' => $dukeLevel->level,
                'duke_name' => $dukeLevel->name,
                'duke_coin' => $dukeLevel->value,
                'special_effects' => CommonUtil::buildImageUrl($dukeLevel->animation),
                'duke_relegation' => $dukeLevel->relegation,
                'privilige' => $this->buildPrivilege($dukeLevelList, $dukeLevel->level)
            ];
        }

        $data['dukeInfo'] = $dukeInfo;
        $dukeModel = DukeModelDao::getInstance()->loadDuke($userId);
        DukeSystem::getInstance()->adjustDuke($dukeModel, time());
        $data['self'] = [
            'duke_id' => $dukeModel->dukeLevel,
            'currentPay' => $dukeModel->dukeValue,
            'duke_exp_time' => $dukeModel->dukeExpiresTime != 0 ? date('Y-m-d', $dukeModel->dukeExpiresTime) : '未开通'
        ];
        return rjson($data);
    }
}