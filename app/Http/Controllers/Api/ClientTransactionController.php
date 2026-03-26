<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClientTransactionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'client_id' => ['required', 'integer', 'exists:clients,id'],
                'transaction_type' => ['required', 'string', Rule::in(['Cargo', 'Abono', 'cargo', 'abono'])],
                'amount' => ['required', 'numeric', 'gt:0'],
                'description' => ['nullable', 'string'],
            ]);

            $result = DB::transaction(function () use ($validated) {
                $client = Client::where('id', $validated['client_id'])->lockForUpdate()->first();

                if (! $client) {
                    return $this->errorResponse(404, 'Client not found.');
                }

                $normalizedType = strtolower($validated['transaction_type']);
                $amount = (float) $validated['amount'];
                $currentDebt = (float) $client->total_debt;
                $currentPaid = (float) $client->paid;

                $newDebt = $normalizedType === 'cargo'
                    ? $currentDebt + $amount
                    : $currentDebt - $amount;
                $newPaid = $normalizedType === 'abono'
                    ? $currentPaid + $amount
                    : $currentPaid;

                if ($newDebt < 0 || $newPaid < 0) {
                    return $this->errorResponse(
                        422,
                        'Validation error.',
                        ['errors' => ['amount' => ['The transaction would result in an invalid client balance.']]]
                    );
                }

                $transaction = ClientTransaction::create([
                    'user_id' => $validated['user_id'],
                    'client_id' => $validated['client_id'],
                    'transaction_type' => ucfirst($normalizedType),
                    'amount' => $validated['amount'],
                    'description' => $validated['description'] ?? '',
                ])->load(['client', 'user']);

                $client->update([
                    'total_debt' => $newDebt,
                    'paid' => $newPaid,
                ]);

                return $this->successResponse(
                    201,
                    'Client transaction created successfully.',
                    [
                        'client_transaction' => $transaction,
                        'client_total_debt' => number_format($newDebt, 2, '.', ''),
                        'client_paid' => number_format($newPaid, 2, '.', ''),
                    ]
                );
            });

            return $result;
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                422,
                'Validation error.',
                ['errors' => $exception->errors()]
            );
        } catch (Throwable $exception) {
            Log::error('Error creating client transaction', ['exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while creating the client transaction.');
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $result = DB::transaction(function () use ($id) {
                $transaction = ClientTransaction::where('id', $id)->lockForUpdate()->first();

                if (! $transaction) {
                    return $this->errorResponse(404, 'Client transaction not found.');
                }

                $client = Client::where('id', $transaction->client_id)->lockForUpdate()->first();

                if (! $client) {
                    return $this->errorResponse(404, 'Client not found.');
                }

                $normalizedType = strtolower($transaction->transaction_type);
                $amount = (float) $transaction->amount;
                $currentDebt = (float) $client->total_debt;
                $currentPaid = (float) $client->paid;

                $newDebt = $normalizedType === 'cargo'
                    ? $currentDebt - $amount
                    : $currentDebt + $amount;
                $newPaid = $normalizedType === 'abono'
                    ? $currentPaid - $amount
                    : $currentPaid;

                if ($newDebt < 0 || $newPaid < 0) {
                    return $this->errorResponse(
                        409,
                        'Cannot delete transaction because it would leave an invalid client balance.'
                    );
                }

                $transaction->delete();
                $client->update([
                    'total_debt' => $newDebt,
                    'paid' => $newPaid,
                ]);

                return $this->successResponse(
                    200,
                    'Client transaction deleted successfully.',
                    [
                        'client_transaction_id' => $id,
                        'client_total_debt' => number_format($newDebt, 2, '.', ''),
                        'client_paid' => number_format($newPaid, 2, '.', ''),
                    ]
                );
            });

            return $result;
        } catch (Throwable $exception) {
            Log::error('Error deleting client transaction', ['id' => $id, 'exception' => $exception]);

            return $this->errorResponse(500, 'An unexpected error occurred while deleting the client transaction.');
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
