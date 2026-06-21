<?php

namespace Modules\Finance\Listeners;

use Modules\Finance\Enums\InvoiceType;
use Modules\Finance\Events\PaymentSettled;
use Modules\Finance\Services\FineService;

class TandaiDendaLunas
{
    public function __construct(private readonly FineService $fineService) {}

    public function handle(PaymentSettled $event): void
    {
        $invoice = $event->payment->invoice;

        if ($invoice?->type !== InvoiceType::FINE) {
            return;
        }

        $this->fineService->tandaiLunas($invoice->id);
    }
}
