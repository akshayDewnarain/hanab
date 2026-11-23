<?php

namespace Domain\Support\Transfer\Actions;

use Domain\Employees\Support\EmployeeImportMatrix;
use Domain\Support\Actions\BaseAction;
use Domain\Support\Transfer\TransferCatalog;

class DescribeTransferFieldsAction extends BaseAction
{
    /**
     * @return list<array{id: string, required: bool, type: string}>
     */
    public function execute(string $modelKey): array
    {
        if ($modelKey === 'employees') {
            return EmployeeImportMatrix::fields();
        }

        return (new TransferCatalog)->find($modelKey)->fields();
    }
}
