<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\Guest\Http\Requests\StoreGuestRequest;
use Modules\Guest\Services\GuestService;
use Modules\Guest\Transformers\GuestResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GuestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestService $guestService
    ) {}

    /**
     * Daftar Tamu Saya
     *
     * Mengambil daftar tamu yang didaftarkan oleh pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $guests = $this->guestService->getMyGuests(Auth::id());

            return $this->apiSuccess(GuestResource::collection($guests), 'Daftar tamu berhasil diambil.');
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    /**
     * Tambah Tamu Baru
     *
     * Mendaftarkan tamu baru untuk pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreGuestRequest $request)
    {
        try {
            $guests = $this->guestService->addGuest(Auth::id(), $request->validated());

            return $this->apiSuccess(GuestResource::collection($guests), 'Data tamu berhasil ditambahkan.', 201);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    /**
     * Hapus Data Tamu
     *
     * Menghapus data tamu berdasarkan ID milik pengguna saat ini.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(int $id)
    {
        try {
            $this->guestService->deleteGuest(Auth::id(), $id);

            return $this->apiSuccess(null, 'Data tamu berhasil dihapus.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function checkout(int $id)
    {
        try {
            $guest = $this->guestService->checkoutMyGuest(Auth::id(), $id);

            return $this->apiSuccess(new GuestResource($guest), 'Tamu berhasil ditandai keluar.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function extend(\Illuminate\Http\Request $request, int $id)
    {
        $request->validate([
            'check_out_at' => 'required|date|after:today',
        ]);

        try {
            // Kita gunakan GuestService yang akan melakukan validasi di dalamnya
            // Tapi pastikan user memiliki akses ke guest tersebut (resolveOwnedGuest)
            // Namun karena logic extendGuestStay hanya butuh guestId, mari tambahkan verifikasi ownership
            $guest = \Modules\Guest\Models\Guest::find($id);
            if (!$guest) throw new NotFoundHttpException('Tamu tidak ditemukan.');
            
            $ownerId = $guest->user_id ?? $guest->lease?->resident?->user_id;
            if ($ownerId !== Auth::id()) {
                throw new HttpException(403, 'Anda tidak memiliki akses ke data tamu ini.');
            }

            $extendedGuest = $this->guestService->extendGuestStay($id, $request->check_out_at);

            return $this->apiSuccess(new GuestResource($extendedGuest), 'Waktu menginap tamu berhasil diperpanjang.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }
}
