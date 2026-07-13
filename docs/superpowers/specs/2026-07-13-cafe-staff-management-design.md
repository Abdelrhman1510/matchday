# Cafe Staff Management — Branch, Role & Permissions (API)

**Date:** 2026-07-13
**Status:** Approved design, ready for implementation planning
**Scope:** matchday-api only. API-only (mobile app). Railway deploy. AWS/tab3s.com untouched.

## 1. Goal

Let a cafe owner add a staff member and, in the same action, choose:

- **Which branch(es)** the staff member works at (one or more)
- The staff member's **role** (`admin` / `manager` / `staff`)
- The staff member's **permissions** (granular)

The owner also sets the staff member's **email and password** directly. The account is
created active immediately; the staff member logs in with those exact credentials. There is
**no email invitation and no accept-invite step** in this flow.

## 2. Context / current state

Staff management already exists in the API and is the surface cafe owners use (there is no
cafe-owner web dashboard). Key existing pieces:

- `StaffMember` model + `staff_members` table (`cafe_id`, `user_id`, `role`,
  `invitation_status`, legacy unused boolean permission columns). Canonical "who is staff at
  this cafe." Unique on `(cafe_id, user_id)`.
- Permissions are stored **globally on the user via Spatie** (`syncPermissions($user, ...)`),
  not per branch.
- `branch_staff` pivot (`branch_id`, `user_id`, `role`, unique `(branch_id, user_id)`) —
  already exists for per-branch scoping but is only used by a **separate** branch-level flow
  (`POST /branches/{branchId}/staff/invite`), not by the main add-staff flow.
- `branch_staff_permissions` table exists but is **not** used by this design.
- `StaffController@store` → `StaffService::inviteStaff()` currently: finds/creates the user
  with a **random** password, creates the `StaffMember` as `pending`, syncs Spatie
  permissions, generates a signed URL, and sends `StaffInvitationNotification`. It does **not**
  accept a branch or a password.
- Subscription staff-limit enforcement (`SubscriptionEnforcementService::canAddStaff`) and the
  `manage-staff` permission gate already wrap `store()`.

**The gap:** the main add-staff flow has no branch selection and uses an email-invitation
onboarding instead of owner-set credentials.

## 3. Chosen approach (Approach 1)

Extend the existing main flow. Reuse `branch_staff` for branch assignments; keep permissions
global via Spatie (which already means "same across all the staff member's branches", matching
the requirement). Do **not** use `branch_staff_permissions`. This also unifies the two parallel
flows: the main add-staff will now populate the same `branch_staff` that `listBranchStaff`
reads.

Rejected alternatives:

- **Per-branch permissions in `branch_staff_permissions`** — duplicates data, contradicts the
  "same permissions across branches" decision, and would require rewriting Spatie-based
  permission checks. YAGNI.
- **`branch_id` on `staff_members` (one row per branch)** — breaks the `(cafe_id, user_id)`
  identity and fragments "one staff member" into many rows.

## 4. API changes

No new routes. Only the existing `/api/v1/cafe-admin/staff` endpoints change (and their
`/admin/*` aliases, which map to the same controller).

### `POST /cafe-admin/staff` (add staff)

Request fields:

| Field | Rules |
|---|---|
| `name` | `required|string|max:255` |
| `email` | `required|email|max:255|unique:users,email` |
| `password` | `required|string|min:8` |
| `role` | `required|in:admin,manager,staff` |
| `permissions` | `nullable|array`; each in the existing permission catalog |
| `branch_ids` | `required|array|min:1`; each `integer`, `exists:branches,id`, **and belongs to the owner's cafe** |

### `PUT /cafe-admin/staff/{id}` (update staff)

- `role` — `sometimes|in:admin,manager,staff` (existing)
- `permissions` — `nullable|array` (existing)
- `branch_ids` — `sometimes|array|min:1`; when present, re-syncs branch assignments (attach
  new, detach removed) and rewrites the pivot `role`
- `password` — `nullable|string|min:8`; when present, owner resets the staff member's password

### `GET /cafe-admin/staff` and `GET /cafe-admin/staff/{id}`

Response gains a `branches` array (see §6).

### `GET /cafe-admin/roles-permissions`

Unchanged — already returns the role list + permission catalog for the picker. The owner's
branch list is fetched from the existing cafe/branches endpoint.

## 5. Data flow

**Add staff** — inside one DB transaction (rewriting `StaffService::inviteStaff`, renamed
conceptually to a direct-create; keep method name to minimise churn unless the plan says
otherwise):

1. Guard: reject if `email` already exists on any user → 422 "email already in use". (The
   promote-an-existing-account path is intentionally dropped for this flow, since we set a
   password.)
2. Create `User` with `Hash::make($data['password'])`, `is_active: true`, role `staff`.
3. Create `StaffMember` (`cafe_id`, `user_id`, `role`, `invited_by`,
   `invitation_status: 'accepted'`).
4. `syncPermissions($user, $data['permissions'] ?? default-for-role)` via Spatie (unchanged
   helper).
5. Upsert one `branch_staff` row per `branch_id` with the chosen `role`
   (`sync`/`syncWithoutDetaching`, idempotent under the unique constraint).
6. **No** signed URL, **no** `StaffInvitationNotification` — nothing is emailed.
7. Return the staff detail resource.

**Update staff:** if `branch_ids` present, sync assignments (attach new / detach removed) and
update pivot `role`; if `password` present, `Hash::make` and update the user; role/permission
updates as today.

**Remove staff (`destroy`):** in addition to the existing removal, detach the user's
`branch_staff` rows for that cafe's branches so no orphan assignments remain.

## 6. Response shape

`StaffResource` gains `branches` (id + name); password is never included.

```json
{
  "id": 12,
  "name": "Sara",
  "email": "sara@example.com",
  "role": "manager",
  "invitation_status": "accepted",
  "permissions": ["manage-bookings", "view-analytics"],
  "branches": [
    { "id": 3, "name": "Downtown" },
    { "id": 5, "name": "Mall" }
  ]
}
```

`StaffService::getStaffDetail()` is extended to load the assigned branches (via the user's
`branch_staff` rows scoped to the cafe's branches).

## 7. Security & validation

- Existing `manage-staff` permission gate and `canAddStaff` subscription-limit check stay in
  place and run before creation.
- **Branch ownership guard:** every `branch_id` must be in `$cafe->branches->pluck('id')`;
  otherwise 422. Prevents attaching staff to another cafe's branch.
- `email` uniqueness enforced at validation; unique DB index remains the backstop.
- `password` is hashed with `Hash::make` and never returned in any response or log.

## 8. Testing

Feature tests in the existing style (`/api/v1/cafe-admin/staff`, authenticated cafe owner):

1. Add staff with multiple `branch_ids` → `staff_members` row created (`invitation_status:
   accepted`) + one `branch_staff` row per branch; response includes `branches`.
2. Duplicate email (already registered) → 422, no rows created.
3. `branch_id` belonging to another cafe → 422, no rows created.
4. Password is hashed (not stored plaintext) and absent from the response body.
5. Update: change `branch_ids` → assignments synced (added/removed correctly); optional
   `password` reset updates the hash.
6. Remove staff → `branch_staff` rows detached.
7. Missing `manage-staff` permission → 403; over subscription staff limit → 403.

## 9. Non-goals

- No cafe-owner web UI (API only).
- No per-branch differing permissions (`branch_staff_permissions` stays unused).
- The separate branch-level invite endpoints (`/branches/{branchId}/staff/*`) are left as-is
  and not removed; they continue to write the same `branch_staff` pivot.
- No changes to the platform-admin dashboard or AWS deployment.
