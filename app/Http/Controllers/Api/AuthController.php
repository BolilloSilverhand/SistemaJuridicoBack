<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]);

            $user = User::create([
                ...$validated,
                'email_verified_at' => now(),
            ]);

            return $this->successResponse(
                201,
                'User registered successfully.',
                ['user' => $user]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error registering user', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while registering the user.');
        }
    }

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string'],
            ]);

            $user = User::where('email', $validated['email'])->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                return $this->errorResponse(401, 'Invalid credentials.');
            }

            if (! $user->email_verified_at) {
                return $this->errorResponse(403, 'Email is not verified.');
            }

            return $this->successResponse(
                200,
                'Login successful.',
                ['user' => $user]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error logging in user', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while logging in.');
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return $this->successResponse(200, 'Logout successful.');
        } catch (Throwable $exception) {
            Log::error('Error logging out user', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while logging out.');
        }
    }

    private function successResponse(int $httpStatus, string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $httpStatus);
    }

    private function errorResponse(int $httpStatus, string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data,
        ], $httpStatus);
    }
}
