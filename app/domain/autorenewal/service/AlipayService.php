<?php


namespace app\domain\autorenewal\service;


use alipay\aop\AopClient;
use alipay\aop\request\AlipayTradePayRequest;
use alipay\aop\request\AlipayUserAgreementQueryRequest;
use app\utils\ArrayUtil;

class AlipayService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AlipayService();
        }
        return self::$instance;
    }

    private function aopClient($conf)
    {
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $conf['app_id'];
        $aop->rsaPrivateKey = $conf['private_key'];
        $aop->alipayrsaPublicKey = $conf['ali_public_key'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset = 'utf-8';
        $aop->format = 'json';

        return $aop;
    }

    /**
     * @desc alipay.user.agreement.query(支付宝个人代扣协议查询接口)
     * https://opendocs.alipay.com/apis/02ffsf?scene=8837b4183390497f84bb53783b488ecc
     * @param $config
     * @param $agreementNo
     * @throws \Exception
     */
    public function alipayUserAgreementQuery($config, $agreementNo)
    {
        $conf = config("$config.alipay_yuansheng");
        $aop = $this->aopClient($conf);

        $request = new AlipayUserAgreementQueryRequest();

        $query = array(
            'personal_product_code' => 'GENERAL_WITHHOLDING_P',
            'sign_scene' => 'INDUSTRY|SOCIALIZATION',
            'agreement_no' => $agreementNo,
        );
        $request->setBizContent(json_encode($query));
        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $requestObj = $result->$responseNode;

        return get_object_vars($requestObj);
    }

    /**
     * @desc 根据协议号判断是否签约正常
     * @param $config
     * @param $agreementNo
     * @return bool
     * @throws \Exception
     */
    public function isUserAgreement($config, $agreementNo): bool
    {
        $request = $this->alipayUserAgreementQuery($config, $agreementNo);
        if (ArrayUtil::safeGet($request, 'code') == 10000 &&
            ArrayUtil::safeGet($request, 'status') == 'NORMAL') {
            return true;
        }

        return false;
    }

    /**
     * @desc 根据签约号扣款金额
     * @param $config
     * @param $agreementNo
     * @param $type
     */
    public function alipayAutoPayVip($config, $agreementNo, $order)
    {
        $conf = config("$config.alipay_yuansheng");
        $aop = $this->aopClient($conf);

        $request = new AlipayTradePayRequest();

        $object = new \stdClass();
        $object->out_trade_no = $order->orderId;
        $object->total_amount = $order->rmb;
        if (config('config.appDev') == 'dev') {
            $object->total_amount = 0.01;
        }
        $object->subject = '音恋';
        $object->product_code = 'CYCLE_PAY_AUTH';
        //协议信息
        $agreementParams = [
            'agreement_no' => $agreementNo,
        ];
        $object->agreement_params = $agreementParams;
        $json = json_encode($object);

        $request->setNotifyUrl(ArrayUtil::safeGet($conf, 'notify_url'));
        $request->setBizContent($json);
        $result = $aop->execute($request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $requestObj = $result->$responseNode;

        return get_object_vars($requestObj);
    }
}