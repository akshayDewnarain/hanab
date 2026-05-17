# Frontend modules structure (`app/src/modules`)

Project note: conventions and layout for shared, mostly domain-agnostic building blocks (list views, forms, API models, request/response typing). Prefer importing from here instead of scattering the same types across features.

Paths below are relative to **`app/src/modules/`** unless noted. Import alias in the Vue app: **`@/modules/...`** (see Vite/TS config).

---

## Layout

| Area | Path | Role |
|------|------|------|
| **Field & form types** | `types/support/inputs/` | Declarative options for inputs (`BaseInputFieldOptions`, `FieldRelation`, per-control `*FieldOptions`, select/calendar helpers). |
| **Form validation types** | `types/support/forms/` | `ValidatorFunction`, `ValidatorRule` (shared by `useFormValidator` and field validators). |
| **Form builder** | `types/support/form-builder/` | `FormBuilder`, `AnyRec`, `PayLoadOptions`, and related types (e.g. `Model.detailSchematic()`, `toPayload`). |
| **List / table** | `types/support/list-views/` | Column/component typing, `EntityRelations`, table relations. |
| **HTTP responses** | `types/support/responses/` | `IndexResponse`, `QueryParameters`, pagination/meta links, etc. |
| **Media** | `types/support/media/` | Small shapes such as `MediaURL` for upload previews. |
| **Model helpers** | `types/support/models/` | `ModelConstructor` and similar generics tied to `Model`. |
| **Modals** | `types/support/modals/` | Modal service shapes (`ModalProps`, `ModalResult`, `ModalAction`, content props such as `EntityModalProps`, …). |
| **Toasts** | `types/support/toasts/` | Toast service contract (`IToastService`); runtime implementation lives under `app/src/services/`. |
| **Enums** | `enums/support/inputs/`, `enums/support/forms/`, `enums/support/list-views/`, `enums/support/modals/` | Field layout (`FieldWidth`), form validation keys (`ValidatorType`), column and modal enums (`TableColumnComponentEnum`, `ModalType`, …). |
| **Runtime classes** | `models/support/` | `Model`, `BaseInputField`, `TableColumn`, … |

---

## Convention: one primary export per file

- **Do:** `FieldRelation.ts` exports only `FieldRelation`; `RemoteFetchConfig.ts` exports only `RemoteFetchConfig`.
- **Do not:** bundle several unrelated exported types into one file, or maintain large barrel `index.ts` files for these inputs (import the concrete file path).

This keeps diffs small, avoids circular-import footguns, and makes “go to definition” land in the right place.

---

## Convention: documentation (JSDoc)

All exported types, interfaces, and enums in this area should use a **single block comment above** the export. Do **not** document individual properties with inline or trailing `/** … */` on the same line as members inside the type body.

**Template (object / record types and interfaces):**

The same rules apply whether you use `export type … = { … }` or `export interface …` (see `types/support/inputs/FieldRelation.ts`).

1. One short paragraph: what the type is.
2. Optional “Used by …” / “Inherits …” bullet list for context.
3. **`@property`** for each field on the type (see `types/support/inputs/FieldRelation.ts`).

**Reference example** (`FieldRelation`):

```ts
/**
 * Describes how a table/form field is connected to a related entity.
 *
 * Used by table columns and form builders to:
 * - resolve relation values from a row object
 * - map selected relation IDs to form data
 * - handle direct relations and pivot-based many-to-many relations
 *
 * @property type - Type of entity relation, e.g. belongsTo, hasMany, manyToMany.
 * @property idKey - Optional key used as the related entity identifier. Defaults to `id`.
 * @property formDataKey - Optional override for the submitted form data key.
 * @property path - Dot path to the relation inside the row object, e.g. `employee_role.name`.
 * @property pivot - When true, the value is treated as pivot data instead of plain IDs.
 */
export type FieldRelation = { ... };
```

**Variants:**

- **String literal unions** (e.g. `EntityRelations`): describe allowed values with backtick bullets instead of fake `@property` entries.
- **Union types** (e.g. `ValidatorRule`): describe each branch in prose or bullets.
- **Callables** (e.g. `ValidatorFunction`): describe arguments and return value in the block; avoid `@param` on the type alias unless your tooling standardizes it.
- **Enums**: one block above the enum; list members in an “Enum members:” bullet section (see `enums/support/inputs/FieldWidth.ts`).

---

## Practical imports

```ts
import type { BaseInputFieldOptions } from '@/modules/types/support/inputs/BaseInputFieldOptions.ts';
import type { FieldRelation } from '@/modules/types/support/inputs/FieldRelation.ts';
import { FieldWidth } from '@/modules/enums/support/inputs/FieldWidth.ts';
import { DropdownWidth } from '@/modules/enums/support/inputs/DropDownWidth.ts';
import type { FormBuilder } from '@/modules/types/support/form-builder/FormBuilder.ts';
import type { AnyRec } from '@/modules/types/support/form-builder/AnyRec.ts';
import type { PayLoadOptions } from '@/modules/types/support/form-builder/PayLoadOptions.ts';
import type { ModalProps } from '@/modules/types/support/modals/ModalProps.ts';
import type { ModalResult } from '@/modules/types/support/modals/ModalResult.ts';
import { ModalType } from '@/modules/enums/support/modals/ModalType.ts';
import type { IToastService } from '@/modules/types/support/toasts/IToastService.ts';
import { ValidatorType } from '@/modules/enums/support/forms/ValidatorType.ts';
import type { ValidatorFunction } from '@/modules/types/support/forms/ValidatorFunction.ts';
import type { ValidatorRule } from '@/modules/types/support/forms/ValidatorRule.ts';
```

`BaseInputField` (`models/support/inputs/BaseInputField.ts`) consumes `BaseInputFieldOptions`; entity models extend `Model` and return field instances from `inputFields()`.

Modal types and modal enums follow the same one-export-per-file rule; import the concrete file (for example `ModalProps.ts`, `ModalType.ts`) rather than a barrel.

---

## When you add a new field kind

1. Add `YourThingFieldOptions.ts` under `types/support/inputs/` extending `BaseInputFieldOptions` where appropriate.
2. Document it with the block + `@property` pattern.
3. Wire a concrete `BaseInputField` subclass under `models/support/inputs/` (or your inputs package) that points `options` at the new type.

Keeping types under `types/support/inputs/` and classes under `models/support/` preserves a clear split between compile-time shape and runtime behavior.
