<?php

namespace Modules\Guest\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ModuleGate;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Modules\Guest\Http\Requests\StoreAdminGuestRequest;
use Modules\Guest\Repositories\Contracts\GuestRepositoryInterface;
use Modules\Guest\Services\GuestService;
use Modules\Guest\Transformers\AdminGuestResource;
use Modules\Schedule\Services\ScheduleService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminGuestController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GuestRepositoryInterface $guestRepository,
        private readonly GuestService $guestService
    ) {}

    public function index(Request $request)
    {
        try {
            $guests = $this->guestRepository->getAllPaginated($request->all());

            if (ModuleGate::isActive('Schedule')) {
                $scheduleIds = $guests->pluck('schedule_reference_id')->filter()->unique()->toArray();
                $schedules = ScheduleService::getByIds($scheduleIds);

                $guests->getCollection()->transform(function ($guest) use ($schedules) {
                    $guest->setAttribute('schedule_data', $schedules[$guest->schedule_reference_id] ?? null);

                    return $guest;
                });
            }

            return $this->apiSuccess(
                AdminGuestResource::collection($guests)->response()->getData(true),
                'Daftar tamu berhasil diambil.'
            );
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function store(StoreAdminGuestRequest $request)
    {
        try {
            $data = $request->validated();
            $scheduleId = (int) $data['schedule_id'];
            unset($data['schedule_id']);

            $guests = $this->guestService->addGuestBySchedule($scheduleId, $data);

            if (ModuleGate::isActive('Schedule')) {
                // Return value is a collection of guests (can be multiple for one schedule)
                $schedules = ScheduleService::getByIds([$scheduleId]);
                $guests->transform(function ($guest) use ($schedules) {
                    $guest->setAttribute('schedule_data', $schedules[$guest->schedule_reference_id] ?? null);

                    return $guest;
                });
            }

            return $this->apiSuccess(AdminGuestResource::collection($guests), 'Data tamu berhasil ditambahkan.', 201);
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function checkout(int $id)
    {
        try {
            $guest = $this->guestService->checkoutGuest($id);

            if (ModuleGate::isActive('Schedule')) {
                $schedule = ScheduleService::getById($guest->schedule_reference_id);
                $guest->setAttribute('schedule_data', $schedule);
            }

            return $this->apiSuccess(new AdminGuestResource($guest), 'Tamu berhasil ditandai keluar.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }

    public function extend(\Illuminate\Http\Request $request, int $id)
    {
        $request->validate([
            'check_out_at' => 'required|date|after:today',
        ]);

        try {
            $extendedGuest = $this->guestService->extendGuestStay($id, $request->check_out_at);

            if (ModuleGate::isActive('Schedule')) {
                $schedule = ScheduleService::getById($extendedGuest->schedule_reference_id);
                $extendedGuest->setAttribute('schedule_data', $schedule);
            }

            return $this->apiSuccess(new AdminGuestResource($extendedGuest), 'Waktu menginap tamu berhasil diperpanjang.');
        } catch (NotFoundHttpException $e) {
            return $this->apiError($e->getMessage(), 404);
        } catch (HttpException $e) {
            return $this->apiError($e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return $this->apiError('Terjadi kesalahan sistem.', 500);
        }
    }
}
