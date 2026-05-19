<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $perPage = $request->per_page ?? 500;

        $users = User::orderBy('id', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function getAllUser()
{
    $users = User::orderBy('id', 'asc')->get();

    return response()->json([
        'success' => true,
        'data' => $users
    ]);
}
}