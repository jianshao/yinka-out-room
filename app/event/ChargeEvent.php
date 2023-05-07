<?php

namespace app\event;


//购买商品
class ChargeEvent extends AppEvent
{
    public $userId = 0;
    public $orderId = '';
    public $productId = '';
    public $payChannel = '';

    public function __construct($order, $product ,$timestamp) {
        parent::__construct($timestamp);
        $this->userId = $order->userId;
        $this->orderId = $order->orderId;
        $this->productId = $product->productId;
        $this->payChannel = $order->payChannel;
    }
}