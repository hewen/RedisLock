<?php

/**
 * redis互斥锁功能
 * 实现参考http://redis.io/commands/setnx
 * 使用php的redis扩展(https://github.com/nicolasff/phpredis/)
 */

//锁默认过期时间
define('EXPIRE_TIME', 3);

//获取锁失败之后程序挂起时间,微妙单位,默认0.2秒
define('SLEEP_TIME', 200000);

//默认最大尝试获取锁的次数
define('LOCK_TRY_NUM', 5);

//redis主机名
define('HOST', 'localhost');

//redis端口
define('PORT', 6379);

class RedisLock {
    protected $oRedis;

    /**
     *过期时间,秒为单位
     */
    protected $expireTime;

    /**
     *获取锁失败之后程序挂起时间,微妙为单位
     */
    protected $sleepTime;

    /**
     *尝试获取锁的次数
     */
    protected $maxTryNum;

    /**
     * 获取锁的时间戳
     */
    protected $timestamp;

    public function __construct() {
        $this->oRedis = new \Redis();
        $bCon         = $this->oRedis->connect(HOST, PORT);
        if (!$bCon) {
            throw new Exception('连接Reis服务器失败！host:' . HOST . ',port:' . PORT);
        }

        $this->setExpire();
        $this->setSleepTime();
        $this->setMaxTryNum();
    }

    public function __destruct() {
        $this->oRedis->close();
    }

    /**
     * 设置开始时间,秒为单位
     */
    public function setTimestamp() {
        $this->timestamp = time();
    }

    /**
     * 设置锁过期时间,秒为单位
     * @param int $iValue
     */
    public function setExpire($iValue = EXPIRE_TIME) {
        if (preg_match('/^[1-9]\d*$/', $iValue)) {
            $this->expireTime = $iValue;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置程序休眠时间,微妙单位
     * @param int $iValue
     */
    public function setSleepTime($iValue = SLEEP_TIME) {
        if (preg_match('/^[1-9]\d*$/', $iValue)) {
            $this->sleepTime = $iValue;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置尝试获取锁的次数
     * @param int $iValue 尝试次数
     */
    public function setMaxTryNum($iValue = LOCK_TRY_NUM) {
        if (preg_match('/^[1-9]\d*$/', $iValue)) {
            $this->maxTryNum = $iValue;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取锁
     * @param string $sKey 要获取锁定的key
     * @return bool
     */
    public function getLock($sKey) {
        $this->setTimestamp();
        $bRet = $this->oRedis->setnx($sKey, $this->timestamp + $this->expireTime + 1);
        if ($bRet === true) {
            return true;
        }

        $iValue = $this->oRedis->get($sKey);
        if ($iValue === false) {
            return false;
        }

        //锁已经被其他人设置,获取的且过期,尝试解锁
        if ($iValue <= $this->timestamp) {
            $iOldValue = $this->oRedis->getSet($sKey, $this->timestamp + $this->expireTime + 1);
            if ($iOldValue <= $this->timestamp) {
                return true;
            }
        }
        //是否超过最大尝试次数,等待一定时间继续重头开始
        if (($this->maxTryNum--) > 0) {
            usleep($this->sleepTime);
            return $this->getLock($sKey);
        } else {
            return false;
        }

    }

    /**
     * 释放锁
     * 1. 一段时间内只有1个客户端来获取锁,客户端处理完相应操作之后,无论是否过期,都应该删除锁；
     * 2. 由于某些原因导致获取锁的客户端在之后的其他操作上消耗时间过长（超过获取锁时设置的有效期）,
     * 锁已经被其他客户端获取,并且更新了锁的过期时间,当前客户端不需要删除锁（由获取锁的客户端来删除）
     * @param string $sKey 要释放的lock在redis中对应的key
     */
    public function delLock($sKey) {
        $iCurrTimestamp = time();
        $this->oRedis->watch($sKey);
        $iValue = $this->oRedis->get($sKey);

        $expire = $this->timestamp + $this->expireTime + 1;
        if ($iValue == $expire || $iCurrTimestamp < $expire) {
            $this->oRedis
                ->multi()
                ->del($sKey)
                ->exec();
        } else {
            $this->oRedis->unwatch($sKey);
        }
    }

    /**
     * 返回redis对象,直接调用redis自身方法
     */
    public function redis() {
        return $this->oRedis;
    }
}
