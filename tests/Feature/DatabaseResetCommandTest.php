<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseResetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_command_preserves_admin_and_rebuilds_lost_found_schema(): void
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => bcrypt('secret123'),
            'status' => true,
            'role' => 'super_admin',
        ]);

        Schema::create('legacy_booking_table', function ($table) {
            $table->id();
            $table->string('legacy_field');
        });

        $this->assertTrue(Schema::hasTable('legacy_booking_table'));

        $exitCode = Artisan::call('lostfound:reset-database', ['--force' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(Schema::hasTable('admin'));
        $this->assertTrue(Schema::hasTable('reports'));
        $this->assertTrue(Schema::hasTable('item_categories'));
        $this->assertTrue(Schema::hasTable('point_rules'));
        $this->assertFalse(Schema::hasTable('legacy_booking_table'));

        $preservedAdmin = Admin::query()->where('id', $admin->id)->first();
        $this->assertNotNull($preservedAdmin);
        $this->assertSame($admin->email, $preservedAdmin->email);
        $this->assertSame($admin->role, $preservedAdmin->role);
        $this->assertSame($admin->name, $preservedAdmin->name);
    }
}
