<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function getAllUsers(Request $request)
    {
        $perPage = $request->per_page ?? 500;

        $users = User::paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}