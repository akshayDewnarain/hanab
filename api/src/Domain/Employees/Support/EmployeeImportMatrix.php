<?php

namespace Domain\Employees\Support;

/**
 * Working field matrix for the employee file.
 * Hanab columns can be created or updated. Employee-owned data has no column, so an import cannot clear it.
 */
final class EmployeeImportMatrix
{
    /**
     * @var array<string, array{owner: string, required_on_create: bool, type: string, example: string}>
     */
    public const FIELDS = [
        'employee_number' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'EXAMPLE-001'],
        'first_name' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'Example'],
        'last_name' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'Sample'],
        'initials' => ['owner' => 'hanab', 'required_on_create' => false, 'type' => 'string', 'example' => 'A.J.'],
        'email' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'example.sample@example.com'],
        'phone' => ['owner' => 'hanab', 'required_on_create' => false, 'type' => 'string', 'example' => '+31600000000'],
        'business_email' => ['owner' => 'hanab', 'required_on_create' => false, 'type' => 'string', 'example' => 'example.sample@company.test'],
        'business_phone' => ['owner' => 'hanab', 'required_on_create' => false, 'type' => 'string', 'example' => '+31600000001'],
        'role' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'Example role'],
        'location' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'Example site'],
        'employment_type' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'string', 'example' => 'internal'],
        'start_date' => ['owner' => 'hanab', 'required_on_create' => false, 'type' => 'date', 'example' => '2026-01-01'],
        'is_active' => ['owner' => 'hanab', 'required_on_create' => true, 'type' => 'boolean', 'example' => 'true'],
        'manager_employee_number' => ['owner' => 'hanab', 'required_on_create' => false, 'type' => 'string', 'example' => ''],
    ];

    /**
     * These stay on the person. The file has no column for them.
     *
     * @var array<string, string>
     */
    public const HELD_BACK = [
        'photo' => 'employee',
        'skills' => 'employee',
        'certificates' => 'employee',
        'notification_preferences' => 'employee',
        'date_of_birth' => 'editor',
    ];

    /**
     * @return list<array{id: string, required: bool, type: string, example: string, owner: string}>
     */
    public static function fields(): array
    {
        $fields = [];
        foreach (self::FIELDS as $id => $field) {
            $fields[] = [
                'id' => $id,
                'required' => $field['required_on_create'],
                'type' => $field['type'],
                'example' => $field['example'],
                'owner' => $field['owner'],
            ];
        }

        return $fields;
    }
}
