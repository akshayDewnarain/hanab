<?php

namespace Domain\Support\Transfer\Actions;

use Domain\Auth\Models\User;
use Domain\Employees\Actions\PreviewEmployeeImportAction;
use Domain\Support\Actions\BaseAction;
use Domain\Support\Transfer\TransferCatalog;
use Domain\Support\Transfer\TransferPlanner;

class PreviewTransferImportAction extends BaseAction
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function execute(string $modelKey, array $rows, ?string $strategy = null, ?string $policy = null, ?User $user = null): array
    {
        if ($modelKey === 'employees') {
            return PreviewEmployeeImportAction::make()->execute($rows, $strategy ?: 'upsert', $policy ?: 'strict');
        }

        $plan = (new TransferPlanner)->plan((new TransferCatalog)->find($modelKey), $rows, $strategy, $policy, $user);
        unset($plan['operations'], $plan['confirm_partial']);

        return $plan;
    }
}
