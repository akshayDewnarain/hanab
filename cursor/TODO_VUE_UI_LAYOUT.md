# Vue UI reuse and Hanab layout migration

## Goal

Reuse Vue 3 components across client projects through the private `vue-ui` Git repository. Adopt cest-ca's cleaner desktop presentation in Hanab while keeping Hanab's branding, routes, API integration, and application behavior.

## Project context

- `../vue-ui`: reusable component library (`@freelance/vue-ui`).
- `../cest-ca`: internal application used as a design and component reference.
- `app/`: Hanab frontend, using Vue 3, TypeScript, Vite 7, and Tailwind 4.
- `api/`: Hanab backend; outside this migration's scope.
- Install the library directly from private Git using immutable release tags. No paid npm registry.
- Import components as `Sidebar` and `Topbar`; do not rename them `SharedSidebar` or `SharedTopbar`.
- Keep routing, permissions, translations, authentication, persistence, and business logic in Hanab's adapters.
- The user will perform visual and functional validation. Do not launch browser validation unless requested.
- Preserve unrelated in-progress edits, including `MobileFiltersOverlay.vue` and existing locale changes.

## Implemented — awaiting user acceptance

- [x] Replace Hanab's sidebar rendering with `Sidebar` from `@freelance/vue-ui`.
- [x] Preserve Hanab logo, navigation, settings panel, logout confirmation, and expansion persistence.
- [x] Integrate the library's Tailwind source registration and override theme tokens with Hanab colors.
- [x] Replace topbar rendering with `Topbar` from the library.
- [x] Supply account details, language preferences, translations, and logout actions from Hanab.
- [x] Add reusable `NotificationBell` in `vue-ui` and expose it in Hanab’s topbar with localized empty state.
- [ ] Connect Hanab notification fetching, unread counts, read/dismiss actions, and error handling once the backend exists.
- [x] Adapt cest-ca's responsive breadcrumbs, replace its brand label with Hanab, and resolve translated route labels.
- [x] Adapt list headers, toolbar controls, table surfaces, and pagination styling.
- [x] Connect the table refresh button to the existing list helper.
- [x] Update detail-page spacing and title surfaces, card filter surfaces, and the unsaved-changes bar.
- [x] Use dynamic viewport height for the desktop admin layout.
- [x] Pass production build, TypeScript checks, targeted ESLint, and diff whitespace checks after the layout changes.

These are implemented changes, not a claim that every cest-ca screen or feature has been migrated. The outer admin layout was already largely identical; the work focuses on reusable navigation and common page presentation.

## Remaining work

### Git dependency and releases

- [ ] Commit the intended library source in `vue-ui`, excluding local IDE files and generated artifacts as appropriate.
- [ ] Build and check the library, then publish its first Git release tag containing both Sidebar and Topbar.
- [ ] Install that actual tag in Hanab's `app/package.json` and update `app/package-lock.json`.
- [ ] Replace the temporary local dependency resolution with the tagged Git dependency.
- [ ] Verify a clean install and build using a supported Node version (20.19+ on Node 20, or 22.12+).
- [ ] Configure read-only private Git access for CI/deployment.

Current limitation: the successful local builds use an existing `app/node_modules/@freelance/vue-ui` link to the sibling library. The library is not yet declared in Hanab's package manifest or lockfile. A clean checkout is therefore not reproducible yet. Do not invent or assume an existing release tag.

### User validation

- [ ] Review the overall desktop layout against cest-ca and identify any remaining differences to adopt.
- [ ] Check sidebar expanded/collapsed states, saved state after reload, active routes, and settings-panel alignment.
- [ ] Check notification disclosure, empty state, keyboard close, and outside-click dismissal.
- [ ] Check account menu, Dutch/English switching and persistence, and logout confirmation/cancellation.
- [ ] Check breadcrumb labels, links, current-page state, truncation, and narrow desktop widths.
- [ ] Check employee cards and their filter column.
- [ ] Check table filtering, search, refresh, configuration, selection, pagination, and scrolling with many rows.
- [ ] Check detail headers, form panels, scrolling, and unsaved-changes Apply/Revert controls.
- [ ] Check modal layering above the topbar/sidebar and keyboard navigation.
- [ ] Check mobile layouts for regressions; this migration targets desktop presentation.
- [ ] Report findings and implement follow-up fixes.

### Follow-up decisions

- [ ] Decide which further cest-ca components should be extracted into `vue-ui` instead of copied into client projects.
- [ ] Consider moving common breadcrumb/list/detail presentation into the library after the design is accepted. Current `navigation.css` and `list-view.css` adaptations are local to Hanab.
- [ ] Decide whether to implement global search separately. The topbar search is still presentation only; no search service or command palette was added.

## Main files

- `app/src/components/navigation/Sidebar.vue`
- `app/src/components/navigation/Topbar.vue`
- `app/src/components/navigation/BreadCrumb.vue`
- `app/src/layouts/AdminLayout.vue`
- `app/src/components/list-views/ListView.vue`
- `app/src/components/list-views/CardListView.vue`
- `app/src/components/pagination/ListViewPagination.vue`
- `app/src/components/details/BaseDetail.vue`
- `app/src/pages/admin/detail-views/*.vue`
- `app/src/css/global.css`
- `app/src/css/navigation.css`
- `app/src/css/list-view.css`
- `app/src/i18n/locales/en.json` and `nl.json`

## Working approach

1. Let the user validate the current presentation and provide feedback.
2. Make focused fixes without replacing Hanab's business logic with cest-ca's.
3. Prefer improvements in `vue-ui` for generic component behavior; keep project-specific adapters here.
4. Run relevant type/build/lint checks for code changes. User acceptance remains separate from automated checks.
5. Finish Git-tag installation before treating the integration as ready for clean checkouts or CI.
