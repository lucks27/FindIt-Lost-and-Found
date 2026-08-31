<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkflowSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_tables_include_required_admin_fields(): void
    {
        $this->assertTrue(Schema::hasTable('reports'));
        $this->assertTrue(Schema::hasColumn('reports', 'type') || Schema::hasColumn('reports', 'report_type'));
        $this->assertTrue(Schema::hasColumn('reports', 'status'));
        $this->assertTrue(Schema::hasColumn('reports', 'requires_images'));

        $this->assertTrue(Schema::hasTable('notifications'));
        $this->assertTrue(Schema::hasColumn('notifications', 'student_id'));
        $this->assertTrue(Schema::hasColumn('notifications', 'recipient_role'));
        $this->assertTrue(Schema::hasColumn('notifications', 'data'));

        $this->assertTrue(Schema::hasTable('point_rules'));
        $this->assertTrue(Schema::hasColumn('point_rules', 'item_category') || Schema::hasColumn('point_rules', 'category_id'));
        $this->assertTrue(Schema::hasColumn('point_rules', 'base_points'));

        $this->assertTrue(Schema::hasTable('event_fines'));
        $this->assertTrue(Schema::hasColumn('event_fines', 'reference_number') || Schema::hasColumn('event_fines', 'event_name'));
    }
}
