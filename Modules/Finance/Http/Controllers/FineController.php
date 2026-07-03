<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Http\Requests\StoreFineRequest;
use Modules\Finance\Http\Requests\WaiveFineRequest;
use Modules\Finance\Repositories\Contracts\FineRepositoryInterface;
use Modules\Finance\Services\FineService;
use Modules\Finance\Transformers\FineResource;

class FineController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly FineRepositoryInterface $fineRepository,
        private readonly FineService $fineService,
    ) {}

    /**
     * Daftar Denda
     *
     * Mengambil daftar semua denda (fines) yang dibebankan kepada penghuni. (Hanya Admin)
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $filters = array_filter([
            'status' => $request->query('status'),
            'tenant_user_id' => $request->query('tenant_user_id'),
        ]);

        $fines = $this->fineRepository->getPaginated($perPage, $filters);

        return FineResource::collection($fines)
            ->additional(['success' => true, 'message' => 'Daftar denda berhasil dimuat.'])
            ->response();
    }

    /**
     * Buat Denda Baru
     *
     * Menambahkan denda baru untuk penghuni tertentu. (Hanya Admin)
     */
    public function store(StoreFineRequest $request): JsonResponse
    {
        $fine = $this->fineService->buatDenda($request->validated());

        return $this->apiSuccess(new FineResource($fine), 'Denda berhasil dibuat.', 201);
    }

    /**
     * Detail Denda
     *
     * Melihat detail lengkap sebuah denda. (Hanya Admin)
     */
    public function show(int $id): JsonResponse
    {
        $fine = $this->fineRepository->findById($id);

        if (! $fine) {
            return $this->apiError('Denda tidak ditemukan.', 404);
        }

        return $this->apiSuccess(new FineResource($fine), 'Detail denda berhasil dimuat.');
    }

    /**
     * Maafkan Denda (Waive)
     *
     * Menghapus kewajiban bayar denda dengan alasan tertentu (waive). (Hanya Admin)
     */
    public function waive(WaiveFineRequest $request, int $id): JsonResponse
    {
        $fine = $this->fineService->maafkanDenda($id, $request->validated('waive_reason'));

        return $this->apiSuccess(new FineResource($fine), 'Denda berhasil dimaafkan.');
    }

    /**
     * Batalkan Denda
     *
     * Membatalkan denda yang salah kirim/input. (Hanya Admin)
     */
    public function cancel(int $id): JsonResponse
    {
        $fine = $this->fineService->batalkanDenda($id);

        return $this->apiSuccess(new FineResource($fine), 'Denda berhasil dibatalkan.');
    }

    /**
     * Calon Penerima Denda
     *
     * Mendapatkan daftar user/penghuni yang valid untuk diberikan denda. (Hanya Admin)
     */
    public function eligibleUsers(): JsonResponse
    {
        $users = DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', 'Modules\Auth\Models\User');
            })
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->whereIn('roles.name', ['member', 'resident'])
            ->where('roles.guard_name', 'api')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'user_profiles.phone_number',
                'roles.name as role',
            ])
            ->orderByRaw("FIELD(roles.name, 'resident', 'member')")
            ->orderBy('users.name')
            ->get();

        if (\App\Support\ModuleGate::isActive('Schedule')) {
            $schedules = \Modules\Schedule\Services\ScheduleService::getActiveSewaByTenantUserIds($users->pluck('id')->toArray());
            $users->transform(function ($user) use ($schedules) {
                $schedule = $schedules[$user->id] ?? null;
                $user->active_room_number = $schedule['room']['number'] ?? null;
                $user->active_room_title = $schedule['room']['title'] ?? null;
                $user->schedule_end_date = isset($schedule['end_date']) ? substr($schedule['end_date'], 0, 10) : null;

                return $user;
            });
        } else {
            $users->transform(function ($user) {
                $user->active_room_number = null;
                $user->active_room_title = null;
                $user->schedule_end_date = null;

                return $user;
            });
        }

        return $this->apiSuccess($users, 'Daftar pengguna eligible berhasil dimuat.');
    }
}
