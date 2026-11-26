<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->withCount('virtualQueues')
            ->with(['roleAssignments.virtualQueue', 'roleAssignments.servicePoint'])
            ->orderBy('name')
            ->get();

        return view('pages.users-index', [
            'users' => $users,
        ]);
    }
}
