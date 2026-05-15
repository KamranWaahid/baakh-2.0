<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminTeamsApiTest extends TestCase
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
        $this->seedRolesAndLegacyOwner();
    }

    public function test_teams_index_serializes_legacy_owner_without_error(): void
    {
        Sanctum::actingAs(User::find(1));

        $response = $this->getJson('/api/admin/teams');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Content Team')
            ->assertJsonPath('data.0.owner.name', 'Legacy Admin')
            ->assertJsonPath('data.0.owner.email', 'legacy@example.com');
    }

    public function test_team_crud_lifecycle_for_authorized_user(): void
    {
        Sanctum::actingAs(User::find(1));

        $create = $this->postJson('/api/admin/teams', [
            'name' => 'Editorial',
            'description' => 'Editors only',
        ]);

        $create
            ->assertCreated()
            ->assertJsonPath('team.name', 'Editorial');

        $teamId = $create->json('team.id');

        $this->putJson("/api/admin/teams/{$teamId}", [
            'name' => 'Editorial Board',
            'description' => 'Updated',
        ])
            ->assertOk()
            ->assertJsonPath('team.name', 'Editorial Board');

        $this->deleteJson("/api/admin/teams/{$teamId}")
            ->assertOk();

        $this->assertDatabaseMissing('teams', ['id' => $teamId]);
    }

    public function test_team_show_denies_unrelated_user(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(
            Permission::whereIn('name', ['assign_roles', 'view_dashboard'])->get()
        );
        User::find(2)->assignRole('admin');

        $team = Team::first();
        Sanctum::actingAs(User::find(2));

        $this->getJson("/api/admin/teams/{$team->id}")
            ->assertForbidden();
    }

    private function seedRolesAndLegacyOwner(): void
    {
        $this->insertLegacyUser(1, 'legacy-admin', 'legacy@example.com', 'Legacy Admin');
        $this->insertLegacyUser(2, 'other-admin', 'other@example.com', 'Other Admin');

        foreach (['assign_roles', 'view_team', 'view_dashboard'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        User::find(1)->assignRole('super_admin');

        $team = Team::create([
            'name' => 'Content Team',
            'slug' => 'content-team',
            'description' => 'Main team',
            'owner_id' => 1,
            'status' => 'active',
        ]);

        $team->members()->create([
            'user_id' => 1,
            'role' => 'owner',
        ]);
    }

    private function insertLegacyUser(int $id, string $username, string $email, string $name): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'email_hash' => hash('sha256', strtolower($email)),
            'username' => $username,
            'phone' => null,
            'whatsapp' => null,
            'avatar' => null,
            'google_id' => null,
            'name_sd' => null,
            'status' => 'active',
            'role' => 'admin',
            'password' => 'not-used',
            'email_verified_at' => now(),
            'last_login_at' => null,
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'deleted_at' => null,
        ]);
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

        Schema::create('teams', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('role')->nullable();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('team_members', function ($table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
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

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('admin_notifications', function ($table) {
            $table->id();
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->string('link')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
}
