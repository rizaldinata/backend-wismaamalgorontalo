<?php

namespace Modules\Resident\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Room\Models\Room;
use Modules\Resident\Models\Lease;
use App\Http\Controllers\Controller;

class AdminLeaseController extends Controller
{
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

        if ($lease) {
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
            'status' => 'required|in:active,rejected,cancelled',
        ]);

        $lease = Lease::find($id);

        if (!$lease) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $room = Room::find($lease->room_id);

        if ($request->status == 'active') {
            $lease->update(['status' => 'active']);
            $message = 'Sewa disetujui. Penghuni resmi aktif.';
        } else {
            $lease->update(['status' => $request->status]);
            $room->update(['status' => 'available']);
            $message = 'Sewa ditolak/dibatalkan. Kamar kembali tersedia';
        }

        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $lease
        ]);
    }
}
