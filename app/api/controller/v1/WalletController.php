<?php
/**
 * 钱包
 * yond
 *
 */

namespace app\api\controller\v1;

use app\api\view\v1\WalletView;
use app\BaseController;
use app\query\banner\BannerService;
use app\domain\exceptions\FQException;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\CoinDao;
use app\domain\user\dao\DiamondModelDao;
use app\domain\user\dao\TodayEarningsModelDao;
use app\domain\user\service\UnderAgeService;
use app\domain\user\service\WalletService;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class WalletController extends BaseController
{
    public function initMyMoney()
    {
        $userId = intval($this->headUid);

        $diamondMode = DiamondModelDao::getInstance()->loadDiamond($userId);
        $beanModel = BeanModelDao::getInstance()->loadBean($userId);
        $goldBalance = CoinDao::getInstance()->loadCoin($userId);

        return rjson([
            'diamond' => $diamondMode->balance(),
            'coin' => $beanModel->balance(),
            'scale' => config('config.khd_scale'),
            'coin_scale' => config('config.coin_scale'),
            'gold' => $goldBalance,
        ]);
    }

    //初始化钱包收入
    public function inintExchange()
    {
        $userId = intval($this->headUid);

        $timestamp = time();
        $diamondModel = DiamondModelDao::getInstance()->loadDiamond($userId);
        $todayEarnings = TodayEarningsModelDao::getInstance()->loadTodayEarnings($userId);
        if ($todayEarnings) {
            $todayEarnings->adjust($timestamp);
        }

        $diamondBalance = $diamondModel->balance();

        $result = [
            'diamond' => $diamondBalance,
            'coin' => floor($diamondBalance),
            'scale' => config('config.khd_scale'),
            'coin_scale' => config('config.coin_scale'),
            'todayearnings' => $todayEarnings ? $todayEarnings->diamond : 0,
            'scaleDoc' => $this->source == 'yinlian' ? '* 1钻石=10音豆，只能填写大于等于1的整数' : '* 1钻石=10豆，只能填写大于等于1的整数',
        ];
        return rjson($result);
    }

    //钱包兑换
    public function diamondExchangeCoin()
    {
        $exchangeDiamond = intval(Request::param('exchangediamond'));

        if (!is_integer($exchangeDiamond) || $exchangeDiamond < 10000) {
            return rjson([], 500, '钻石数量输入错误');
        }

        $userId = intval($this->headUid);

        try {
            list($beanBalance, $diamondBalance) = WalletService::getInstance()->diamondExchangeBean($userId, $exchangeDiamond);
            return rjson([
                'coin' => $beanBalance,
                'diamond' => $diamondBalance,
            ], 200, '兑换成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //工会代充
    public function tradeUnionAgent()
    {
        $uid = (int)$this->headUid;                             //转钻人id
        $toUid = (int)Request::param('toUid');           //收豆人id
        $exchangeDiamond = (int)Request::param('exchangeDiamond');   //代充钻石数量
        if (!is_integer($exchangeDiamond) || $exchangeDiamond < 1000) {
            return rjson([], 500, '操作过快,请重试');
        }
        try {
            list($beanBalance, $diamondBalance) = WalletService::getInstance()->tradeUnionAgent($uid, $toUid, $exchangeDiamond);
            return rjson(['diamond' => $diamondBalance], 200, 'success');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**
     * 充值banner列表
     */
    public function walletBanner()
    {
        $banners = BannerService::getInstance()->getBannerList($this->headUid, 5, $this->channel);
        $bannerList = [];
        foreach ($banners as $banner) {
            $bannerList[] = WalletView::viewBanner($banner, $this->getParamToken());
        }
        $result = [
            'list' => $bannerList,    //首页轮播接口
        ];
        return rjsonFit($result, 200, 'success');
    }

    //豆兑换金币，1：100
    public function beanchanggecoin()
    {
        $exchangeBean = intval(Request::param('exchangebean'));
        if (!is_integer($exchangeBean) || $exchangeBean < 1) {
            return rjson([], 500, '音豆数量输入错误');
        }
        $userId = $this->headUid;
        try {
            list($beanBalance, $diamondBalance) = WalletService::getInstance()->beanExchangeCoin($userId, $exchangeBean);
            return rjson([
                'coin' => $beanBalance,
                'gold' => $diamondBalance,
            ], 200, '兑换成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    //初始化钱包收入
    public function initBeanExchange()
    {
        $userId = $this->headUid;
        $timestamp = time();
        $beanModel = BeanModelDao::getInstance()->loadBean($userId);
        if ($beanModel === null) {
            throw new FQException("用户不存在", 500);
        }
        $beanBalance = $beanModel->balance();
        $result = [
            'bean' => floor($beanBalance),
            'scale' => config('config.bean_coin_scale'),
            'scaleDoc' => $this->source === 'yinlian' ? '* 1音豆=10金币，只能填写大于等于1的整数' : '* 1豆=10金币，只能填写大于等于1的整数',
        ];
        return rjson($result);
    }

}