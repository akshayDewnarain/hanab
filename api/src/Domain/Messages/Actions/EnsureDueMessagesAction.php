<?php

namespace Domain\Messages\Actions;

use Domain\Employees\Models\Employee;
use Domain\Messages\Models\Message;
use Domain\Support\Actions\BaseAction;
use Illuminate\Support\Carbon;

class EnsureDueMessagesAction extends BaseAction
{
    public function execute(?Carbon $today = null): void
    {
        $today = ($today ?? now())->startOfDay();

        Employee::query()
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->whereNotNull('date_of_birth')->orWhereNotNull('start_date');
            })
            ->get()
            ->each(function (Employee $employee) use ($today): void {
                $this->birthday($employee, $today);
                $this->anniversary($employee, $today);
            });
    }

    private function birthday(Employee $employee, Carbon $today): void
    {
        $born = $employee->date_of_birth;
        if ($born === null || $born->month !== $today->month || $born->day !== $today->day) {
            return;
        }

        $name = trim($employee->first_name.' '.$employee->last_name);

        RecordMessageAction::make()->execute([
            'type' => Message::TYPE_BIRTHDAY,
            'category' => Message::CATEGORY_MILESTONES,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'Birthday',
            'body' => $name.' is celebrating a birthday.',
            'subject_name' => $name,
            'subject_employee_id' => $employee->id,
            'published_at' => $today,
            'occurs_on' => $today->toDateString(),
            'dedupe_key' => 'birthday:'.$employee->id.':'.$today->year,
        ]);
    }

    private function anniversary(Employee $employee, Carbon $today): void
    {
        $started = $employee->start_date;
        if ($started === null || $started->month !== $today->month || $started->day !== $today->day) {
            return;
        }

        $years = $today->year - $started->year;
        if ($years < 1) {
            return;
        }

        $name = trim($employee->first_name.' '.$employee->last_name);

        RecordMessageAction::make()->execute([
            'type' => Message::TYPE_ANNIVERSARY,
            'category' => Message::CATEGORY_MILESTONES,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'Work anniversary',
            'body' => $name.' has worked here for '.$years.' years.',
            'subject_name' => $name,
            'years' => $years,
            'subject_employee_id' => $employee->id,
            'published_at' => $today,
            'occurs_on' => $today->toDateString(),
            'dedupe_key' => 'anniversary:'.$employee->id.':'.$today->year,
        ]);
    }
}
