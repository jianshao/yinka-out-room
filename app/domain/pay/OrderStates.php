<?php


namespace app\domain\pay;


class OrderStates
{
    public static $CREATE = 0;
    public static $PAID = 1;
    public static $DELIVERY = 2;
}