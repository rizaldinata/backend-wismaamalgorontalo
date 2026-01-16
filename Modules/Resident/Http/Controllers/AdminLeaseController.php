<?php

namespace Modules\Resident\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use Modules\Resident\Models\Lease;
use Modules\Room\Enums\RoomStatus;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rules\Enum;
use Modules\Resident\Enums\LeaseStatus;
use Modules\Resident\Services\LeaseService;

class AdminLeaseController extends Controller
{
    use ApiResponse;

    protected $leaseService;

    public function __construct(LeaseService $leaseService)
    {
        $this->leaseService = $leaseService;
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Lease::with(['resident.user', 'room'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $leases = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Data pengajuan sewa',
            'data' => $leases
        ]);
    }

    public function show($id)
    {
        $lease = Lease::with(['resident.user', 'room'])->find($id);

        if (!$lease) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $lease
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            new Enum(LeaseStatus::class),
        ]);

        $lease = Lease::find($id);

        if (!$lease) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $room = Room::find($lease->room_id);

        if ($request->status == LeaseStatus::ACTIVE->value) {
            $lease->update(['status' => LeaseStatus::ACTIVE]);
            $message = 'Sewa disetujui. Penghuni resmi aktif.';
        } else {
            $lease->update(['status' => $request->status]);
            $room->update(['status' => RoomStatus::AVAILABLE]);
            $message = 'Sewa ditolak/dibatalkan. Kamar kembali tersedia';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $lease
        ]);
    }
}
