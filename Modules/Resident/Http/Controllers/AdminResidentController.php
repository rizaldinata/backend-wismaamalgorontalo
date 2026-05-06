<?php

namespace Modules\Resident\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Rental\Models\Lease;
use Modules\Room\Enums\RoomStatus;
use Modules\Room\Models\Room;
use Modules\Resident\Transformers\AdminResidentDetailResource;
use Modules\Resident\Transformers\AdminResidentResource;
use Exception;

class AdminResidentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $search = $request->get('search');

            $stats = [
                'penghuni_aktif' => Lease::where('status', 'active')
                    ->whereHas('latestInvoice', function ($query) {
                        $query->where('status', InvoiceStatus::PAID->value);
                    })
                    ->count(),
                'kontrak_pending' => Lease::where('status', 'pending')
                    ->where(function ($query) {
                        $query->whereDoesntHave('latestInvoice')
                            ->orWhereHas('latestInvoice', function ($invoiceQuery) {
                                $invoiceQuery->where('status', '!=', InvoiceStatus::PAID->value);
                            });
                    })
                    ->count(),
                'kamar_tersedia' => Room::where('status', RoomStatus::AVAILABLE->value)->count(),
            ];

            // 2. QUERY DATA TABEL PENGHUNI (Berbasis Kontrak Sewa)
            $query = Lease::with(['resident.user', 'room', 'latestInvoice']);

            // Fitur Pencarian (Search by Nama atau Nomor Kamar)
            if ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('resident.user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })->orWhereHas('room', function ($roomQuery) use ($search) {
                        $roomQuery->where('number', 'like', "%{$search}%");
                    })->orWhereHas('resident', function ($residentQuery) use ($search) {
                        $residentQuery->where('phone_number', 'like', "%{$search}%");
                    });
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

    public function show(int $id)
    {
        try {
            $lease = Lease::with(['resident.user', 'room'])->findOrFail($id);

            return $this->apiSuccess(new AdminResidentDetailResource($lease), 'Detail penghuni berhasil diambil');
        } catch (ModelNotFoundException $e) {
            return $this->apiError('Data penghuni tidak ditemukan', 404);
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }
}