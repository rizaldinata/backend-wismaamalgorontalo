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
            $leaseId = (int) $data['lease_id'];
            unset($data['lease_id']);

            $guest = $this->guestService->addGuestByLease($leaseId, $data);
            $guest->loadMissing(['lease.resident.user', 'lease.room']);

            return $this->apiSuccess(new AdminGuestResource($guest), 'Data tamu berhasil ditambahkan.', 201);
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }
}
