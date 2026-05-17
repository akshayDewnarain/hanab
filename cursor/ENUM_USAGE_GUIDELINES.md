# Enum Usage Guidelines (Backend + Frontend)

This document defines the enum conventions used in this app, based on existing implementations like:

- `api/src/Domain/General/Models/Drink.php` (`unit`)
- `api/src/Domain/Payments/Models/Payment.php` (`type`, `status`)
- `app/src/modules/enums/general/*.ts` (frontend enums)

Use this as the source of truth whenever adding or changing enum fields.

---

## 1) Backend PHP Enum Pattern

### 1.1 Enum class

Create a PHP native enum in the relevant domain:

```php
<?php

namespace Domain\Payments\Enums;

enum PaymentType: string
{
    case BOOKING = 'booking';
    case DEPOSIT = 'deposit';
}
```

### 1.2 Model cast

Cast enum fields directly in the model `$casts` array:

```php
protected $casts = [
    'type'   => PaymentType::class,
    'status' => PaymentStatus::class,
];
```

Do **not** use legacy string constants like `TYPE_BOOKING` in models. Use enum values directly.

### 1.3 DTO/Data class (Spatie Data)

Use enum-typed properties and `EnumCast`:

```php
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;

public function __construct(
    #[WithCast(EnumCast::class)]
    public PaymentType $type,

    #[WithCast(EnumCast::class)]
    public PaymentStatus $status,
) {}
```

### 1.4 Request validation

Whenever enum values are accepted from request payloads, validate with:

```php
use Illuminate\Validation\Rule;

'type' => ['required', Rule::enum(PaymentType::class)],
```

### 1.5 Queries and comparisons

Use enums in code, and enum values when needed for SQL filtering:

- Query filters: `->where('type', PaymentType::DEPOSIT->value)`
- In-memory/model comparisons:
  - `$payment->type === PaymentType::DEPOSIT`
  - `$payment->status === PaymentStatus::PAID`

Avoid raw string literals (`'paid'`, `'deposit'`) in domain logic.

### 1.6 Resource serialization

Eloquent casted enums are objects. In API resources, return scalar strings:

```php
'type' => $payment->type?->value,
'status' => $payment->status?->value,
```

---

## 2) Frontend TypeScript Enum Pattern

### 2.1 Enum definition

Define enums in `app/src/modules/enums/...`:

```ts
export enum PaymentStatus {
    PENDING = 'pending',
    PAID = 'paid',
}
```

### 2.2 Form/select usage

When an enum field is selectable in forms, use `SelectField` with the enum object.

### 2.3 API contract

Backend resources must return enum fields as strings (not objects), so frontend enums can map directly without transforms.

---

## 3) Migration Rules for Enum Fields

When adding enum DB columns:

- Add new enum values in migrations first.
- Keep DB enum values exactly aligned with PHP enum case values.
- Ensure backwards compatibility defaults for existing rows.

For existing enum columns (status expansions, etc.), update DB enum definitions in migrations before using new enum cases in code.

---

## 4) Payment Enum Implementation Checklist

When adding/changing payment enum fields, update all of:

- `Domain/Payments/Enums/*`
- `Domain/Payments/Models/Payment.php` casts
- `Domain/Payments/DataTransferObjects/PaymentData.php` enum casts
- request validation (`Rule::enum(...)`) where relevant
- actions/services using comparisons and query filters
- resources returning `?->value`
- factories/seeds/test fixtures
- migrations (DB enum definitions)

---

## 5) Anti-patterns (Do Not Do)

- ❌ Model constants duplicating enum values (`TYPE_BOOKING = 'booking'`)
- ❌ Raw string comparisons in business logic (`$status === 'paid'`)
- ❌ Returning enum objects directly in JSON resources
- ❌ Using enum in code without DB migration support for new values

---

## 6) Reference Files

- `api/src/Domain/General/Models/Drink.php`
- `api/src/Domain/General/DataTransferObjects/DrinkData.php`
- `api/src/Domain/General/Http/Requests/BaseDrinkRequest.php`
- `api/src/Domain/Payments/Models/Payment.php`
- `api/src/Domain/Payments/DataTransferObjects/PaymentData.php`
- `app/src/modules/enums/general/`

