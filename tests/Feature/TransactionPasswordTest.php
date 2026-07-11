<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\WithdrawMethod;

class TransactionPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);
    }

    public function test_mandatory_creation_middleware_blocks_sensitive_routes()
    {
        // Try to access a POST route, should be blocked by CheckTransactionPassword
        $response = $this->actingAs($this->user)->post(route('user.developer.api-keys.store'), [
            'name' => 'Test Key',
            'environment' => 'test'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Por favor, crie sua senha transacional antes de realizar operações.');
    }

    public function test_can_create_transaction_password()
    {
        $response = $this->actingAs($this->user)->post(route('user.transaction-password.store'), [
            'transaction_password' => '1597',
            'transaction_password_confirmation' => '1597'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Senha transacional criada com sucesso.');
        
        $this->assertTrue($this->user->transactionPassword()->exists());
        $this->assertTrue(Hash::check('1597', $this->user->transactionPassword->password_hash));
    }

    public function test_cannot_create_weak_password()
    {
        $response = $this->actingAs($this->user)->post(route('user.transaction-password.store'), [
            'transaction_password' => '1234',
            'transaction_password_confirmation' => '1234'
        ]);

        $response->assertSessionHasErrors('transaction_password');
        $this->assertFalse($this->user->transactionPassword()->exists());
    }

    public function test_can_change_password_with_correct_credentials()
    {
        $this->user->transactionPassword()->create([
            'password_hash' => Hash::make('1597'),
            'failed_attempts' => 0
        ]);

        $response = $this->actingAs($this->user)->post(route('user.transaction-password.update'), [
            'current_password' => 'password123',
            'current_transaction_password' => '1597',
            'transaction_password' => '9876',
            'transaction_password_confirmation' => '9876'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        
        $this->user->refresh();
        $this->assertTrue(Hash::check('9876', $this->user->transactionPassword->password_hash));
    }

    public function test_cannot_change_password_with_wrong_login_password()
    {
        $this->user->transactionPassword()->create([
            'password_hash' => Hash::make('1597'),
            'failed_attempts' => 0
        ]);

        $response = $this->actingAs($this->user)->post(route('user.transaction-password.update'), [
            'current_password' => 'wrongpassword',
            'current_transaction_password' => '1597',
            'transaction_password' => '9876',
            'transaction_password_confirmation' => '9876'
        ]);

        $response->assertSessionHas('error');
        $this->assertTrue(Hash::check('1597', $this->user->transactionPassword->password_hash));
    }

    public function test_cannot_change_password_with_wrong_transaction_password()
    {
        $this->user->transactionPassword()->create([
            'password_hash' => Hash::make('1597'),
            'failed_attempts' => 0
        ]);

        $response = $this->actingAs($this->user)->post(route('user.transaction-password.update'), [
            'current_password' => 'password123',
            'current_transaction_password' => '0000',
            'transaction_password' => '9876',
            'transaction_password_confirmation' => '9876'
        ]);

        $response->assertSessionHas('error');
        $this->user->refresh();
        $this->assertEquals(1, $this->user->transactionPassword->failed_attempts);
    }

    public function test_lockout_after_five_failed_attempts()
    {
        $this->user->transactionPassword()->create([
            'password_hash' => Hash::make('1597'),
            'failed_attempts' => 4
        ]);

        $response = $this->actingAs($this->user)->post(route('user.transaction-password.update'), [
            'current_password' => 'password123',
            'current_transaction_password' => '0000',
            'transaction_password' => '9876',
            'transaction_password_confirmation' => '9876'
        ]);

        $this->user->refresh();
        $this->assertEquals(5, $this->user->transactionPassword->failed_attempts);
        $this->assertNotNull($this->user->transactionPassword->locked_until);

        // Try with correct password now, should fail due to lockout
        $response2 = $this->actingAs($this->user)->post(route('user.transaction-password.update'), [
            'current_password' => 'password123',
            'current_transaction_password' => '1597',
            'transaction_password' => '9876',
            'transaction_password_confirmation' => '9876'
        ]);

        $response2->assertSessionHas('error', 'Muitas tentativas incorretas. Tente novamente em alguns minutos.');
    }

    public function test_api_key_creation_requires_valid_transaction_password()
    {
        $this->user->transactionPassword()->create([
            'password_hash' => Hash::make('1597'),
            'failed_attempts' => 0
        ]);

        // Wrong TP
        $response = $this->actingAs($this->user)->post(route('user.developer.api-keys.store'), [
            'name' => 'Test Key',
            'environment' => 'test',
            'transaction_password' => '0000'
        ]);
        $response->assertSessionHas('error', 'Senha transacional incorreta. Verifique e tente novamente.');

        // Correct TP
        $response = $this->actingAs($this->user)->post(route('user.developer.api-keys.store'), [
            'name' => 'Test Key',
            'environment' => 'test',
            'transaction_password' => '1597'
        ]);
        
        // Assert successful redirect to index
        $response->assertRedirect(route('user.developer.api-keys.index'));
        $response->assertSessionHas('success');
    }
}
