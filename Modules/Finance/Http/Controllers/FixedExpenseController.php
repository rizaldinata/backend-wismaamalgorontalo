<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Http\Requests\UpdateFixedExpenseRequest;
use Modules\Finance\Repositories\Contracts\FixedExpenseEntryRepositoryInterface;
use Modules\Finance\Services\FixedExpenseService;
use Modules\Finance\Transformers\FixedExpenseEntryResource;

class FixedExpenseController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FixedExpenseService $fixedExpenseService,
        private readonly FixedExpenseEntryRepositoryInterface $repository,
    ) {}

    /**
     * Daftar Pengeluaran Tetap
     *
     * Mengambil daftar pengeluaran rutin bulanan (listrik, air, kebersihan, dll). (Hanya Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);
        $filters = $request->only(['jenis', 'bulan', 'tahun']);

        $entries = $this->fixedExpenseService->getAll($filters, $perPage);

        return FixedExpenseEntryResource::collection($entries)
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Detail Pengeluaran Tetap
     *
     * Melihat detail dari satu record pengeluaran bulanan. (Hanya Admin)
     */
    public function show(int $id): JsonResponse
    {
        $entry = $this->repository->findById($id);

        if (! $entry) {
            return $this->apiError('Entri pengeluaran tetap tidak ditemukan.', 404);
        }

        return $this->apiSuccess(new FixedExpenseEntryResource($entry), 'Detail pengeluaran tetap.');
    }

    /**
     * Update Pengeluaran Tetap
     *
     * Memasukkan jumlah nominal/tagihan real untuk pengeluaran di bulan terkait. (Hanya Admin)
     */
    public function update(UpdateFixedExpenseRequest $request, int $id): JsonResponse
    {
        $entry = $this->repository->findById($id);

        if (! $entry) {
            return $this->apiError('Entri pengeluaran tetap tidak ditemukan.', 404);
        }

        $updated = $this->fixedExpenseService->perbarui($entry, $request->validated(), Auth::id());

        return $this->apiSuccess(new FixedExpenseEntryResource($updated), 'Pengeluaran tetap berhasil diperbarui.');
    }

    /**
     * Generate Pengeluaran Bulan Ini
     *
     * Meng-generate secara otomatis kerangka tagihan rutin untuk bulan/tahun berjalan. (Hanya Admin)
     */
    public function generateBulanIni(Request $request): JsonResponse
    {
        $bulan = $request->query('bulan') ? (int) $request->query('bulan') : null;
        $tahun = $request->query('tahun') ? (int) $request->query('tahun') : null;

        $jumlah = $this->fixedExpenseService->generateBulanIni($bulan, $tahun);

        return $this->apiSuccess(['jumlah_dibuat' => $jumlah], "Berhasil membuat {$jumlah} entri pengeluaran tetap.");
    }

    /**
     * Status Pengisian Pengeluaran
     *
     * Mengecek apakah admin sudah melengkapi/mengisi semua tagihan rutin bulan ini. (Hanya Admin)
     */
    public function status(Request $request): JsonResponse
    {
        $bulan = $request->query('bulan') ? (int) $request->query('bulan') : null;
        $tahun = $request->query('tahun') ? (int) $request->query('tahun') : null;

        $status = $this->fixedExpenseService->getStatusBulanIni($bulan, $tahun);

        return $this->apiSuccess($status, 'Status pengisian pengeluaran tetap bulan ini.');
    }
}
