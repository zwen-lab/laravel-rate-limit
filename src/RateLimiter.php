<?php

namespace Zwen\RateLimit;

use Illuminate\Support\Facades\Redis;

class RateLimiter
{
    /**
     * Determine if the given key has been "accessed" too many times.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decaySeconds
     * @return bool
     */
    public function tooManyAttempts($key, $maxAttempts, $decaySeconds = 1)
    {
        //队列都还没有满
        if (Redis::llen($key) < $maxAttempts) {
            Redis::lpush($key, time());
            return false;
        }

        //获取早的一条数据
        $oldestRequest = Redis::lrange($key, $maxAttempts - 1, -1);
        if (time() - $oldestRequest[0] > $decaySeconds) {
            Redis::rpop($key);
            Redis::lpush($key, time());

            return false;
        }

        return true;
    }

    /**
     * Increment the counter for a given key for a given decay time.
     *
     * @param  string  $key
     * @param  int  $decayMinutes
     * @return int
     */
    public function hit($key)
    {
        return Redis::lpush($key, time());
    }
}
