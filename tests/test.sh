#!/bin/bash

curl http://localhost/RedisLock/tests/initSet.php
ab -n40 -c40 http://localhost/RedisLock/tests/RedisLockTest.php >/dev/null 2>&1
curl http://localhost/RedisLock/tests/result.php
