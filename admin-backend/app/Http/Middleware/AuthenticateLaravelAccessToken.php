<?php

namespace App\Http\Middleware;

use App\Support\LaravelAccessToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateLaravelAccessToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = LaravelAccessToken::verifyAndResolveUser($request->bearerToken());
        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        Auth::guard('web')->setUser($user);

        return $next($request);
    }
}
