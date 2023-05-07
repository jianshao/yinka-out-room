<?php


namespace app\domain\asset\rewardcontent;


use app\domain\asset\AssetItem;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class RandomContent
{
    public static $TYPE_ID = 'RandomContent';
    public $contents = [];
    public $total = 0;

    public function getContent() {
        $ret = [];
        foreach ($this->contents as $content){
            $ret[] = $content[1];
        }

        return $ret;
    }

    public function getItem() {
        $content = $this->selectContent();
        return $content != null ? $content : [];
    }

    private function selectContent() {
        $value = random_int(1, $this->total);
        foreach ($this->contents as $content) {
            if ($value <= $content[0]) {
                return $content[1];
            }
        }

        return null;
    }

    public function decodeFromJson($jsonObj) {
        foreach (ArrayUtil::safeGet($jsonObj, 'randoms', []) as $reward) {
            $weight = ArrayUtil::safeGet($reward, 'weight', 0);
            assert($weight >= 0);

            $name = ArrayUtil::safeGet($reward, 'name');
            $img = ArrayUtil::safeGet($reward, 'img', '');
            $item = new AssetItem($reward['assetId'], $reward['count'], $name, $img);
            $this->contents[] = array($this->total+$weight, $item);
            $this->total += $weight;
        }

        return $this;
    }

}