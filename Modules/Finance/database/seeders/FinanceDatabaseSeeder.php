<?php

namespace Modules\Finance\database\seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Enums\PaymentStatus;
use Modules\Finance\Models\Expense;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Rental\Models\Lease;

class FinanceDatabaseSeeder extends Seeder
{
    /**
     * Skenario seeder yang realistis untuk Wisma Amal Gorontalo:
     * - Invoice 6 bulan historis untuk semua lease aktif (data grafik dashboard)
     * - Invoice bulan ini: sebagian lunas, sebagian masih outstanding/overdue
     * - Beberapa payment masih pending (menunggu verifikasi admin)
     * - Satu payment pernah ditolak (rejected)
     * - Data pengeluaran operasional 6 bulan terakhir yang beragam & realistis
     */
    public function run(): void
    {
        $leases = Lease::with('room')->get();

        if ($leases->isEmpty()) {
            $this->command->warn('Tidak ada data lease. Jalankan RentalDatabaseSeeder terlebih dahulu.');
            return;
        }

        $this->seedInvoicesAndPayments($leases);
        $this->seedExpenses();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // INVOICE & PAYMENT
    // ─────────────────────────────────────────────────────────────────────────

    private function seedInvoicesAndPayments($leases): void
    {
        $now = Carbon::now();
        $invoiceSeq = 1;

        foreach ($leases as $lease) {
            $amount = $this->getLeaseAmount($lease);

            // ── A. LEASE AKTIF: buat 6 bulan historis + bulan ini ──────────
            if ($lease->status->value === 'active') {

                // 5 bulan ke belakang – semua LUNAS dengan payment VERIFIED
                for ($monthsAgo = 5; $monthsAgo >= 1; $monthsAgo--) {
                    $baseDate   = $now->copy()->subMonths($monthsAgo)->startOfMonth()->addDays(rand(1, 5));
                    $dueDate    = $baseDate->copy()->addDays(10);
                    $paidAt     = $baseDate->copy()->addDays(rand(1, 9));
                    $method     = $this->randomMethod();

                    $invoice = Invoice::create([
                        'lease_id'       => $lease->id,
                        'invoice_number' => $this->invoiceNumber($baseDate, $invoiceSeq++),
                        'amount'         => $amount,
                        'status'         => InvoiceStatus::PAID->value,
                        'due_date'       => $dueDate,
                        'created_at'     => $baseDate,
                        'updated_at'     => $paidAt,
                    ]);

                    Payment::create([
                        'invoice_id'       => $invoice->id,
                        'payment_method'   => $method,
                        'transaction_id'   => $this->transactionId($method, $invoice->id),
                        'payment_proof_path' => $method === 'manual' ? 'proofs/bukti_transfer_sample.jpg' : null,
                        'status'           => PaymentStatus::VERIFIED->value,
                        'admin_notes'      => null,
                        'created_at'       => $paidAt,
                        'updated_at'       => $paidAt->copy()->addHours(rand(1, 4)),
                    ]);
                }

                // Bulan ini — distribusi realistis:
                // - 60% LUNAS (verified)
                // - 20% PENDING verifikasi (upload bukti, tunggu admin)
                // - 20% BELUM BAYAR / OVERDUE (tidak ada payment sama sekali)
                $roll          = $lease->id % 5; // deterministik berdasar ID
                $invoiceDate   = $now->copy()->startOfMonth()->addDays(rand(1, 3));
                $dueDate       = $invoiceDate->copy()->addDays(10);

                $isOverdue = $dueDate->isPast();

                $invoiceStatus = match (true) {
                    $roll < 3   => InvoiceStatus::PAID->value,   // 60%
                    $roll === 3 => InvoiceStatus::UNPAID->value, // 20% – pending payment
                    default     => InvoiceStatus::UNPAID->value, // 20% – belum bayar
                };

                $invoice = Invoice::create([
                    'lease_id'       => $lease->id,
                    'invoice_number' => $this->invoiceNumber($invoiceDate, $invoiceSeq++),
                    'amount'         => $amount,
                    'status'         => $invoiceStatus,
                    'due_date'       => $dueDate,
                    'created_at'     => $invoiceDate,
                    'updated_at'     => $invoiceDate,
                ]);

                if ($roll < 3) {
                    // LUNAS
                    $paidAt = $invoiceDate->copy()->addDays(rand(1, 8));
                    $method = $this->randomMethod();

                    Payment::create([
                        'invoice_id'       => $invoice->id,
                        'payment_method'   => $method,
                        'transaction_id'   => $this->transactionId($method, $invoice->id),
                        'payment_proof_path' => $method === 'manual' ? 'proofs/bukti_transfer_sample.jpg' : null,
                        'status'           => PaymentStatus::VERIFIED->value,
                        'created_at'       => $paidAt,
                        'updated_at'       => $paidAt->copy()->addHours(2),
                    ]);

                } elseif ($roll === 3) {
                    // PENDING – sudah upload bukti, belum diverifikasi admin
                    $uploadedAt = $now->copy()->subDays(rand(0, 2));

                    Payment::create([
                        'invoice_id'         => $invoice->id,
                        'payment_method'     => 'manual',
                        'payment_proof_path' => 'proofs/bukti_transfer_pending.jpg',
                        'transaction_id'     => null,
                        'status'             => PaymentStatus::PENDING->value,
                        'admin_notes'        => null,
                        'created_at'         => $uploadedAt,
                        'updated_at'         => $uploadedAt,
                    ]);
                }
                // $roll === 4: tidak ada payment – overdue jika sudah lewat jatuh tempo

            // ── B. LEASE PENDING: satu invoice awal, sebagian sudah upload ──
            } elseif ($lease->status->value === 'pending') {
                $dueDate = $now->copy()->addDays(rand(3, 10));

                $invoice = Invoice::create([
                    'lease_id'       => $lease->id,
                    'invoice_number' => $this->invoiceNumber($now, $invoiceSeq++),
                    'amount'         => $amount,
                    'status'         => InvoiceStatus::UNPAID->value,
                    'due_date'       => $dueDate,
                    'created_at'     => $now->copy()->subDays(rand(1, 3)),
                    'updated_at'     => $now,
                ]);

                // Separuh lease pending sudah upload bukti bayar
                if ($lease->id % 2 === 0) {
                    Payment::create([
                        'invoice_id'         => $invoice->id,
                        'payment_method'     => 'manual',
                        'payment_proof_path' => 'proofs/bukti_transfer_pending.jpg',
                        'status'             => PaymentStatus::PENDING->value,
                        'created_at'         => $now->copy()->subHours(rand(1, 12)),
                        'updated_at'         => $now,
                    ]);
                }
            }
        }

        // ── C. Tambah 1 contoh payment REJECTED (realistis untuk history) ──
        $firstUnpaidInvoice = Invoice::where('status', InvoiceStatus::UNPAID->value)->first();
        if ($firstUnpaidInvoice) {
            $rejectedAt = $now->copy()->subDays(3);
            Payment::create([
                'invoice_id'         => $firstUnpaidInvoice->id,
                'payment_method'     => 'manual',
                'payment_proof_path' => 'proofs/bukti_blur.jpg',
                'status'             => PaymentStatus::REJECTED->value,
                'admin_notes'        => 'Bukti transfer tidak terbaca / buram. Mohon upload ulang bukti yang lebih jelas.',
                'created_at'         => $rejectedAt,
                'updated_at'         => $rejectedAt->copy()->addHours(1),
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PENGELUARAN OPERASIONAL
    // ─────────────────────────────────────────────────────────────────────────

    private function seedExpenses(): void
    {
        $now = Carbon::now();

        /**
         * Data pengeluaran realistis untuk wisma / kos:
         * Kategori: Utilitas, Perawatan, Kebersihan, Administrasi, Perlengkapan
         */
        $expenseTemplates = [
            // ── UTILITAS ──────────────────────────────────────────────────
            ['title' => 'Tagihan Listrik PLN', 'min' => 850_000,  'max' => 1_200_000, 'freq' => 'monthly', 'desc' => 'Pembayaran tagihan listrik PLN untuk seluruh area wisma.'],
            ['title' => 'Tagihan Air PDAM',    'min' => 180_000,  'max' => 320_000,   'freq' => 'monthly', 'desc' => 'Pembayaran tagihan air PDAM bulan berjalan.'],
            ['title' => 'Biaya Internet Indihome', 'min' => 350_000, 'max' => 350_000, 'freq' => 'monthly', 'desc' => 'Berlangganan internet IndiHome 50 Mbps untuk fasilitas bersama.'],

            // ── PERAWATAN & PERBAIKAN ──────────────────────────────────────
            ['title' => 'Servis AC Kamar',          'min' => 150_000, 'max' => 350_000, 'freq' => 'occasional', 'desc' => 'Servis dan isi freon AC di beberapa kamar.'],
            ['title' => 'Perbaikan Keran Bocor',     'min' => 75_000,  'max' => 200_000, 'freq' => 'occasional', 'desc' => 'Biaya tukang dan material untuk perbaikan keran bocor di kamar mandi.'],
            ['title' => 'Penggantian Lampu & Fitting', 'min' => 50_000, 'max' => 150_000, 'freq' => 'occasional', 'desc' => 'Pembelian lampu LED dan fitting untuk menggantikan yang rusak.'],
            ['title' => 'Perbaikan Kunci Pintu Kamar', 'min' => 80_000, 'max' => 180_000, 'freq' => 'occasional', 'desc' => 'Biaya tukang kunci untuk perbaikan atau penggantian kunci pintu.'],
            ['title' => 'Cat Dinding & Material',     'min' => 300_000, 'max' => 800_000, 'freq' => 'rare', 'desc' => 'Pengecatan ulang dinding kamar atau area koridor yang mulai kusam.'],

            // ── KEBERSIHAN ─────────────────────────────────────────────────
            ['title' => 'Perlengkapan Kebersihan', 'min' => 120_000, 'max' => 250_000, 'freq' => 'bimonthly', 'desc' => 'Pembelian sabun, cairan pembersih, sapu, dan perlengkapan kebersihan lainnya.'],
            ['title' => 'Upah Petugas Kebersihan',  'min' => 400_000, 'max' => 600_000, 'freq' => 'monthly',  'desc' => 'Honorarium petugas kebersihan harian untuk area umum.'],
            ['title' => 'Pewangi Ruangan & Kamar Mandi', 'min' => 50_000, 'max' => 120_000, 'freq' => 'bimonthly', 'desc' => 'Pengharum ruangan dan kamar mandi untuk kenyamanan penghuni.'],

            // ── PERLENGKAPAN & INVENTARIS ──────────────────────────────────
            ['title' => 'Pembelian Bantal & Guling', 'min' => 200_000, 'max' => 500_000, 'freq' => 'rare', 'desc' => 'Penggantian bantal dan guling yang sudah tidak layak pakai.'],
            ['title' => 'Penambahan Rak Handuk Kamar Mandi', 'min' => 90_000, 'max' => 220_000, 'freq' => 'rare', 'desc' => 'Pemasangan rak handuk baru di beberapa kamar mandi.'],
            ['title' => 'Pembelian Sabun Cuci Umum', 'min' => 35_000,  'max' => 80_000,  'freq' => 'bimonthly', 'desc' => 'Sabun cuci untuk area laundry/cuci bersama.'],

            // ── ADMINISTRASI & LAIN-LAIN ───────────────────────────────────
            ['title' => 'Biaya ATK & Administrasi', 'min' => 30_000,  'max' => 100_000, 'freq' => 'monthly',   'desc' => 'Pembelian alat tulis kantor dan kebutuhan administrasi.'],
            ['title' => 'Snack & Konsumsi Rapat',   'min' => 80_000,  'max' => 200_000, 'freq' => 'occasional', 'desc' => 'Konsumsi rapat bulanan pengelola wisma.'],
            ['title' => 'Biaya Parkir & Transportasi', 'min' => 20_000, 'max' => 75_000, 'freq' => 'monthly', 'desc' => 'Biaya transportasi untuk keperluan operasional wisma.'],
            ['title' => 'Iuran Keamanan Lingkungan', 'min' => 100_000, 'max' => 100_000, 'freq' => 'monthly', 'desc' => 'Iuran keamanan warga untuk keamanan lingkungan wisma.'],
        ];

        $expenseSeq = 1;

        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $targetMonth = Carbon::now()->subMonths($monthsAgo);

            foreach ($expenseTemplates as $tmpl) {
                // Tentukan apakah expense ini muncul di bulan ini
                $shouldCreate = match ($tmpl['freq']) {
                    'monthly'    => true,
                    'bimonthly'  => ($monthsAgo % 2 === 0),
                    'occasional' => (rand(0, 2) > 0),  // ~67%
                    'rare'       => (rand(0, 4) === 0), // ~20%
                    default      => false,
                };

                if (!$shouldCreate) continue;

                $amount      = rand((int)($tmpl['min'] / 1000), (int)($tmpl['max'] / 1000)) * 1000;
                $expenseDate = $targetMonth->copy()->startOfMonth()->addDays(rand(1, 25));

                // Jangan over-shoot ke masa depan
                if ($expenseDate->isFuture()) {
                    $expenseDate = Carbon::now()->subDays(rand(0, 3));
                }

                Expense::create([
                    'title'        => $tmpl['title'],
                    'description'  => $tmpl['desc'],
                    'amount'       => $amount,
                    'expense_date' => $expenseDate->toDateString(),
                    'reference_id' => null,
                    'reference_type' => null,
                    'created_at'   => $expenseDate,
                    'updated_at'   => $expenseDate,
                ]);

                $expenseSeq++;
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    private function getLeaseAmount(Lease $lease): int
    {
        if ($lease->rental_type === 'daily') {
            return (int) ($lease->room->price_daily ?? 150_000);
        }
        return (int) ($lease->room->price ?? 500_000);
    }

    private function invoiceNumber(Carbon $date, int $seq): string
    {
        return 'INV-' . $date->format('Ym') . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function randomMethod(): string
    {
        // 70% manual transfer, 30% midtrans/QRIS
        return rand(1, 10) <= 7 ? 'manual' : 'midtrans';
    }

    private function transactionId(string $method, int $invoiceId): ?string
    {
        if ($method === 'midtrans') {
            return 'TXN-MID-' . strtoupper(substr(md5($invoiceId . microtime()), 0, 12));
        }
        return 'TXN-TF-' . strtoupper(substr(md5($invoiceId . rand()), 0, 10));
    }
}
