# TODO: Create New Model — Frontend (Vue.js/TypeScript)

This document outlines the complete process for creating a new model in the frontend application, including the model class, list view, detail view, and routing configuration.

## Prerequisites

- Choose a reference model (e.g., `Drink`, `Coupon`, `Customer`) that closely matches your new model's structure
- Determine if the model needs an enum (e.g., `CouponType`, `DrinkLiquidUnit`)
- Identify all required fields and their types
- Understand the backend API structure (entity name, route parameter)

---

## Step-by-Step Checklist

### 0) Enum (If Needed)

**If your model has a field that should be an enum:**

**File:** `app/src/modules/enums/general/{EnumName}.ts`

**Structure:**
```typescript
export enum EnumName {
    CASE_ONE = 'case_one',
    CASE_TWO = 'case_two',
}
```

**Reference:** `app/src/modules/enums/general/CouponType.ts`, `app/src/modules/enums/general/LiquidUnit.ts`

---

### 1) Model Class

**File:** `app/src/models/general/{ModelName}.ts`

**Structure:**

- Extend `Model<{ModelName}>`
- Define all properties with TypeScript types
- Constructor: `super('{entity_plural}', '{entity_singular}')`
- Implement `columns()`: Return array of `TableColumn` instances
- Implement `inputFields()`: Return array of `BaseInputField` instances
- Implement `detailSchematic()`: Return `FormBuilder` configuration

**Key Components:**

1. **Properties:**
   ```typescript
   export default class ModelName extends Model<ModelName> {
       property1!: string;
       property2?: number;
       // ... other properties
   }
   ```

2. **Constructor:**
   ```typescript
   constructor() {
       super('entity-plural', 'entity-singular');
   }
   ```

3. **Columns:**
   ```typescript
   columns(): TableColumn[] {
       return [
           new TableColumn({
               name: 'property_name',
               label: 'I18N_KEY',
               sortable: true,
               filterable: true,
               visible: true,
               quickFilter: true,
               component: {
                   type: ComponentEnum.TYPE, // Optional
               },
           }),
           ...super.columns(),
       ];
   }
   ```

4. **Input Fields:**
   ```typescript
   inputFields(): BaseInputField[] {
       return [
           new InputField({
               name: 'property_name',
               label: 'I18N_KEY',
               required: true,
               placeholder: 'I18N_PLACEHOLDER',
               validators: {
                   required: true,
                   minLength: 2,
               },
           }),
           // ... other fields
       ];
   }
   ```

5. **Detail Schematic:**
   ```typescript
   detailSchematic(): FormBuilder {
       const all = this.inputFields();
       const by = (name: string) => {
           const f = all.find((f) => f.options.name === name);
           if (!f) throw new Error(`Field "${name}" not found in inputFields()`);
           return f;
       };
       const fields = (...names: string[]) => names.map(by);

       return {
           name: 'DETAIL',
           panels: [
               {
                   name: 'Panel Name',
                   col: 6,
                   tabs: [
                       {
                           label: 'Tab Label',
                           rows: [
                               {
                                   type: 'fields',
                                   fields: fields('field1', 'field2'),
                                   col: 12,
                               },
                           ],
                       },
                   ],
               },
           ],
       };
   }
   ```

**Available Field Types:**
- `InputField` - Text input
- `TextAreaField` - Multi-line text
- `NumberField` - Numeric input
- `SelectField` - Dropdown (requires enum)
- `ToggleSwitchField` - Boolean toggle
- `DateField` - Date picker
- `UploadImageField` - Image upload (if model has images)

**Reference:** `app/src/models/general/Drink.ts`, `app/src/models/general/Coupon.ts`

---

### 2) List View Component

**File:** `app/src/pages/admin/list-views/{ModelName}ListView.vue`

**Structure:**
```vue
<template>
    <div class="flex flex-col w-full h-full">
        <ListView :entity="'entity-plural'" :model="ModelName" />
    </div>
</template>

<script lang="ts" setup>
    import ListView from '@/components/list-views/ListView.vue';
    import ModelName from '@/models/general/ModelName';
    import { defineComponent } from 'vue';

    defineComponent({
        name: 'ModelNameListView',
    });
</script>
```

**Reference:** `app/src/pages/admin/list-views/DrinkListView.vue`, `app/src/pages/admin/list-views/CouponListView.vue`

---

### 3) Detail View Component

**File:** `app/src/pages/admin/detail-views/{ModelName}View.vue`

**Structure:**
```vue
<template>
    <BaseDetail :model="ModelName">
        <template #header>
            <div class="flex pb-4 pt-2">
                <div class="bg-[var(--color-background)] rounded border border-gray-200 shadow px-2 py-1">
                    <span class="text-xl font-bold text-white">Model Name overview</span>
                </div>
            </div>
        </template>
    </BaseDetail>
</template>

<script lang="ts" setup>
    import { defineComponent } from 'vue';
    import BaseDetail from '@/components/details/BaseDetail.vue';
    import ModelName from '@/models/general/ModelName';

    defineComponent({
        name: 'ModelNameDetailView',
    });
</script>
```

**Reference:** `app/src/pages/admin/detail-views/DrinkView.vue`, `app/src/pages/admin/detail-views/CouponView.vue`

---

### 4) Routes

**File:** `app/src/utils/router.ts`

**Add routes in the `/admin` children array:**

