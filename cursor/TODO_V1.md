# Hanab v1 — priority and todo list

Goal: agree what is ready, how employee data is managed without AFAS, and what is required for a first working version.

Meeting result to leave with: a concrete agreement on expertise search, data management without AFAS, and the date this can be tested.

Status words for Kay on the previous action list: **gereed**, **demonstreren**, **nog ontwikkelen**, **nog besluiten**.

Owners, delivery dates, and the “done” criterion stay blank until the meeting assigns them.

## Priority

Work in this order. Do not schedule a later priority if it pushes the test date of priority 1 and 2.

| Priority | Topic | What “ready” means for the first test |
| --- | --- | --- |
| 1 | Find colleagues by expertise | Typing a term such as `ketel` returns the right people, with agreed result fields and acceptance criteria. |
| 2 | Fill and maintain data without AFAS | An allowed source, a safe Excel/CSV import, field ownership, and a process for joiners, role changes, and leavers. |
| 3 | Close the previous action list | Each open point has a Kay status. Small profile gaps that search depends on are in the first test; the rest is scheduled after it. |
| 4 | Messages, reactions, and notification preferences | In-app messages and push are specified per type before any of it is built. |
| 5 | Map of work locations | Feasibility and extra cost only. Build it only if priority 1 and 2 stay on the test date. |
| 6 | Approval, pilot, and planning | Confirm the earlier stop was the AFAS link only. Name pilot scope, testers, reviews, and extra cost before adding scope. |

## Already in the product

This is the baseline. Do not rebuild it.

- Admin records for employees, roles, locations, skills, and certificates.
- Employee fields: first name, last name, initials, email, phone, employee number, employment type, start date, active flag, photo, role, location. Initials are optional.
- Skill and certificate catalogs with name, short code, category, and description. Skills can be linked to an employee with a level. Certificates can be linked with number, issue date, and expiry.
- Location is a name plus a cluster. Role is a name plus a description.
- Admin list search on employees already matches name, email, phone, employee number, employment type, skill name and code, certificate name and code, role name, and location name and cluster.

Also in the product since 8 Nov 2026: skill and certificate synonyms, colleague expertise search, and a column mapper for CSV, delimiter text, and Excel. The employee file matches on employee number, can update Hanab fields, and leaves photo, skills, certificates, notification preferences, and date of birth as they are. A code in search results is a button: hover or press it to show the full name.

Access follows roles. `super-admin` can maintain every record and run the employee import. A deputy is another user with that role, or a role that includes the permission for the job. No person is named. Catalog writes and the active flag use the skill and certificate permissions. Profile links use the employee skill and employee certificate permissions.

The organogram has two views. Management follows who reports to whom: an employee can have a manager, and people with the same role can report to different people. Roles remain a separate view, where a role can sit under another role. An organization level is a name, an order, and a color on a role, with an optional override on the person. It does not decide the reporting line. Work location stays the work site. Team and contract or entity are still not fields.

Sign-in is an existing account. A signed-in person can search, open the organogram, and read the directory. Date of birth is returned only to someone who can update employees. Export uses the same permission as the employee import or the catalog create. A leaver stays on file. The uploaded file is not stored, and the import log keeps the file name, the user, the outcome, and the counts. No retention period is set.

In-app messages cover introductions, birthdays, work anniversaries, new certificates, and company news, with one reaction per person from 👍 🎉 👏. Company messages stay in the inbox. Push is not sent.

The first version is the website. A QR code opens the login. A home-screen app and a store app are not built. A map is not built: a location is a name and a cluster, with no coordinates.

Not in the product yet: team and contract or entity as their own fields, push, a map, and an installable app or store listing.

## 1. Find colleagues by expertise

Highest priority. Outcome: how search works, and acceptance criteria for that function.

### Decide before building

Working decisions from 8 Nov 2026, so building could start. Revise these. They are not a Hanab sign-off, and no owners are named.

- [x] Search covers specialisms (skills), techniques, and certificates. Techniques stay a skill category (`technical`). There is no separate technique list.
- [x] Synonyms are maintained terms stored on the skill or certificate (a comma-separated list, for example `ketel` and `boiler` on `CV-ketel`), not free-text guesses.
- [x] A match is case-insensitive and matches the start or the middle of a name, code, or synonym.
- [x] Inactive skills, inactive certificates, and inactive employees are excluded.
- [x] Results show: name, job title (role), location, the expertise that matched, and contact (business email and phone). Team is omitted because that field is not in the product yet.
- [x] Team is omitted from the first test. The acceptance criteria say so.
- [x] Working rule until Hanab names people: catalog names, codes, synonyms, and the active flag are edited on the skill and certificate records by a user with the matching update permission. A term is searchable only while that record is active, so `is_active` is the review gate. Profile links use the employee skill and employee certificate permissions.

