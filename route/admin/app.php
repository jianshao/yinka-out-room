<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::post('signIn','AdminUserController/signin');//登录
Route::get('signOut','AdminUserController/signOut');//退出
Route::get('adminUserLists','AdminUserController/adminUserLists');//管理员列表
Route::post('addAdminUser','AdminUserController/addAdminUser');//管理员列表
Route::post('editAdminUserInfo','AdminUserController/editAdminUserInfo');//管理员列表
Route::post('delAdminUser','AdminUserController/delAdminUser');//管理员删除
Route::get('getRoleLists','RoleController/getRoleLists');//角色管理列表
Route::post('addRole','RoleController/addRole');//添加角色
Route::post('delRole','RoleController/delRole');//删除角色
Route::post('editRoleToMenu','RoleController/editRoleToMenu');//删除角色
Route::post('editRole','RoleController/editRole');//删除角色

Route::get('getMenuLists','MenuController/getMenuLists');//菜单列表
Route::post('addMenuItems','MenuController/addMenuItems');//菜单添加
Route::post('editMenuItems','MenuController/editMenuItems');//菜单编辑
Route::post('delMenuItems','MenuController/delMenuItems');//菜单删除

Route::rule('forumList','ForumController/getforumlist');    //动态列表接口
Route::rule('delForum','ForumController/delforum');         //删除某个动态接口
Route::rule('replyList','ForumController/replylist');       //动态回复评论列表接口
Route::rule('delReply','ForumController/delreply');         //删除某个回复评论接口

Route::get('giftList','giftController/giftSelect');       //礼物列表
Route::post('giftAdd','giftController/saveGift');       //添加礼物
Route::post('giftEdit','giftController/exitGift');       //修改礼物
Route::get('giftClear','giftController/clearCache');       //清除礼物缓存
Route::get('eggList','GiftController/eggList');       //砸蛋奖次礼物列表
Route::post('addGiftUserAssign','GiftController/addGiftUserAssign');       //添加砸蛋指定用户绑定指定礼物
Route::post('getUserToGiftLists','GiftController/getUserToGiftLists');       //砸蛋指定用户绑定指定礼物列表
Route::post('delUserToGiftAssign','GiftController/delUserToGiftAssign');       //取消砸蛋指定用户绑定指定礼物
Route::post('ossFile','giftController/ossFile');       //礼物图片上传OSS

Route::get('roomList','RoomController/roomList');       //房间列表
Route::post('roomEdit','RoomController/exitRoom');       //修改房间
Route::get('typeList','RoomController/roomTypeList');       //房间类型接口

Route::get('bannerList','BannerController/bannerList');       //广告列表
Route::post('bannerAdd','BannerController/saveBanner');       //添加广告
Route::post('bannerEdit','BannerController/exitBanner');       //修改广告
Route::get('bannerClear','BannerController/clearCache');       //清除广告缓存

Route::get('guildList','GuildController/guildlist');       //公会列表信息
Route::get('guildMember','GuildController/member');       //公会成员列表信息
Route::post('socityMember','GuildController/insertMember');       //添加公会成员信息
Route::post('guildAdd','GuildController/addGuilds');       //添加公会
Route::post('guildEdit','GuildController/saveGuilds');       //修改公会信息
Route::post('socityEdit','GuildController/exitMember');       //修改公会成员信息
Route::get('socityDel','GuildController/delMember');       //删除公会成员信息

Route::get('memberList','MemberController/memberList');       //用户列表
Route::get('cashOutList','MemberController/cashOutList');       //所有用户提现消费列表

Route::get('blackList','BlackListController/blacklist');       //封禁列表
Route::post('addKickUser','BlackListController/addKickUser');       //封禁用户
Route::post('delKickUser','BlackListController/delKickUser');       //解除封禁用户

Route::get('chargeList','ChargeController/chargeList');       //所有充值列表
Route::get('consumeList','CoindetailController/consumeList');       //所有用户消费列表
Route::get('incomeList','BeandetailController/incomeList');       //所有用户收益列表
Route::get('faceList','EmoticonController/faceList');       //所有表情列表

Route::rule('tixian','UserController/memberList');       //用户搜索信息展示
Route::rule('guilds','UserController/guildList');       //用户公会信息展示

Route::get('faceList','EmoticonController/faceList');       //所有表情列表
Route::rule('userList','UserController/memberList');       //用户搜索
Route::rule('tixian','UserController/memberList');       //用户搜索信息展示

Route::get('faceList','EmoticonController/faceList');       //所有表情列表
Route::rule('userList','UserController/memberList');       //用户搜索
Route::rule('tixian','UserController/memberList');       //用户搜索信息展示

