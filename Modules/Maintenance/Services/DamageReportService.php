<?php

namespace Modules\Maintenance\Services;

use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Maintenance\Enums\MaintenanceStatus;
use Modules\Maintenance\Models\MaintenanceRequest;
use Modules\Maintenance\Repositories\Contracts\DamageReportRepositoryInterface;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DamageReportService
{
    public function __construct(
        private readonly DamageReportRepositoryInterface $requestRepository,
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly \App\Services\ImageService $imageService
    ) {}

    public function createReport(int $userId, array $data, array $images = []): MaintenanceRequest
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new HttpException(403, 'Hanya penghuni yang dapat membuat laporan kerusakan.');
        }

        $data['resident_id'] = $resident->id;
        $data['status'] = MaintenanceStatus::PENDING->value;
        $data['reported_at'] = now();

        $request = $this->requestRepository->createRequest($data);

        if (!empty($images)) {
            $imagePaths = $this->uploadImages($images, 'maintenance_requests');
            $this->requestRepository->addRequestImages($request, $imagePaths);
        }

        return $request->load('images');
    }

    public function getResidentReports(int $userId)
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            return collect([]);
        }

        return $this->requestRepository->getByResidentId($resident->id);
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

        if (!empty($images)) {
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
                // Menggunakan global ImageService untuk compress & convert ke WebP
                $paths[] = $this->imageService->uploadAndCompress($image, $folder);
            }
        }
        return $paths;
    }
}
