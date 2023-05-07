<?php


namespace app\api\controller\v1;


use app\api\controller\ApiBaseController;
use app\domain\emoticon\EmoticonSystem;
use app\domain\exceptions\FQException;
use app\view\EmoticonView;

class EmoticonController extends ApiBaseController
{
    public function getList() {
        $result = [];
        try {
            $panels = EmoticonSystem::getInstance()->getPanels();
            foreach ($panels as $panel) {
                $emoticons = [];
                foreach ($panel->emoticons as $emoticon) {
                    $emoticons[] = EmoticonView::encodeEmoticon($emoticon);
                }
                if (count($emoticons) > 0) {
                    $result[] = [
                        'id' => $emoticons[0]["face_id"],
                        'mold_icon' => $emoticons[0]["face_image"],
                        'mode' => $panel->mold,
                        'list' => $emoticons,
                    ];
                }
            }
            return rjson($result,200);
        } catch (FQException $e) {
            return rjson([], $e->getCode(),$e->getMessage());
        }
    }
}