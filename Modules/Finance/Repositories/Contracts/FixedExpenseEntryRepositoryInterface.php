<?php

namespace Modules\Finance\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\FixedExpenseEntry;

interface FixedExpenseEntryRepositoryInterface
{
    public function create(array $data): FixedExpenseEntry;

    public function update(FixedExpenseEntry $entry, array $data): FixedExpenseEntry;

    public function findById(int $id): ?FixedExpenseEntry;

    public function findByPeriode(string $jenis, int $bulan, int $tahun): ?FixedExpenseEntry;

    public function getPaginated(int $perPage, array $filters = []): LengthAwarePaginator;

    /** Kembalikan array indexed by jenis, berisi entry atau null untuk setiap jenis di $jenisAktif. */
    public function getStatusBulan(array $jenisAktif, int $bulan, int $tahun): array;

    /**
     * Buat entri kosong (is_filled=false, amount=0) untuk setiap jenis yang belum ada di periode tsb.
     * Kembalikan jumlah entri yang dibuat.
     */
    public function generateBulanan(array $jenisAktif, int $bulan, int $tahun): int;
}
