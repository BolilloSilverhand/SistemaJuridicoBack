<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            if (! $request->filled('user_id')) {
                return $this->errorResponse(
                    422,
                    'Validation error.',
                    ['errors' => ['user_id' => ['The user_id field is required.']]]
                );
            }

            $query = Client::query();
            $query->where('user_id', $request->integer('user_id'));

            if ($request->filled('search')) {
                $search = trim($request->string('search')->toString());

                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            }

            $clients = $query->orderByDesc('created_at')->get();

            return $this->successResponse(
                200,
                'Clients retrieved successfully.',
                ['clients' => $clients]
            );
        } catch (Throwable $exception) {
            Log::error('Error retrieving clients', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while retrieving clients.');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'name' => ['required', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'agreed_amount' => ['required', 'numeric', 'min:0'],
            ]);

            $client = Client::create([
                'user_id' => $validated['user_id'],
                'name' => $validated['name'],
                'last_name' => $validated['last_name'] ?? '',
                'agreed_amount' => $validated['agreed_amount'],
                'total_debt' => $validated['agreed_amount'],
                'paid' => 0,
            ]);

            return $this->successResponse(
                201,
                'Client created successfully.',
                ['client' => $client]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error creating client', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while creating the client.');
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $client = Client::with(['transactions'])->find($id);

            if (! $client) {
                return $this->errorResponse(404, 'Client not found.');
            }

            return $this->successResponse(
                200,
                'Client retrieved successfully.',
                ['client' => $client]
            );
        } catch (Throwable $exception) {
            Log::error('Error retrieving client', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while retrieving the client.');
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $client = Client::with(['transactions'])->find($id);

            if (! $client) {
                return $this->errorResponse(404, 'Client not found.');
            }

            if ($request->has('total_debt') || $request->has('paid')) {
                $errors = [];

                if ($request->has('total_debt')) {
                    $errors['total_debt'] = ['The total_debt cannot be updated directly.'];
                }

                if ($request->has('paid')) {
                    $errors['paid'] = ['The paid cannot be updated directly.'];
                }

                return $this->errorResponse(
                    422,
                    'Validation error.',
                    ['errors' => $errors]
                );
            }

            $validated = $request->validate([
                'user_id' => ['sometimes', 'integer', 'exists:users,id'],
                'name' => ['sometimes', 'string', 'max:255'],
                'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
                'agreed_amount' => ['sometimes', 'numeric', 'min:0'],
            ]);

            if ($validated === []) {
                return $this->errorResponse(422, 'No fields to update.');
            }

            if (array_key_exists('last_name', $validated) && $validated['last_name'] === null) {
                $validated['last_name'] = '';
            }

            $client->update($validated);
            $client->refresh()->load(['transactions']);

            return $this->successResponse(
                200,
                'Client updated successfully.',
                ['client' => $client]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error updating client', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while updating the client.');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $client = Client::find($id);

            if (! $client) {
                return $this->errorResponse(404, 'Client not found.');
            }

            $client->delete();

            return $this->successResponse(
                200,
                'Client deleted successfully.',
                ['client_id' => $id]
            );
        } catch (Throwable $exception) {
            Log::error('Error deleting client', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while deleting the client.');
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
