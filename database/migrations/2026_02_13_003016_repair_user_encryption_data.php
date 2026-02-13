<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $rawEmail = $user->email;
            $rawName = $user->name;

            $updateData = [];

            // Encrypt and Hash Email
            if ($rawEmail && !str_starts_with($rawEmail, 'eyJpdiI6')) {
                $updateData['email'] = encrypt($rawEmail);
                $updateData['email_hash'] = hash('sha256', strtolower($rawEmail));
            }

            // Encrypt Name
            if ($rawName && !str_starts_with($rawName, 'eyJpdiI6')) {
                $updateData['name'] = encrypt($rawName);
            }

            if (!empty($updateData)) {
                DB::table('users')->where('id', $user->id)->update($updateData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No easy way to reversing encryption once hashed/encrypted without original keys
    }
};
