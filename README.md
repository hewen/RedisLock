redis lock (redis并发锁)
======

* 实现参考[http://redis.io/commands/setnx](http://redis.io/commands/setnx)
* 使用php的[redis扩展](https://github.com/nicolasff/phpredis)

* 使用方法
```php
require_once "/path/to/RedisLock.php";

$oRedisLock = new RedisLock();
$oRedisLock->getLock("XXXX");
//do something
$oRedisLock->delLock("XXXX");
```

* 并发测试了一个简单的场景，买手机，先扣钱再扣总量，用ab模拟了用户并发来刷手机，测试运行：
```bash
% bash tests/test.sh
初始设置:我的钱10000元
初始设置:总共iphone10部
运行结果:
我的钱:2000
剩余iphone:8
```
