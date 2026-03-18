<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $requiredRole = strtolower($role);
        $userRole = strtolower((string) optional($request->user()?->role)->name);

        // Super-admin can access admin routes, but admin cannot access super-admin routes.
        if ($requiredRole === "admin" && in_array($userRole, ["admin", "super-admin"], true)) {
            return $next($request);
        }

        if ($userRole === $requiredRole && $userRole !== "") {
            return $next($request);
        }

        return response()->json(['message' => 'Forbidden'], 403);
    }
}
