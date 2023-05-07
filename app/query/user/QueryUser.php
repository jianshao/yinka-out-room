<?php


namespace app\query\user;

use app\domain\user\model\UserModel;

class QueryUser extends UserModel
{
    public function toJson() {
        return [
            'userId' => $this->userId,
            'prettyId' => $this->prettyId,
            'nickname' => $this->nickname,
            'avatar' => $this->avatar,
            'prettyAvatar' => $this->prettyAvatar,
            'sex' => $this->sex,
            'intro' => $this->intro,
            'vipLevel' => $this->vipLevel,
            'lvDengji' => $this->lvDengji,
            'dukeLevel' => $this->dukeLevel,
            'cancellationTime' => $this->cancellationTime
        ];
    }

    public function fromJson($jsonObj) {
        $this->userId = $jsonObj['userId'];
        $this->prettyId = $jsonObj['prettyId'];
        $this->nickname = $jsonObj['nickname'];
        $this->avatar = $jsonObj['avatar'];
        $this->prettyAvatar = $jsonObj['prettyAvatar'];
        $this->sex = $jsonObj['sex'];
        $this->intro = $jsonObj['intro'];
        $this->vipLevel = $jsonObj['vipLevel'];
        $this->lvDengji = $jsonObj['lvDengji'];
        $this->dukeLevel = $jsonObj['dukeLevel'];
        $this->cancellationTime = $jsonObj['cancellationTime'];
        return $this;
    }

    public static function toJsonList($users)
    {
        $ret = [];
        foreach ($users as $user) {
            $ret[] = $user->toJson();
        }
        return $ret;
    }

    public static function fromJsonList($jsonList) {
        $ret = [];
        foreach ($jsonList as $jsonObj) {
            $user = new QueryUser();
            $user->fromJson($jsonObj);
            $ret[] = $user;
        }
        return $ret;
    }
}