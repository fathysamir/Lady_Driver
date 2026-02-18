<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use Illuminate\Support\Facades\DB;



class ProfileController extends Controller
{
    public function updateName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
        ], [
            'name.required' => 'Name is required',
            'name.string'   => 'Name must be a string',
            'name.min'      => 'Name must be at least 3 characters',
            'name.max'      => 'Name must not exceed 255 characters',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        auth()->user()->update([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Name updated successfully'
        ]);
    }

    public function updatePhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|unique:users,phone,' . auth()->id() . '|regex:/^\+?\d{10,15}$/',
        ], [
            'phone.required' => 'Phone number is required',
            'phone.unique'   => 'This phone number is already taken',
            'phone.string'   => 'Phone must be a string',
            'phone.regex'    => 'Phone number must be valid and contain 10-15 digits',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        auth()->user()->update([
            'phone' => $request->phone
        ]);

        return response()->json([
            'message' => 'Phone updated successfully'
        ]);
    }

    public function updateEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . auth()->id(),
        ], [
            'email.required' => 'Email is required',
            'email.email'    => 'Email must be a valid email address',
            'email.unique'   => 'This email is already taken',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    if ($request->email === auth()->user()->email) {
        return response()->json([
            'success' => false,
            'message' => 'New email must be different from your current email',
        ], 422);
    }

        $user = auth()->user();
        $otpCode = rand(100000, 999999);

        $user->OTP            = $otpCode;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->pending_email  = $request->email;
        $user->save();

        Mail::to($request->email)->send(new SendOTP($otpCode, $user->name));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to the new email address.',
            'otp'     => $otpCode, //Testing Will be reomved
        ], 200);
    }

    public function verifyEmailOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $user = auth()->user()->fresh();

        if ((string)$user->OTP !== (string)$request->otp) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP'], 401);
        }

        if ($user->otp_expires_at < now()) {
            return response()->json(['success' => false, 'message' => 'Expired OTP'], 401);
        }

        DB::table('users')->where('id', $user->id)->update([
            'email'          => $user->pending_email,
            'pending_email'  => null,
            'OTP'            => null,
            'otp_expires_at' => null,
            'is_verified'    => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully',
            'email'   => $user->pending_email,
        ], 200);
    }
    public function updateBirthDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'birth_date' => 'required|date|before:today',
        ], [
            'birth_date.required' => 'Birth date is required',
            'birth_date.date'     => 'Birth date must be a valid date',
            'birth_date.before'   => 'Birth date must be before today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        auth()->user()->update([
            'birth_date' => $request->birth_date
        ]);

        return response()->json([
            'message' => 'Birth date updated successfully'
        ]);
    }
}

