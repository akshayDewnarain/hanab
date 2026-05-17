# TODO: Create New Model — Backend API

This document outlines the complete process for creating a new model in the backend API, following the Spatie DDD structure and mirroring the conventions from existing models (e.g., `Drink.php`).

## Prerequisites

- Choose a reference model (e.g., `Drink`, `Dish`, `Arrangement`) that closely matches your new model's structure
- Decide which bounded context / domain namespace it belongs in (e.g., `Domain\General\...`, `Domain\Employees\...`)
- Identify all required fields and their types

---

## Step-by-Step Checklist

### 0) Migration

**If table doesn't exist:**

- Create migration: `php artisan make:migration create_{table_name}_table`
- Add all columns with appropriate types and foreign keys your schema requires
- Add indexes as needed (e.g., foreign keys, searchable columns, uniqueness)
- Add `timestamps()` if needed
- **Do NOT add `softDeletes()` unless explicitly required**

**If table already exists:**

- Create a new migration: `php artisan make:migration add_{purpose}_to_{table_name}_table`
- Use `Schema::table()` instead of `Schema::create()`
- Include a proper `down()` method to reverse changes

---

### 1) Domain Model

**File:** `api/src/Domain/{DomainName}/Models/{ModelName}.php`  
(Replace `{DomainName}` with your bounded context, e.g. `General`, `Employees`.)

**Structure:**

- Use `HasFactory` trait
- Use `LogsActivity` trait
- Use `WithData` trait
- Set `protected $table = '{table_name}';`
- Set `protected string $dataClass = {ModelName}Data::class;`
- Define `$fillable` array
- Define `$casts` array (appropriate types for each field)
- Define `$guarded` array (typically `['id']`)
- Implement `getActivitylogOptions()`:
  ```php
  public function getActivitylogOptions(): LogOptions
  {
      return LogOptions::defaults()
          ->useLogName('{table_name}')
          ->logFillable()
          ->logOnlyDirty()
          ->dontSubmitEmptyLogs();
  }
  ```

**Reference:** `api/src/Domain/General/Models/Drink.php`  
**Non–location-scoped example:** `api/src/Domain/Employees/Models/Employee.php`

---

### 2) Data Transfer Object (DTO)

**File:** `api/src/Domain/{DomainName}/DataTransferObjects/{ModelName}Data.php`

**Structure:**

- Extend `BaseData`
- Constructor with all fields (required fields first, then optional)
- Include `public ?int $id = null` for updates
- Use **Spatie Laravel Data** mapping via `::from(...)` only — do **not** add custom `fromModel()` methods on DTOs. `Data::from($model)`, `Data::from($request->validated())`, `Data::from([...])`, and `BaseData::fromUpdateRequest()` already merge and map by matching property names to array keys / model attributes.
- Keep constructor property names aligned with your validation keys and model attributes (typically `snake_case` for DB-backed fields). When you need non-default shaping (dates, enums), prefer Spatie **casts / transformers / mapping attributes** on the DTO rather than a hand-written `fromModel()`.

**Reference:** `api/src/Domain/General/DataTransferObjects/DrinkData.php`  
**Employees example:** `api/src/Domain/Employees/DataTransferObjects/EmployeeData.php`

---

### 3) Actions (CRUD)

#### Create Action

**File:** `api/src/Domain/{DomainName}/Actions/Create{ModelName}Action.php`

**Structure:**

- Extend `BaseAction`
- `execute()` method accepts `{ModelName}Data $data`
- Returns `{ModelName}` model
- Create new model instance
- Fill with `$data->all()`
- Save model
- Handle any special logic (e.g., media uploads, relations)

**Reference:** `api/src/Domain/General/Actions/CreateDrinkAction.php`

#### Update Action

**File:** `api/src/Domain/{DomainName}/Actions/Update{ModelName}Action.php`

**Structure:**

- Extend `BaseAction`
- `execute()` method accepts `{ModelName} $model, {ModelName}Data $data`
- Returns `{ModelName}` model
- Use `$data->getUpdateData($model)` to get update data
- Update model
- Handle any special logic

