<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Auth\Models\UserProfile;

class UserProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ImageService $imageService) {}

    public function show(Request $request): JsonResponse
    {
        $profile = UserProfile::where('user_id', $request->user()->id)->first();

        if (! $profile) {
            return $this->apiError('Profil belum diisi', 404);
        }

        return $this->apiSuccess($this->transform($profile), 'Profil berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_card_number'          => 'required|string|max:20',
            'phone_number'            => 'required|string|max:20',
            'gender'                  => 'required|in:male,female',
            'job'                     => 'nullable|string|max:100',
            'address_ktp'             => 'required|string|max:500',
            'emergency_contact_name'  => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'ktp_photo'               => 'nullable|image|max:2048',
        ]);

        $data = [
            'user_id'                 => $request->user()->id,
            'id_card_number'          => $validated['id_card_number'],
            'phone_number'            => $validated['phone_number'],
            'gender'                  => $validated['gender'],
            'job'                     => $validated['job'] ?? null,
            'address_ktp'             => $validated['address_ktp'],
            'emergency_contact_name'  => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
        ];

        if ($request->hasFile('ktp_photo')) {
            $data['ktp_photo_path'] = $this->imageService->uploadAndCompress(
                $request->file('ktp_photo'),
                'ktp',
                800,
                80
            );
        }

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return $this->apiSuccess($this->transform($profile), 'Profil berhasil disimpan');
    }

    private function transform(UserProfile $profile): array
    {
        return [
            'id'                      => (string) $profile->id,
            'user_id'                 => (string) $profile->user_id,
            'id_card_number'          => $profile->id_card_number,
            'phone_number'            => $profile->phone_number,
            'gender'                  => $profile->gender,
            'job'                     => $profile->job,
            'address_ktp'             => $profile->address_ktp,
            'emergency_contact_name'  => $profile->emergency_contact_name,
            'emergency_contact_phone' => $profile->emergency_contact_phone,
            'ktp_photo_url'           => $profile->ktp_photo_url,
        ];
    }
}
