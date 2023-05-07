<?php

namespace app\domain\asset;

/**
 * 资产类
 */
abstract class AssetKind
{
    // 资产类型ID
    public $kindId = '';
    // 单位
    public $unit = '';
    // 显示名称
    public $displayName = '';
    // 显示图片
    public $image = '';

    /**
     * 给当前类型的资产增加count个单位
     * 
     * @param userAssets: 用户自残
     * @param count: 增加数量
     * @param timestamp: 当前时间戳
     * 
     * @return: 剩余数量
     */
    abstract public function add($userAssets, $count, $timestamp, $biEvent);

    /**
     * 给当前类型的资产消耗count个单位
     * 
     * @param userAssets: 用户自残
     * @param count: 减少的数量
     * @param timestamp: 当前时间戳
     * 
     * @return: 剩余数量
     */
    abstract public function consume($userAssets, $count, $timestamp, $biEvent);

    /**
     * 余额
     * 
     * @param userAssets: 用户自残
     * @param timestamp: 当前时间戳
     * 
     * @return: 剩余数量
     */
    abstract public function balance($userAssets, $timestamp);
}


