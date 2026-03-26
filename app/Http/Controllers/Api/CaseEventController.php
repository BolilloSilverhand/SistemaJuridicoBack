<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class CaseEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CaseEvent::query()->with(['legalCase', 'user']);

            if ($request->filled('search')) {
                $search = trim($request->string('search')->toString());
                $formattedDate = null;

                if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $search) === 1) {
                    [$day, $month, $year] = explode('-', $search);
                    $formattedDate = "{$year}-{$month}-{$day}";
                }

                $query->where(function ($inner) use ($search, $formattedDate) {
                    $inner->where('event_type', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");

                    if ($formattedDate) {
                        $inner->orWhereDate('event_date', $formattedDate);
                    }
                });
            }

            if ($request->filled('legal_case_id')) {
                $query->where('legal_case_id', $request->integer('legal_case_id'));
            }

            $events = $query->orderByDesc('event_date')->orderByDesc('created_at')->get();

            return $this->successResponse(
                200,
                'Case events retrieved successfully.',
                ['case_events' => $events]
            );
        } catch (Throwable $exception) {
            Log::error('Error retrieving case events', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while retrieving case events.');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'legal_case_id' => ['required', 'integer', 'exists:legal_cases,id'],
                'event_date' => ['nullable', 'date'],
                'event_type' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'is_payment' => ['nullable', 'boolean'],
            ]);

            $event = CaseEvent::create([
                ...$validated,
                'event_date' => $validated['event_date'] ?? now()->toDateString(),
                'is_payment' => $validated['is_payment'] ?? false,
            ])->load(['legalCase', 'user']);

            return $this->successResponse(
                201,
                'Case event created successfully.',
                ['case_event' => $event]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error creating case event', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while creating the case event.');
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $event = CaseEvent::with(['legalCase', 'user'])->find($id);

            if (! $event) {
                return $this->errorResponse(404, 'Case event not found.');
            }

            return $this->successResponse(
                200,
                'Case event retrieved successfully.',
                ['case_event' => $event]
            );
        } catch (Throwable $exception) {
            Log::error('Error retrieving case event', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while retrieving the case event.');
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $event = CaseEvent::with(['legalCase', 'user'])->find($id);

            if (! $event) {
                return $this->errorResponse(404, 'Case event not found.');
            }

            if ($request->has('legal_case_id')) {
                return $this->errorResponse(
                    422,
                    'Validation error.',
                    ['errors' => ['legal_case_id' => ['The legal_case_id cannot be updated.']]]
                );
            }

            $validated = $request->validate([
                'user_id' => ['sometimes', 'integer', 'exists:users,id'],
                'event_date' => ['sometimes', 'date'],
                'event_type' => ['sometimes', 'string', 'max:255'],
                'description' => ['sometimes', 'string'],
                'is_payment' => ['sometimes', 'boolean'],
            ]);

            if ($validated === []) {
                return $this->errorResponse(422, 'No fields to update.');
            }

            $event->update($validated);
            $event->refresh()->load(['legalCase', 'user']);

            return $this->successResponse(
                200,
                'Case event updated successfully.',
                ['case_event' => $event]
            );
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error updating case event', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while updating the case event.');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $event = CaseEvent::find($id);

            if (! $event) {
                return $this->errorResponse(404, 'Case event not found.');
            }

            $event->delete();

            return $this->successResponse(
                200,
                'Case event deleted successfully.',
                ['case_event_id' => $id]
            );
        } catch (Throwable $exception) {
            Log::error('Error deleting case event', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while deleting the case event.');
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
