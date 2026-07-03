<?php

namespace Modules\Finance\Services;

use App\Contracts\ConfigProviderInterface;
use Carbon\Carbon;
use DomainException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Enums\JenisPengeluaranTetap;
use Modules\Finance\Models\FixedExpenseEntry;
use Modules\Finance\Repositories\Contracts\FixedExpenseEntryRepositoryInterface;

class FixedExpenseService
{
    public function __construct(
        private readonly FixedExpenseEntryRepositoryInterface $repository,
        private readonly ExpenseService $expenseService,
        private readonly ConfigProviderInterface $settingService,
    ) {}

    public function guardFeatureAktif(): void
    {
        if (! $this->settingService->isPengeluaranTetapEnabled()) {
            throw new DomainException('Fitur Pengeluaran Tetap tidak diaktifkan. Aktifkan terlebih dahulu di Pengaturan.');
        }
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $this->guardFeatureAktif();

        return $this->repository->getPaginated($perPage, $filters);
    }

    public function getStatusBulanIni(?int $bulan = null, ?int $tahun = null): array
    {
        $this->guardFeatureAktif();

        $bulan = $bulan ?? (int) now()->format('n');
        $tahun = $tahun ?? (int) now()->format('Y');

        $jenisAktif = $this->settingService->getJenisPengeluaranTetapAktif();
        $statusMap = $this->repository->getStatusBulan($jenisAktif, $bulan, $tahun);

        $belumDiisi = [];
        $detail = [];

        foreach ($jenisAktif as $jenis) {
            $entry = $statusMap[$jenis] ?? null;

            if ($entry === null || ! $entry->is_filled) {
                $belumDiisi[] = $jenis;
                $detail[$jenis] = [
                    'filled' => false,
                    'entry_id' => $entry?->id,
                ];
            } else {
                $detail[$jenis] = [
                    'filled' => true,
                    'entry_id' => $entry->id,
                    'amount' => (float) $entry->amount,
                    'notes' => $entry->notes,
                    'recorded_at' => $entry->updated_at->toIso8601String(),
                ];
            }
        }

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'semua_terisi' => empty($belumDiisi),
            'belum_diisi' => $belumDiisi,
            'detail' => $detail,
        ];
    }

    public function generateBulanIni(?int $bulan = null, ?int $tahun = null): int
    {
        $this->guardFeatureAktif();

        $bulan = $bulan ?? (int) now()->format('n');
        $tahun = $tahun ?? (int) now()->format('Y');

        $jenisAktif = $this->settingService->getJenisPengeluaranTetapAktif();

        return $this->repository->generateBulanan($jenisAktif, $bulan, $tahun);
    }

    public function perbarui(FixedExpenseEntry $entry, array $data, ?int $userId = null): FixedExpenseEntry
    {
        $this->guardFeatureAktif();

        return DB::transaction(function () use ($entry, $data, $userId) {
            $updated = $this->repository->update($entry, [
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? $entry->notes,
                'is_filled' => true,
                'recorded_by' => $userId ?? $entry->recorded_by,
            ]);

            $this->expenseService->syncExpenseByReference(
                $updated->id,
                'fixed_utility',
                $this->buildExpenseData($updated)
            );

            return $updated;
        });
    }

    private function buildExpenseData(FixedExpenseEntry $entry): array
    {
        $jenisLabel = JenisPengeluaranTetap::from($entry->jenis instanceof JenisPengeluaranTetap ? $entry->jenis->value : $entry->jenis)->label();
        $namaBulan = Carbon::create($entry->tahun, $entry->bulan, 1)->translatedFormat('F');

        return [
            'title' => "{$jenisLabel} - {$namaBulan} {$entry->tahun}",
            'amount' => (float) $entry->amount,
            'expense_date' => Carbon::create($entry->tahun, $entry->bulan, 1)->toDateString(),
        ];
    }
}
