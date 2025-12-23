<?php

namespace Modules\Resident\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Resident\Models\Lease;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Resident\Enums\LeaseStatus;
use Modules\Resident\Models\Resident;
use Modules\Resident\Http\Requests\StoreLeaseRequest;
use Modules\Resident\Services\LeaseService;
use Modules\Room\Enums\RoomStatus;

class LeaseController extends Controller
{
    use \App\Traits\ApiResponse;

    protected $leaseService;

    public function __construct(LeaseService $leaseService)
    {
        $this->leaseService = $leaseService;
    }

    public function store(StoreLeaseRequest $request)
    {
        $user = Auth::user();

        if (!$user->resident) {
            return $this->apiError('Anda harus melengkapi biodata penghuni terlebih dahulu', 403);
        }

        $room = Room::findOrFail($request->room_id);

        if ($room->status !== RoomStatus::AVAILABLE) {
            return $this->apiError('Maaf, kamar ini sudah tidak tersedia', 400);
        }

        try {
            $lease = $this->leaseService->createLease(
                Auth::user(),
                $room,
                $request->validated(),
            );

            return $this->apiSucces($lease, 'Sewa kamar berhasil dibuat', 201);
        } catch (\Exception $e) {
            return $this->apiError('Gagal memproses sewa: ' . $e->getMessage(), 500);
        }
    }

    public function myLeases()
    {
        $user = Auth::user();
        $resident = Resident::where('user_id', $user->id)->first();

        if (!$resident) {
            return response()->json([
                'status' => false,
                'data' => []
            ], 200);
        }

        $leases = Lease::with('room')->where('user_id', Auth::id())->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'List sewa saya',
            'data' => $leases
        ], 200);
    }

    public function uploadPayment(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = Auth::user();
        $resident = Resident::where('user_id', $user->id)->first();

        $lease = Lease::where('id', $id)
            ->where('resident_id', $resident->id)
            ->first();

        if (!$lease) {
            return response()->json([
                'status' => false,
                'message' => 'Data sewa tidak ditemukan.'
            ], 404);
        }

        if ($request->hasFile('payment_proof')) {
            $path = $request->file('payment_proof')->store('payments', 'public');

            $lease->update([
                'payment_proof' => $path,
                'status' => LeaseStatus::VERIFIED,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Bukti pembayaran berhasil diupload',
                'data' => $lease
            ], 200);
        }
        return response()->json([
            'status' => false,
            'message' => 'Gagal upload gambar.'
        ], 400);
    }
}
