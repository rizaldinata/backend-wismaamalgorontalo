<?php

namespace Modules\Finance\Tests\Unit;

use App\Events\Jadwal\JadwalDibuat;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Listeners\BuatInvoiceSetelahJadwalDibuat;
use Modules\Finance\Repositories\Contracts\InvoiceRepositoryInterface;
use Tests\TestCase;

class InvoiceListenerTest extends TestCase
{
    private InvoiceRepositoryInterface $invoiceRepository;
    private BuatInvoiceSetelahJadwalDibuat $listener;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceRepository = $this->createMock(InvoiceRepositoryInterface::class);
        $this->listener = new BuatInvoiceSetelahJadwalDibuat($this->invoiceRepository);
    }

    private function buatEventSewa(array $override = []): JadwalDibuat
    {
        // array_key_exists dipakai agar nilai null yang disengaja tidak diganti default
        $get = fn (string $key, mixed $default) => array_key_exists($key, $override)
            ? $override[$key]
            : $default;

        return new JadwalDibuat(
            scheduleId:   $get('scheduleId',   1),
            roomId:       $get('roomId',        10),
            roomNumber:   $get('roomNumber',    'A-01'),
            tipeJadwal:   $get('tipeJadwal',   'sewa'),
            startDate:    $get('startDate',    '2026-06-01'),
            endDate:      $get('endDate',      '2026-07-01'),
            tenantName:   $get('tenantName',   'Budi Santoso'),
            tenantPhone:  $get('tenantPhone',  '081234567890'),
            agreedPrice:  $get('agreedPrice',  500000.0),
            source:       $get('source',       'schedule'),
            tenantUserId: $get('tenantUserId', 5),
        );
    }

    // =========================================================
    // ✅ BERHASIL
    // =========================================================

    public function test_invoice_dibuat_ketika_jadwal_sewa_diterima(): void
    {
        $this->invoiceRepository
            ->expects($this->once())
            ->method('create');

        $this->listener->handle($this->buatEventSewa());
    }

    public function test_invoice_dibuat_dengan_status_unpaid(): void
    {
        $this->invoiceRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return $data['status'] === InvoiceStatus::UNPAID->value;
            }));

        $this->listener->handle($this->buatEventSewa());
    }

    public function test_invoice_menggunakan_schedule_id_jika_source_schedule(): void
    {
        $event = $this->buatEventSewa(['scheduleId' => 7, 'source' => 'schedule']);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return isset($data['schedule_id'])
                    && $data['schedule_id'] === 7
                    && ! isset($data['lease_id']);
            }));

        $this->listener->handle($event);
    }

    public function test_invoice_menggunakan_lease_id_jika_source_lease(): void
    {
        $event = $this->buatEventSewa(['scheduleId' => 3, 'source' => 'lease']);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return isset($data['lease_id'])
                    && $data['lease_id'] === 3
                    && ! isset($data['schedule_id']);
            }));

        $this->listener->handle($event);
    }

    public function test_nomor_invoice_mengandung_schedule_id(): void
    {
        $event = $this->buatEventSewa(['scheduleId' => 42]);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return str_contains($data['invoice_number'], '0042');
            }));

        $this->listener->handle($event);
    }

    public function test_data_snapshot_penghuni_tersimpan_di_invoice(): void
    {
        $event = $this->buatEventSewa([
            'tenantName'   => 'Andi Wijaya',
            'tenantPhone'  => '082200001111',
            'roomNumber'   => 'B-05',
            'tenantUserId' => 9,
        ]);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return $data['tenant_name']    === 'Andi Wijaya'
                    && $data['tenant_phone']   === '082200001111'
                    && $data['room_number']    === 'B-05'
                    && $data['tenant_user_id'] === 9;
            }));

        $this->listener->handle($event);
    }

    public function test_jumlah_tagihan_sesuai_harga_yang_disepakati(): void
    {
        $event = $this->buatEventSewa(['agreedPrice' => 750000.0]);

        $this->invoiceRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) {
                return $data['amount'] === 750000.0;
            }));

        $this->listener->handle($event);
    }

    // =========================================================
    // ❌ GAGAL / DIABAIKAN
    // =========================================================

    public function test_invoice_tidak_dibuat_untuk_jadwal_maintenance(): void
    {
        $event = $this->buatEventSewa(['tipeJadwal' => 'maintenance']);

        $this->invoiceRepository->expects($this->never())->method('create');

        $this->listener->handle($event);
    }

    public function test_invoice_tidak_dibuat_untuk_jadwal_kebersihan(): void
    {
        $event = $this->buatEventSewa(['tipeJadwal' => 'kebersihan']);

        $this->invoiceRepository->expects($this->never())->method('create');

        $this->listener->handle($event);
    }

    public function test_invoice_tidak_dibuat_untuk_jadwal_blokir(): void
    {
        $event = $this->buatEventSewa(['tipeJadwal' => 'blokir']);

        $this->invoiceRepository->expects($this->never())->method('create');

        $this->listener->handle($event);
    }

    public function test_invoice_tidak_dibuat_jika_harga_null(): void
    {
        $event = $this->buatEventSewa(['agreedPrice' => null]);

        $this->invoiceRepository->expects($this->never())->method('create');

        $this->listener->handle($event);
    }

    public function test_invoice_tidak_dibuat_jika_harga_nol(): void
    {
        $event = $this->buatEventSewa(['agreedPrice' => 0]);

        $this->invoiceRepository->expects($this->never())->method('create');

        $this->listener->handle($event);
    }

    public function test_invoice_tidak_dibuat_jika_harga_negatif(): void
    {
        $event = $this->buatEventSewa(['agreedPrice' => -100000]);

        $this->invoiceRepository->expects($this->never())->method('create');

        $this->listener->handle($event);
    }
}
