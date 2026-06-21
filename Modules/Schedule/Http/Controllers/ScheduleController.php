<?php

namespace Modules\Schedule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Schedule\Services\ScheduleService;
use Modules\Schedule\Transformers\ScheduleResource;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ScheduleService $scheduleService) {}

    /**
     * Daftar Semua Jadwal
     *
     * Melihat daftar semua jadwal (sewa, maintenance, dll). (Hanya Admin)
     * @return Response|JsonResponse
     */
    public function index(Request $request): Response|JsonResponse
    {
        $schedules = $this->scheduleService->ambilSemuaJadwal($request->only(['room_id', 'type', 'status', 'per_page']));

        return ScheduleResource::collection($schedules)
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Buat Jadwal Baru
     *
     * Membuat jadwal baru untuk kamar (contoh: sewa, maintenance, dll). (Hanya Admin)
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $maxDate = now()->addDays(30)->toDateString();

        $validated = $request->validate([
            'room_id'          => 'required|integer',
            'type'             => 'required|in:sewa,maintenance,kebersihan,blokir',
            'start_date'       => [
                'required', 'date', 'after_or_equal:today',
                'before_or_equal:' . $maxDate,
            ],
            'end_date'         => 'required|date|after_or_equal:start_date',
            'tenant_name'      => 'nullable|string|max:255',
            'tenant_id_number' => 'nullable|string|max:50',
            'tenant_phone'     => 'nullable|string|max:20',
            'tenant_user_id'   => 'nullable|integer|exists:users,id',
            'agreed_price'     => 'nullable|numeric|min:0',
            'payment_scheme'   => 'nullable|in:full,dp',
        ], [
            'room_id.required'              => 'Kamar wajib dipilih.',
            'type.required'                 => 'Jenis jadwal wajib diisi.',
            'type.in'                       => 'Jenis jadwal tidak valid.',
            'start_date.required'           => 'Tanggal mulai wajib diisi.',
            'start_date.date'               => 'Format tanggal mulai tidak valid.',
            'start_date.after_or_equal'     => 'Tanggal mulai tidak boleh sebelum hari ini.',
            'start_date.before_or_equal'    => 'Tanggal mulai maksimal 30 hari ke depan (' . $maxDate . ').',
            'end_date.required'             => 'Tanggal selesai wajib diisi.',
            'end_date.date'                 => 'Format tanggal selesai tidak valid.',
            'end_date.after_or_equal'       => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'agreed_price.numeric'          => 'Harga sewa harus berupa angka.',
            'agreed_price.min'              => 'Harga sewa tidak boleh negatif.',
            'payment_scheme.in'             => 'Skema pembayaran tidak valid. Gunakan "full" atau "dp".',
            'tenant_user_id.exists'         => 'Akun penghuni tidak ditemukan.',
        ]);

        $validated['created_by'] = Auth::id();

        $schedule = $this->scheduleService->buatJadwal($validated);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil dibuat', 201);
    }

    /**
     * Detail Jadwal
     *
     * Mengambil data lengkap suatu jadwal berdasarkan ID.
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->ambilJadwalById($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Detail jadwal');
    }

    /**
     * Aktifkan Jadwal
     *
     * Mengubah status jadwal menjadi aktif (contoh: masa sewa dimulai). (Hanya Admin)
     * @return JsonResponse
     */
    public function aktifkan(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->aktifkanJadwal($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil diaktifkan');
    }

    /**
     * Selesaikan Jadwal
     *
     * Menandai jadwal telah selesai (contoh: masa sewa habis/checkout). (Hanya Admin)
     * @return JsonResponse
     */
    public function selesaikan(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->selesaikanJadwal($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil diselesaikan');
    }

    /**
     * Batalkan Jadwal
     *
     * Membatalkan jadwal sebelum dimulai (contoh: penghuni batal booking). (Hanya Admin)
     * @return JsonResponse
     */
    public function batalkan(int $id): JsonResponse
    {
        $schedule = $this->scheduleService->batalkanJadwal($id);

        return $this->apiSuccess(new ScheduleResource($schedule), 'Jadwal berhasil dibatalkan');
    }

    /**
     * Jadwal Saya
     *
     * Melihat daftar jadwal (riwayat sewa) milik pengguna yang sedang login.
     * @return Response|JsonResponse
     */
    public function mySchedules(Request $request): Response|JsonResponse
    {
        $filters = array_merge(
            $request->only(['type', 'status', 'per_page']),
            ['tenant_user_id' => Auth::id()]
        );

        $schedules = $this->scheduleService->ambilSemuaJadwal($filters);

        return ScheduleResource::collection($schedules)
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Jadwal per Kamar
     *
     * Mengambil semua jadwal yang terkait dengan ID kamar tertentu.
     * @return JsonResponse
     */
    public function byKamar(int $roomId): JsonResponse
    {
        $schedules = $this->scheduleService->ambilJadwalKamar($roomId);

        return $this->apiSuccess(ScheduleResource::collection($schedules), 'Daftar jadwal kamar');
    }
}
