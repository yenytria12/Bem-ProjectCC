<?php

namespace App\Http\Middleware;

use App\Models\PageVisit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageVisit
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only track GET requests and successful responses
        if ($request->isMethod('GET') && $response->isSuccessful()) {
            // Skip API requests, assets, and specific paths
            $path = $request->path();
            $skipPaths = ['livewire', 'storage', 'build', 'css', 'js', 'images', 'favicon'];

            $shouldSkip = false;
            foreach ($skipPaths as $skipPath) {
                if (str_starts_with($path, $skipPath)) {
                    $shouldSkip = true;
                    break;
                }
            }

            if (!$shouldSkip) {
                try {
                    PageVisit::create([
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'page_url' => $request->fullUrl(),
                        'user_id' => auth()->id(),
                        'visited_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Silently fail - don't break the request
                    \Log::warning('Failed to track page visit: ' . $e->getMessage());
                }
            }
        }

        return $response;
    }
}
