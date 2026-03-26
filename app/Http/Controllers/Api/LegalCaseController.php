<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class LegalCaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = LegalCase::query()->with(['client', 'user']);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->integer('user_id'));
            }

            if ($request->filled('client_id')) {
                $query->where('client_id', $request->integer('client_id'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->string('status'));
            }

            if ($request->filled('search')) {
                $search = trim($request->string('search')->toString());

                $query->where(function ($inner) use ($search) {
                    $inner->where('case_number', 'like', "%{$search}%")
                        ->orWhere('parties', 'like', "%{$search}%")
                        ->orWhere('court', 'like', "%{$search}%");
                });
            }

            $legalCases = $query->orderByDesc('created_at')->get();

            return $this->successResponse(
                200,
                'Legal cases retrieved successfully.',
                ['legal_cases' => $legalCases]
            );
        } catch (Throwable $exception) {
            Log::error('Error retrieving legal cases', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while retrieving legal cases.');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            if ((string) $request->input('client_id') === '0') {
                $request->merge(['client_id' => null]);
            }

            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'case_number' => ['required', 'string', 'max:255', 'unique:legal_cases,case_number'],
                'parties' => ['required', 'string'],
                'court' => ['required', 'string', 'max:255'],
                'status' => ['nullable', 'string', 'max:255'],
                'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            ]);

            $legalCase = LegalCase::create([
                ...$validated,
                'status' => $validated['status'] ?? 'Active',
            ])->load(['client', 'user']);

            return $this->successResponse(
                201,
                'Legal case created successfully.',
                ['legal_case' => $legalCase]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error creating legal case', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while creating the legal case.');
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $legalCase = LegalCase::with(['client', 'user', 'events'])->find($id);

            if (! $legalCase) {
                return $this->errorResponse(404, 'Legal case not found.');
            }

            return $this->successResponse(
                200,
                'Legal case retrieved successfully.',
                ['legal_case' => $legalCase]
            );
        } catch (Throwable $exception) {
            Log::error('Error retrieving legal case', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while retrieving the legal case.');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $legalCase = LegalCase::withCount('events')->find($id);

            if (! $legalCase) {
                return $this->errorResponse(404, 'Legal case not found.');
            }

            if ($legalCase->events_count > 0) {
                return $this->errorResponse(
                    409,
                    'Cannot delete legal case because it has related events.'
                );
            }

            $legalCase->delete();

            return $this->successResponse(
                200,
                'Legal case deleted successfully.',
                ['legal_case_id' => $id]
            );
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return $this->errorResponse(
                    409,
                    'Cannot delete legal case because it has related records.'
                );
            }

            Log::error('Database error deleting legal case', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'Database error while deleting legal case.');
        } catch (Throwable $exception) {
            Log::error('Error deleting legal case', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while deleting the legal case.');
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $legalCase = LegalCase::find($id);

            if (! $legalCase) {
                return $this->errorResponse(404, 'Legal case not found.');
            }

            if ((string) $request->input('client_id') === '0') {
                $request->merge(['client_id' => null]);
            }

            $validated = $request->validate([
                'user_id' => ['sometimes', 'integer', 'exists:users,id'],
                'case_number' => ['sometimes', 'string', 'max:255', 'unique:legal_cases,case_number,'.$id],
                'parties' => ['sometimes', 'string'],
                'court' => ['sometimes', 'string', 'max:255'],
                'status' => ['sometimes', 'string', 'max:255'],
                'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            ]);

            if ($validated === []) {
                return $this->errorResponse(422, 'No fields to update.');
            }

            $legalCase->update($validated);
            $legalCase->refresh()->load(['client', 'user']);

            return $this->successResponse(
                200,
                'Legal case updated successfully.',
                ['legal_case' => $legalCase]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error updating legal case', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while updating the legal case.');
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
