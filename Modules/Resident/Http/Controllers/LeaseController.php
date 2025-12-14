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
use Modules\Resident\Models\Resident;

class LeaseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'start_date' => 'required|date|after_or_equal:today',
            'duration_months' => 'required|integer|min:1|max:12'
        ]);

        $user = Auth::user();

        $resident = Resident::where('user_id', $user->id)->first();
        if (!$resident) {
            return response()->json([
                'status' => false,
                'message' => 'Anda harus melengkapi biodata penghuni terlebih dahulu.',
            ], 403);
        }

        $room = Room::find($request->room_id);
        if (!$room->status !== 'available') {
            return response()->json([
                'status' => false,
                'message' => 'Maaf, kamar ini sudah tidak tersedia.'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $endDate = Carbon::parse($request->start_date)
                ->addMonts($request->duration_months)
                ->format('Y-m-d');

            $totalPrice = $room->price * $request->duration_months;

            $lease = Lease::creare([
                'resident_id' => $resident->id,
                'room_id' => $room->id,
                'start_date' => $request->start_date,
                'end_date' => $endDate,
                'status' => 'active',
                'total_price' => $totalPrice,
            ]);

            $room->update(['status' => 'occupied']);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sewa kamar berhasil dibuat',
                'data' => $lease
            ], 201);
        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan ' . $e->getMessage()
            ], 500);
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

        $leases = Lease::with('room')->where('resident_id', $resident->id)->latest()->get();

        return response()->json([
            'status' => true,
            'message' => 'List sewa saya',
            'data' => $leases
        ], 200);
    }
}
