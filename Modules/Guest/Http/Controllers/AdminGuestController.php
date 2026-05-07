<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Modules\Guest\Transformers\AdminGuestResource;

class AdminGuestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository
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
}
