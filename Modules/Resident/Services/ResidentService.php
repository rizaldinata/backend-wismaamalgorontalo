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
        private readonly ResidentRepositoryInterface $residentRepository
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
            $ktpPath = $ktpPhoto->store('ktp_images', 'public');
        }

        $data['ktp_photo_path'] = $ktpPath;

        $resident = $this->residentRepository->updateOrCreateByUserId($userId, $data);
        
        // Otomatis upgrade role ke resident
        $resident->user->syncRoles(['resident']);
        
        return $resident;
    }
}
