<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuditObserver
{
    protected static $isDeleting = false;

    /**
     * Extract admin details (name, first name, last name) and log them.
     */
    protected function extractAdminDetails()
    {
        $admin = Auth::user();

        if ($admin) {
            $fullName = $admin->name;
            $nameParts = explode(' ', $fullName, 2);
            $firstName = $nameParts[0] ?? null;
            $lastName = $nameParts[1] ?? null;

            Log::info('Admin details:', ['first_name' => $firstName, 'last_name' => $lastName]);

            return [
                'admin' => $admin,
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }

        Log::warning('No authenticated admin found.');
        return null;
    }

    /**
     * Handle the "created" event.
     */
    public function created($model)
    {
        $adminDetails = $this->extractAdminDetails();

        if ($adminDetails) {
            $attributes = $model->getAttributes();

            AuditLog::create([
                'user_id' => $adminDetails['admin']->id,
                'first_name' => $adminDetails['first_name'],
                'last_name' => $adminDetails['last_name'],
                'action' => 'Created',
                'model' => get_class($model),
                'model_id' => $model->id,
                'changes' => json_encode($attributes),
            ]);
        }
    }

    /**
     * Handle the "updated" event.
     */
    public function updated($model)
    {
        if (self::$isDeleting) {
            return;
        }

        $adminDetails = $this->extractAdminDetails();

        if ($adminDetails) {
            $changes = $model->getChanges();

            AuditLog::create([
                'user_id' => $adminDetails['admin']->id,
                'first_name' => $adminDetails['first_name'],
                'last_name' => $adminDetails['last_name'],
                'action' => 'Updated',
                'model' => get_class($model),
                'model_id' => $model->id,
                'changes' => json_encode($changes),
            ]);
        }
    }

    /**
     * Handle the "deleting" event.
     */
    public function deleting($model)
    {
        self::$isDeleting = true;

        $adminDetails = $this->extractAdminDetails();

        if ($adminDetails) {
            $attributesBeforeDeletion = $model->getAttributes();

            AuditLog::create([
                'user_id' => $adminDetails['admin']->id,
                'first_name' => $adminDetails['first_name'],
                'last_name' => $adminDetails['last_name'],
                'action' => 'Deleted',
                'model' => get_class($model),
                'model_id' => $model->id,
                'changes' => json_encode($attributesBeforeDeletion),
            ]);
        }
    }

    /**
     * Handle the "restored" event.
     */
    public function restored($model)
    {
        $adminDetails = $this->extractAdminDetails();

        if ($adminDetails) {
            AuditLog::create([
                'user_id' => $adminDetails['admin']->id,
                'first_name' => $adminDetails['first_name'],
                'last_name' => $adminDetails['last_name'],
                'action' => 'Restored',
                'model' => get_class($model),
                'model_id' => $model->id,
                'changes' => null,
            ]);
        }
    }

    /**
     * Handle the "force deleted" event.
     */
    public function forceDeleted($model)
    {
        $adminDetails = $this->extractAdminDetails();

        if ($adminDetails) {
            AuditLog::create([
                'user_id' => $adminDetails['admin']->id,
                'first_name' => $adminDetails['first_name'],
                'last_name' => $adminDetails['last_name'],
                'action' => 'Force_deleted',
                'model' => get_class($model),
                'model_id' => $model->id,
                'changes' => null,
            ]);
        }
    }
}
