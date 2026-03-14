<?php
// app/Http/Middleware/CorsMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    private array $allowedOrigins = [
        'https://bagroulette.xyz',
        'https://www.bagroulette.xyz',
        'https://bagroulette.vercel.app',
        'http://localhost:3000', // dev
    ];

    public function handle(Request $request, Closure $next)
    {
        $origin = $request->header('Origin', '');

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)
                ->header('Access-Control-Allow-Origin',  in_array($origin, $this->allowedOrigins) ? $origin : '')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With')
                ->header('Access-Control-Max-Age',       '86400');
        }

        $response = $next($request);

        if (in_array($origin, $this->allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin',      $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
