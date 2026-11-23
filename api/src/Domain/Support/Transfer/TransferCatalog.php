<?php

namespace Domain\Support\Transfer;

use Application\API\Employees\Requests\CreateCertificateRequest;
use Application\API\Employees\Requests\CreateEmployeeCertificateRequest;
use Application\API\Employees\Requests\CreateEmployeeLocationRequest;
use Application\API\Employees\Requests\CreateEmployeeRequest;
use Application\API\Employees\Requests\CreateEmployeeRoleRequest;
use Application\API\Employees\Requests\CreateEmployeeSkillRequest;
use Application\API\Employees\Requests\CreateSkillRequest;
use Domain\Auth\Services\PermissionService;
use Domain\Employees\Actions\CreateCertificateAction;
use Domain\Employees\Actions\CreateEmployeeAction;
use Domain\Employees\Actions\CreateEmployeeCertificateAction;
use Domain\Employees\Actions\CreateEmployeeLocationAction;
use Domain\Employees\Actions\CreateEmployeeRoleAction;
use Domain\Employees\Actions\CreateEmployeeSkillAction;
use Domain\Employees\Actions\CreateSkillAction;
use Domain\Employees\DataTransferObjects\CertificateData;
use Domain\Employees\DataTransferObjects\EmployeeCertificateData;
use Domain\Employees\DataTransferObjects\EmployeeData;
use Domain\Employees\DataTransferObjects\EmployeeLocationData;
use Domain\Employees\DataTransferObjects\EmployeeRoleData;
use Domain\Employees\DataTransferObjects\EmployeeSkillData;
use Domain\Employees\DataTransferObjects\SkillData;
use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeCertificate;
use Domain\Employees\Models\EmployeeLocation;
use Domain\Employees\Models\EmployeeRole;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Employees\Models\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionClass;

/**
 * One entry per model that can be imported and exported.
 * Fields come from that model's fillable list and its existing create rules.
 */
final class TransferCatalog
{
    /**
     * @var array<string, TransferModel>|null
     */
    private ?array $models = null;

    /**
     * @return array<string, TransferModel>
     */
    public function all(): array
    {
        $strategies = ['create_only', 'upsert', 'update_only'];

        return $this->models ??= [
            'employees' => new TransferModel('employees', Employee::class, EmployeeData::class, CreateEmployeeAction::class, CreateEmployeeRequest::class, new TransferProfile(
                match: ['employee_number'],
                strategies: $strategies,
                defaultStrategy: 'upsert',
                policies: ['strict'],
                defaultPolicy: 'strict',
                partial: false,
                routeParameter: 'employee',
            )),
            'employee-roles' => new TransferModel('employee-roles', EmployeeRole::class, EmployeeRoleData::class, CreateEmployeeRoleAction::class, CreateEmployeeRoleRequest::class, new TransferProfile(
                match: ['name'],
                strategies: $strategies,
                defaultStrategy: 'create_only',
                policies: ['strict', 'valid_rows'],
                defaultPolicy: 'strict',
                partial: true,
                routeParameter: 'employee_role',
                clearable: ['description', 'parent_id', 'organization_level_id'],
            )),
            'employee-locations' => new TransferModel('employee-locations', EmployeeLocation::class, EmployeeLocationData::class, CreateEmployeeLocationAction::class, CreateEmployeeLocationRequest::class, new TransferProfile(
                match: ['name', 'cluster_name'],
                strategies: $strategies,
                defaultStrategy: 'create_only',
                policies: ['strict', 'valid_rows'],
                defaultPolicy: 'strict',
                partial: true,
                routeParameter: 'employee_location',
                clearable: ['latitude', 'longitude'],
            )),
            'skills' => new TransferModel('skills', Skill::class, SkillData::class, CreateSkillAction::class, CreateSkillRequest::class, new TransferProfile(
                match: ['name'],
                strategies: $strategies,
                defaultStrategy: 'create_only',
                policies: ['strict', 'valid_rows'],
                defaultPolicy: 'strict',
                partial: true,
                routeParameter: 'skill',
                clearable: ['code', 'description', 'label_background_color', 'label_text_color', 'label_border_color'],
            )),
            'certificates' => new TransferModel('certificates', Certificate::class, CertificateData::class, CreateCertificateAction::class, CreateCertificateRequest::class, new TransferProfile(
                match: ['name'],
                strategies: $strategies,
                defaultStrategy: 'create_only',
                policies: ['strict', 'valid_rows'],
                defaultPolicy: 'strict',
                partial: true,
                routeParameter: 'certificate',
                clearable: ['code', 'description', 'label_background_color', 'label_text_color', 'label_border_color'],
            )),
            'employee-skills' => new TransferModel('employee-skills', EmployeeSkill::class, EmployeeSkillData::class, CreateEmployeeSkillAction::class, CreateEmployeeSkillRequest::class, new TransferProfile(
                match: ['employee_id', 'skill_id'],
                strategies: $strategies,
                defaultStrategy: 'create_only',
                policies: ['strict', 'valid_rows'],
                defaultPolicy: 'strict',
                partial: true,
                routeParameter: 'employee_skill',
                clearable: ['notes', 'assessed_at'],
            )),
            'employee-certificates' => new TransferModel('employee-certificates', EmployeeCertificate::class, EmployeeCertificateData::class, CreateEmployeeCertificateAction::class, CreateEmployeeCertificateRequest::class, new TransferProfile(
                match: ['employee_id', 'certificate_id', 'certificate_number'],
                strategies: $strategies,
                defaultStrategy: 'create_only',
                policies: ['strict', 'valid_rows'],
                defaultPolicy: 'strict',
                partial: true,
                routeParameter: 'employee_certificate',
                clearable: ['issued_at', 'expires_at', 'notes'],
            )),
        ];
    }

