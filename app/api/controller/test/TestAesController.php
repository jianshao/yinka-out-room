<?php


namespace app\api\controller\test;


use app\BaseController;
use app\domain\exceptions\FQException;
use app\utils\Aes;
use think\facade\Request;


class TestAesController extends BaseController
{
    public function decodeAes()
    {
        $msgData = Request::param("data", "");
        $hash = Request::param("hash", "");
        if ($hash != "313bdf658a7ed29da3b62507fcd0ea11") {
            return;
        }
        if (empty($msgData)) {
            throw new FQException("param error", 400);
        }

//        part1:--------------------------------------
//        $Aes=new Aes();
//        $key=config('config.EncryptKey');
//        $re=$Aes->aesEncrypt("num=1&giftId=365&skip=1&usePack=0&touId=1451150&mic=999&roomId=124206&sign=88d00e59dcea45d6cbd3703013fdd3a1",$key);
//        $re=urlencode($re);

//        var_dump($re);die;// 34IiljN8wG3hfF0ZUZdexwBk6mYumTX1CUnIvXD99u4wdExTgD4YxGeXmJTZQyIlUfSz%2Fmtr4vUGZ5lhPnHnqz%2BDszh52ldvkpaOpVchrWJhi7yGGfX1oaCYKcPHfHXuUthfihxKAl13eSUv1fas3Q%3D%3D
//        -----------------
//        $origin=$Aes->aesDecrypt(urldecode($re),$key);
//        var_dump($origin);die;


//        $Aes = new Aes();
//        $key = config('config.EncryptKey');
//        $origin = $Aes->aesDecrypt(urldecode($msgData), $key);
//        parse_str($origin, $result);
//        return rjsonFit(['data'=>$result],200,'success');


//        part2:--------------------------------------
//        $data['num'] = "1";
//        $data['giftId'] = "365";
//        $data['skip'] = '1';
//        $data['usePack'] = "0";
//        $data['touId'] = "1451150";
//        $data['mic'] = "999";
//        $data['roomId'] = "124206";
//        $data['sign'] = "88d00e59dcea45d6cbd3703013fdd3a1";
//        $jsonData = json_encode($data);
//        $Aes = new Aes();
//        $key = config('config.EncryptKey');
//        $result = $Aes->aesEncrypt($jsonData, $key);
//        var_dump($result);die;

//        string(192)
//"DNr9OmpAEeuJOvvh3+vf0+FjAiYj2lWZK1fpNp1SzVRvnCN3EbzIRVXjyc9dzDBUG6e8wxU7SQs9NcTifoN7VCEfl7ItVjHQ4+eeeryCnCultsgBoWY/uR9AgSVJadHLjv4ehWT48bsuz5oGyh5v+B21RUQ/3oSm27o7dxT2/p0SUkZhlxMNBODf0ldVtlj+"

        $Aes = new Aes();
        $result = $Aes->aesDecrypt($msgData);
        echo $result;
    }

}