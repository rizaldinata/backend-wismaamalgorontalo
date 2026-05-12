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

    public function store(StoreGuestRequest $request)
    {
        try {
            $guest = $this->guestService->addGuest(Auth::id(), $request->validated());

            return $this->apiSuccess(new GuestResource($guest), 'Data tamu berhasil ditambahkan.', 201);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

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
}
