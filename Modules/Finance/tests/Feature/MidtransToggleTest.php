<?php

namespace Modules\Finance\tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Auth\Models\User;
use Modules\Finance\Enums\InvoiceStatus;
use Modules\Finance\Models\Invoice;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MidtransToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_use_midtrans_when_feature_flag_is_disabled()
    {
        config()->set('finance.midtrans.enabled', false);

        $tenant = User::factory()->create();
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);
        $tenant->assignRole('member');

        $invoice = Invoice::factory()->create([
            'amount' => 1000000,
            'status' => InvoiceStatus::UNPAID->value,
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($tenant)->withoutMiddleware()->postJson("/api/finance/invoices/{$invoice->id}/pay", [
            'payment_method' => 'midtrans',
        ]);
        $response->assertStatus(403);
        $this->assertStringContainsString('Penyedia layanan (Admin) sedang menonaktifkan fitur pembayaran dengan Midtrans saat ini.', $response->content());
    }

    public function test_can_use_manual_payment_even_when_midtrans_is_disabled()
    {
        config()->set('finance.midtrans.enabled', false);

        $tenant = User::factory()->create();
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);
        $tenant->assignRole('member');

        $invoice = Invoice::factory()->create([
            'amount' => 1000000,
            'status' => InvoiceStatus::UNPAID->value,
            'due_date' => now()->addDays(7),
        ]);

        $response = $this->actingAs($tenant)->withoutMiddleware()->postJson("/api/finance/invoices/{$invoice->id}/pay", [
            'payment_method' => 'manual',
            'sender_bank' => 'BCA',
            'sender_name' => 'John Doe',
            'proof_of_payment' => null,
        ]);

        // Should not be 500 due to DomainException about midtrans
        $this->assertNotEquals(500, $response->status());
        $this->assertStringNotContainsString('Penyedia layanan (Admin) sedang menonaktifkan fitur pembayaran dengan Midtrans saat ini.', $response->content());
    }
}
