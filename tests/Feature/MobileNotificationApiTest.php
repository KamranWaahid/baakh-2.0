<?php

namespace Tests\Feature;

use App\Models\MobileDevice;
use App\Models\MobileNotification;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileNotificationApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
    }

    public function test_inbox_returns_published_notifications_for_the_requested_platform(): void
    {
        $this->makeNotification([
            'type' => 'daily_verse',
            'title_sd' => 'آڄ جو بيت',
            'title_en' => "Today's couplet",
            'body_sd' => 'بيت پڙهو',
            'body_en' => 'Read a couplet',
            'platforms' => ['android', 'ios'],
            'status' => 'published',
        ]);

        $this->makeNotification([
            'type' => 'app_update',
            'title_sd' => 'صرف اينڊرائيڊ',
            'title_en' => 'Android only',
            'body_sd' => 'اپڊيٽ',
            'body_en' => 'Update',
            'platforms' => ['android'],
            'status' => 'published',
        ]);

        $this->makeNotification([
            'type' => 'announcement',
            'title_sd' => 'ڊرافٽ',
            'title_en' => 'Draft',
            'body_sd' => 'لڪيل',
            'body_en' => 'Hidden',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/v1/mobile/notifications?lang=en&platform=ios');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains("Today's couplet"));
        $this->assertFalse($titles->contains('Android only'));
        $this->assertFalse($titles->contains('Draft'));
        $this->assertSame("Today's couplet", $response->json('data.0.title'));
        $this->assertSame('Read a couplet', $response->json('data.0.body'));
    }

    public function test_inbox_hides_expired_and_future_scheduled_notifications(): void
    {
        $this->makeNotification([
            'title_en' => 'Live',
            'status' => 'published',
            'schedule_at' => now()->subHour(),
        ]);
        $this->makeNotification([
            'title_en' => 'Expired',
            'status' => 'published',
            'expires_at' => now()->subMinute(),
        ]);
        $this->makeNotification([
            'title_en' => 'Later',
            'status' => 'scheduled',
            'schedule_at' => now()->addDay(),
        ]);

        $titles = collect($this->getJson('/api/v1/mobile/notifications?lang=en')->json('data'))->pluck('title');

        $this->assertTrue($titles->contains('Live'));
        $this->assertFalse($titles->contains('Expired'));
        $this->assertFalse($titles->contains('Later'));
    }

    public function test_guests_do_not_see_signed_in_only_notifications(): void
    {
        $this->makeNotification([
            'title_en' => 'For everyone',
            'audience' => 'everyone',
            'status' => 'published',
        ]);
        $this->makeNotification([
            'title_en' => 'Members only',
            'audience' => 'signed_in',
            'status' => 'published',
        ]);

        $titles = collect($this->getJson('/api/v1/mobile/notifications?lang=en')->json('data'))->pluck('title');

        $this->assertTrue($titles->contains('For everyone'));
        $this->assertFalse($titles->contains('Members only'));
    }

    public function test_device_can_register_and_unregister(): void
    {
        $this->postJson('/api/v1/mobile/devices', [
            'token' => 'fcm-token-123',
            'platform' => 'android',
            'device_id' => 'pixel-1',
            'locale' => 'sd',
        ])->assertOk()
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.provider', 'fcm')
            ->assertJsonPath('data.push_enabled', true);

        $this->assertSame(1, MobileDevice::count());

        $this->deleteJson('/api/v1/mobile/devices', [
            'token' => 'fcm-token-123',
        ])->assertOk();

        $this->assertFalse(MobileDevice::first()->push_enabled);
    }

    public function test_admin_mobile_notifications_require_authentication(): void
    {
        $this->getJson('/api/admin/mobile-notifications')->assertUnauthorized();
        $this->postJson('/api/admin/mobile-notifications', [])->assertUnauthorized();
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('mobile_notification_receipts');
        Schema::dropIfExists('mobile_devices');
        Schema::dropIfExists('mobile_notifications');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('title_sd');
            $table->string('title_en')->nullable();
            $table->text('body_sd');
            $table->text('body_en')->nullable();
            $table->string('cta_sd')->nullable();
            $table->string('cta_en')->nullable();
            $table->string('image_url')->nullable();
            $table->string('icon', 60)->nullable();
            $table->string('color', 30)->nullable();
            $table->json('platforms');
            $table->string('audience', 30)->default('everyone');
            $table->json('audience_filter')->nullable();
            $table->string('deep_link')->nullable();
            $table->string('web_path')->nullable();
            $table->nullableMorphs('linkable');
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('draft');
            $table->boolean('is_active')->default(true);
            $table->timestamp('schedule_at')->nullable();
            $table->string('recurrence', 20)->default('once');
            $table->time('recurrence_time')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('open_count')->default(0);
            $table->json('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('platform', 20);
            $table->string('provider', 20)->default('fcm');
            $table->string('token', 512);
            $table->string('device_id', 80)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('locale', 10)->default('sd');
            $table->boolean('push_enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mobile_notification_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mobile_notification_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('mobile_device_id')->nullable();
            $table->string('status', 20)->default('delivered');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();
        });
    }

    private function makeNotification(array $overrides = []): MobileNotification
    {
        return MobileNotification::create(array_merge([
            'type' => 'announcement',
            'title_sd' => 'اطلاع',
            'title_en' => 'Notice',
            'body_sd' => 'تفصيل',
            'body_en' => 'Details',
            'platforms' => ['android', 'ios'],
            'audience' => 'everyone',
            'priority' => 'normal',
            'status' => 'published',
            'is_active' => true,
            'recurrence' => 'once',
            'icon' => 'Bell',
            'color' => 'blue',
        ], $overrides));
    }
}