    public function find(string $key): TransferModel
    {
        return $this->all()[$key] ?? throw new TransferModelNotFound($key);
    }

    public function maintainPermission(string $key): string
    {
        if ($key === 'employees') {
            return 'import employees';
        }

        return PermissionService::createPermission($this->find($key)->modelClass);
    }
}

final class TransferModel
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  class-string  $dataClass
     * @param  class-string  $createAction
     * @param  class-string<FormRequest>  $createRequest
     */
    public function __construct(
        public string $key,
        public string $modelClass,
        public string $dataClass,
        public string $createAction,
        public string $createRequest,
        public TransferProfile $profile,
    ) {}

    /**
     * @return list<array{id: string, required: bool, type: string}>
     */
    public function fields(): array
    {
        /** @var Model $model */
        $model = new $this->modelClass;
        $rules = $this->rulesFor([]);
        $casts = $model->getCasts();
        $fields = [];

        foreach ($model->getFillable() as $name) {
            $rule = $rules[$name] ?? [];
            $fields[] = [
                'id' => $name,
                'required' => self::isRequired($rule),
                'type' => self::typeFor($name, $casts, $rule),
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function rulesFor(array $payload, ?Model $existing = null): array
    {
        $request = $this->createRequest::create('/transfers/'.$this->key, 'POST', $payload);
        $request->setContainer(app());
        if ($existing !== null && $this->profile->routeParameter !== null) {
            $parameter = $this->profile->routeParameter;
            $request->setRouteResolver(function () use ($existing, $parameter) {
                return new class($parameter, $existing)
                {
                    public function __construct(private string $parameter, private Model $existing) {}

                    public function parameter(string $name, mixed $default = null): mixed
                    {
                        return $name === $this->parameter ? $this->existing : $default;
                    }
                };
            });
        }

        return $request->rules();
    }

    /**
     * @return list<string>
     */
    public function dataKeys(): array
    {
        $constructor = (new ReflectionClass($this->dataClass))->getConstructor();
        if ($constructor === null) {
            return [];
        }

        return array_map(fn ($parameter) => $parameter->getName(), $constructor->getParameters());
    }

    /**
     * @param  array<int, mixed>  $rule
     */
    private static function isRequired(array $rule): bool
    {
        $required = false;
        $nullable = false;
        foreach ($rule as $item) {
            if (! is_string($item)) {
                continue;
            }
            if ($item === 'nullable') {
                $nullable = true;
            }
            if ($item === 'required' || str_starts_with($item, 'required')) {
                $required = true;
            }
        }

        return $required && ! $nullable;
    }

    /**
     * @param  array<string, string>  $casts
     * @param  array<int, mixed>  $rule
     */
    private static function typeFor(string $name, array $casts, array $rule): string
    {
        $cast = $casts[$name] ?? null;
        if ($cast === 'boolean' || $cast === 'bool' || in_array('boolean', $rule, true)) {
            return 'boolean';
        }
        if ($cast === 'array' || in_array('array', $rule, true)) {
            return 'array';
        }
        if ($cast === 'integer' || $cast === 'int' || in_array('integer', $rule, true)) {
            return 'integer';
        }
        if (in_array('date', $rule, true) || $cast === 'date' || $cast === 'datetime' || $cast === 'immutable_date') {
            return 'date';
        }
        if (in_array($cast, ['float', 'double', 'real', 'decimal'], true) || in_array('numeric', $rule, true)) {
            return 'number';
        }

        return 'string';
    }
}
