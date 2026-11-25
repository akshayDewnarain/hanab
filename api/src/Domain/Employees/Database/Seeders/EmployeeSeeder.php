<?php

namespace Domain\Employees\Database\Seeders;

use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\Skill;
use Domain\Messages\Actions\EnsureDueMessagesAction;
use Domain\Messages\Actions\RecordMessageAction;
use Domain\Messages\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        if (Employee::query()->doesntExist()) {
            $this->createCompany();
        }

        $this->alignOrganizationLevels();
        $this->linkOpenReports();
        $this->publishCompanyAnnouncement();
        EnsureDueMessagesAction::make()->execute();
    }

    private function createCompany(): void
    {
        foreach ($this->people() as $person) {
            $role = EmployeeRole::query()->where('name', $person['role'])->first();
            $location = EmployeeLocation::query()->where('name', $person['location'])->first();
            $employee = Employee::query()->updateOrCreate(
                ['employee_number' => $person['number']],
                [
                    'employee_role_id' => $role?->id,
                    'employee_location_id' => $location?->id,
                    'organization_level_id' => $role?->organization_level_id,
                    'first_name' => $person['first_name'],
                    'last_name' => $person['last_name'],
                    'initials' => $person['initials'],
                    'email' => $person['email'],
                    'phone' => $person['phone'],
                    'business_email' => $person['business_email'],
                    'business_phone' => $person['business_phone'],
                    'introduction' => $person['introduction'],
                    'employment_type' => 'internal',
                    'start_date' => $person['start_date'],
                    'date_of_birth' => $person['date_of_birth'],
                    'is_active' => true,
                    'manager_id' => null,
                ],
            );

            $this->attachSkills($employee, $person['skills']);
            $this->attachCertificates($employee, $person['certificates']);
            $this->publishIntroduction($employee);
        }
    }

    private function alignOrganizationLevels(): void
    {
        Employee::query()
            ->whereNull('organization_level_id')
            ->whereNotNull('employee_role_id')
            ->with('employeeRole')
            ->each(function (Employee $employee): void {
                $levelId = $employee->employeeRole?->organization_level_id;
                if ($levelId === null) {
                    return;
                }
                $employee->organization_level_id = $levelId;
                $employee->save();
            });
    }

    private function linkOpenReports(): void
    {
        $director = Employee::query()
            ->where('is_active', true)
            ->whereHas('employeeRole', fn ($query) => $query->where('name', 'Directeur'))
            ->orderBy('id')
            ->first();

        $ranks = [
            'Regiodirecteur' => 1,
            'Hoofd uitvoerder' => 2,
            'Planner' => 2,
            'Project Manager' => 2,
            'Uitvoerder' => 3,
            'Calculator' => 4,
            'Administrateur' => 4,
            'Monteur' => 4,
        ];

        $open = Employee::query()
            ->where('is_active', true)
            ->whereNull('manager_id')
            ->when($director !== null, fn ($query) => $query->whereKeyNot($director->id))
            ->with('employeeRole')
            ->get()
            ->sortBy(fn (Employee $employee): int => $ranks[$employee->employeeRole?->name ?? ''] ?? 9);

        foreach ($open as $employee) {
            $manager = $this->pickManager($employee, $director);
            if ($manager === null || $manager->id === $employee->id) {
                continue;
            }
            $employee->manager_id = $manager->id;
            $employee->save();
        }
    }

    private function pickManager(Employee $employee, ?Employee $director): ?Employee
    {
        $preferred = match ($employee->employeeRole?->name) {
            'Regiodirecteur', 'Administrateur' => ['Directeur'],
            'Hoofd uitvoerder', 'Planner', 'Project Manager' => ['Regiodirecteur', 'Directeur'],
            'Uitvoerder', 'Calculator' => ['Hoofd uitvoerder', 'Regiodirecteur', 'Directeur'],
            default => ['Uitvoerder', 'Hoofd uitvoerder', 'Regiodirecteur', 'Directeur'],
        };

        foreach ($preferred as $roleName) {
            $local = $this->candidate($employee, $roleName, true);
            if ($local !== null) {
                return $local;
            }
            if ($roleName !== 'Directeur') {
                $anywhere = $this->candidate($employee, $roleName, false);
                if ($anywhere !== null) {
                    return $anywhere;
                }
            }
        }

        return $director;
    }

    private function candidate(Employee $employee, string $roleName, bool $sameLocation): ?Employee
    {
        return Employee::query()
            ->where('is_active', true)
            ->whereKeyNot($employee->id)
            ->whereHas('employeeRole', fn ($query) => $query->where('name', $roleName))
            ->when(
                $sameLocation && $employee->employee_location_id !== null,
                fn ($query) => $query->where('employee_location_id', $employee->employee_location_id),
            )
            ->orderBy('id')
            ->first();
    }

    /**
     * @param  list<string>  $names
     */
    private function attachSkills(Employee $employee, array $names): void
    {
        if ($names === []) {
            return;
        }

        $ids = Skill::query()->whereIn('name', $names)->pluck('id');
        foreach ($ids as $id) {
            if ($employee->skills()->where('skills.id', $id)->exists()) {
                continue;
            }
            $employee->skills()->attach($id, [
                'level' => 3,
                'assessed_at' => now()->subMonths(2)->toDateString(),
            ]);
        }
    }

    /**
     * @param  list<string>  $names
     */
    private function attachCertificates(Employee $employee, array $names): void
    {
        foreach ($names as $name) {
            $certificate = Certificate::query()->where('name', $name)->first();
            if ($certificate === null || $employee->certificates()->where('certificates.id', $certificate->id)->exists()) {
                continue;
            }
            $employee->certificates()->attach($certificate->id, [
                'certificate_number' => $employee->employee_number.'-'.$certificate->code,
                'issued_at' => now()->subYear()->toDateString(),
                'expires_at' => now()->addYears(2)->toDateString(),
            ]);
        }
    }

    private function publishIntroduction(Employee $employee): void
    {
        if ($employee->introduction === null || $employee->introduction === '') {
            return;
        }

        RecordMessageAction::make()->execute([
            'type' => Message::TYPE_INTRODUCTION,
            'category' => Message::CATEGORY_PEOPLE,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'Introduction',
            'body' => $employee->introduction,
            'subject_name' => trim($employee->first_name.' '.$employee->last_name),
            'subject_employee_id' => $employee->id,
            'published_at' => Carbon::parse($employee->start_date)->max(now()->subMonths(2)),
            'dedupe_key' => 'introduction:'.$employee->id,
        ]);
    }

    private function publishCompanyAnnouncement(): void
    {
        RecordMessageAction::make()->execute([
            'type' => Message::TYPE_CONTRACT,
            'category' => Message::CATEGORY_COMPANY,
            'status' => Message::STATUS_PUBLISHED,
            'title' => 'Winterprogramma',
            'body' => 'Het winterprogramma voor gas- en warmtenetten is gestart. Vestigingen stemmen de storingsdienst af met de planner.',
            'subject_name' => 'Hanab',
            'published_at' => now()->subDay(),
            'occurs_on' => now()->toDateString(),
            'dedupe_key' => 'demo:company:winter-program',
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function people(): array
    {
        return [
            $this->person('HAN-1001', 'Eva', 'de Vries', 'EV', 'Directeur', 'Utrecht', '2014-03-03', '1978-04-12', 'eva.devries@hanab.test', '+31 6 1100 1001', ['Leidinggeven', 'Klantcontact'], ['VCA VOL'], 'Eindverantwoordelijk voor de vestigingen en de uitvoering.'),
            $this->person('HAN-1002', 'Mark', 'Jansen', 'MJ', 'Administrateur', 'Utrecht', '2019-01-07', '1986-09-18', 'mark.jansen@hanab.test', null, ['Klantcontact'], ['VCA Basis'], 'Ondersteunt de administratie en de projectdossiers.'),
            $this->person('HAN-1010', 'Sophie', 'Hendriks', 'SH', 'Regiodirecteur', 'Rotterdam', '2016-05-02', '1981-02-09', 'sophie.hendriks@hanab.test', '+31 6 1100 1010', ['Leidinggeven', 'Gasdistributie'], ['VCA VOL'], 'Verantwoordelijk voor de regio Zuid.'),
            $this->person('HAN-1011', 'Thomas', 'Bakker', 'TB', 'Regiodirecteur', 'Amsterdam', '2015-08-17', '1979-11-03', 'thomas.bakker@hanab.test', '+31 6 1100 1011', ['Leidinggeven', 'Laagspanning'], ['VCA VOL'], 'Verantwoordelijk voor de regio Noord en West.'),
            $this->person('HAN-1020', 'Sanne', 'Visser', 'SV', 'Hoofd uitvoerder', 'Amsterdam', '2019-11-25', '1988-06-21', 'sanne.visser@hanab.test', '+31 6 1100 1020', ['Leidinggeven', 'Gasdistributie', 'Samenwerken'], ['VCA VOL', 'VIAG'], 'Stuurt de uitvoering in Amsterdam aan.'),
            $this->person('HAN-1021', 'Pieter', 'van Dijk', 'PD', 'Hoofd uitvoerder', 'Eindhoven', '2018-04-09', '1984-01-30', 'pieter.vandijk@hanab.test', '+31 6 1100 1021', ['Leidinggeven', 'Warmtenetten'], ['VCA VOL'], 'Stuurt de uitvoering in Eindhoven aan.'),
            $this->person('HAN-1022', 'Hanne', 'de Groot', 'HG', 'Hoofd uitvoerder', 'Groningen', '2017-10-02', '1983-07-14', 'hanne.degroot@hanab.test', '+31 6 1100 1022', ['Leidinggeven', 'Hoogspanning'], ['VCA VOL', 'BEI-LS'], 'Stuurt de uitvoering in Groningen aan.'),
            $this->person('HAN-1030', 'Jeroen', 'Kuipers', 'JK', 'Planner', 'Rotterdam', '2020-02-10', '1990-03-28', null, '+31 6 1100 1030', ['Werkvoorbereiding', 'Klantcontact'], ['VCA Basis'], 'Plant mensen en materiaal voor de regio Zuid.'),
            $this->person('HAN-1031', 'Lisa', 'Meijer', 'LM', 'Planner', 'Zwolle', '2021-06-14', '1992-12-05', 'lisa.meijer@hanab.test', '+31 6 1100 1031', ['Werkvoorbereiding'], ['VCA Basis'], 'Plant de werkzaamheden voor Oost-Nederland.'),
            $this->person('HAN-1040', 'Daan', 'Mulder', 'DM', 'Uitvoerder', 'Amsterdam', '2020-09-01', '1991-05-16', 'daan.mulder@hanab.test', '+31 6 1100 1040', ['Gasdistributie', 'Samenwerken'], ['VCA VOL', 'VIAG'], 'Leidt het uitvoeringsteam in Amsterdam.'),
            $this->person('HAN-1041', 'Femke', 'de Jong', 'FJ', 'Uitvoerder', 'Den Haag', '2021-03-15', '1993-08-08', 'femke.dejong@hanab.test', '+31 6 1100 1041', ['Laagspanning', 'Samenwerken'], ['VCA VOL', 'NEN 3140'], 'Leidt het uitvoeringsteam in Den Haag.'),
            $this->person('HAN-1042', 'Ruben', 'Smeets', 'RS', 'Uitvoerder', 'Eindhoven', '2019-07-22', '1989-10-19', 'ruben.smeets@hanab.test', '+31 6 1100 1042', ['Warmtenetten', 'Samenwerken'], ['VCA VOL'], 'Leidt het uitvoeringsteam in Eindhoven.'),
            $this->person('HAN-1050', 'Lars', 'de Boer', 'LB', 'Monteur', 'Amsterdam', '2022-01-10', '1994-11-25', 'lars.deboer@hanab.test', '+31 6 1100 1050', ['Gasdistributie', 'Samenwerken'], ['VCA Basis', 'VIAG'], 'Monteur gasdistributie in Amsterdam.'),
            $this->person('HAN-1051', 'Noor', 'Smit', 'NS', 'Monteur', 'Amsterdam', '2024-04-08', '1998-02-02', 'noor.smit@hanab.test', '+31 6 1100 1051', [], ['VCA Basis'], 'Nieuw in het team in Amsterdam.'),
            $this->person('HAN-1052', 'Milan', 'Peters', 'MP', 'Monteur', 'Rotterdam', '2023-05-29', '1996-09-11', 'milan.peters@hanab.test', '+31 6 1100 1052', ['Laagspanning'], [], 'Monteur laagspanning in Rotterdam.'),
            $this->person('HAN-1053', 'Isa', 'Willems', 'IW', 'Monteur', 'Groningen', '2022-11-07', '1995-01-23', 'isa.willems@hanab.test', '+31 6 1100 1053', ['Hoogspanning', 'Samenwerken'], ['VCA Basis', 'BEI-LS'], 'Monteur hoogspanning in Groningen.'),
            $this->person('HAN-1054', 'Teun', 'Bos', 'TBO', 'Monteur', 'Zwolle', '2023-08-21', '1997-06-30', 'teun.bos@hanab.test', '+31 6 1100 1054', ['Warmtenetten'], ['VCA Basis'], 'Monteur warmtenetten in Zwolle.'),
        ];
    }

    /**
     * @param  list<string>  $skills
     * @param  list<string>  $certificates
     * @return array<string, mixed>
     */
    private function person(
        string $number,
        string $firstName,
        string $lastName,
        string $initials,
        string $role,
        string $location,
        string $startDate,
        string $dateOfBirth,
        ?string $businessEmail,
        ?string $businessPhone,
        array $skills,
        array $certificates,
        string $introduction,
    ): array {
        $slug = strtolower(str_replace(' ', '.', $firstName.'.'.$lastName));

        return [
            'number' => $number,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'initials' => $initials,
            'role' => $role,
            'location' => $location,
            'start_date' => $startDate,
            'date_of_birth' => $dateOfBirth,
            'email' => str_replace('..', '.', $slug).'@hanab.test',
            'phone' => $businessPhone ?? '+31 30 555 0190',
            'business_email' => $businessEmail,
            'business_phone' => $businessPhone,
            'introduction' => $introduction,
            'skills' => $skills,
            'certificates' => $certificates,
        ];
    }
}
