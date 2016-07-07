<?php
require_once "../src/RedisLock.php";

$oRedisLock = new RedisLock();

echo "初始设置:我的钱10000元\n";
$oRedisLock->redis()->set("userOpenId:money", 10000);
echo "初始设置:总共10部手机\n";
$oRedisLock->redis()->set("phoneCount", 10);
echo "每个手机4000元\n";
