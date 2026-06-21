<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Finance\Enums\FineStatus;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Fine;
use Modules\Finance\Models\Payment;
use Modules\Finance\Repositories\Contracts\FineRepositoryInterface;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Modules\Notification\Services\NotificationService;

class FineService
{
    public function __construct(
        private readonly FineRepositoryInterface $fineRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly FinanceService $financeService,
        private readonly NotificationService $notificationService,
    ) {}

    public function buatDenda(array $data): Fine
    {
        $user  = User::with('profile')->findOrFail($data['tenant_user_id']);
        $phone = $user->profile?->phone_number;

        $fine = $this->fineRepository->create([
            'tenant_user_id' => $user->id,
            'schedule_id'    => $data['schedule_id'] ?? null,
            'amount'         => $data['amount'],
            'reason'         => $data['reason'],
            'status'         => FineStatus::UNPAID->value,
        ]);

        if ($phone) {
            $this->kirimNotifikasiDenda($fine, $user->name, $phone);
        }

        return $fine;
    }

    public function maafkanDenda(int $fineId, string $waiveReason): Fine
    {
        $fine = $this->fineRepository->findById($fineId);

        if (! $fine) {
            throw new \DomainException('Denda tidak ditemukan.');
        }

        if ($fine->status !== FineStatus::UNPAID) {
            throw new \DomainException('Hanya denda yang belum dibayar yang bisa dimaafkan.');
        }

        return $this->fineRepository->update($fine, [
            'status'       => FineStatus::WAIVED->value,
            'waive_reason' => $waiveReason,
        ]);
    }

    public function batalkanDenda(int $fineId): Fine
    {
        $fine = $this->fineRepository->findById($fineId);

        if (! $fine) {
            throw new \DomainException('Denda tidak ditemukan.');
        }

        if ($fine->status !== FineStatus::UNPAID) {
            throw new \DomainException('Hanya denda yang belum dibayar yang bisa dibatalkan.');
        }

        return $this->fineRepository->update($fine, ['status' => FineStatus::CANCELLED->value]);
    }

    public function bayarDenda(int $userId, array $fineIds, array $paymentData): Payment
    {
        return DB::transaction(function () use ($userId, $fineIds, $paymentData) {
            $fines = $this->fineRepository->findManyByIds($fineIds);

            foreach ($fines as $fine) {
                if ($fine->tenant_user_id !== $userId) {
                    throw new \DomainException('Denda tidak ditemukan atau bukan milik Anda.');
                }
                if ($fine->status !== FineStatus::UNPAID) {
                    throw new \DomainException("Denda #{$fine->id} tidak dalam status unpaid.");
                }
            }

            if ($fines->count() !== count($fineIds)) {
                throw new \DomainException('Beberapa denda tidak ditemukan.');
            }

            // Batalkan invoice denda lama yang masih unpaid untuk denda-denda ini
            $existingInvoiceIds = DB::table('fine_invoice')
                ->whereIn('fine_id', $fineIds)
                ->pluck('invoice_id');

            if ($existingInvoiceIds->isNotEmpty()) {
                DB::table('invoices')
                    ->whereIn('id', $existingInvoiceIds)
                    ->where('status', InvoiceStatus::UNPAID->value)
                    ->update(['status' => InvoiceStatus::CANCELLED->value, 'updated_at' => now()]);
            }

            // Ambil nama & nomor HP dari relasi user untuk snapshot invoice
            $user  = User::with('profile')->findOrFail($userId);
            $phone = $user->profile?->phone_number;

            $totalAmount   = $fines->sum('amount');
            $suffix        = strtoupper(substr(md5(uniqid()), 0, 6));
            $invoiceNumber = 'FINE-' . date('Ymd') . '-' . str_pad($userId, 4, '0', STR_PAD_LEFT) . '-' . $suffix;

            $invoice = $this->invoiceRepository->create([
                'type'           => 'fine',
                'invoice_number' => $invoiceNumber,
                'amount'         => $totalAmount,
                'status'         => InvoiceStatus::UNPAID->value,
                'due_date'       => now()->toDateString(),
                'tenant_user_id' => $userId,
                'tenant_name'    => $user->name,
                'tenant_phone'   => $phone,
            ]);

            $pivotRows = $fines->map(fn ($fine) => [
                'fine_id'    => $fine->id,
                'invoice_id' => $invoice->id,
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            DB::table('fine_invoice')->insert($pivotRows);

            return $this->financeService->processPayment($invoice->id, $paymentData);
        });
    }

    public function tandaiLunas(int $invoiceId): void
    {
        $fineIds = DB::table('fine_invoice')
            ->where('invoice_id', $invoiceId)
            ->pluck('fine_id');

        if ($fineIds->isEmpty()) {
            return;
        }

        Fine::whereIn('id', $fineIds)
            ->where('status', FineStatus::UNPAID->value)
            ->update([
                'status'  => FineStatus::PAID->value,
                'paid_at' => now(),
            ]);
    }

    private function kirimNotifikasiDenda(Fine $fine, string $tenantName, string $phone): void
    {
        $nominal = 'Rp ' . number_format((float) $fine->amount, 0, ',', '.');

        $message = "*PEMBERITAHUAN DENDA*\n"
            . "Wisma Amal Gorontalo\n\n"
            . "Yth. Bpk/Ibu {$tenantName},\n\n"
            . "Anda mendapatkan denda dengan rincian berikut:\n\n"
            . "Nominal  : *{$nominal}*\n"
            . "Alasan   : {$fine->reason}\n\n"
            . "Silakan lakukan pembayaran denda melalui aplikasi Wisma Amal Gorontalo.\n"
            . "Jika ada keberatan, harap hubungi manajemen.\n\n"
            . "Hormat kami,\n*Manajemen Wisma Amal Gorontalo*";

        $this->notificationService->sendCustomNotification($phone, $message);
    }
}
