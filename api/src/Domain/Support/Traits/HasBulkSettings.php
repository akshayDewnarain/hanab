<?php

namespace Domain\Support\Traits;

use Domain\General\Actions\Settings\BulkUpsertSettingsAction;
use Domain\General\Models\Location;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait HasBulkSettings
{
    protected function bulkUpdateSettings(
        Request $request,
        Location $location,
        string $domain,
        ?string $section = null,
        array $changes = []
    ): JsonResponse {
        $batchId = $request->header('X-Idempotency-Key') ?? Str::uuid()->toString();

        $bulkUpsertAction = app(BulkUpsertSettingsAction::class);

        try {
            $result = $bulkUpsertAction->execute(
                locationId: $location->id,
                domain: $domain,
                section: $section,
                changes: $changes,
                userId: auth()->id(),
                batchId: $batchId
            );

            return response()->json([
                'data' => $result,
                'meta' => [
                    'batch_id' => $batchId,
                    'domain' => $domain,
                    'section' => $section,
                    'updated_count' => count($changes),
                ],
            ]);

        } catch (InvalidArgumentException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'changes' => [$e->getMessage()],
                ],
            ], 422);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function validateBulkSettingsRequest(Request $request): array
    {
        return $request->validate([
            'domain' => 'required|string|max:255',
            'section' => 'nullable|string|max:255',
            'changes' => 'required|array|min:1',
            'changes.*.key' => 'required|string|max:255',
            'changes.*.value' => 'required',
            'version' => 'nullable|string',
        ]);
    }
}
