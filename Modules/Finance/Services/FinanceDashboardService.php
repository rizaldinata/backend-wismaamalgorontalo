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
            'totoal_unpaid_invoices' => $this->invoiceRepository->getTotalUnpaid(),
            'pending_verification_count' => $this->paymentRepository->countPendingVerification(),
        ];
    }

    public function getRevenueChartData(): array
    {
        $rawData = $this->invoiceRepository->getMonthlyRevenue(6);

        return [
            'labels' => array_column($rawData, 'month'),
            'datasets' => [
                [
                    'label' => 'Total Pendapatan',
                    'data' => array_column($rawData, 'total'),
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