### Build

- [x] Colleague search screen: one query box, results as people, not as separate skill and certificate tables.
- [x] Match query against skill name, skill code, skill synonyms, certificate name, certificate code, and certificate synonyms.
- [x] Show why a person matched (the skill or certificate, and the synonym if that is what hit).
- [x] Empty state when nothing matches, and a short hint to try a synonym or a shorter term.
- [x] Same search on mobile: query, results, and contact details usable without hover.

### Acceptance criteria

- [x] `ketel` returns every active employee linked to a skill or certificate whose name, code, or synonym contains that term, and no one else.
- [x] A synonym hit is labeled with the canonical expertise name.
- [x] Each result shows name, role, location, the matched expertise, and business email and phone. Team is omitted. Only the expertise that matched is shown, not the full profile.
- [x] An employee with only inactive expertise does not appear.
- [x] Updating a synonym or a link changes the next search without a reload of the whole catalog by hand.
- [x] The written rule for who enters, checks, and updates expertise is a working decision: the skill, certificate, employee skill, and employee certificate permissions. Hanab can still replace it.

| Action | Owner | Delivery date | Done when |
| --- | --- | --- | --- |
| Agree search behavior and acceptance criteria | | | Criteria above are accepted in the meeting notes |
| Agree who maintains expertise | | | Named roles for catalog, profile links, and review |
| Implement colleague expertise search | | | Acceptance checks pass on desktop and mobile |

## 2. Fill and maintain data without AFAS

Outcome: a process Hanab can run for the first load and for later changes.

### Decide before building

Working decisions from 8 Nov 2026, so the employee file could be built. Revise these. They are not a Hanab sign-off, and no owners are named.

- [x] The allowed source is a cleaned spreadsheet the administrator uploads: CSV, text with a delimiter, or Excel. No AFAS connection in this version. The file owner is not named yet.
- [x] Stable key for a person is `employee_number`. Email is a secondary check: it must not belong to a different employee number, and it is not used to find the person.
- [x] Hanab-managed fields in this import: employee number, first name, last name, initials, business email, business phone, role (by name), work location (by name), employment type, start date, active or left, and an optional manager employee number. A later file updates a Hanab field only when that column is in the file. An empty manager cell clears the manager. A missing manager column leaves the current manager. Initials are optional. Team and contract or entity are not columns yet.
- [x] Employee-managed fields have no import column, so the file cannot clear them: photo, skills, certificates, and notification preferences.
- [x] This employee file does not add or remove skill or certificate links. Links stay as they are because the file does not mention them.
- [x] New employee: a row with an unknown employee number creates a profile. Required on that row: employee number, first name, last name, email, role, location, employment type, and active. Phone and start date are optional.
- [x] Role or location change: the same employee number updates the Hanab fields present in the file. Role and location are matched by name and must already exist.
- [x] Leaver: `is_active` false (also `left`, `nee`, `inactive`) keeps the profile and removes the person from expertise search. There is no separate end date and no retention period yet.
- [x] The primary administrator is any signed-in user with the `super-admin` role. A deputy is another user with that role, or a role that includes `import employees`. No person is named in the product.
- [x] The organogram has a management view and a role view. Management follows an optional manager on the employee. People with the same role can report to different managers. The role view keeps the role parent and sort order. An organization level supplies the card color. Team and contract or entity stay out of the employee file.

### Build

Working note, 8 Nov 2026: the column mapper remains for every registered model. Catalog models still only add records. The employee model now follows the matrix above.

- [x] CSV template with the Hanab columns and an example row. Other models still download headers only.
- [x] Dry-run: report created, updated, unchanged, skipped, and errors before anything is written.
- [x] Import applies only after the dry-run is accepted.
- [x] Reject the file on duplicate employee numbers inside the file, missing required values for a new person, or an unknown role or location. Team and entity are not columns in this import.
- [x] Write an import log: who ran it when a user is signed in, when, file name, and counts. A rejected file is logged and writes no employees.
- [x] Field matrix stored with the product (`EmployeeImportMatrix`) so a later import has no column for photo, skills, certificates, or notification preferences.

### Field matrix to confirm

