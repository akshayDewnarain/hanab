# Prompt — Hanab v1

Implement the next unchecked build item in `cursor/TODO_V1.md`. Stop when a decision checkbox is still open. Do not invent owners, required fields, pilot scope, or Hanab policy. Priority 1 and 2 come before messages, the organogram UI, push, distribution, and the map.

Work in the Hanab repo. The component library is the sibling project `../vue-ui` (`@freelance/vue-ui`). Read `vue-ui/src/index.ts` before adding UI.

## Vue UI

Before creating a component, search `../vue-ui/src`. Prefer an existing export: `PageLayout`, `ListView`, `ListFilters`, `ListViewPagination`, `ListViewBulkBar`, `DetailView`, `DetailHeader`, `DetailTabs`, `DetailCard`, `DetailUnsavedBar`, `Topbar`, `TopbarSearch`, `Sidebar`, `NotificationBell`, `AdvancedDropdown`, `ToggleSwitch`, `useDropdown`.

Use it from Hanab. Pass data, translated labels, and callbacks in. Do not copy the component into Hanab and do not edit it to call Hanab APIs.

If nothing fits, add it to `vue-ui` only when all of these are true:

- It is presentation: layout, interaction, empty, loading, and error visuals.
- It has no employee, skill, certificate, import, permission, route, or API knowledge.
- It does not import Pinia, vue-i18n, Axios, or Vue Router.
- Another app could use it through props, slots, and events.

Otherwise keep it in Hanab.

Library components take props in and emit events out. Labels are already translated strings. Slots are for icons and actions. A library composable is only for UI behavior, as `useDropdown` is. Export the component, its props type, and that composable from `src/index.ts`.

Hanab keeps a thin page. A composable loads data, maps it to library props, and handles routes, i18n, auth, and persistence.

## Frontend

Vue 3 `<script setup lang="ts">`. Put state and requests in one composable per concern under `app/src/composables/`. The component renders that composable and existing fields. Reuse Hanab list, detail, form, and modal building blocks before adding markup. Add Dutch and English strings together. Keep components small.

## Backend

Follow the Employees bounded context. Copy `Employee`, not a generic CRUD sketch. New employee concepts stay in `Domain\Employees` unless the todo introduces a different context.

Domain, under `api/src/Domain/Employees/`:

- Model, factory, and migration. No soft deletes unless the todo requires them.
- DTO extending `BaseData`. Map with Spatie `::from()`. No custom `fromModel()`.
- Action extending `BaseAction`, invoked as `Action::make()->execute(...)`.
- Helper extending `BaseHelper` for `searchable()`, `sortable()`, `filterable()`, and `relations()`.
- Policy extending `BasePolicy`.
- Domain form request and domain query request.

Application, under `api/src/Application/API/Employees/`:

- Controller extending `BaseAPICrudController`. It delegates. No business rules.
- Form request and query request that only extend the domain classes. The query request returns `ApiQueryBuilder`.
- API resource extending the domain resource when the response shape changes.

Search belongs on the helper and query builder. Import matching, duplicate checks, and field ownership belong in an action. Controllers stay thin.

## Done

Match the acceptance criteria of that todo item only. Say which `vue-ui` component you reused, which one you added and why, and which part stayed in Hanab because it is domain-specific.
