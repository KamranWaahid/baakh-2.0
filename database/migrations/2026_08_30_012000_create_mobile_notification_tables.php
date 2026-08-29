<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'is_active', 'schedule_at']);
            $table->index(['type', 'status']);
        });

        Schema::create('mobile_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform', 20);
            $table->string('provider', 20)->default('fcm');
            $table->string('token', 512);
            $table->string('device_id', 80)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('locale', 10)->default('sd');
            $table->boolean('push_enabled')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique('token');
            $table->index(['platform', 'push_enabled']);
            $table->index(['user_id', 'platform']);
        });

        Schema::create('mobile_notification_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobile_notification_id')->constrained('mobile_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mobile_device_id')->nullable()->constrained('mobile_devices')->nullOnDelete();
            $table->string('status', 20)->default('delivered');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->string('error')->nullable();
            $table->timestamps();

            $table->unique(['mobile_notification_id', 'mobile_device_id'], 'mobile_receipt_notification_device_unique');
            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_notification_receipts');
        Schema::dropIfExists('mobile_devices');
        Schema::dropIfExists('mobile_notifications');
    }
};