**Reference:** `api/src/Domain/General/Actions/UpdateDrinkAction.php`

#### Delete Action

**File:** `api/src/Domain/{DomainName}/Actions/Delete{ModelName}Action.php`

**Structure:**

- Extend `BaseAction`
- `execute()` method accepts `{ModelName} $model`
- Returns `bool`
- Call `$model->delete()` (hard delete, no soft deletes unless required)

**Reference:** `api/src/Domain/General/Actions/DeleteDrinkAction.php`

---

### 4) Helper (Query Builder)

**File:** `api/src/Domain/{DomainName}/Helpers/{ModelName}Helper.php`

**Structure:**

- Extend `BaseHelper`
- Implement `searchable()`: array of field names to search
- Implement `sortable()`: array of field names to sort (merge with `parent::sortable()`)
- Implement `filterable()`: array of `AllowedFilter` instances (merge with `parent::filterable()`)
- Implement `relations()`: array of relation names (usually empty initially)

**Reference:** `api/src/Domain/General/Helpers/DrinkHelper.php`

---

### 5) Query Request (Domain Layer)

**File:** `api/src/Domain/{DomainName}/Http/Requests/QueryBuilders/{ModelName}QueryRequest.php`

**Structure:**

- Extend `BaseModelQueryRequest`
- Abstract class
- Implement `queryBuilder()`:
  ```php
  public function queryBuilder(): BaseQueryBuilder
  {
      return $this->baseQueryBuilder({ModelName}::class)
          ->defaultSort('-id')
          ->allowedFilters({ModelName}Helper::filterable())
          ->allowedIncludes({ModelName}Helper::relations())
          ->allowedSorts({ModelName}Helper::sortable())
          ->search({ModelName}Helper::searchable());
  }
  ```

**Reference:** `api/src/Domain/General/Http/Requests/QueryBuilders/DrinkQueryRequest.php`

---

### 6) Query Request (Application Layer)

**File:** `api/src/Application/API/General/Requests/QueryBuilder/{ModelName}QueryRequest.php`

**Structure:**

- Extend domain `{ModelName}QueryRequest`
- Implement `queryClass()`:
  ```php
  protected function queryClass(): mixed
  {
      return ApiQueryBuilder::class;
  }
  ```

**Reference:** `api/src/Application/API/General/Requests/QueryBuilder/DrinkQueryRequest.php`

*(If you introduce an `Application\API\Employees\...` layer later, mirror this pattern there.)*

---

### 7) Policy

**File:** `api/src/Domain/{DomainName}/Policies/{ModelName}Policy.php`

**Structure:**

- Extend `BasePolicy`
- Implement `model()` method returning model class string:
  ```php
  protected function model(): string
  {
      return {ModelName}::class;
  }
  ```

**Note:** Laravel auto-discovers policies based on naming convention, no manual registration needed.

**Reference:** `api/src/Domain/General/Policies/DrinkPolicy.php`

---

### 8) Base Request (Domain Layer)

**File:** `api/src/Domain/{DomainName}/Http/Requests/Base{ModelName}Request.php`

**Structure:**

- Extend `BaseModelRequest`
- Abstract class
- Implement `rules()`: array of validation rules
- Implement `model()`: return model class string

**Reference:** `api/src/Domain/General/Http/Requests/BaseDrinkRequest.php`

---

### 8.5) Base Request (Application Layer)

**File:** `api/src/Application/API/General/Requests/Base{ModelName}Request.php`

**Structure:**

- Extend domain `Base{ModelName}Request`
- Import with alias: `use Domain\{DomainName}\Http\Requests\Base{ModelName}Request as DomainBase{ModelName}Request;`
- Empty class body (just extends the domain base)

**Reference:** `api/src/Application/API/General/Requests/BaseDrinkRequest.php`

**Example:**

```php
<?php

namespace Application\API\General\Requests;

use Domain\General\Http\Requests\BaseCustomerRequest as DomainBaseCustomerRequest;

class BaseCustomerRequest extends DomainBaseCustomerRequest
{
    //
}
```

---

### 9) Create Request (Application Layer)

