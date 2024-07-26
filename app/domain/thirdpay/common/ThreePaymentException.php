<?php

namespace app\domain\thirdpay\common;


class ThreePaymentException extends \Exception
{
    /**
     * @desc 获取异常错误信息
     * @return string
     */
    public function errorMessage()
    {
        return $this->getMessage();
    }
}