| Field | Owner | Required | In first import |
| --- | --- | --- | --- |
| Employee number | Hanab | Yes | Yes |
| Initials | Hanab | No | Yes, optional |
| First name, last name | Hanab | Yes | Yes |
| Business email | Hanab | Yes | Yes |
| Business phone | Hanab | No | Yes, optional |
| Role | Hanab | Yes | Yes, by name |
| Team | Hanab | Later. The field does not exist yet. | No |
| Manager | Hanab | No | Optional column `manager_employee_number`. Empty clears it. Omitted leaves it. |
| Work location | Hanab | Yes | Yes, by name |
| Contract / entity | Hanab | Later. The field does not exist yet. | No |
| Employment type, start date | Hanab | Employment type yes; start date no | Yes. Start date is optional. |
| Active / left | Hanab | Yes | Yes |
| Photo | Employee | No | No |
| Skills and certificates | Split: catalog by Hanab, links by employee or editor | No | Optional |
| Notification preferences | Employee | No | No |
| Date of birth | Editor, on the employee record | No | No. The file cannot set or clear it |

### Acceptance criteria

- [x] A first file creates employees without duplicates when the same employee number appears once.
- [x] A second file updates a role and does not clear photo, skills, or notification preferences. Notification preferences have no column yet, so the file cannot write them.
- [x] A leaver row removes the person from expertise search and leaves the record recoverable by an administrator.
- [x] A bad file changes nothing and lists the row errors.
- [x] Administrator access is the `super-admin` role, and a deputy is another user with that role or with `import employees`. Both can run the dry-run. No person is named.

| Action | Owner | Delivery date | Done when |
| --- | --- | --- | --- |
| Choose the first-list source | | | Source and file owner are named |
| Confirm field matrix and required fields | | | Matrix above is signed off |
| Name administrator and deputy | | | Two named people |
| Implement CSV dry-run and import | | | Acceptance checks pass on a sample file |

## 3. Previous action list

Kay marks each row. Items that block search or the employee profile for the first test move into priority 1 or 2. The rest wait.

| Item | Suggested status to confirm | Kay status | In first test |
| --- | --- | --- | --- |
| Abbreviations show the full name on click and on hover, including mobile | Built for search results. The code is a button. Hover shows the name on desktop. Pressing the button keeps it open, including on mobile. | | Yes |
| Separate initials field | Built. Optional on the employee, the profile, and the employee import. | | Yes |
| Business email and phone, and which fields are required | Email and phone exist. Required rules are not confirmed. Phone is optional in the database. | | Yes |
| Organogram: director, team lead, and employees per location | Management view shows who reports to whom. Role view keeps the role tree. Location is a filter. Cards use a configurable level color. | | Yes |
| Team, work location, and contract or entity on the profile | Work location exists as employee location. Team and contract or entity do not. | | Team and entity only if search results must show team |
| Employees can turn notifications on and off | Built for in-app categories. Company messages stay on. Push is not sent. | | In-app only |
| Which alerts still work when the site or app is closed | None. Push is not built. The plain website does not deliver a closed tab. | | No |
| Distribution: website, installable web app, or store, and where a QR code points | Working rule: the first version is the website. A QR code opens the login. A home-screen app and a store app wait. | | Decision only; store build is out of the first test |

- [ ] Kay fills the status column for every row.
- [x] Abbreviations: tap or click the code to show the full name; hover does the same on desktop; the control is a button, not hover-only, so mobile works.
- [x] Add `initials` on the employee, show it on the profile, and include it in the import template. Working decision: initials are optional.
- [x] Confirm required fields in the matrix in section 2. Working decision, not a Hanab sign-off: employee number, first name, last name, business email, role, work location, employment type, and active are required for a new person. Business phone, initials, and start date are optional.
- [x] Organogram: the management view is a dotted canvas of employees and their managers. The role view keeps the role parent tree. A role can carry an organization level for color, and a person can override that level. Director and team lead are not fixed titles.
- [ ] Show team and contract or entity on the employee profile once those fields exist. Work location is already on the profile. The manager is on the employee record and in the management organogram.
- [x] In-app notification categories can be switched off, except company messages, which stay in the inbox. Closed-app delivery is not available.
- [x] Distribution decision, working rule 8 Nov 2026: the first version is the website. An installable web app is the next step if Hanab wants a home-screen icon. A store app is a later quote. A QR code opens the website login, not a store page.

| Action | Owner | Delivery date | Done when |
| --- | --- | --- | --- |
| Kay status on each previous point | Kay | | Every row has one of the four statuses |
| Abbreviations on click, hover, and mobile | | | A code reveals the full name without hover |
| Initials field and import column | | | Initials round-trip through profile and CSV |
| Required-field confirmation | | | Matrix in section 2 is updated |

## 4. Messages and reactions

Specify this before building. It does not block the expertise-search test.

Per message type, decide who creates it, who approves it, what can be automatic, whether it is an in-app message, a push notification, or both, and the default preference.

