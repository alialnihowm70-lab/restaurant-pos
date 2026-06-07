<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Carbon;

class DateFilterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Intercept new filter parameters (preset or custom date range)
        if ($request->has('date_preset') || ($request->has('start_date') && $request->has('end_date'))) {
            $preset = $request->input('date_preset', 'custom');
            $startDate = null;
            $endDate = null;

            switch ($preset) {
                case 'today':
                    $startDate = Carbon::today()->startOfDay();
                    $endDate = Carbon::today()->endOfDay();
                    break;
                case 'yesterday':
                    $startDate = Carbon::yesterday()->startOfDay();
                    $endDate = Carbon::yesterday()->endOfDay();
                    break;
                case 'this_week':
                    $startDate = Carbon::now()->startOfWeek()->startOfDay();
                    $endDate = Carbon::now()->endOfWeek()->endOfDay();
                    break;
                case 'this_month':
                    $startDate = Carbon::now()->startOfMonth()->startOfDay();
                    $endDate = Carbon::now()->endOfMonth()->endOfDay();
                    break;
                case 'custom':
                default:
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
                        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();
                        $preset = 'custom';
                    } else {
                        $preset = 'this_month';
                        $startDate = Carbon::now()->startOfMonth()->startOfDay();
                        $endDate = Carbon::now()->endOfMonth()->endOfDay();
                    }
                    break;
            }

            session([
                'date_preset' => $preset,
                'start_date' => $startDate->toDateTimeString(),
                'end_date' => $endDate->toDateTimeString(),
            ]);

            // Redirect to the clean request URL without the query params
            return redirect($request->url());
        }

        // Initialize session default dates if missing
        if (!session()->has('date_preset')) {
            session([
                'date_preset' => 'this_month',
                'start_date' => Carbon::now()->startOfMonth()->startOfDay()->toDateTimeString(),
                'end_date' => Carbon::now()->endOfMonth()->endOfDay()->toDateTimeString(),
            ]);
        }

        // Share values globally as Carbon instances to all views
        view()->share('datePreset', session('date_preset'));
        view()->share('startDate', Carbon::parse(session('start_date')));
        view()->share('endDate', Carbon::parse(session('end_date')));

        return $next($request);
    }
}
