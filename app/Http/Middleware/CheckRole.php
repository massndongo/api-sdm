<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $allowedRoles = explode('|', $roles);

        if (!in_array($user->role->name, $allowedRoles)) {
            return response()->json(['error' => 'Forbidden: You do not have access to this resource'], 403);
        }

        return $next($request);
    }
}