**File:** `api/src/Application/API/General/Requests/Create{ModelName}Request.php`

**Structure:**

- Extend `Base{ModelName}Request` (the Application layer one, not the domain one)
- Override `rules()` if needed (e.g., for file uploads)
- Override `validated()` if needed (e.g., to merge auth/context fields required by your DTO)

**Reference:** `api/src/Application/API/General/Requests/CreateDrinkRequest.php`

---

### 10) Update Request (Application Layer)

**File:** `api/src/Application/API/General/Requests/Update{ModelName}Request.php`

**Structure:**

- Extend `Base{ModelName}Request`
- Override `rules()` if needed
- Override `validated()` if needed (e.g., for file uploads)

**Reference:** `api/src/Application/API/General/Requests/UpdateDrinkRequest.php`

---

### 11) Resource (Domain Layer)

**File:** `api/src/Domain/{DomainName}/Http/Resources/{ModelName}Resource.php`

**Structure:**

- Extend `BaseResource`
- Abstract class
- Implement `toArray()`:
  ```php
  public function toArray(Request $request)
  {
      return $this->withAttributes([
          // List all attributes to expose
      ]);
  }
  ```

**Reference:** `api/src/Domain/General/Http/Resources/DrinkResource.php`

---

### 12) Resource (Application Layer)

**File:** `api/src/Application/API/General/Resources/{ModelName}Resource.php`

**Structure:**

- Extend domain `{ModelName}Resource`
- Override `toArray()` if needed (e.g., to add media URLs, computed fields)
- Return merged parent array

**Reference:** `api/src/Application/API/General/Resources/DrinkResource.php`

---

### 13) Resource Collection

**File:** `api/src/Application/API/General/Resources/{ModelName}ResourceCollection.php`

**Structure:**

- Extend `ResourceCollection`
- Set `public $collects = {ModelName}Resource::class;`
- **IMPORTANT:** Do NOT import the domain abstract resource class
- The `{ModelName}Resource::class` will automatically resolve to the concrete class in the same namespace (`Application\API\General\Resources\{ModelName}Resource`)
- If you import the abstract domain class, PHP will try to instantiate it and cause an error

**Reference:** `api/src/Application/API/General/Resources/DrinkResourceCollection.php`

**Example:**

```php
<?php

namespace Application\API\General\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class CustomerResourceCollection extends ResourceCollection
{
    /** {@inheritdoc} */
    public $collects = CustomerResource::class; // Resolves to Application\API\General\Resources\CustomerResource
}
```

---

### 14) Controller

**File:** `api/src/Application/API/General/Controllers/{ModelName}sController.php`

**Structure:**

- Extend `BaseAPICrudController`
- Implement `__construct()`:
  ```php
  public function __construct()
  {
      $this->authorizeResource({ModelName}::class, '{route_parameter}');
  }
  ```
- Implement CRUD methods:
  - `index({ModelName}QueryRequest $request): JsonResponse`
  - `store(Create{ModelName}Request $request): JsonResponse`
  - `show({ModelName} ${route_parameter}): JsonResponse`
  - `update(Update{ModelName}Request $request, {ModelName} ${route_parameter}): JsonResponse`
  - `destroy({ModelName} ${route_parameter}): JsonResponse`
- Implement abstract methods:
  - `modelName()`: return string (e.g., `'customers'`)
  - `routeModelParameter()`: return string (e.g., `'customer'`)
  - `model()`: return model class
  - `helper()`: return helper class
  - `dto()`: return DTO class
  - `resource()`: return resource class
  - `resourceCollection()`: return resource collection class
  - `createActionClass()`: return action class string
  - `updateActionClass()`: return action class string
  - `deleteActionClass()`: return action class string

**Reference:** `api/src/Application/API/General/Controllers/DrinksController.php`

---

### 15) Routes

**File:** `api/routes/api.php`

**URL prefix:** This app uses **`apiPrefix: 'api/v1'`** in `api/bootstrap/app.php`, so HTTP paths are **`/api/v1/...`** (not `/api/...`). Stateless public routes live under **`/api/v1/public/...`** (`routes/public.php`).

