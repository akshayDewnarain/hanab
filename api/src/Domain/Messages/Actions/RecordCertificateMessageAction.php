<?php

namespace Domain\Messages\Actions;

use Domain\Employees\Models\EmployeeCertificate;
use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;

class RecordCertificateMessageAction extends BaseAction
{
    public function execute(EmployeeCertificate $link): ?Message
    {
        $link->loadMissing('employee', 'certificate');
        $employee = $link->employee;
        $certificate = $link->certificate;

        if ($employee === null || $certificate === null || ! $employee->is_active || ! $certificate->is_active) {
            return null;
        }

        $name = trim($employee->first_name.' '.$employee->last_name);

        return RecordMessageAction::make()->execute([
            'type' => Message::TYPE_CERTIFICATE,
            'category' => Message::CATEGORY_MILESTONES,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'New certificate',
            'body' => $name.' earned '.$certificate->name.'.',
            'subject_name' => $name,
            'detail' => $certificate->name,
            'subject_employee_id' => $employee->id,
            'published_at' => now(),
            'occurs_on' => now()->toDateString(),
            'dedupe_key' => 'certificate:'.$link->id,
        ]);
    }
}
