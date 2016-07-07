<?php
require_once "../src/RedisLock.php";

$oRedisLock = new RedisLock();
echo "运行结果:\n";
echo "我的钱:" . $oRedisLock->redis()->get("userOpenId:money") . "\n";
echo "剩余iphone:" . $oRedisLock->redis()->get("iphoneCount") . "\n";
