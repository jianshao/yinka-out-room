<?php

namespace app\domain\bank;

class BankAccountType
{
    // 账号类型id
    public $typeId = '';
    // 显示名称
    public $displayName = '';
    // 显示图片
    public $image = '';
    // 单位
    public $unit = '';
    // 是否可以是负数
    public $canNegative = false;
}