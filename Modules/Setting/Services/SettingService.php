<?php

namespace Modules\Setting\Services;

use App\Contracts\ConfigProviderInterface;
use Modules\Setting\Repositories\Contracts\SettingRepositoryInterface;

class SettingService implements ConfigProviderInterface
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepository,
        private readonly FeatureToggleService $featureToggleService
    ) {}

    public function isFeatureEnabled(string $featureKey): bool
    {
        return $this->featureToggleService->isEnabled($featureKey);
    }



    public function updateSetting(string $key, $value, string $description = ''): void
    {
        if (is_array($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $this->settingRepository->updateOrCreate($key, (string) $value, $description);
    }

    public function getSettingValue(string $key, mixed $default = ''): mixed
    {
        return $this->settingRepository->getValueByKey($key, $default);
    }

    public static function midtransMethodCatalog(): array
    {
        return [
            'qris'       => 'QRIS',
            'gopay'      => 'GoPay',
            'shopeepay'  => 'ShopeePay',
            'dana'       => 'DANA',
            'ovo'        => 'OVO',
            'linkaja'    => 'LinkAja',
            'bca_va'     => 'BCA Virtual Account',
            'bni_va'     => 'BNI Virtual Account',
            'bri_va'     => 'BRI Virtual Account',
            'permata_va' => 'Permata Virtual Account',
            'mandiri_va' => 'Mandiri Virtual Account',
        ];
    }

    public function getEnabledMidtransPaymentMethods(): array
    {
        $raw = $this->settingRepository->getValueByKey('midtrans_enabled_payment_methods', '[]');
        $decoded = json_decode(is_string($raw) ? $raw : '[]', true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setEnabledMidtransPaymentMethods(array $methods): void
    {
        $this->settingRepository->updateOrCreate(
            'midtrans_enabled_payment_methods',
            json_encode(array_values($methods)),
            'Daftar metode pembayaran Midtrans yang diaktifkan'
        );
    }

    public function getPublicSettings(): array
    {
        return [
            'wisma_name' => $this->getSettingValue('wisma_name', 'Sistem Manajemen Kos'),
            'wisma_address' => $this->getSettingValue('wisma_address', 'Jl. Wisma Amal No. 1, Gorontalo'),
            'wisma_phone' => $this->getSettingValue('wisma_phone', '0811-4300-XXX'),
            'wisma_email' => $this->getSettingValue('wisma_email', 'wismaamal@email.com'),
            'wisma_maps_link' => $this->getSettingValue('wisma_maps_link', 'https://maps.google.com'),
            'wisma_operational_hours' => $this->getSettingValue('wisma_operational_hours', 'Senin - Sabtu, 08.00 - 17.00 WITA'),
            'feature_daily_rental' => $this->isDailyRentalEnabled(),
            'feature_whatsapp_receipt' => $this->isFeatureEnabled('notif_receipt'),
            'feature_whatsapp_pdf_link' => $this->isFeatureEnabled('notif_pdf_link'),
            'feature_payment_midtrans' => $this->isMidtransEnabled(),
            'midtrans_enabled_payments' => $this->getEnabledMidtransPaymentMethods(),
            'bank_name' => $this->getSettingValue('bank_name', ''),
            'bank_account' => $this->getSettingValue('bank_account', ''),
            'bank_holder' => $this->getSettingValue('bank_holder', ''),
            'feature_pengeluaran_tetap' => $this->isPengeluaranTetapEnabled(),
            'pengeluaran_tetap_jenis_aktif' => $this->getJenisPengeluaranTetapAktif(),
            'midtrans_fee_config' => $this->getMidtransFeeConfig(),
            'landing_header_title' => $this->getSettingValue('landing_header_title', ''),
            'landing_header_subtitle' => $this->getSettingValue('landing_header_subtitle', ''),
            'landing_facilities' => $this->getSettingValue('landing_facilities', ''),
            'landing_highlighted_rooms' => $this->getSettingValue('landing_highlighted_rooms', '[]'),
        ];
    }

    public function isPengeluaranTetapEnabled(): bool
    {
        return $this->isFeatureEnabled('finance_fixed_expense');
    }

    public function getJenisPengeluaranTetapAktif(): array
    {
        $raw     = $this->settingRepository->getValueByKey('pengeluaran_tetap_jenis_aktif', '[]');
        $decoded = json_decode(is_string($raw) ? $raw : '[]', true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setJenisPengeluaranTetapAktif(array $jenis): void
    {
        $this->settingRepository->updateOrCreate(
            'pengeluaran_tetap_jenis_aktif',
            json_encode(array_values($jenis)),
            'Daftar jenis pengeluaran tetap yang diaktifkan (listrik, air, wifi)'
        );
    }

    public function isDailyRentalEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_daily_rental');
    }

    public function isWhatsAppReceiptEnabled(): bool
    {
        return $this->isFeatureEnabled('notif_receipt');
    }

    public function isWhatsAppPdfLinkEnabled(): bool
    {
        return $this->isFeatureEnabled('notif_pdf_link');
    }

    public function isMidtransEnabled(): bool
    {
        return $this->isFeatureEnabled('finance_midtrans');
    }

    public static function midtransFeeCatalog(): array
    {
        return [
            'bank_transfer' => ['label' => 'Transfer Bank (VA)',  'type' => 'flat'],
            'gopay'         => ['label' => 'GoPay',               'type' => 'percent'],
            'qris'          => ['label' => 'QRIS',                'type' => 'percent'],
            'shopeepay'     => ['label' => 'ShopeePay',           'type' => 'percent'],
            'dana'          => ['label' => 'DANA',                'type' => 'percent'],
            'ovo'           => ['label' => 'OVO',                 'type' => 'percent'],
            'linkaja'       => ['label' => 'LinkAja',             'type' => 'percent'],
        ];
    }

    public static function defaultMidtransFeeConfig(): array
    {
        return [
            'bearer' => 'merchant',
            'fees'   => [
                'bank_transfer' => ['type' => 'flat',    'amount' => 4000],
                'gopay'         => ['type' => 'percent', 'rate'   => 2.0],
                'qris'          => ['type' => 'percent', 'rate'   => 0.7],
                'shopeepay'     => ['type' => 'percent', 'rate'   => 2.0],
                'dana'          => ['type' => 'percent', 'rate'   => 1.5],
                'ovo'           => ['type' => 'percent', 'rate'   => 1.5],
                'linkaja'       => ['type' => 'percent', 'rate'   => 1.5],
            ],
        ];
    }

    public function getMidtransFeeConfig(): array
    {
        $raw     = $this->settingRepository->getValueByKey('midtrans_fee_config', null);
        $decoded = $raw ? json_decode(is_string($raw) ? $raw : '{}', true) : null;

        return is_array($decoded) ? $decoded : self::defaultMidtransFeeConfig();
    }

    public function setMidtransFeeConfig(array $config): void
    {
        $this->settingRepository->updateOrCreate(
            'midtrans_fee_config',
            json_encode($config),
            'Konfigurasi biaya transaksi Midtrans (bearer + tarif per metode)'
        );
    }
}
