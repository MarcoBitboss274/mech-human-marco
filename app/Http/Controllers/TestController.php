<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Notifications\Admin\RegisteredUserForAdmin;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function index()
    {
        $user = User::find(16);

        NotificationService::sendToAdmins(new RegisteredUserForAdmin($user));
        return '';
    }
}