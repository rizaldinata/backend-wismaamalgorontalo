<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Modules\Guest\Http\Requests\StoreAdminGuestRequest;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Modules\Guest\Services\GuestService;
use Modules\Guest\Transformers\AdminGuestResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminGuestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly GuestService $guestService
    ) {}

    public function index(Request $request)
    {
        try {
            $guests = $this->guestRepository->getAllPaginated($request->all());

            return $this->apiSuccess(
                AdminGuestResource::collection($guests)->response()->getData(true),
                'Daftar tamu berhasil diambil.'
            );
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function store(StoreAdminGuestRequest $request)
    {
        try {
            $data = $request->validated();
            $scheduleId = (int) $data['schedule_id'];
            unset($data['schedule_id']);

            $guests = $this->guestService->addGuestBySchedule($scheduleId, $data);
            $guests->loadMissing(['schedule.tenant', 'schedule.room']);

            return $this->apiSuccess(AdminGuestResource::collection($guests), 'Data tamu berhasil ditambahkan.', 201);
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
            $guest = $this->guestService->checkoutGuest($id);
            $guest->loadMissing(['schedule.tenant', 'schedule.room']);

            return $this->apiSuccess(new AdminGuestResource($guest), 'Tamu berhasil ditandai keluar.');
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
            $extendedGuest = $this->guestService->extendGuestStay($id, $request->check_out_at);
            $extendedGuest->loadMissing(['schedule.tenant', 'schedule.room']);

            return $this->apiSuccess(new AdminGuestResource($extendedGuest), 'Waktu menginap tamu berhasil diperpanjang.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }
}
