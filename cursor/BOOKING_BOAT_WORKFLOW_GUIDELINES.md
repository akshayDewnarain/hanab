# Booking boat workflow — business rules (current implementation)

This document describes **what the codebase actually checks today** when deciding whether a boat booking can proceed for a given **date** and **timeslot**. Use it when changing booking logic, debugging “no slot / no boat” issues, or aligning product rules with engineering.

Paths are relative to the repo root (`api/…`, `app/…`).

---

## 1. Layers of truth

Public booking uses **stacked layers**:

1. **Calendar (day)** — is the calendar day selectable at all?
2. **Timeslots (day + slot)** — which slots are returned for that day, and do weekday / period / slot rules include them?
3. **Activity boat-category caps** — for the chosen **activity**, date, slot, and **boat category**, has the location already committed the maximum number of boats allowed for that activity/category (`activity_boat_category.max_bookable_boats`)?
4. **Boat assignment (hold)** — is there a **physical boat** free for that location, date, slot, and guest count (and not blocked by another reservation or an active hold), **and** whose category is not at the activity cap above?

The UI adds **client-side** checks (guest count, phone, pricing display, optional **`activity_slot_available`** from the API) before calling the hold API. The server **does not** repeat every UI check on hold creation; the hard gate for “a boat exists” is **`BoatAssignmentService`** inside **`CreateBoatHoldAction`**. **Finalize** re-checks activity capacity and pricing.

---

## 2. Date-level: when is a day “bookable”?

**API:** `GET /public/locations/{location}/calendar?year=&month=`  
**Implementation:** `api/src/Application/Public/Controllers/CalendarController.php` → `Domain\General\Services\BookingService::buildCalendarDays()` and `isBookable()`.

A day is **not bookable** if any of the following is true:

| Rule | Detail |
|------|--------|
| **Past date** | Compared to “today” in the **location’s timezone** (`$location->timezone` or app default). Past days are never bookable. |
| **No covering booking period** | `BookingService::getBookingPeriods()` loads periods for the location that **intersect** the calendar range, with `active = true`, ordered by **`priority` descending**. For each calendar day, periods whose `start_date`–`end_date` (interpreted in location timezone, start/end of day) **contain** that day are considered. If **none** cover the day → not bookable. |
| **Highest-priority covering period disables appointments** | Among covering periods, the code uses **`$coveringPeriods->first()`** (same collection order → **highest priority first**). If that period’s `disable_appointments` is **true**, the day is **not bookable**, even if another lower-priority period would allow bookings. |

**UI:** `app/src/components/calendar/BookingCalendar.vue` — days outside the current month are styled/blocked separately; only days marked `bookable` from the API are clickable for the current month.

---

## 3. Timeslot-level: which slots appear for a date?

**API:** `GET /public/locations/{location}/activities/{activity}/timeslots?date=YYYY-MM-DD`  
**Optional:** `&guests=<integer>` (minimum **2**, validated in `TimeslotsIndexRequest`).  
**Implementation:** `api/src/Application/Public/Controllers/TimeslotsController.php`.

 Preconditions:

- **Activity belongs to location** — otherwise 404-style error response.

Then:

| Rule | Detail |
|------|--------|
| **Booking periods** | Same intersection idea as calendar: `BookingService::getBookingPeriods($location, startOfDay, endOfDay)` for that **single date**. If this collection is **empty**, the API returns **no timeslots** (empty list). |
| **Period disables appointments** | Periods with `disable_appointments` are **skipped** when **collecting** slots (they contribute no slots). |
| **Time slot must be linked to the period** | Slots come from `$bookingPeriod->timeSlots()` (many-to-many via `booking_period_time_slot`). |
| **Time slot `active`** | Only slots with `active = true`. |
| **Weekday** | `TimeSlot` has optional `weekday` (JSON array). The request date is mapped to **0 = Monday … 6 = Sunday** (`dayOfWeekIso - 1`). A slot is included if **`weekday` is null** (every day) **or** the JSON array **contains** that weekday index. |
| **Deduping** | Slots from multiple periods are merged and **deduplicated by `id`**, then sorted by `start_time`. |

**Note on pivot `is_enabled`:** The `booking_period_time_slot` table has an `is_enabled` flag on the pivot, but **`TimeslotsController` does not filter on it**. Any time slot attached to the period is eligible if the other rules pass.

**Activity ID** in the URL is used for **ownership** when validating the activity; slot membership is still driven by **booking periods + time slot links**. When **`guests`** is present, the controller also computes per-slot **`activity_slot_available`** (boolean) using **`BoatAssignmentService::findAvailableBoat`** for that activity, date, slot, and guest count — same rules as hold creation, including activity caps (see §4).

