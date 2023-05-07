<?php


namespace app\domain\game\poolbase;
use app\common\RedisCommon;
use \app\domain\exceptions\FQException;
use app\utils\TimeUtil;

class RunningRewardPool
{
    public static $BREAK_BOX_POOL_WITH_REGIFT_SCRIPT = "
        local function breakReGift(curPool, reGiftId)
            local giftMap = curPool['gifts']
            local reGiftCount = giftMap[reGiftId]
            if (reGiftCount ~= nil and reGiftCount > 0) then
                giftMap[reGiftId] = reGiftCount - 1
                return 1
            end
            return 0
        end
        local function breakGift(curPool, randomSeed)
            local total = 0
            local giftIdWeights = {}
            local giftMap = curPool['gifts']
            for giftId,weight in pairs(giftMap) do
                if weight > 0 then
                    total = total + weight
                    table.insert(giftIdWeights, {giftId, weight, total})
                end
            end
            if total > 0 then
                local num = math.random(1, total)
                for i=1, #giftIdWeights do
                    local giftIdWeight = giftIdWeights[i]
                    local giftId = giftIdWeight[1]
                    local giftCount = giftMap[giftId]
                    if num <= giftIdWeight[3] then
                        giftMap[giftId] = giftCount - 1
                        return giftId
                    end
                end
            end
            return nil
        end
        local function breakWithReGift(curPool, randomSeed, reGiftId)
            if reGiftId ~= 0 then
                if breakReGift(curPool, reGiftId) == 1 then
                    return reGiftId
                end
            end
            return breakGift(curPool, randomSeed)
        end
        local function savePool(key, poolId, poolStr)
            redis.call('hset', key, poolId, poolStr)
        end
    
        local key = tostring(KEYS[1])
        local poolId = tonumber(KEYS[2])
        local count = tonumber(KEYS[3])
        local randomSeed = tonumber(KEYS[4])
        local poolStr = tostring(KEYS[5])
        local reGiftId = tostring(KEYS[6])
        local poolExists = redis.call('hexists', key, poolId)
        local curPoolStr = poolStr
        if poolExists == 0 then
            savePool(key, poolId, poolStr)
        else
            curPoolStr = redis.call('hget', key, poolId)
        end
        local curPool = cjson.decode(curPoolStr)
        local breakMap = {}
        local breakReGiftId = 0
        math.randomseed(randomSeed)
        for i=1, count do
            local giftId = breakWithReGift(curPool, randomSeed, reGiftId)
            if giftId == nil then
                savePool(key, poolId, poolStr)
                curPool = cjson.decode(poolStr)
                giftId = breakWithReGift(curPool, randomSeed, reGiftId)
                if giftId == nil then
                    return {1, cjson.encode(breakMap), poolStr}
                end
            end
            local giftCount = breakMap[giftId]
            if giftCount == nil then
                breakMap[giftId] = 1
            else
                breakMap[giftId] = giftCount + 1
            end
            if reGiftId == giftId then
                reGiftId = nil
                breakReGiftId = giftId
            end
        end
        local afterPoolStr = cjson.encode(curPool)
        savePool(key, poolId, afterPoolStr)
        return {0, cjson.encode(breakMap), breakReGiftId, afterPoolStr}
    ";

    public static  $ADJUST_BOX_POOL_GIFT_SCRIPT = "
        local function savePool(key, poolId, poolStr)
            redis.call('hset', key, poolId, poolStr)
        end
        local function adjustGift(curPool, giftId, count)
            local giftMap = curPool['gifts']
            if giftMap ~= nil then
                local giftCount = giftMap[giftId]
                if giftCount ~= nil then
                    if giftCount + count >= 0 then
                        giftMap[giftId] = giftCount + count
                        return 1
                    end
                end
            end
            return 0
        end
        local key = tostring(KEYS[1])
        local poolId = tonumber(KEYS[2])
        local giftId = tostring(KEYS[3])
        local count = tonumber(KEYS[4])
        local poolExists = redis.call('hexists', key, poolId)
        if poolExists == 1 then
            local curPoolStr = redis.call('hget', key, poolId)
            local curPool = cjson.decode(curPoolStr)
            if adjustGift(curPool, giftId, count) == 1 then
                curPoolStr = cjson.encode(curPool)
                savePool(key, poolId, curPoolStr)
                return {0, curPoolStr}
            end
        end
        return {1, ''}
    ";

    public static  $ENSURE_BOX_POOL_EXISTS_SCRIPT = "
        local function savePool(key, poolId, poolStr)
            redis.call('hset', key, poolId, poolStr)
        end
        local key = tostring(KEYS[1])
        local poolId = tonumber(KEYS[2])
        local poolStr = tostring(KEYS[3])
        local poolExists = redis.call('hexists', key, poolId)
        if poolExists == 0 then
            savePool(key, poolId, poolStr)
            return {0, poolStr}
        else
            local curPoolStr = redis.call('hget', key, poolId)
            return {1, curPoolStr}
        end
    ";


    public static  $REFRESH_BOX_POOL_SCRIPT = "
        local function savePool(key, poolId, poolStr)
            redis.call('hset', key, poolId, poolStr)
        end
        local key = tostring(KEYS[1])
        local poolId = tonumber(KEYS[2])
        local poolStr = tostring(KEYS[3])
        savePool(key, poolId, poolStr)
        return {0, poolStr}
    ";


    public static function breakGift($key, $poolId, $count, $poolStr, $reGiftId) {
        $randomSeed = TimeUtil::getMillisecond();
        $redis = RedisCommon::getInstance()->getRedis();
        list($ec, $breakGiftMapStr, $breakReGiftId, $afterPoolStr) = $redis->eval(self::$BREAK_BOX_POOL_WITH_REGIFT_SCRIPT,
            [$key, $poolId, $count, $randomSeed, $poolStr, $reGiftId], 6);

        if ($ec != 0) {
            throw new FQException('奖池不存在', 500);
        }

        $giftMap = json_decode($breakGiftMapStr, true);

        return [$giftMap, $breakReGiftId];
    }

    public static function adjustPool($key, $poolId, $giftId, $count) {
        $redis = RedisCommon::getInstance()->getRedis();
        list($ec, $afterPoolStr) = $redis->eval(self::$ADJUST_BOX_POOL_GIFT_SCRIPT,
            [$key, $poolId, $giftId, $count], 4);

        return [$ec, $afterPoolStr];
    }

    public static function ensurePoolExists($key, $poolId, $poolStr) {
        $redis = RedisCommon::getInstance()->getRedis();
        list($ec, $curPoolStr) = $redis->eval(self::$ENSURE_BOX_POOL_EXISTS_SCRIPT,
            [$key, $poolId, $poolStr], 3);

        return [$ec, $curPoolStr];
    }

    public static function refreshPool($key, $poolId, $poolStr) {
        $redis = RedisCommon::getInstance()->getRedis();
        list($ec, $afterPoolStr) = $redis->eval(self::$REFRESH_BOX_POOL_SCRIPT,
            [$key, $poolId, $poolStr], 3);

        return [$ec, $afterPoolStr];
    }
}
