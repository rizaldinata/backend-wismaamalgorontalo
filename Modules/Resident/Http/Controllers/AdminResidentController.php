<?php

namespace Modules\Resident\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Rental\Models\Lease;
use Modules\Room\Models\Room;
use Modules\Resident\Transformers\AdminResidentResource;
use Carbon\Carbon;
use Exception;

class AdminResidentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 10);
            $search = $request->get('search');

            // 1. MENGHITUNG DATA STAT CARD (Statistik Atas)
            $stats = [
                'penghuni_aktif' => Lease::where('status', 'active')->count(),
                'kontrak_pending' => Lease::where('status', 'pending')->count(),
                'kontrak_akan_berakhir' => Lease::where('status', 'active')
                                                ->whereBetween('end_date', [Carbon::now(), Carbon::now()->addDays(30)])
                                                ->count(),
                'kamar_tersedia' => Room::where('status', 'available')->count(),
            ];

            // 2. QUERY DATA TABEL PENGHUNI (Berbasis Kontrak Sewa)
            $query = Lease::with(['user.resident', 'room', 'latestInvoice']);

            // Fitur Pencarian (Search by Nama atau Nomor Kamar)
            if ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('room', function ($q) use ($search) {
                    $q->where('number', 'like', "%{$search}%");
                });
            }

            $leases = $query->latest()->paginate($perPage);

            // 3. RETURN RESPONSE
            return response()->json([
                'status' => true,
                'message' => 'Daftar penghuni berhasil diambil',
                'data' => [
                    'stats' => $stats,
                    'residents' => AdminResidentResource::collection($leases)->response()->getData(true),
                ]
            ]);

        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }
}