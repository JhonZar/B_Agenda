<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');
        $allowed = [
            env('FRONTEND_URL', 'http://localhost:5173'),
            'http://127.0.0.1:5173',
        ];

        $headers = [
            'Access-Control-Allow-Origin'      => in_array($origin, $allowed, true) ? $origin : $allowed[0],
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With, Accept, Origin',
            'Access-Control-Allow-Credentials' => 'true',
            'Vary'                              => 'Origin',
            'Access-Control-Max-Age'           => '86400',
        ];

        if ($request->getMethod() === 'OPTIONS') {
            return response('OK', 204)->withHeaders($headers);
        }

        $response = $next($request);
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
        return $response;
    }
}

