<?php

namespace Zwen\RateLimit;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{

    /**
     * The rate limiter instance.
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new request throttler.
     *
     * @param  \App\Http\Middleware\RateLimiter  $limiter
     * @return void
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decaySeconds = 60)
    {
        //为每个请求生成唯一指纹
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            return $this->buildResponse($maxAttempts);
        }

        $response = $next($request);

        return $this->addHeaders($response, $maxAttempts);
    }

    /**
     * Resolve request signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function resolveRequestSignature($request)
    {
        return $request->fingerprint();
    }

    /**
     * Create a 'too many attempts' response.
     *
     * @param  string  $key
     * @param  int  $maxAttempts
     * @return \Illuminate\Http\Response
     */
    protected function buildResponse($maxAttempts)
    {
        $response = new Response('Too Many Attempts.', 429);


        return $this->addHeaders(
            $response, $maxAttempts
        );
    }

    /**
     * Add the limit header information to the given response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  int  $maxAttempts
     * @param  int  $remainingAttempts
     * @param  int|null  $retryAfter
     * @return \Illuminate\Http\Response
     */
    protected function addHeaders(Response $response, $maxAttempts)
    {
        $headers = [
            'X-RateLimit-Limit' => $maxAttempts,
        ];

        $response->headers->add($headers);

        return $response;
    }
}
