<?php

namespace Modules\Finance\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Services\FixedExpenseService;

class GenerateFixedExpenses extends Command
{
    protected $signature = 'finance:generate-fixed-expenses
                            {--bulan= : Bulan target (1-12, default bulan berjalan)}
                            {--tahun= : Tahun target (default tahun berjalan)}';

    protected $description = 'Buat entri pengeluaran tetap kosong untuk semua jenis aktif di bulan tertentu';

    public function __construct(private readonly FixedExpenseService $fixedExpenseService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $bulan = $this->option('bulan') ? (int) $this->option('bulan') : null;
        $tahun = $this->option('tahun') ? (int) $this->option('tahun') : null;

        $targetBulan = $bulan ?? (int) now()->format('n');
        $targetTahun = $tahun ?? (int) now()->format('Y');

        try {
            $jumlah = $this->fixedExpenseService->generateBulanIni($bulan, $tahun);

            $this->info("Berhasil membuat {$jumlah} entri pengeluaran tetap untuk {$targetBulan}/{$targetTahun}.");

            Log::info('GenerateFixedExpenses: selesai.', [
                'bulan'        => $targetBulan,
                'tahun'        => $targetTahun,
                'jumlah_dibuat' => $jumlah,
            ]);
        } catch (\Throwable $e) {
            $this->error("Gagal: {$e->getMessage()}");

            Log::error('GenerateFixedExpenses: gagal.', [
                'bulan' => $targetBulan,
                'tahun' => $targetTahun,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
