<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActionLog;



class LogUserAction
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        try {
            $method = $request->method();
            // Only log state-changing requests
            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $user = $request->user();
                ActionLog::create([
                    'user_id' => $user ? $user->id : null,
                    'action' => $request->route()?->getName() ?? ($request->method() . ' ' . $request->path()),
                    'method' => $method,
                    'route' => $request->path(),
                    'ip' => $request->ip(),
                    'user_agent' => substr($request->userAgent() ?? '', 0, 1000),
                    'meta' => [
                        'input' => $this->filterInput($request->except(['password', 'password_confirmation'])),
                        'status' => $response->getStatusCode(),
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // Don't break the request on logging errors
            \Log::error('Failed to write action log: ' . $e->getMessage());
        }

        return $response;
    }

    protected function filterInput(array $input)
    {
        // Keep payload small — drop large arrays/files
        foreach ($input as $k => $v) {
            if (is_array($v) && count($v) > 20) {
                $input[$k] = '<<large_array>>';
            }
        }
        return $input;
    }
}