| Type | Proposed creator | Proposed automation | In-app | Push | Preference |
| --- | --- | --- | --- | --- | --- |
| New employee introduction (role and team) | Hanab editor | Draft when a new active employee is imported; publish after approval | Yes | Optional | Category: people |
| Birthday | Automatic from date of birth | Needs a date-of-birth field and a privacy decision | Yes | Optional | Category: milestones |
| Work anniversary | Automatic from start date | Start date already exists | Yes | Optional | Category: milestones |
| New certificate or diploma | Automatic when a certificate link is added | Publish only if the certificate is marked visible | Yes | Optional | Category: milestones |
| Contract won | Hanab editor after internal release | Never automatic from an external source | Yes | Optional | Category: company |
| Emoji reactions (for example 👍 🎉 👏) | Any signed-in employee on an existing message | Add, change, and remove own reaction only | On the message | No | Not a separate alert |

- [x] Working rules for the table, 8 Nov 2026. Revise these. An imported active employee becomes a draft introduction until someone with `publish messages` publishes it. Birthdays use an optional date of birth and do not show that date to colleagues. Work anniversaries use the start date. An active certificate link publishes a milestone. A company message is written by someone with `create messages` and is never taken from an external source.
- [x] Date of birth is optional. Editors can set it on the employee record. Colleague search and the organogram do not show it. The birthday message does not include the date.
- [x] The inbox is separate from push. Push is not sent. Categories the employee turns off leave the inbox, except company messages, which stay.
- [x] Employees can switch people and milestones on or off. Company messages stay in the inbox. Push remains off for every category.
- [x] Closed-app delivery is not available. The plain website does not deliver a message while the tab is closed.
- [x] Reactions: one reaction per person per message, changeable and removable, from 👍 🎉 👏.

| Action | Owner | Delivery date | Done when |
| --- | --- | --- | --- |
| Agree message types, approval, and preferences | | | Table above is accepted |
| Decide date-of-birth privacy | | | Stored or not, and what colleagues see |
| Build messages and reactions | | | In-app inbox, drafts, and reactions. Push stays out |

## 5. Map of work locations

Optional. Do not start it until priority 1 and 2 have a test date that still holds.

- [x] Feasibility, 8 Nov 2026: a map can only mark fixed work sites, with the people assigned to that site. There is no live location and no home address. A location today is a name and a cluster, with no coordinates, so a marker cannot be placed until a site has a latitude and longitude. The map is not built.
- [ ] Extra cost is written down before it is planned.
- [ ] Include it in the first version only by an explicit decision in section 6.

| Action | Owner | Delivery date | Done when |
| --- | --- | --- | --- |
| Estimate map cost and say if it fits the test date | | | Written estimate and a yes or no |

## 6. Approval, pilot, and planning

- [ ] Confirm the earlier stop decision applied to the AFAS connection only, and that this version continues without that connection.
- [ ] Confirm the ICT, privacy, and security reviews this version still needs, and how it sits next to the central intranet (link from intranet, separate login, or both).
- [x] Working rule, 8 Nov 2026. Sign-in is an existing account. There is no open registration. A signed-in person can search, open the organogram, and read the directory. Date of birth is returned only to someone who can update employees. Export uses the same permission as the employee import or the catalog create for that model. A leaver stays on file. The uploaded file is not stored. The import log keeps the file name, the signed-in user, the outcome, and the counts. No retention period is set.
- [ ] Pilot scope is priority 1 and 2, plus the Kay items marked for the first test. Messages, push, organogram UI, store distribution, and the map stay out unless a dated extra-cost line is accepted first.
- [ ] Name the pilot testers (administrator, deputy, and a small group of employees who will search).
- [ ] Record extra cost before any item outside the pilot scope is scheduled.
- [ ] Set the test date in the table below.

| Action | Owner | Delivery date | Done when |
| --- | --- | --- | --- |
| Confirm AFAS stop scope | | | Written: AFAS link is out; the product continues |
| ICT, privacy, and security review list | | | Open points have an owner |
| Access, publication, and retention rules | | | Rules are updated for this version |
| Pilot scope and testers | | | Named people and an in-scope list |
| Extra-cost gate | | | No extra feature is scheduled without a cost note |
| First test date | | | Date the expertise search and import can be tried |

## First test checklist

Use this on the test date. Everything else can follow.

- [ ] Search `ketel` (and one agreed synonym) and get the right colleagues.
- [ ] Result shows name, role, location, matched expertise, and contact. Team is present only if that field was in scope.
- [ ] Import a sample file, then a change file, without duplicate people or lost employee-owned data.
- [ ] A leaver disappears from search.
- [ ] Abbreviations in results open to the full name on mobile.
- [ ] Required fields match the signed matrix.
- [ ] A user with the administrator role runs the test import.