**Response field:** Each time slot may include **`activity_slot_available`** only when the request included **`guests`**; it is omitted when `guests` is absent.

---

## 4. Boat-level: when can a hold actually reserve a boat?

**API:** `POST /public/locations/{location}/boat-holds`  
**Implementation:** `CreateBoatHoldAction` → `BoatAssignmentService::findAvailableBoat()` (and `findAndLockAvailableBoat()` for transactional locking).

Request validation (`BaseBoatHoldRequest`): `activity_id`, `time_slot_id`, `service_date`, `guests` (integer **min 2**), optional `boat_category_id`, optional `customer_id`, etc. — it does **not** re-verify that the slot is valid for that calendar day; assignment is purely the rules below plus physical boat conflicts.

### 4a. Activity + boat category capacity (`activity_boat_category`)

**Data:** Table **`activity_boat_category`**, model **`ActivityBoatCategory`**, fields `activity_id`, `boat_category_id`, **`max_bookable_boats`** (nullable = no cap for that pair).

**Committed usage** (for one activity + service date + time slot + boat category), implemented in **`ActivityCapacityHelper::committedBoatsForActivitySlotCategory()`**:

- Count **reservations** with matching `activity_id`, `time_slot_id`, `service_date`, `boat_category_id`, and **`status != cancelled`**.
- Plus **boat holds** with matching keys, **`status = ACTIVE`**, and **`expires_at` > now()**.

For each row in **`activity_boat_category`** with a non-null **`max_bookable_boats`**, if **`committed >= max`**, that **boat category** is **at capacity** for that activity/slot/date. **`ActivityCapacityHelper::boatCategoriesAtCapacityForActivitySlot()`** returns the list of such `boat_category_id` values.

**`BoatAssignmentService`** receives **`$activityId`** (and optional **`$boatCategoryId`** from the hold). It excludes any boat whose **`boat_category_id`** is in the at-capacity list (`whereNotIn`), then applies the physical-boat rules below.

### 4b. Physical boat eligibility

A boat is **eligible** only if **all** of the following hold:

| Rule | Detail |
|------|--------|
| **Location** | `boats.location_id` matches. |
| **Operational** | `status = OPERATIONAL` (`Domain\General\Enums\BoatStatus`). |
| **Active** | `active = true`. |
| **Guest capacity vs category** | The boat’s **`boatCategory`** must satisfy `min_guests <= guests <= max_guests` and **`boat_categories.active = true`**. |
| **Optional category filter** | If `boat_category_id` is passed on the hold, only boats in that category; otherwise any matching category **not** ruled out by activity caps. |
| **Activity category not at cap** | Boat’s category must not be in **`boatCategoriesAtCapacityForActivitySlot`** for this activity/date/slot. |
| **No reservation conflict** | No **reservation** for this boat on the same **`service_date`** (date string) and **`time_slot_id`**. |
| **No active hold conflict** | No **`boat_holds`** row for this boat with same date + slot, **`status = ACTIVE`**, and **`expires_at` > now()**. |

The first boat in a deterministic order (`orderBy boat_category_id`, then `id`) is chosen.

If **no** boat matches → **`ConflictHttpException`**: *“No available boat found for the selected parameters.”*

---

## 5. Frontend checks before creating a hold

**Component:** `app/src/components/calendar/BookingCalendar.vue` (`selectTimeSlot`, `bookSlotHint`, `getTimeslotPrice`).

**Timeslots fetch:** `app/src/composables/useTimeslots.ts` passes **`guests`** (party size) on the timeslots request when **`guests >= 2`**, so the API can return **`activity_slot_available`**. Cache keys include guest count. When the user changes guest count while a day is open, timeslots are **refetched**.

These affect UX and whether the user can click **Book**; they are **not** all enforced again on `POST boat-holds` in the same shape, but the **server** still enforces caps via **`BoatAssignmentService`**:

| Check | Behaviour |
|-------|-----------|
| **Location / activity / selected date** | Required; errors surfaced via toast. |
| **Customer phone** | Required on the client before hold; may attempt upsert of customer by phone. |
| **Guest amount** | Required; minimum party size for holds on API is **2** (`guests` `min:2`). |
| **Per-slot minimum guests** | Uses `min_guests_required` on the **time slot** resource. If party size &lt; minimum, booking is disabled and validation messages are shown. |
| **Activity capacity (UI)** | If **`activity_slot_available === false`**, Book is disabled and **`BOOKING_CALENDAR_ACTIVITY_BOAT_CAPACITY_FULL`** is shown (same check on submit in **`selectTimeSlot`**). |
| **Pricing display** | Price is shown only when `guestAmount` is set and a **pricing tier** exists in booking settings for the slot’s `pricing_table_id` and **adult count** (total guests − children, minimum 1 adult for pricing math — see `BookingTimeslotPrice` / `app/src/utils/bookingTimeslotPrice.ts`). Missing tier → no price shown (`null`); this does **not** block the Book button by itself unless other rules disable it. |

