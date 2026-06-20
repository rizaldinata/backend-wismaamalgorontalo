<?php

namespace Modules\Setting\Services;

use App\Contracts\ConfigProviderInterface;
use Modules\Setting\Repositories\Contracts\SettingRepositoryInterface;

class SettingService implements ConfigProviderInterface
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepository
    ) {}

    public function isFeatureEnabled(string $featureKey): bool
    {
        $value = $this->settingRepository->getValueByKey($featureKey, 'false');

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function setFeatureState(string $featureKey, bool $isEnabled, string $description = ''): void
    {
        $valueString = $isEnabled ? 'true' : 'false';
        $this->settingRepository->updateOrCreate($featureKey, $valueString, $description);
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
            'feature_daily_rental' => $this->isDailyRentalEnabled(),
            'feature_whatsapp_receipt' => $this->isFeatureEnabled('feature_whatsapp_receipt'),
            'feature_whatsapp_pdf_link' => $this->isFeatureEnabled('feature_whatsapp_pdf_link'),
            'feature_payment_midtrans' => $this->isMidtransEnabled(),
            'midtrans_enabled_payments' => $this->getEnabledMidtransPaymentMethods(),
            'bank_name' => $this->getSettingValue('bank_name', ''),
            'bank_account' => $this->getSettingValue('bank_account', ''),
            'bank_holder' => $this->getSettingValue('bank_holder', ''),
            'feature_pengeluaran_tetap' => $this->isPengeluaranTetapEnabled(),
            'pengeluaran_tetap_jenis_aktif' => $this->getJenisPengeluaranTetapAktif(),
        ];
    }

    public function isPengeluaranTetapEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_pengeluaran_tetap');
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
        return $this->isFeatureEnabled('feature_whatsapp_receipt');
    }

    public function isWhatsAppPdfLinkEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_whatsapp_pdf_link');
    }

    public function isMidtransEnabled(): bool
    {
        return $this->isFeatureEnabled('feature_payment_midtrans');
    }
}
