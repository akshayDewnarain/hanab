<?php

namespace Domain\Support\Transfer\Actions;

use Domain\Auth\Models\User;
use Domain\Employees\Actions\CommitEmployeeImportAction;
use Domain\Support\Actions\BaseAction;
use Domain\Support\Transfer\TransferCatalog;
use Domain\Support\Transfer\TransferPlanner;

class CommitTransferImportAction extends BaseAction
{
    /**
     * Plans the file again at execution time, then writes.
     * A strict import is one transaction. A partial import commits each eligible row on its own.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    public function execute(
        string $modelKey,
        array $rows,
        ?string $fileName = null,
        ?int $userId = null,
        ?string $strategy = null,
        ?string $policy = null,
        ?User $user = null,
        bool $confirmPartial = false,
    ): array {
        if ($modelKey === 'employees') {
            return CommitEmployeeImportAction::make()->execute($rows, $fileName, $userId, $strategy ?: 'upsert', $policy ?: 'strict');
        }

        $model = (new TransferCatalog)->find($modelKey);
        $planner = new TransferPlanner;
        $plan = $planner->plan($model, $rows, $strategy, $policy, $user, $confirmPartial);
        $result = $planner->commit($model, $plan);
        unset($result['operations']);

        return $result;
    }
}