---

## 6. After the hold: checkout / finalize

**Implementation:** `FinalizeReservationFromHoldAction` (hold active, not expired, boat exists and operational, **activity capacity** for the hold’s boat category, then pricing snapshot, etc.).

### Activity capacity at finalize

If **`activity_boat_category`** defines **`max_bookable_boats`** for the hold’s **`activity_id`** and the boat’s **`boat_category_id`**, the action compares **`committedBoatsForActivitySlotCategory`** to that cap. If **`committed > max`** → **`CapacityExceededException`** (*“Activity boat capacity exceeded for this time slot.”*). This guards races or data changes after the hold was created.

### Pricing at finalize

When finalizing, if the time slot has a **`pricing_table_id`**, the server recomputes pricing via **`BookingTimeslotPrice::mergeAuthoritativeSnapshot()`**:

- Requires a **`pricing_tiers`** row for that location, table, and **adult count** used for pricing.
- If missing → **`CapacityExceededException`** with message *“No pricing tier found for the selected time slot and number of adults.”*

So a hold might exist, but **finalize** can still fail on **capacity** or **pricing** — treat both as **hard rules at finalize**, not only UI concerns.

---

## 7. Activity capacity helper (reference)

**`ActivityCapacityHelper`** (`api/src/Domain/Calendar/Support/ActivityCapacityHelper.php`):

| Method | Role |
|--------|------|
| **`committedBoatsForActivitySlotCategory`** | Reservations (non-cancelled) + active unexpired holds for one activity / date / slot / boat category. |
| **`boatCategoriesAtCapacityForActivitySlot`** | Returns **`boat_category_id`** values where committed usage **≥** configured **`max_bookable_boats`**. |
| **`capsForActivity` / `resolveCapFor`** | Read caps from **`activity_boat_category`**; **`resolveCapFor`** also allows a future **slot-specific** override parameter (currently unused in the public timeslot list path). |

---

## 8. Quick checklist (“why can’t I book this slot?”)

1. **Day greyed out** → past, no period, or **highest-priority** covering period has **`disable_appointments`**.
2. **No timeslots list** → no intersecting **active** periods for that date, or all contributing periods disable appointments, or no slots pass **active + weekday** rules.
3. **Slot shows full / Book disabled after guest count set** → **`activity_slot_available === false`**: activity **`max_bookable_boats`** reached for every boat category that could still satisfy guest count and physical availability (or no physical boat left even without cap).
4. **Hold fails / conflict** → no boat passing **§4** (includes activity cap + operational + active + category guest range + no reservation + no active unexpired hold on that boat).
5. **Finalize fails on activity capacity** → committed count **>** cap for that activity/slot/date/category (re-check in **`FinalizeReservationFromHoldAction`**).
6. **Finalize fails on pricing** → missing **`pricing_tiers`** row for slot’s pricing table and computed **adults** count.

---

## 9. Primary code references

| Concern | Location |
|---------|----------|
| Calendar day rules | `api/src/Domain/General/Services/BookingService.php` |
| Public calendar | `api/src/Application/Public/Controllers/CalendarController.php` |
| Public timeslots (+ optional `guests`, `activity_slot_available`) | `api/src/Application/Public/Controllers/TimeslotsController.php` |
| Timeslots request rules | `api/src/Application/Public/Requests/TimeslotsIndexRequest.php` |
| Public time slot JSON | `api/src/Application/Public/Resources/TimeSlotResource.php` |
| Activity committed counts / categories at cap | `api/src/Domain/Calendar/Support/ActivityCapacityHelper.php` |
| Boat pick / conflicts + activity caps | `api/src/Domain/Bookings/Support/BoatAssignmentService.php` |
| Hold creation | `api/src/Domain/Bookings/Actions/CreateBoatHoldAction.php` |
| Hold request rules | `api/src/Domain/Bookings/Http/Requests/BaseBoatHoldRequest.php` |
| Authoritative slot pricing | `api/src/Domain/Bookings/Support/BookingTimeslotPrice.php` |
| Finalize from hold | `api/src/Domain/Bookings/Actions/FinalizeReservationFromHoldAction.php` |
| Activity ↔ category caps (model) | `api/src/Domain/General/Models/ActivityBoatCategory.php` |
| Booking UI | `app/src/components/calendar/BookingCalendar.vue` |
| Timeslot HTTP client | `app/src/composables/useTimeslots.ts` |

---

*Last aligned with repository behaviour by automated documentation pass; if behaviour diverges, update this file together with the code.*
