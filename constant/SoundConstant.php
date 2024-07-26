<?php


namespace constant;


class SoundConstant
{
    // 是否是默认录音 1:是  2:不是
    const IS_DEFAULT_TRUE = 1;
    const IS_DEFAULT_FALSE = 2;

    const REDIS_MATCH_TIMES_PREFIX = "SoundMatchTimes:";

    const REDIS_CACHE_ORDER_IDS = "SoundOrderIds";

    const REDIS_SOUND_USER_LIKE_PREFIX = "SoundUserLike:";  // 用户喜欢列表
    const REDIS_SOUND_USER_CANCEL_PREFIX = "SoundUserCancel:"; // 用户取消列表
    const REDIS_SOUND_USER_LEAVE_PREFIX = "SoundMatchUserLeave:"; // 用户匹配后剩余录音记录

    const MATCH_TIMES_TOTAL = 3;
}