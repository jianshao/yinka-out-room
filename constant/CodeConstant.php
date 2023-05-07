<?php


namespace constant;


class CodeConstant
{
    /*
     * Code 码处理
     * */

    //成功
    const CODE_成功 = 200;
    const CODE_暂无数据 = 201;
    const CODE_插入成功 = 202;
    const CODE_更新成功 = 203;
    const CODE_删除成功 = 204;

    const CODE_OK_MAP = [
        self::CODE_成功 => '成功',
        self::CODE_暂无数据 => '暂无数据',
        self::CODE_插入成功 => '插入成功',
        self::CODE_更新成功 => '更新成功',
        self::CODE_删除成功 => '删除成功',
    ];

    /*
     * 失败
     */

    //参数错误
    const CODE_参数错误 = -1000;
    const CODE_账户错误 = -1001;
    const CODE_密码错误 = -1002;
    const CODE_Token错误 = -1003;
    const CODE_接口地址错误 = -1004;
    const CODE_用户ID错误 = -1005;
    const CODE_礼物ID错误 = -1006;
    const CODE_ID错误 = -1007;
    const CODE_请输入正确的角色名称 = -1008;
    const CODE_角色名称已存在 = -1009;
    const CODE_管理员名称已存在 = -1010;
    const CODE_管理员真实姓名已存在 = -1011;
    const CODE_房间ID不能为空 = -1012;
    const CODE_房间类型错误 = -1013;
    const CODE_分页或条数不能为空 = -1014;
    const CODE_动态ID不能为空 = -1015;
    const CODE_动态评论ID不能为空 = -1016;
    const CODE_用户已经加入了其他公会 = -1017;
    const CODE_用户已经创建了其他公会 = -1018;
    const CODE_添加的用户没有指定角色 = -1019;
    const CODE_没有该角色 = -1020;
    const CODE_节点名称已存在 = -1021;

    const CODE_PARAMETER_ERR_MAP = [
        self::CODE_参数错误 => '参数错误',
        self::CODE_账户错误 => '账户错误',
        self::CODE_密码错误 => '密码错误',
        self::CODE_Token错误 => 'Token错误',
        self::CODE_接口地址错误 => '接口地址错误',
        self::CODE_用户ID错误 => '用户ID错误',
        self::CODE_礼物ID错误 => '礼物ID错误',
        self::CODE_ID错误 => 'ID错误',
        self::CODE_请输入正确的角色名称 => '请输入正确的角色名称',
        self::CODE_角色名称已存在 => '角色名称已存在',
        self::CODE_管理员名称已存在 => '管理员名称已存在',
        self::CODE_管理员真实姓名已存在 => '管理员真实姓名已存在',
        self::CODE_房间ID不能为空 => '房间ID不能为空',
        self::CODE_房间类型错误 => '房间类型错误',
        self::CODE_分页或条数不能为空 => '分页或条数不能为空',
        self::CODE_动态ID不能为空 => '动态ID不能为空',
        self::CODE_动态评论ID不能为空 => '动态评论ID不能为空',
        self::CODE_用户已经加入了其他公会 => '用户已经加入了其他公会',
        self::CODE_用户已经创建了其他公会 => '用户已经创建了其他公会',
        self::CODE_添加的用户没有指定角色 => '添加的用户没有指定角色',
        self::CODE_没有该角色 => '没有该角色',
        self::CODE_节点名称已存在 => '节点名称已存在',
    ];

    //内部错误
    const CODE_内部错误 = -2000;
    const CODE_用户不存在 = -2001;
    const CODE_没有查询到数据 = -2002;
    const CODE_用户未登录 = -2003;
    const CODE_该用户没有权限 = -2004;
    const CODE_插入失败 = -2005;
    const CODE_更新失败 = -2006;
    const CODE_删除失败 = -2007;

    const CODE_INSIDE_ERR_MAP = [
        self::CODE_内部错误 => '内部错误',
        self::CODE_用户不存在 => '用户不存在',
        self::CODE_没有查询到数据 => '没有查询到数据',
        self::CODE_用户未登录 => '用户未登录',
        self::CODE_该用户没有权限 => '该用户没有权限',
        self::CODE_插入失败 => '插入失败',
        self::CODE_更新失败 => '更新失败',
        self::CODE_删除失败 => '删除失败',
    ];

}
