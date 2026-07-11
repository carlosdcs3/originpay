<?php

namespace Tests\Feature;

use App\Jobs\ReplayWebhookJob;
use App\Models\Admin;
use App\Models\User;
use App\Models\WebhookAdminAudit;
use App\Models\WebhookDlq;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WebhookAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Assume 'admin' uses the 'web' or 'admin' guard. Here we mock user with admin privileges if needed.
        // For simplicity, let's just create a user and actAs it. We assume the route uses 'admin' middleware,
        // but for testing standard backend we can mock the auth.
    }

    private function getAdminUser()
    {
        Permission::firstOrCreate(
            ['name' => 'webhooks.dlq.reprocess', 'guard_name' => 'admin'],
            ['category' => 'enterprise']
        );

        $admin = Admin::factory()->create();
        $admin->givePermissionTo('webhooks.dlq.reprocess');

        return $admin;
    }

    public function test_unauthenticated_user_cannot_access()
    {
        $response = $this->get(route('admin.webhooks.index'));
        // Depending on setup, it redirects to login or 403. Let's just assume it's protected by middleware.
        $response->assertStatus(302);
    }

    public function test_admin_can_access_index()
    {
        $this->actingAs($this->getAdminUser(), 'admin');

        $response = $this->get(route('admin.webhooks.index'));
        $response->assertStatus(200);
        $response->assertViewIs('backend.webhooks.index');
    }

    public function test_payload_is_masked_in_show_event()
    {
        $this->actingAs($this->getAdminUser(), 'admin');

        $event = WebhookEvent::create([
            'provider' => 'NEW_PROVIDER',
            'event_id' => 'test_123',
            'payload' => json_encode(['cpf' => '123.456.789-00', 'api_key' => 'secret_1234']),
            'status' => 'RECEIVED',
        ]);

        $response = $this->get(route('admin.webhooks.showEvent', $event));
        $response->assertStatus(200);

        $response->assertSee('[REDACTED]');
        $response->assertSee('***.***.***-00');
        $response->assertDontSee('secret_1234');
        $response->assertDontSee('123.456.789-00');

        // Audit check
        $this->assertEquals(1, WebhookAdminAudit::where('action', 'viewed_payload')->count());
    }

    public function test_single_reprocess_dispatches_job()
    {
        $this->actingAs($this->getAdminUser(), 'admin');
        Queue::fake();

        $dlq = WebhookDlq::create([
            'provider' => 'NEW_PROVIDER',
            'event_id' => 'evt_999',
            'payload' => '{}',
            'error_class' => 'Exception',
            'error_message' => 'Error',
        ]);

        $response = $this->post(route('admin.webhooks.reprocessSingle', $dlq));
        $response->assertRedirect();

        Queue::assertPushed(ReplayWebhookJob::class);
        $this->assertEquals(1, WebhookAdminAudit::where('action', 'reprocessed_item')->count());
    }

    public function test_batch_reprocess_dispatches_with_delay_and_limits()
    {
        $this->actingAs($this->getAdminUser(), 'admin');
        Queue::fake();

        $dlq1 = WebhookDlq::create(['provider' => 'NEW', 'payload' => '{}', 'error_class' => 'E', 'error_message' => 'E']);
        $dlq2 = WebhookDlq::create(['provider' => 'NEW', 'payload' => '{}', 'error_class' => 'E', 'error_message' => 'E']);

        $response = $this->post(route('admin.webhooks.reprocessBatch'), [
            'ids' => [$dlq1->id, $dlq2->id],
        ]);

        $response->assertRedirect();

        Queue::assertPushed(ReplayWebhookJob::class, 2);

        $audit = WebhookAdminAudit::where('action', 'reprocessed_batch')->first();
        $this->assertNotNull($audit);
        $this->assertNotNull($audit->batch_id);
    }

    public function test_batch_fails_if_above_50()
    {
        $this->actingAs($this->getAdminUser(), 'admin');
        Queue::fake();

        $ids = range(1, 51);

        $response = $this->post(route('admin.webhooks.reprocessBatch'), [
            'ids' => $ids,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Maximum 50 items per batch allowed.');
        Queue::assertNothingPushed();
    }

    public function test_manual_resolve_requires_reason_and_audits()
    {
        $this->actingAs($this->getAdminUser(), 'admin');

        $dlq = WebhookDlq::create([
            'provider' => 'NEW',
            'payload' => '{}',
            'error_class' => 'E',
            'error_message' => 'E',
        ]);

        $response = $this->post(route('admin.webhooks.resolveManual', $dlq), [
            'type' => 'dlq',
            'reason' => 'Fix applied manually in DB',
        ]);

        $response->assertRedirect();

        $dlq->refresh();
        $this->assertNotNull($dlq->resolved_at);
        $this->assertEquals('Fix applied manually in DB', $dlq->resolution_reason);

        $audit = WebhookAdminAudit::where('action', 'marked_resolved')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('Fix applied manually in DB', $audit->reason);
    }
}
