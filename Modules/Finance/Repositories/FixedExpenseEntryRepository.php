<?php

namespace Modules\Finance\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Finance\Models\FixedExpenseEntry;
use Modules\Finance\Repositories\Contracts\FixedExpenseEntryRepositoryInterface;

class FixedExpenseEntryRepository implements FixedExpenseEntryRepositoryInterface
{
    public function create(array $data): FixedExpenseEntry
    {
        return FixedExpenseEntry::create($data);
    }

    public function update(FixedExpenseEntry $entry, array $data): FixedExpenseEntry
    {
        $entry->update($data);

        return $entry;
    }

    public function findById(int $id): ?FixedExpenseEntry
    {
        return FixedExpenseEntry::find($id);
    }

    public function findByPeriode(string $jenis, int $bulan, int $tahun): ?FixedExpenseEntry
    {
        return FixedExpenseEntry::where('jenis', $jenis)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();
    }

    public function getPaginated(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = FixedExpenseEntry::query();

        if (! empty($filters['jenis'])) {
            $query->where('jenis', $filters['jenis']);
        }

        if (! empty($filters['bulan'])) {
            $query->where('bulan', (int) $filters['bulan']);
        }

        if (! empty($filters['tahun'])) {
            $query->where('tahun', (int) $filters['tahun']);
        }

        return $query->orderByDesc('tahun')->orderByDesc('bulan')->paginate($perPage);
    }

    public function generateBulanan(array $jenisAktif, int $bulan, int $tahun): int
    {
        $created = 0;
        foreach ($jenisAktif as $jenis) {
            $exists = FixedExpenseEntry::where('jenis', $jenis)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->exists();

            if (! $exists) {
                FixedExpenseEntry::create([
                    'jenis' => $jenis,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                    'amount' => 0,
                    'is_filled' => false,
                ]);
                $created++;
            }
        }

        return $created;
    }

    public function getStatusBulan(array $jenisAktif, int $bulan, int $tahun): array
    {
        $entries = FixedExpenseEntry::whereIn('jenis', $jenisAktif)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy(fn ($e) => $e->jenis instanceof \BackedEnum ? $e->jenis->value : $e->jenis);

        $result = [];
        foreach ($jenisAktif as $jenis) {
            $result[$jenis] = $entries->get($jenis); // null jika belum diisi
        }

        return $result;
    }
}
