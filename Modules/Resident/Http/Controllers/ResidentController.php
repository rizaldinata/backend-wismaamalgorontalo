<?php

namespace Modules\Resident\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\Auth;
use Modules\Resident\Http\Requests\StoreResidentProfileRequest;
use Modules\Resident\Services\ResidentService;
use Modules\Resident\Transformers\ResidentResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResidentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ResidentService $residentService
    ) {}

    public function show()
    {
        try {
            $resident = $this->residentService->getProfileByUserId(Auth::id());
            return $this->apiSuccess(new ResidentResource($resident), 'Data profile penghuni berhasil diambil');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem', 500);
        }
    }

    public function store(StoreResidentProfileRequest $request)
    {
        $resident = $this->residentService->updateProfile(
            Auth::id(),
            $request->validated(),
            $request->file('ktp_photo')
        );

        return $this->apiSuccess(new ResidentResource($resident), 'Biodata berhasil disimpan');
    }
}
