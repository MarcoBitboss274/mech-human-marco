<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\Supplier;
use App\Models\User;
use Exception;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as FacadesNotification;

class NotificationService
{

    public const SYSTEM_SENDER_NAME = 'Sistema Portal Mech & Human';
    public const LABORATORY_SENDER_NAME = 'Mech & Human Laboratorio';


    /**
     * Get all admins
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    public static function admins()
    {
        return User::where('role', RoleEnum::ADMIN->value)->get();
    }

    /**
     * Send notification to admins
     *
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public static function sendToAdmins(Notification $notification): void
    {
        try {
            FacadesNotification::send(self::admins(), $notification);
        } catch (Exception $e) {
            Log::error('Error sending notification to admins: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to user
     *
     * @param \App\Models\User|null $user
     * @param \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public static function sendToUser(?User $user = null, Notification $notification): void
    {
        try {
            if (! $user) {
                return;
            }

            $user->notify($notification);
        } catch (Exception $e) {
            Log::error('Error sending notification to user: ' . $e->getMessage());
        }
    }

    /**
     * Send a notification to all relevant recipients of a supplier:
     * - the supplier's anagraphic email (Supplier.mail), always if present;
     * - every registered user belonging to the supplier's team.
     *
     * Each address receives an independent notification. Duplicate emails
     * (e.g. anagraphic email matching a user email) are de-duplicated.
     */
    public static function sendToSupplier(Supplier $supplier, Notification $notification): void
    {
        try {
            $supplier->loadMissing('users');

            $emails = collect();

            if (! empty($supplier->mail)) {
                $emails->push($supplier->mail);
            }

            foreach ($supplier->users as $user) {
                if (! empty($user->email)) {
                    $emails->push($user->email);
                }
            }

            $unique = $emails
                ->map(fn ($e) => strtolower(trim((string) $e)))
                ->filter()
                ->unique()
                ->values();

            foreach ($unique as $email) {
                FacadesNotification::route('mail', $email)->notify($notification);
            }
        } catch (Exception $e) {
            Log::error('Error sending notification to supplier: ' . $e->getMessage());
        }
    }
}