```typescript
{
    path: 'entity-plural',
    name: 'admin-entity-plural-overview',
    component: () => import('@/pages/admin/list-views/ModelNameListView.vue'),
    meta: {
        requiresAuth: true,
        requiresScope: true, // Set to false if not location-scoped
    },
},
{
    path: 'entity-plural/:id',
    name: 'admin-entity-plural-detail',
    component: () => import('@/pages/admin/detail-views/ModelNameView.vue'),
    meta: {
        requiresAuth: true,
        requiresScope: true, // Set to false if not location-scoped
    },
},
```

**Placement:**
- Add routes after similar models (e.g., after `customers` routes)
- Keep alphabetical or logical grouping

**Reference:** `app/src/utils/router.ts` (lines 78-94 for drinks example)

---

## Additional Notes

### Entity Naming Convention

- **Backend API:** Uses plural form (e.g., `coupons`, `drinks`)
- **Route Parameter:** Uses singular form (e.g., `coupon`, `drink`)
- **Model Class:** Singular PascalCase (e.g., `Coupon`, `Drink`)
- **Component Names:** Singular PascalCase with suffix (e.g., `CouponListView`, `CouponView`)

### Field Types Mapping

| Backend Type | Frontend Type | Field Component |
|-------------|---------------|-----------------|
| `string` | `string` | `InputField` |
| `text` | `string` | `TextAreaField` |
| `integer` | `number` | `NumberField` |
| `decimal` | `number` | `NumberField` |
| `boolean` | `boolean` | `ToggleSwitchField` |
| `date` | `string` | `DateField` |
| `datetime` | `string` | `DateField` |
| `enum` | `string` | `SelectField` (with enum) |

### ComponentEnum Types

Common component types for table columns:
- `ComponentEnum.CURRENCY` - For price/money fields
- `ComponentEnum.BOOLEAN` - For boolean fields
- `ComponentEnum.DATE` - For date fields
- `ComponentEnum.DATETIME` - For datetime fields
- `ComponentEnum.IMAGE` - For image fields

### Validation Rules

Common validators:
- `required: true` - Field is required
- `minLength: 2` - Minimum string length
- `maxLength: 255` - Maximum string length
- `min: 0` - Minimum numeric value
- `max: 100` - Maximum numeric value

### Field Width

Use `FieldWidth` enum for field sizing:
- `FieldWidth.FULL` - Full width (default)
- `FieldWidth.HALF` - Half width
- `FieldWidth.THIRD` - Third width

### Detail Schematic Organization

Organize fields into logical panels and tabs:
- **Panels:** Major sections (e.g., "Basic Info", "Pricing", "Status")
- **Tabs:** Sub-sections within panels
- **Rows:** Contain fields
- **Col:** Column width (6 = half, 12 = full)

---

## Quick Reference: File Locations

```
app/
├── src/
│   ├── models/
│   │   └── general/
│   │       └── {ModelName}.ts
│   ├── modules/
│   │   └── enums/
│   │       └── general/
│   │           └── {EnumName}.ts (if needed)
│   ├── pages/
│   │   └── admin/
│   │       ├── list-views/
│   │       │   └── {ModelName}ListView.vue
│   │       └── detail-views/
│   │           └── {ModelName}View.vue
│   └── utils/
│       └── router.ts
```

---

## Testing Checklist

- [ ] Model class compiles without TypeScript errors
- [ ] List view displays correctly
- [ ] Detail view displays correctly
- [ ] Routes work (can navigate to list and detail views)
- [ ] CRUD operations work (Create, Read, Update, Delete)
- [ ] Table columns display correctly
- [ ] Form fields render correctly
- [ ] Validation works
- [ ] Enum dropdowns work (if applicable)
- [ ] Date fields work (if applicable)
- [ ] Image upload works (if applicable)

---

## Example: Coupon Model (Completed)

See the Coupon model implementation as a complete reference:

- **Enum:** `app/src/modules/enums/general/CouponType.ts`
- **Model:** `app/src/models/general/Coupon.ts`
- **List View:** `app/src/pages/admin/list-views/CouponListView.vue`
- **Detail View:** `app/src/pages/admin/detail-views/CouponView.vue`
- **Routes:** `app/src/utils/router.ts` (lines 221-238)

---

## Common Patterns

### Location-Scoped Models

If the model is location-scoped:
- Routes should have `requiresScope: true`
- The backend API will automatically filter by location
- No special frontend handling needed

### Models with Images

If the model has images:
- Use `UploadImageField` in `inputFields()`
- Add image column in `columns()` with `ComponentEnum.IMAGE`
- Implement `removeImage()` method if needed (see `Drink.ts`)

### Models with Enums

If the model uses an enum:
- Create enum file first
- Use `SelectField` with the enum in `inputFields()`
- Import enum in model file

### Models with Relationships

If the model has relationships:
- May need custom fields (e.g., `CustomField` with custom component)
- See `BookingPeriod.ts` for `TimeSlotManager` example

---

## Troubleshooting

### Route Not Found
- Check route name matches exactly
- Verify route is inside `/admin` children array
- Check component import path is correct

### Model Not Loading
- Verify entity name matches backend API route
- Check model constructor uses correct entity names
- Ensure backend API is running and accessible

### Fields Not Displaying
- Check `inputFields()` returns all fields
- Verify field names match model properties
- Check `detailSchematic()` includes all fields

### Type Errors
- Ensure all properties are typed correctly
- Check enum imports if using enums
- Verify field component types match property types

