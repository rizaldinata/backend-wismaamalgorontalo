<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Setting\Services\FeatureToggleService;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $featureToggleService = app(FeatureToggleService::class);
        $path = $request->path();

        // Daftar mapping prefix path ke module key
        // Hanya memetakan modul yang bisa ditoggle (bisnis & core)
        $modulesMap = [
            // Granular Finance Features (Lebih spesifik, taruh di atas)
            'api/v1/finance/fines' => 'finance_fine',
            'api/v1/finance/invoices' => 'finance_invoice',
            'api/finance/expenses' => 'finance_expense',
            'api/finance/fixed-expenses' => 'finance_fixed_expense',
            'api/finance/me/leases' => 'finance_lease',
            'api/finance/dashboard' => 'finance_dashboard',
            
            // Granular Auth
            'api/v1/auth/register' => 'auth_registration',

            // Modul Utama
            'api/finance' => 'finance',
            'api/v1/damage-reports' => 'damage_report',
            'api/v1/schedules' => 'maintenance_schedule',
            'api/inventory' => 'inventory',
            'api/guests' => 'guest', // <-- menggunakan s (guests) sesuai route list
            'api/notification' => 'notification',
            
            // Modul Inti (Core)
            'api/room' => 'room',
            'api/schedule' => 'schedule',
            'api/v1/room-schedules' => 'schedule',
        ];

        foreach ($modulesMap as $prefix => $moduleKey) {
            // Jika request path dimulai dengan prefix modul
            if (str_starts_with($path, $prefix)) {
                // Cek apakah modul aktif menggunakan FeatureToggleService
                if (!$featureToggleService->isEnabled($moduleKey)) {
                    return response()->json([
                        'message' => 'Modul ' . ucfirst($moduleKey) . ' sedang dinonaktifkan oleh administrator.'
                    ], Response::HTTP_FORBIDDEN);
                }
                break; // Hanya perlu cek satu prefix terpanjang/pertama yang match
            }
        }

        return $next($request);
    }
}