**Register the resource where it fits your routing layout** (middleware groups, prefixes, etc.):

```php
Route::apiResource('{route_name}', {ModelName}sController::class)
    ->parameters([
        '{route_name}' => '{route_parameter}',
    ]);
```

**Example:**

```php
Route::apiResource('customers', CustomersController::class)
    ->parameters([
        'customers' => 'customer',
    ]);
```

**Note:** Add import at top of file:

```php
use Application\API\General\Controllers\{ModelName}sController;
```

---

### 16) Factory

**File:** `api/src/Domain/{DomainName}/Database/Factories/{ModelName}Factory.php`

**Structure:**

- Extend `Factory`
- Set `protected $model = {ModelName}::class;`
- Implement `definition()`: return array of fake data
- Use appropriate faker methods for each field; satisfy required foreign keys (other factories, defaults, or seeded IDs)

**Reference:** `api/src/Domain/General/Database/Factories/DrinkFactory.php`

---

## Additional Notes

### Validation Rules

- All nullable strings: `'nullable', 'string', 'max:255'`
- Email: `'nullable', 'email', 'max:255'`
- Required fields: `'required', ...`
- Numbers: `'required', 'integer', 'min:0'` or `'required', 'numeric', 'min:0'`
- Booleans: `'required', 'boolean'` or `'nullable', 'boolean'`

### Activity Logging

- Configured in model's `getActivitylogOptions()`
- Uses `logFillable()`, `logOnlyDirty()`, `dontSubmitEmptyLogs()`
- Log name should match table name

### Testing Checklist

- [ ] Migration runs successfully
- [ ] Model can be created with factory
- [ ] CRUD operations work via API
- [ ] Policy authorization works
- [ ] Search/filter/sort work via query builder
- [ ] Activity logging records changes
- [ ] Validation rules work correctly

---

## Quick Reference: File Locations

```
api/
├── database/migrations/
│   └── {timestamp}_create_{table_name}_table.php
├── src/
│   ├── Domain/
│   │   └── {DomainName}/
│   │       ├── Models/{ModelName}.php
│   │       ├── DataTransferObjects/{ModelName}Data.php
│   │       ├── Actions/
│   │       │   ├── Create{ModelName}Action.php
│   │       │   ├── Update{ModelName}Action.php
│   │       │   └── Delete{ModelName}Action.php
│   │       ├── Helpers/{ModelName}Helper.php
│   │       ├── Http/
│   │       │   ├── Requests/
│   │       │   │   └── Base{ModelName}Request.php
│   │       │   ├── Requests/QueryBuilders/
│   │       │   │   └── {ModelName}QueryRequest.php
│   │       │   └── Resources/
│   │       │       └── {ModelName}Resource.php
│   │       ├── Policies/{ModelName}Policy.php
│   │       └── Database/Factories/{ModelName}Factory.php
│   └── Application/
│       └── API/
│           └── General/
│               ├── Controllers/{ModelName}sController.php
│               ├── Requests/
│               │   ├── Create{ModelName}Request.php
│               │   ├── Update{ModelName}Request.php
│               │   └── QueryBuilder/{ModelName}QueryRequest.php
│               └── Resources/
│                   ├── {ModelName}Resource.php
│                   └── {ModelName}ResourceCollection.php
└── routes/
    └── api.php
```

---

## Example References

- **Employees bounded context (full CRUD):**
  - Domain: `api/src/Domain/Employees/` — models, DTO, actions, `EmployeeHelper`, `EmployeeQueryBuilder`, `Http/Requests`, `Http/Resources`, policy, factory, `Database/Seeders/EmployeeSeeder.php`.
  - Application API: `api/src/Application/API/Employees/` — `EmployeesController`, form/query requests, `EmployeeResource`, `EmployeeResourceCollection`.
  - Routes: `api/routes/api.php` (`auth:sanctum` group), served under **`/api/v1/`** via `bootstrap/app.php`.
- **General bounded context:** follow `Drink` / `Customer` under `Domain\General` and `Application\API\General` — same patterns with different namespaces.
