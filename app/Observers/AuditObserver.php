<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;  // To log the user details

class AuditObserver
{
    protected static $isDeleting = false;

    /**
     * Handle the "created" event.
     */
    public function created($model)
    {
        $user = Auth::user();
        Log::info('User details for created:', ['user' => $user]);

        AuditLog::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'action' => 'Created',
            'model' => get_class($model),
            'model_id' => $model->id,
            'changes' => json_encode($model->getAttributes()),
        ]);
    }

    /**
     * Handle the "updated" event.
     */
    public function updated($model)
    {
        // Skip logging if the deletion process is in progress
        if (self::$isDeleting) {
            return;
        }

        $user = Auth::user();
        Log::info('User details for updated:', ['user' => $user]);

        AuditLog::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'action' => 'Updated',
            'model' => get_class($model),
            'model_id' => $model->id,
            'changes' => json_encode($model->getChanges()),
        ]);
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting($model)
{
    self::$isDeleting = true; // Set the deletion flag

    // Fetch the authenticated user (Admin)
    $admin = Auth::user();

    // Log the full $admin object for inspection
    Log::info('Admin details for deletion:', ['admin' => $admin]);

    // Check if $admin object contains the 'name' field
    if (isset($admin->name)) {
        // Extract first and last name from the 'name' field if it's a full name
        $fullName = $admin->name;
        $nameParts = explode(' ', $fullName, 2); // Split into first and last name
        $firstName = isset($nameParts[0]) ? $nameParts[0] : null;
        $lastName = isset($nameParts[1]) ? $nameParts[1] : null;

        // Log the names to verify extraction
        Log::info('Extracted admin names:', ['first_name' => $firstName, 'last_name' => $lastName]);

        // Capture attributes before deletion
        $attributesBeforeDeletion = $model->getAttributes();

        Log::info('First Name:', ['first_name' => $firstName]);
        Log::info('Last Name:', ['last_name' => $lastName]);

        // Create audit log
        try {
            AuditLog::create([
                'user_id' => $admin->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'action' => 'Deleted',
                'model' => get_class($model),
                'model_id' => $model->id,
                'changes' => json_encode($attributesBeforeDeletion),
            ]);
        } catch (\Exception $e) {
            Log::error('Audit log creation failed', ['error' => $e->getMessage()]);
        }
    } else {
        // Log an error if 'name' is not available
        Log::error('Name field not found in Admin details.');
    }
}

    /**
     * Handle the "restored" event.
     */
    public function restored($model)
    {
        $user = Auth::user();
        Log::info('User details for restored:', ['user' => $user]);

        AuditLog::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'action' => 'Restored',
            'model' => get_class($model),
            'model_id' => $model->id,
            'changes' => null,
        ]);
    }

    /**
     * Handle the "force deleted" event.
     */
    public function forceDeleted($model)
    {
        $user = Auth::user();
        Log::info('User details for force deleted:', ['user' => $user]);

        AuditLog::create([
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'action' => 'Force_deleted',
            'model' => get_class($model),
            'model_id' => $model->id,
            'changes' => null,
        ]);
    }
}
