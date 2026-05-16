<?php

namespace Modules\Maintenance\Services;

use App\Events\Maintenance\LaporanKerusakanMasuk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Modules\Maintenance\Enums\MaintenanceStatus;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Repositories\Contracts\DamageReportRepositoryInterface;

class DamageReportService
{
    public function __construct(
        private readonly DamageReportRepositoryInterface $requestRepository,
        private readonly \App\Services\ImageService $imageService
    ) {}

    public function createReport(int $userId, array $data, array $images = []): MaintenanceRequest
    {
        $user = Auth::user() ?? \Modules\Auth\Models\User::find($userId);

        $data['reporter_user_id'] = $userId;
        $data['reporter_name'] = $user?->name ?? 'Unknown';
        $data['reporter_phone'] = $data['reporter_phone'] ?? null;
        $data['status'] = MaintenanceStatus::PENDING->value;
        $data['reported_at'] = now();

        $request = $this->requestRepository->createRequest($data);

        if (! empty($images)) {
            $imagePaths = $this->uploadImages($images, 'maintenance_requests');
            $this->requestRepository->addRequestImages($request, $imagePaths);
        }

        $request->load(['images', 'room']);

        LaporanKerusakanMasuk::dispatch(
            reportId: $request->id,
            reporterName: $request->reporter_name,
            reporterPhone: $request->reporter_phone ?? '',
            description: $request->description,
            roomId: $request->room_id,
            roomNumber: $request->room?->number,
        );

        return $request;
    }

    public function getResidentReports(int $userId)
    {
        return $this->requestRepository->getByUserId($userId);
    }

    public function getAllReports()
    {
        return $this->requestRepository->getAll();
    }

    public function getReportById(int $id)
    {
        return $this->requestRepository->findById($id);
    }

    public function addUpdate(int $adminUserId, int $requestId, array $data, array $images = [])
    {
        $request = $this->requestRepository->findById($requestId);

        $updateData = [
            'user_id' => $adminUserId,
            'description' => $data['description'],
            'status' => $data['status'] ?? null,
        ];

        $update = $this->requestRepository->addUpdate($request, $updateData);

        if (! empty($images)) {
            $imagePaths = $this->uploadImages($images, 'maintenance_updates');
            $this->requestRepository->addUpdateImages($update, $imagePaths);
        }

        if (isset($data['status'])) {
            $this->requestRepository->updateStatus($request, $data['status']);
        }

        return $update->load(['images', 'user']);
    }

    private function uploadImages(array $images, string $folder): array
    {
        $paths = [];
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $paths[] = $this->imageService->uploadAndCompress($image, $folder);
            }
        }

        return $paths;
    }
}
