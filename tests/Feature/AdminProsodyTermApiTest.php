<?php

namespace Tests\Feature;

use App\Models\ProsodyTerm;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminProsodyTermApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');
        Cache::flush();

        $this->createSchema();
        $this->seedAdmin();
    }

    public function test_admin_can_create_update_and_list_prosody_terms(): void
    {
        Sanctum::actingAs(User::find(1));

        $create = $this->postJson('/api/admin/prosody-terms', [
            'title_sd' => 'بحر',
            'title_en' => 'Beher (Meter)',
            'desc_sd' => 'تال وارو نمونو',
            'desc_en' => 'A rhythmic pattern',
            'tech_detail_sd' => 'مثمن',
            'tech_detail_en' => 'Musamman',
            'logic_type' => 'arooz',
            'icon' => 'Ruler',
            'order' => 6,
        ]);

        $create->assertCreated()->assertJsonPath('data.title_en', 'Beher (Meter)');
        $id = $create->json('data.id');

        $this->putJson("/api/admin/prosody-terms/{$id}", [
            'title_sd' => 'بحر',
            'title_en' => 'Meter',
            'desc_en' => 'Updated introduction for the public card',
            'logic_type' => 'arooz',
            'icon' => 'Scale',
            'order' => 1,
        ])->assertOk()->assertJsonPath('data.title_en', 'Meter');

        $this->getJson('/api/admin/prosody-terms')
            ->assertOk()
            ->assertJsonPath('0.title_en', 'Meter');

        $public = $this->getJson('/api/v1/prosody?lang=en');
        $public->assertOk();
        $this->assertTrue(collect($public->json())->contains(fn ($row) => ($row['title'] ?? '') === 'Meter'));
        $this->assertTrue(collect($public->json())->contains(fn ($row) => ($row['description'] ?? '') === 'Updated introduction for the public card'));
    }

    public function test_guest_cannot_write_prosody_terms(): void
    {
        $this->postJson('/api/admin/prosody-terms', [
            'title_sd' => 'پٽي',
            'title_en' => 'Patti',
        ])->assertUnauthorized();

        $this->assertSame(0, ProsodyTerm::count());
    }

    private function seedAdmin(): void
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Legacy Admin',
            'email' => 'legacy@example.com',
            'email_hash' => hash('sha256', 'legacy@example.com'),
            'username' => 'legacy-admin',
            'status' => 'active',
            'role' => 'admin',
            'password' => 'not-used',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Permission::create(['name' => 'view_dashboard', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::all());
        User::find(1)->assignRole('super_admin');
    }

    private function createSchema(): void
    {
        Schema::create('users', function ($table) {
            $table->id();
            $table->text('name')->nullable();
            $table->text('email')->nullable();
            $table->string('email_hash')->nullable()->unique();
            $table->string('username')->nullable();
            $table->text('phone')->nullable();
            $table->text('whatsapp')->nullable();
            $table->string('avatar')->nullable();
            $table->string('google_id')->nullable();
            $table->string('name_sd')->nullable();
            $table->string('status')->default('active');
            $table->string('role')->default('user');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permissions', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->primary(['permission_id', 'role_id'], 'role_has_permissions_permission_id_role_id_primary');
        });

        Schema::create('personal_access_tokens', function ($table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prosody_terms', function ($table) {
            $table->id();
            $table->string('title_sd');
            $table->string('title_en');
            $table->text('desc_sd')->nullable();
            $table->text('desc_en')->nullable();
            $table->text('tech_detail_sd')->nullable();
            $table->text('tech_detail_en')->nullable();
            $table->string('logic_type')->nullable();
            $table->string('icon')->default('Info');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
