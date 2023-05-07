<?php


namespace app\api\controller\v1;


use app\api\controller\ApiBaseController;
use app\domain\level\LevelSystem;
use app\utils\CommonUtil;

class MemberLevel extends ApiBaseController
{
    public function levelPrivilegeList()
    {
        $data = [];
        $privilegeLevels = LevelSystem::getInstance()->getPrivilegeLevels();
        foreach ($privilegeLevels as $level){
            $data[] = [
                'level' => $level->level,
                'title' => $level->title,
                'picture' => CommonUtil::buildImageUrl($level->image),
                'picture2' => CommonUtil::buildImageUrl($level->twoImage),
                'preview_picture' => CommonUtil::buildImageUrl($level->previewImage),
                'content' => $level->content,
                'rewardType' => $level->rewardType,
            ];
        }
        return rjson($data, 200, '成功');
    }
}