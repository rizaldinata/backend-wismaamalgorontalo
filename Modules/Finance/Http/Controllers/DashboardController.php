<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\Finance\Services\FinanceDashboardService;

class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FinanceDashboardService $dashboardService
    ) {}

    /**
     * KPI Summary
     *
     * Mengambil ringkasan indikator kinerja utama (KPI) keuangan untuk bulan tertentu. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function kpiSummary(\Illuminate\Http\Request $request)
    {
        $month = $request->query('month') ? (int) $request->query('month') : null;
        $year = $request->query('year') ? (int) $request->query('year') : null;

        $data = $this->dashboardService->getKpiSummary($month, $year);

        return $this->apiSuccess($data, 'Data KPI summary berhasil diambil');
    }

    /**
     * Grafik Pendapatan
     *
     * Mengambil data pendapatan 6 bulan terakhir untuk divisualisasikan dalam bentuk grafik. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revenueChart()
    {
        $data = $this->dashboardService->getRevenueChartData();

        return $this->apiSuccess($data, 'Data grafik pendapatan 6 bulan terakhir');
    }

    /**
     * Tagihan Jatuh Tempo (Widget)
     *
     * Mengambil data singkat tentang tagihan yang sudah atau akan segera jatuh tempo. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dueInvoices()
    {
        $data = $this->dashboardService->getDueInvoicesWidget();

        return $this->apiSuccess($data, 'Data tagihan jatuh tempo berhasil diambil');
    }

    /**
     * Pembayaran Tertunda (Widget)
     *
     * Mengambil data singkat tentang pembayaran manual yang menunggu verifikasi admin. (Hanya Admin)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pendingPayments()
    {
        $data = $this->dashboardService->getPendingPaymentsWidget();

        return $this->apiSuccess($data, 'Data pembayaran tertunda berhasil diambil');
    }

    /**
     * Monitoring Midtrans
     *
     * Mengambil data ringkas terkait status transaksi gateway pembayaran (Midtrans). (Hanya Admin)
     */
    public function midtransMonitoring(): JsonResponse
    {
        $data = $this->dashboardService->getMidtransMonitoring();

        return $this->apiSuccess($data, 'Data monitoring Midtrans berhasil diambil');
    }
}
