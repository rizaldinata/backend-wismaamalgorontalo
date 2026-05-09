<?php

namespace Modules\Resident\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Resident\Models\Resident;
use Modules\Resident\Repositories\Contracts\ResidentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ResidentService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly \App\Services\ImageService $imageService
    ) {}

    public function getProfileByUserId(int $userId): Resident
    {
        $resident = $this->residentRepository->findByUserId($userId);

        if (!$resident) {
            throw new NotFoundHttpException('Anda belum melengkapi biodata');
        }

        return $resident;
    }

    public function updateProfile(int $userId, array $data, ?UploadedFile $ktpPhoto = null): Resident
    {
        $resident = $this->residentRepository->findByUserId($userId);
        $ktpPath = $resident ? $resident->ktp_photo_path : null;

        if ($ktpPhoto) {
            if ($ktpPath && Storage::disk('public')->exists($ktpPath)) {
                Storage::disk('public')->delete($ktpPath);
            }
            // Menggunakan ImageService untuk compress foto KTP
            $ktpPath = $this->imageService->uploadAndCompress($ktpPhoto, 'ktp_images');
        }

        $data['ktp_photo_path'] = $ktpPath;

        $resident = $this->residentRepository->updateOrCreateByUserId($userId, $data);
        
        // Role tetap sama (member), tidak perlu mengubah role
        // Status penghuni otomatis valid karena profil resident sudah dibuat
        
        return $resident;
    }
}
