<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_audit_logs_screen_can_be_rendered_for_authorized_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index'));

        $response->assertOk();
    }

    public function test_user_model_events_automatically_create_audit_logs(): void
    {
        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('Super Admin');

        // Login as admin
        $this->actingAs($admin);

        // Creating a user should record a 'created' audit log
        $newUser = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'created',
            'subject_type' => User::class,
            'subject_id' => $newUser->id,
        ]);

        // Updating the user should record an 'updated' audit log
        $newUser->update(['name' => 'Budi Santoso S.Kom']);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $newUser->id,
        ]);

        $log = AuditLog::where('event', 'updated')
            ->where('subject_id', $newUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Budi Santoso', $log->properties['old']['name']);
        $this->assertEquals('Budi Santoso S.Kom', $log->properties['new']['name']);
    }

    public function test_sensitive_attributes_are_sanitized_in_audit_logs(): void
    {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => bcrypt('supersecretpassword'),
        ]);

        $log = AuditLog::where('event', 'created')
            ->where('subject_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('********', $log->properties['attributes']['password']);
    }

    public function test_manual_record_creates_audit_log_successfully(): void
    {
        $admin = User::factory()->create();

        AuditLog::record(
            event: 'system',
            description: 'Konfigurasi sistem berhasil diperbarui',
            user: $admin
        );

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'system',
            'description' => 'Konfigurasi sistem berhasil diperbarui',
            'user_id' => $admin->id,
        ]);
    }
}
