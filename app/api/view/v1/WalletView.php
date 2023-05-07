<?php

namespace app\api\view\v1;

use app\domain\banner\BannerModel;


class WalletView
{

    public static function viewBanner(BannerModel $banner,$token="")
    {
        return [
            'bannerId' => $banner->id,
            'image' => $banner->image,
            'linkurl' => sprintf("%s?mtoken=%s", $banner->linkUrl,$token),
            'title' => $banner->title,
            'bannerChannel' => $banner->channel,
            'showType' => $banner->showType,
            'bannerType' => $banner->bannerType
        ];
    }

}