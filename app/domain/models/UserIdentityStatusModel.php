<?php
/**
 * User: yond
 * Date: 2020
 * 认证表
 */
namespace app\domain\models;

//状态0：失败 1：成功 2：待确认
class UserIdentityStatusModel {

    public static $ERROR = 0;
    public static $SUCCESS = 1;
    public static $AUDIT = 2;


}