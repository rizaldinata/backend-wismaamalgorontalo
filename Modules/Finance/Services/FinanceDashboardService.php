<?php

namespace Modules\Finance\Services;

use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Finance\Repositories\Contracts\PaymentRepositoryInterface;

class FinanceDashboardService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {}

    public function getKpiSummary(): array
    {
        return [
            'total_revenue_this_month' => $this->invoiceRepository->getTotalRevenueThisMonth(),
            'revenue_monthly_rents' => $this->invoiceRepository->getRevenueByRentalTypeThisMonth('monthly'),
            'revenue_daily_rents' => $this->invoiceRepository->getRevenueByRentalTypeThisMonth('daily'),
            'total_unpaid_invoices_amount' => $this->invoiceRepository->getTotalUnpaid(),
            'total_overdue_amount' => $this->invoiceRepository->getTotalOverdueAmount(),
            'overdue_invoices_count' => $this->invoiceRepository->countOverdueInvoices(),
            'pending_verification_count' => $this->paymentRepository->countPendingVerification(),
        ];
    }

    public function getRevenueChartData(): array
    {
        $rawData = $this->invoiceRepository->getMonthlyRevenue(6);

        $labels = array_map(function ($item) {
            return $item['date_instance']->translatedFormat('M Y');
        }, $rawData);

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Pendapatan',
                    'data' => array_column($rawData, 'total'),
                ],
                [
                    'label' => 'Sewa Bulanan',
                    'data' => array_column($rawData, 'monthly_rent_revenue'),
                ],
                [
                    'label' => 'Sewa Harian',
                    'data' => array_column($rawData, 'daily_rent_revenue'),
                ]
            ]
        ];
    }

    public function getDueInvoicesWidget(int $limit = 5)
    {
        return $this->invoiceRepository->getDueInvoices($limit);
    }

    public function getPendingPaymentsWidget(int $limit = 5)
    {
        return $this->paymentRepository->getPendingPayments($limit);
    }
}
