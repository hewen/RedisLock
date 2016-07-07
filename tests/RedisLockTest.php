<?php
require_once "../src/RedisLock.php";

$oRedisLock = new RedisLock();
$result     = $oRedisLock->getLock("userOpenId:myPhone");
if ($result === true) {
    $phoneCount = $oRedisLock->redis()->get("phoneCount");
    if ($phoneCount == 0) {
        exit;
    }

    $money = $oRedisLock->redis()->get("userOpenId:money");
    if ($money < 4000) {
        exit;
    }

    $oRedisLock->redis()->decr("userOpenId:money", 4000);
    $oRedisLock->redis()->decr("phoneCount", 1);

    $oRedisLock->delLock("userOpenId:myPhone");
}
