<?php

namespace Domain\Messages\Actions;

use Domain\Employees\Models\Employee;
use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;

class DraftEmployeeIntroductionAction extends BaseAction
{
    public function execute(Employee $employee, ?int $authorId = null): Message
    {
        $employee->loadMissing('employeeRole');
        $name = trim($employee->first_name.' '.$employee->last_name);
        $role = $employee->employeeRole?->name;

        return RecordMessageAction::make()->execute([
            'type' => Message::TYPE_INTRODUCTION,
            'category' => Message::CATEGORY_PEOPLE,
            'status' => Message::STATUS_DRAFT,
            'title' => 'New colleague',
            'body' => $role
                ? $name.' joined as '.$role.'.'
                : $name.' joined.',
            'subject_name' => $name,
            'detail' => $role,
            'subject_employee_id' => $employee->id,
            'author_id' => $authorId,
            'dedupe_key' => 'introduction:'.$employee->id,
        ]);
    }
}
