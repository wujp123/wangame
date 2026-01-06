<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * 允许所有代理（免费主机反向代理）
     */
    protected $proxies = '*';

    /**
     * 只信任 X-Forwarded-Proto 来识别 HTTPS
     */
    protected $headers = Request::HEADER_X_FORWARDED_PROTO;
}
