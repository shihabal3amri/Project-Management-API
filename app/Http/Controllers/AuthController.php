<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
class AuthController extends Controller
{
    // User registration method
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|phone:OM|unique:users,phone_number',
            'password' => 'required|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
    
        $user = User::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'password' => $request->password,
        ]);
    
        $token = $user->createToken('authToken')->accessToken;
        return response()->json(['token' => $token, 'user' => $user], 201);
    }

    // User login method
    public function login(Request $request)
    {
        try {
            $request->validate([
                'phone_number' => 'required|phone:OM',
                'password' => 'required',
            ]);
    
            if (!Auth::attempt(['phone_number' => $request->phone_number, 'password' => $request->password])) {
                return response()->json(['message' => 'The provided credentials are incorrect.'], 401);
            }
    
            $user = Auth::user();
            $token = $user->createToken('authToken')->accessToken;
    
            return response()->json(['token' => $token, 'user' => $user], 200);
    
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}

