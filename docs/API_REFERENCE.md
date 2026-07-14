# MatchDay API — Full Endpoint Reference

> Auto-generated from `docs/Matchday_API_Complete.postman_collection.json`. For architecture, environments, auth and testing setup see [SYSTEM_DOCUMENTATION.md](SYSTEM_DOCUMENTATION.md).

**Base URL:** `{{base_url}}` (e.g. `https://web-production-2ef3c.up.railway.app/api/v1`)  
**Total endpoints:** 192  
**Auth:** Bearer token (Laravel Sanctum) unless marked *Public*.

## Contents

- [Health](#health) (1)
- [Authentication](#authentication) (13)
- [Teams](#teams) (4)
- [Cafes](#cafes) (5)
- [Branches](#branches) (4)
- [Matches](#matches) (8)
- [Bookings](#bookings) (11)
- [Payments](#payments) (8)
- [Saved Cafes](#saved-cafes) (3)
- [User](#user) (1)
- [Profile](#profile) (9)
- [Loyalty](#loyalty) (3)
- [Achievements](#achievements) (2)
- [Chat](#chat) (7)
- [Notifications](#notifications) (6)
- [Home & Explore](#home---explore) (4)
- [Search](#search) (1)
- [Offers](#offers) (4)
- [Cafe Admin](#cafe-admin) (85)
- [Rebook & Fan Room](#rebook---fan-room) (2)
- [Support](#support) (3)
- [FAQs](#faqs) (1)
- [Legal Pages](#legal-pages) (5)
- [App Config](#app-config) (2)

---

## Health

### `GET` Health Check

```
GET {{base_url}}/health
```

**Auth:** Public (no token)

---

## Authentication

### `POST` Register User

```
POST {{base_url}}/auth/register
```

**Auth:** Public (no token)

**Body:**

```json
{
  "name": "John Doe",
  "email": "john.doe@example.com",
  "phone": "1234567890",
  "password": "password123",
  "password_confirmation": "password123",
  "date_of_birth": "1995-05-15",
  "gender": "male"
}
```

**UPDATED:** Phone validation stricter

- Phone must be minimum 10 digits
- Valid: `1234567890`, `+1234567890`, `123-456-7890`
- Invalid: `123456` (too short)

---

### `POST` Register Cafe Owner

```
POST {{base_url}}/auth/register/cafe-owner
```

**Auth:** Public (no token)

**Body:**

```json
{
  "name": "Cafe Owner",
  "email": "owner@matchday.app",
  "phone": "+966507654321",
  "password": "OwnerPass123!",
  "password_confirmation": "OwnerPass123!",
  "cafe_name": "Sports View Cafe",
  "business_license": "BL-2026-12345"
}
```

---

### `POST` Login

```
POST {{base_url}}/auth/login
```

**Auth:** Public (no token)

**Body:**

```json
{
  "email_or_phone": "ahmed@matchday.app",
  "password": "password"
}
```

---

### `POST` Login with Google

```
POST {{base_url}}/auth/login/google
```

**Auth:** Public (no token)

**Body:**

```json
{
  "token": "google_oauth_token_here",
  "device_name": "iPhone 13"
}
```

---

### `POST` Login with Apple

```
POST {{base_url}}/auth/login/apple
```

**Auth:** Public (no token)

**Body:**

```json
{
  "token": "apple_oauth_token_here",
  "device_name": "iPhone 13"
}
```

---

### `POST` Forgot Password

```
POST {{base_url}}/auth/forgot-password
```

**Auth:** Public (no token)

**Body:**

```json
{
  "email": "ahmed@matchday.app"
}
```

---

### `POST` Reset Password

```
POST {{base_url}}/auth/reset-password
```

**Auth:** Public (no token)

**Body:**

```json
{
  "email": "ahmed@matchday.app",
  "otp": "123456",
  "password": "newpass12",
  "password_confirmation": "newpass12"
}
```

**UPDATED:** Password complexity removed

- Now only requires minimum 8 characters
- No uppercase/lowercase/numbers/symbols required
- Simpler passwords accepted: `simplepass`, `password123`, etc.

---

### `GET` [DEBUG] Get OTP from Cache

```
GET {{base_url}}/debug/otp/{{email}}
```

**Auth:** Public (no token)

TEMP DEBUG endpoint â€” returns the current OTP stored in cache for the given email.

Use after calling Forgot Password to retrieve the OTP without needing real email delivery.

**Remove before going to production.**

---

### `GET` Get Current User

```
GET {{base_url}}/auth/me
```

**Auth:** Bearer token

---

### `POST` Refresh Token

```
POST {{base_url}}/auth/refresh
```

**Auth:** Bearer token

---

### `POST` Verify Email

```
POST {{base_url}}/auth/verify-email
```

**Auth:** Bearer token

**Body:**

```json
{
  "otp": "123456"
}
```

---

### `POST` Resend Verification OTP

```
POST {{base_url}}/auth/resend-verification
```

**Auth:** Bearer token

---

### `POST` Logout

```
POST {{base_url}}/auth/logout
```

**Auth:** Bearer token

---

## Teams

### `GET` List All Teams

```
GET {{base_url}}/teams?per_page=20&page=1
```

**Auth:** Public (no token)

**Query params:**
- `per_page=20`
- `page=1`

---

### `GET` Popular Teams

```
GET {{base_url}}/teams/popular?limit=10
```

**Auth:** Public (no token)

**Query params:**
- `limit=10`

---

### `GET` Search Teams

```
GET {{base_url}}/teams/search?q=arsenal
```

**Auth:** Public (no token)

**Query params:**
- `q=arsenal`

---

### `GET` Get Team Details

```
GET {{base_url}}/teams/1
```

**Auth:** Public (no token)

---

## Cafes

### `GET` List All Cafes

```
GET {{base_url}}/cafes?per_page=15&page=1&city=Riyadh
```

**Auth:** Public (no token)

**Query params:**
- `per_page=15`
- `page=1`
- `city=Riyadh`

---

### `GET` Search Cafes

```
GET {{base_url}}/cafes/search?q=sports&city=Riyadh
```

**Auth:** Public (no token)

**Query params:**
- `q=sports`
- `city=Riyadh`

---

### `GET` Nearby Cafes

```
GET {{base_url}}/cafes/nearby?lat=24.7136&lng=46.6753&radius=5
```

**Auth:** Bearer token

**Query params:**
- `lat=24.7136` — Latitude (required, -90 to 90)
- `lng=46.6753` — Longitude (required, -180 to 180)
- `radius=5` — Search radius in km (optional, default: 10, max: 100)

---

### `GET` Get Cafe Details

```
GET {{base_url}}/cafes/1
```

**Auth:** Public (no token)

---

### `GET` Get Cafe Branches

```
GET {{base_url}}/cafes/1/branches
```

**Auth:** Public (no token)

---

## Branches

### `GET` Get Branch Details

```
GET {{base_url}}/branches/1
```

**Auth:** Public (no token)

---

### `GET` Get Branch Matches

```
GET {{base_url}}/branches/1/matches?date=2026-02-15
```

**Auth:** Public (no token)

**Query params:**
- `date=2026-02-15`

---

### `GET` Get Branch Reviews

```
GET {{base_url}}/branches/1/reviews?per_page=10
```

**Auth:** Public (no token)

**Query params:**
- `per_page=10`

---

### `POST` Create Branch Review

```
POST {{base_url}}/branches/1/reviews
```

**Auth:** Bearer token

**Body:**

```json
{
  "rating": 5,
  "comment": "Amazing atmosphere! Great screens and comfortable seating. Staff was very friendly.",
  "booking_id": 1
}
```

---

## Matches

### `GET` List All Matches

```
GET {{base_url}}/matches?per_page=20&status=upcoming&league=Premier League
```

**Auth:** Public (no token)

**Query params:**
- `per_page=20`
- `status=upcoming`
- `league=Premier League`

---

### `GET` Live Matches

```
GET {{base_url}}/matches/live
```

**Auth:** Public (no token)

---

### `GET` Upcoming Matches

```
GET {{base_url}}/matches/upcoming?days=7
```

**Auth:** Public (no token)

**Query params:**
- `days=7`

---

### `GET` Popular Matches

```
GET {{base_url}}/matches/popular?limit=10
```

**Auth:** Public (no token)

**Query params:**
- `limit=10`

---

### `GET` Get Match Details

```
GET {{base_url}}/matches/1
```

**Auth:** Public (no token)

---

### `GET` Get Match Seating

```
GET {{base_url}}/matches/1/seating?branch_id=1
```

**Auth:** Public (no token)

**Query params:**
- `branch_id=1`

---

### `POST` Toggle Save Match

```
POST {{base_url}}/matches/1/save
```

**Auth:** Bearer token

Toggle save/unsave a match for the authenticated user.

**Behavior:**
- If match is NOT saved â†’ saves it (201)
- If match IS saved â†’ unsaves it (200)

**Response:** { is_saved: true/false }

**Auth:** Required

---

### `GET` List Saved Matches

```
GET {{base_url}}/matches/saved
```

**Auth:** Bearer token

List all saved/bookmarked matches for the authenticated user.

**Response:** Array of matches with teams, branch, and cafe info.

**Notes:**
- Only published matches are returned
- Ordered by match_date descending
- Each match includes is_saved: true flag

**Auth:** Required

---

## Bookings

### `POST` Create Booking

```
POST {{base_url}}/bookings
```

**Auth:** Bearer token

**Body:**

```json
{
  "match_id": 1,
  "seat_ids": [
    1,
    2
  ],
  "guests_count": 2,
  "special_requests": "Window seat preferred"
}
```

---

### `GET` List My Bookings

```
GET {{base_url}}/bookings?per_page=10&status=confirmed
```

**Auth:** Bearer token

**Query params:**
- `per_page=10`
- `status=confirmed`

---

### `GET` Get Booking Details

```
GET {{base_url}}/bookings/{{booking_id}}
```

**Auth:** Bearer token

---

### `PUT` Update Booking

```
PUT {{base_url}}/bookings/{{booking_id}}
```

**Auth:** Bearer token

**Body:**

```json
{
  "special_requests": "Changed to VIP section",
  "guests_count": 3
}
```

---

### `PUT` Cancel Booking (PUT)

```
PUT {{base_url}}/bookings/{{booking_id}}/cancel
```

**Auth:** Bearer token

**Body:**

```json
{
  "reason": "Change of plans"
}
```

PUT method for cancelling bookings (POST still works too).

**Changes:**
- Returns 422 for already cancelled (was 400)
- Returns 403 for other user's booking (was 404)

---

### `GET` Get Booking Pass

```
GET {{base_url}}/bookings/{{booking_id}}/pass
```

**Auth:** Bearer token

---

### `POST` Share Booking

```
POST {{base_url}}/bookings/{{booking_id}}/share
```

**Auth:** Bearer token

**Body:**

```json
{
  "method": "email",
  "recipient": "friend@example.com"
}
```

---

### `POST` Add to Calendar

```
POST {{base_url}}/bookings/{{booking_id}}/add-to-calendar
```

**Auth:** Bearer token

**Body:**

```json
{
  "calendar_type": "google"
}
```

---

### `GET` List Booking Players

```
GET {{base_url}}/bookings/{{booking_id}}/players
```

**Auth:** Bearer token

---

### `POST` Add Booking Player

```
POST {{base_url}}/bookings/{{booking_id}}/players
```

**Auth:** Bearer token

**Body:**

```json
{
  "name": "Cristiano Ronaldo",
  "position": "Forward",
  "jersey_number": 7
}
```

**NEW FIELD: jersey_number**

Add a player to a booking with optional jersey number.

Request:
```json
{
    "name": "Player Name",
    "position": "Forward",
    "jersey_number": 10
}
```

Response includes jersey_number in the player data.

---

### `DELETE` Remove Booking Player

```
DELETE {{base_url}}/bookings/{{booking_id}}/players/1
```

**Auth:** Bearer token

---

## Payments

### Payment Methods

#### `GET` List Payment Methods

```
GET {{base_url}}/payment-methods
```

**Auth:** Bearer token

---

#### `POST` Add Payment Method

```
POST {{base_url}}/payment-methods
```

**Auth:** Bearer token

**Body:**

```json
{
  "type": "credit_card",
  "card_last_four": "1111",
  "card_holder": "Ahmed Hassan",
  "expires_at": "2028-12",
  "is_primary": true
}
```

---

#### `PUT` Update Payment Method

```
PUT {{base_url}}/payment-methods/{{payment_method_id}}
```

**Auth:** Bearer token

**Body:**

```json
{
  "expiry_month": "06",
  "expiry_year": "2029"
}
```

---

#### `PUT` Set Primary Payment Method

```
PUT {{base_url}}/payment-methods/{{payment_method_id}}/set-primary
```

**Auth:** Bearer token

---

#### `DELETE` Delete Payment Method

```
DELETE {{base_url}}/payment-methods/{{payment_method_id}}
```

**Auth:** Bearer token

---

### `POST` Process Payment

```
POST {{base_url}}/payments/process
```

**Auth:** Bearer token

**Body:**

```json
{
  "booking_id": 1,
  "payment_method_id": 1,
  "amount": 150.0,
  "currency": "SAR"
}
```

---

### `GET` Payment History

```
GET {{base_url}}/payments/history?per_page=10&status=completed
```

**Auth:** Bearer token

**Query params:**
- `per_page=10`
- `status=completed`

---

### `POST` Refund Payment

```
POST {{base_url}}/payments/1/refund
```

**Auth:** Bearer token

**Body:**

```json
{
  "reason": "Booking cancelled by customer",
  "amount": 150.0
}
```

---

## Saved Cafes

### `GET` List Saved Cafes

```
GET {{base_url}}/saved-cafes
```

**Auth:** Bearer token

---

### `POST` Save Cafe

```
POST {{base_url}}/saved-cafes/1
```

**Auth:** Bearer token

---

### `DELETE` Unsave Cafe

```
DELETE {{base_url}}/saved-cafes/1
```

**Auth:** Bearer token

---

## User

### `GET` Get Authenticated User

```
GET {{base_url}}/user
```

**Auth:** Bearer token

---

## Profile

### `GET` Get Profile

```
GET {{base_url}}/profile
```

**Auth:** Bearer token

---

### `PUT` Update Profile

```
PUT {{base_url}}/profile
```

**Auth:** Bearer token

**Body:**

```json
{
  "name": "Ahmed Hassan Updated",
  "phone": "+966501234567",
  "date_of_birth": "1990-01-15",
  "gender": "male",
  "city": "Riyadh"
}
```

---

### `POST` Update Avatar

```
POST {{base_url}}/profile/avatar
```

**Auth:** Bearer token

**Body (form-data):**

- `avatar` (file)

---

### `PUT` Update Password

```
PUT {{base_url}}/profile/password
```

**Auth:** Bearer token

**Body:**

```json
{
  "current_password": "password",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!"
}
```

---

### `PUT` Update Locale

```
PUT {{base_url}}/profile/locale
```

**Auth:** Bearer token

**Body:**

```json
{
  "locale": "ar"
}
```

---

### `PUT` Update Device Token

```
PUT {{base_url}}/profile/device-token
```

**Auth:** Bearer token

**Body:**

```json
{
  "device_token": "fcm_token_abc123xyz",
  "device_type": "ios"
}
```

---

### `PUT` Update Favorite Team

```
PUT {{base_url}}/profile/favorite-team
```

**Auth:** Bearer token

**Body:**

```json
{
  "favorite_team_id": 1
}
```

---

### `GET` Get Activity Feed

```
GET {{base_url}}/profile/activity
```

**Auth:** Bearer token

---

### `DELETE` Delete Account

```
DELETE {{base_url}}/profile
```

**Auth:** Bearer token

---

## Loyalty

### `GET` Get Loyalty Tiers (Public)

```
GET {{base_url}}/loyalty/tiers
```

**Auth:** Public (no token)

---

### `GET` Get Loyalty Card

```
GET {{base_url}}/loyalty/card
```

**Auth:** Bearer token

---

### `GET` Get Loyalty Transactions

```
GET {{base_url}}/loyalty/transactions?per_page=10&type=earned
```

**Auth:** Bearer token

**Query params:**
- `per_page=10`
- `type=earned` — Filter by type: earned or redeemed

---

## Achievements

### `GET` Get All Achievements

```
GET {{base_url}}/achievements
```

**Auth:** Bearer token

---

### `GET` Get My Achievements

```
GET {{base_url}}/achievements/my
```

**Auth:** Bearer token

---

## Chat

### `GET` Get Public Chat Room

```
GET {{base_url}}/chat/rooms/:matchId
```

**Auth:** Bearer token

**Path variables:**
- `:matchId` — ID of the match

Get or create a public chat room for a match. Any authenticated user can access.

---

### `GET` Get Cafe Chat Room

```
GET {{base_url}}/chat/rooms/:matchId/branch/:branchId
```

**Auth:** Bearer token

**Path variables:**
- `:matchId` — ID of the match
- `:branchId` — ID of the branch

Get or create a cafe-specific chat room. Requires a confirmed booking at that branch for this match.

---

### `POST` Send Message

```
POST {{base_url}}/chat/rooms/:roomId/messages
```

**Auth:** Bearer token

**Path variables:**
- `:roomId` — ID of the chat room

**Body:**

```json
{
  "message": "Great goal! What a match!",
  "type": "text"
}
```

Send a message to a chat room. Rate limited to 10 messages per minute. Type can be 'text' or 'emoji'. Max 500 characters.

---

### `GET` Get Messages

```
GET {{base_url}}/chat/rooms/:roomId/messages?page=1
```

**Auth:** Bearer token

**Path variables:**
- `:roomId` — ID of the chat room

**Query params:**
- `page=1` — Page number (15 messages per page)

Get paginated messages from a chat room. Returns 15 messages per page.

---

### `POST` Send Reaction

```
POST {{base_url}}/chat/rooms/:roomId/reaction
```

**Auth:** Bearer token

**Path variables:**
- `:roomId` — ID of the chat room

**Body:**

```json
{
  "emoji": "goal"
}
```

Send a quick reaction emoji. Valid emojis: goal, fire, heart, laugh, clap

---

### `GET` Get Viewers Count

```
GET {{base_url}}/chat/rooms/:roomId/viewers
```

**Auth:** Bearer token

**Path variables:**
- `:roomId` — ID of the chat room

Get the current number of viewers in the chat room.

---

### `GET` Get Online Users

```
GET {{base_url}}/chat/rooms/:roomId/online-users
```

**Auth:** Bearer token

**Path variables:**
- `:roomId` — ID of the chat room

Get list of users currently online in the chat room (requires WebSocket connection).

---

## Notifications

### `GET` Get All Notifications

```
GET {{base_url}}/notifications?page=1
```

**Auth:** Bearer token

**Query params:**
- `page=1` — Page number (20 notifications per page)

Get paginated notifications for the authenticated user. Returns 20 notifications per page ordered by newest first.

---

### `GET` Get Unread Count

```
GET {{base_url}}/notifications/unread-count
```

**Auth:** Bearer token

Get the count of unread notifications for the authenticated user.

---

### `PUT` Mark Notification as Read

```
PUT {{base_url}}/notifications/:id/read
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Notification UUID - Get this from the 'Get Notifications' endpoint first

Mark a specific notification as read. NOTE: First run 'Get Notifications' to get a valid notification ID from your account.

---

### `PUT` Mark All as Read

```
PUT {{base_url}}/notifications/read-all
```

**Auth:** Bearer token

Mark all notifications as read for the authenticated user.

---

### `DELETE` Delete Notification

```
DELETE {{base_url}}/notifications/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Notification UUID - Get this from the 'Get Notifications' endpoint first

Delete a specific notification. NOTE: First run 'Get Notifications' to get a valid notification ID from your account.

---

### `PUT` Update Notification Settings

```
PUT {{base_url}}/notifications/settings
```

**Auth:** Bearer token

**Body:**

```json
{
  "booking_reminders": true,
  "match_updates": true,
  "promotions": true,
  "chat_messages": false
}
```

Update notification preferences for the authenticated user.

**Settings:**
- booking_reminders: Booking confirmations, cancellations
- match_updates: Match reminders, score updates
- promotions: Achievements, points earned
- chat_messages: Chat notifications

Note: Welcome and staff invitation notifications are always sent regardless of settings.

---

## Home & Explore

### `GET` Get Home Feed

```
GET {{base_url}}/home
```

**Auth:** Bearer token

Get personalized home feed for the authenticated user.

**Returns:**
- User summary (name, tier, unread notifications)
- Upcoming booking details
- Featured cafes
- Live matches
- Upcoming matches
- Popular matches this week

**Cache:** 5 minutes per user

---

### `GET` Get Home Feed (with Location)

```
GET {{base_url}}/home?lat=24.7136&lng=46.6753
```

**Auth:** Bearer token

**Query params:**
- `lat=24.7136` — Latitude (-90 to 90)
- `lng=46.6753` — Longitude (-180 to 180)

Get home feed with nearby cafes based on user's location.

**Parameters:**
- lat: User latitude (required, -90 to 90)
- lng: User longitude (required, -180 to 180)

**Returns:** Same as home feed + nearby cafes within 20km with distance_km field

---

### `GET` Get Explore Data

```
GET {{base_url}}/explore
```

**Auth:** Bearer token

Get aggregated explore page data (public endpoint - enhanced if authenticated).

**Returns:**
- Featured cafes (2)
- Trending cafes (3)
- Matches today (all)
- Popular matches (3)
- Active offers (5)

**Notes:**
- Public access: Basic data
- Authenticated: Includes is_saved flags

**Cache:** 5 minutes

---

### `GET` Get Explore (with Location)

```
GET {{base_url}}/explore?lat=24.7136&lng=46.6753
```

**Auth:** Bearer token

**Query params:**
- `lat=24.7136` — Latitude (-90 to 90)
- `lng=46.6753` — Longitude (-180 to 180)

Get explore data with nearby cafes based on location.

**Parameters:**
- lat: User latitude (required if lng provided)
- lng: User longitude (required if lat provided)

**Returns:** Same as explore + nearby cafes within 20km with:
- distance_km
- is_saved (if authenticated)
- is_open_now

---

## Search

### `GET` Global Search

```
GET {{base_url}}/search?q=barcelona
```

**Auth:** Public (no token)

**Query params:**
- `q=barcelona` — Search query (min 2 characters)

Global search across cafes, matches, and cities.

**Searches:**
- Cafes: name, branch names
- Matches: team names, league
- Cities: distinct cities from branches

**Returns:** Max 5 results per category

**Requirements:**
- Query must be at least 2 characters
- Case-insensitive
- Partial matches supported

---

## Offers

### `GET` Get All Offers

```
GET {{base_url}}/offers?page=1
```

**Auth:** Public (no token)

**Query params:**
- `page=1` — Page number

Get all active offers with pagination.

**Response includes:**
- title, description, discount_percentage
- valid_from, valid_until
- cafe_name, cafe_id
- UI helpers: is_expiring_soon, days_remaining, is_active

**Default:** 10 offers per page

---

### `GET` Get Featured Offers

```
GET {{base_url}}/offers?featured=1
```

**Auth:** Public (no token)

**Query params:**
- `featured=1` — Filter featured offers only

Get only featured offers (is_featured = true).

---

### `GET` Get Offers by Cafe

```
GET {{base_url}}/offers?cafe_id=1
```

**Auth:** Public (no token)

**Query params:**
- `cafe_id=1` — Filter by specific cafe

Get offers for a specific cafe.

---

### `GET` Get Offer Details

```
GET {{base_url}}/offers/1
```

**Auth:** Public (no token)

**Path variables:**
- `:id` — Offer ID

Get detailed information about a specific offer.

**Returns:**
- Full offer details
- cafe_details object (id, name, logo)
- UI helpers: is_expiring_soon, days_remaining, is_active

**Errors:**
- 404: Offer not found
- 410 Gone: Offer expired

---

## Cafe Admin

Cafe owner management endpoints. All require 'manage-cafe-profile' permission.

**Features:**
- 20 endpoints for complete cafe management
- Multi-step onboarding flow (cafe â†’ branch â†’ staff)
- Multi-step branch creation (basic â†’ hours â†’ amenities)
- Multi-size image uploads
- Cached branch overview (5 min)
- Session-based current branch switching
- Ownership verification on all operations

### Onboarding & Status

#### `GET` Get Onboarding Status

```
GET {{base_url}}/cafe-admin/onboarding-status
```

**Auth:** Bearer token

Get current onboarding progress.

**Returns:**
- step: 1, 2, 3, or 'complete'
- cafe_created: boolean
- has_branches: boolean
- has_staff: boolean

**Steps:**
1. Create cafe profile
2. Add first branch
3. Add staff members

---

### Cafe Management

#### `POST` Create Cafe

```
POST {{base_url}}/cafe-admin/cafe
```

**Auth:** Bearer token

**Body:**

```json
{
  "name": "Downtown Sports Cafe",
  "description": "Best sports viewing experience in the city with premium screens and atmosphere",
  "phone": "+966501234567",
  "city": "Riyadh"
}
```

Create cafe profile (Step 1 of onboarding).

**Required:**
- name: string (max 255)
- description: string (max 1000)
- phone: string
- city: string

**Restrictions:**
- User can only own one cafe

**Returns:**
- Cafe with UI flags (can_add_branch, status_badge, etc.)

---

#### `PUT` Update Cafe

```
PUT {{base_url}}/cafe-admin/cafe
```

**Auth:** Bearer token

**Body:**

```json
{
  "name": "Downtown Sports Cafe & Lounge",
  "description": "Updated: Premier sports viewing venue with state-of-the-art facilities",
  "phone": "+966501234567",
  "city": "Riyadh"
}
```

Update cafe profile.

**Optional fields:**
- name, description, phone, city

**Returns:**
- Updated cafe with UI flags

---

#### `POST` Upload Cafe Logo

```
POST {{base_url}}/cafe-admin/cafe/logo
```

**Auth:** Bearer token

**Body (form-data):**

- `logo` (file)

Upload cafe logo (multi-size generation).

**Sizes Generated:**
- original: 300x300
- medium: auto-sized
- thumbnail: 150x150

**Storage:**
- Saved as JSON array in database

**Returns:**
- Cafe with logo URLs array

---

#### `GET` Get My Cafe

```
GET {{base_url}}/cafe-admin/cafe
```

**Auth:** Bearer token

Get owned cafe profile.

**Returns:**
- Full cafe details
- branches_count
- UI flags (can_add_branch, status_badge, is_premium_active)

**Errors:**
- 404: No cafe found for this owner

---

### Branch Management

#### `GET` List Branches

```
GET {{base_url}}/cafe-admin/branches
```

**Auth:** Bearer token

List all branches for owned cafe.

**Returns:**
- Array of branches
- active_bookings_count per branch
- Average rating
- UI flags (status_badge, can_delete, is_current)

---

#### `POST` Create Branch (Step 1: Basic Info)

```
POST {{base_url}}/cafe-admin/branches
```

**Auth:** Bearer token

**Body:**

```json
{
  "name": "Downtown Branch",
  "address": "King Fahd Road, Al Olaya District, Riyadh 12214, Saudi Arabia",
  "latitude": 24.7136,
  "longitude": 46.6753,
  "total_seats": 50
}
```

Create new branch (Step 1 of multi-step flow).

**Required:**
- name: string
- address: string
- latitude: float (-90 to 90)
- longitude: float (-180 to 180)
- total_seats: integer (min 1)

**Next Steps:**
2. Configure operating hours
3. Add amenities

**Returns:**
- Branch details with setup_complete: false

---

#### `PUT` Update Branch Hours (Step 2)

```
PUT {{base_url}}/cafe-admin/branches/:id/hours
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "hours": [
    {
      "day_of_week": "Monday",
      "open_time": "09:00",
      "close_time": "22:00",
      "is_closed": false
    },
    {
      "day_of_week": "Tuesday",
      "open_time": "09:00",
      "close_time": "22:00",
      "is_closed": false
    },
    {
      "day_of_week": "Wednesday",
      "open_time": "09:00",
      "close_time": "22:00",
      "is_closed": false
    },
    {
      "day_of_week": "Thursday",
      "open_time": "09:00",
      "close_time": "22:00",
      "is_closed": false
    },
    {
      "day_of_week": "Friday",
      "open_time": "09:00",
      "close_time": "23:00",
      "is_closed": false
    },
    {
      "day_of_week": "Saturday",
      "open_time": "09:00",
      "close_time": "23:00",
      "is_closed": false
    },
    {
      "day_of_week": "Sunday",
      "open_time": "10:00",
      "close_time": "23:00",
      "is_closed": false
    }
  ]
}
```

Set branch operating hours (Step 2).

**UPDATED:**
- `day_of_week` is now STRING (was integer)
- Can send 1-7 days (was required to send all 7)
- Field names changed: `open_time`/`close_time` (was `opens_at`/`closes_at`)
- New `is_closed` boolean field

**Required:**
- hours: array of 1-7 objects
- day_of_week: String (Monday, Tuesday, etc.)
- open_time: HH:MM format
- close_time: HH:MM format
- is_closed: boolean (optional)

**Returns:**
- Branch with updated hours

---

#### `POST` Add Amenities Bulk (Step 3)

```
POST {{base_url}}/cafe-admin/branches/:id/amenities/bulk
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "amenities": [
    {
      "name": "WiFi",
      "icon": "wifi"
    },
    {
      "name": "Parking",
      "icon": "parking"
    },
    {
      "name": "Big Screen",
      "icon": "tv"
    },
    {
      "name": "Air Conditioning",
      "icon": "ac"
    },
    {
      "name": "Smoking Area",
      "icon": "smoking"
    }
  ]
}
```

Add multiple amenities (Step 3 - completes branch setup).

**Format 1 - By IDs (NEW - returns 200):**
```json
{
    "amenity_ids": [1, 2, 3]
}
```

**Format 2 - Create new (still works - returns 201):**
```json
{
    "amenities": [
        {"name": "WiFi", "icon": "wifi"}
    ]
}
```

Both formats supported!

**Common Icons:**
- wifi, parking, tv, ac, smoking, outdoor, vip, food, drinks

**Returns:**
- Branch with all amenities
- setup_complete: true (if hours also configured)

---

#### `GET` Get Branch Details

```
GET {{base_url}}/cafe-admin/branches/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

Get detailed branch information.

**Returns:**
- Full branch details
- Operating hours (7 days)
- Amenities list
- Setup progress flags
- UI helpers (can_open, setup_complete)

---

#### `PUT` Update Branch

```
PUT {{base_url}}/cafe-admin/branches/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "name": "Downtown Branch - Updated",
  "address": "New Address, Riyadh",
  "latitude": 24.714,
  "longitude": 46.676,
  "total_seats": 60
}
```

Update branch information.

**Optional fields:**
- name, address, latitude, longitude, total_seats

**Returns:**
- Updated branch details

---

#### `DELETE` Delete Branch

```
DELETE {{base_url}}/cafe-admin/branches/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID (not the last branch)

Delete a branch.

**Restrictions:**
- Cannot delete the last remaining branch
- Must have at least one active branch

**Errors:**
- 400: Cannot delete last branch
- 404: Branch not found or not owned

---

#### `PUT` Toggle Branch Status

```
PUT {{base_url}}/cafe-admin/branches/:id/status
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "is_open": false
}
```

Open or close a branch.

**Required:**
- is_open: boolean

**Use Cases:**
- Temporarily close for maintenance
- Open after preparation
- Seasonal operations

**Returns:**
- Branch with updated status

---

#### `GET` Get Branch Setup Progress

```
GET {{base_url}}/cafe-admin/branches/:id/setup-progress
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

Check branch setup completion.

**Returns:**
- basic_info: boolean (always true after creation)
- hours_complete: boolean (7 days configured)
- amenities_added: boolean (at least 1 amenity)
- completed_steps: integer (0-3)
- total_steps: 3
- percentage: float (0-100)

**Use for:**
- Progress indicators
- Onboarding checklist
- Setup validation

---

### Branch Overview

#### `GET` Get Branch Overview (Cached)

```
GET {{base_url}}/cafe-admin/branches/:id/overview
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

Get comprehensive branch overview in single call.

**Cached for 5 minutes** for optimal performance.

**Returns:**
- branch: Full branch details with hours & amenities
- stats:
  - today_bookings: integer
  - today_matches: integer
  - total_seats: integer
  - occupied_seats: integer
  - occupancy_percentage: float
  - rating: float (0-5)
  - total_reviews: integer
- setup_progress: object with completion flags
- upcoming_matches: array (next 3 matches)

**Performance:**
- First call: ~200-300ms
- Cached calls: <50ms
- Cache invalidation: Auto after 5 minutes

---

### Current Branch

#### `GET` Get Current Branch

```
GET {{base_url}}/cafe-admin/current-branch
```

**Auth:** Bearer token

Returns the currently active branch for this cafe.

**Returns:**
- Branch full detail (BranchDetailResource)
- 404 if no current branch has been set yet

**Use after:** PUT /cafe-admin/current-branch to verify the switch succeeded.

---

#### `PUT` Switch Current Branch

```
PUT {{base_url}}/cafe-admin/current-branch
```

**Auth:** Bearer token

**Body:**

```json
{
  "branch_id": 1
}
```

Switch the current active branch context.

**Required:**
- branch_id: integer (must be owned by user's cafe)

**Purpose:**
- Sets session: current_branch_id
- Used for UI context switching
- Highlights current branch in lists
- Default context for operations

**Returns:**
- Success message
- Updated session

---

### Branch Amenities

#### `GET` List Branch Amenities

```
GET {{base_url}}/cafe-admin/branches/:id/amenities
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

Get all amenities for a specific branch.

**Returns:**
- Array of amenities
- Each with: id, name, icon

**Common Amenities:**
- WiFi, Parking, Big Screen TV
- Air Conditioning, Smoking Area
- Outdoor Seating, VIP Rooms
- Food Service, Beverage Bar

---

#### `POST` Add Branch Amenity

```
POST {{base_url}}/cafe-admin/branches/:id/amenities
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "name": "Game Consoles",
  "icon": "gamepad"
}
```

Add a single amenity to branch.

**Required:**
- name: string (unique for branch)
- icon: string

**Icon Suggestions:**
- wifi, parking, tv, ac, smoking
- outdoor, vip, food, drinks, gamepad
- wheelchair, kids, music, stage

**Returns:**
- Created amenity with ID

---

#### `DELETE` Remove Branch Amenity

```
DELETE {{base_url}}/cafe-admin/amenities/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Amenity ID (not branch ID)

Remove an amenity from branch.

**Required:**
- id: amenity ID (from list or add response)

**Ownership:**
- Verifies amenity belongs to user's cafe branch

**Returns:**
- Success message

**Errors:**
- 404: Amenity not found or not owned

---

### Seating Management

#### `GET` List Branch Sections

```
GET {{base_url}}/cafe-admin/branches/:id/sections
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

List all seating sections for a branch with occupied/available seat counts and active booking info.

**Response includes:**
- Section details with type, extra cost, screen size
- Seat counts (total, available, occupied)
- Active bookings count
- UI flags: can_delete, has_active_bookings, is_full
- Summary totals

---

#### `POST` Create Section

```
POST {{base_url}}/cafe-admin/branches/:id/sections
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "name": "VIP Lounge",
  "type": "vip",
  "total_seats": 10,
  "extra_cost": 50.0,
  "icon": "crown",
  "screen_size": "65 inch"
}
```

Create a new seating section with auto-generated seat labels.

**Type options:** main_screen, vip, premium, standard
**Auto-label prefixes:** M (main_screen), V (vip), P (premium), S (standard)

Example: Creating VIP section with 10 seats generates labels V1, V2, ... V10

---

#### `PUT` Update Section

```
PUT {{base_url}}/cafe-admin/sections/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Section ID

**Body:**

```json
{
  "name": "VIP Premium Lounge",
  "extra_cost": 75.0,
  "screen_size": "75 inch"
}
```

Update a seating section. All fields are optional.

If total_seats is increased, new seats are auto-generated.
If type is changed, seat labels are re-prefixed automatically.

---

#### `DELETE` Delete Section

```
DELETE {{base_url}}/cafe-admin/sections/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Section ID

Delete a seating section and all its seats.

**Blocked if active bookings exist** (returns 409 Conflict).
Only sections with no confirmed/pending bookings can be deleted.

---

#### `GET` List Section Seats

```
GET {{base_url}}/cafe-admin/sections/:id/seats
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Section ID

List all seats in a section with status and booking info.

**Seat statuses:** available, unavailable, booked
**UI flags:** can_delete, has_active_booking

Includes summary with total/available/occupied/booked counts.

---

#### `POST` Bulk Add Seats

```
POST {{base_url}}/cafe-admin/sections/:id/seats
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Section ID

**Body:**

```json
{
  "count": 5,
  "prefix": "V",
  "start_from": 11
}
```

Bulk add seats to an existing section.

**Parameters:**
- count (required): Number of seats to add (1-200)
- prefix (optional): Custom label prefix (defaults to type prefix)
- start_from (optional): Starting number (defaults to next available)
- table_number (optional): Assign table number to all new seats

---

#### `PUT` Update Seat

```
PUT {{base_url}}/cafe-admin/seats/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Seat ID

**Body:**

```json
{
  "is_available": false,
  "table_number": "T3"
}
```

Update a single seat's properties.

**Updatable fields:**
- label: Seat label (max 10 chars)
- table_number: Table assignment
- is_available: Toggle availability

---

#### `DELETE` Delete Seat

```
DELETE {{base_url}}/cafe-admin/seats/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Seat ID

Delete a single seat.

**Blocked if seat has an active booking** (returns 409 Conflict).
Section total_seats is automatically decremented.

---

#### `POST` Bulk Create Sections (Branch Setup)

```
POST {{base_url}}/cafe-admin/branches/:id/sections/bulk
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Branch ID

**Body:**

```json
{
  "sections": [
    {
      "name": "Main Screen Area",
      "type": "main_screen",
      "total_seats": 30,
      "extra_cost": 0,
      "icon": "tv",
      "screen_size": "85 inch"
    },
    {
      "name": "VIP Section",
      "type": "vip",
      "total_seats": 10,
      "extra_cost": 100,
      "icon": "crown",
      "screen_size": "55 inch"
    },
    {
      "name": "Standard Area",
      "type": "standard",
      "total_seats": 20,
      "extra_cost": 0,
      "icon": "chair"
    }
  ]
}
```

Bulk create multiple sections with auto-generated seats in one request.

**Designed for Branch Setup Step 4.**
Each section auto-generates seats with type-based prefixes.

Max 20 sections per request, max 500 seats per section.

---

### Match Management

#### `GET` List Matches

```
GET {{base_url}}/cafe-admin/matches?status=upcoming
```

**Auth:** Bearer token

**Query params:**
- `status=upcoming` — Filter: upcoming, live, finished, cancelled

List all matches for your cafe's branches.

**Filters:** status, branch_id, per_page
**Permission:** manage-matches

Includes: booking_count, revenue, seats_available, field_name, UI flags.

---

#### `POST` Create Match

```
POST {{base_url}}/cafe-admin/matches
```

**Auth:** Bearer token

**Body:**

```json
{
  "branch_id": 8,
  "home_team_id": 1,
  "away_team_id": 2,
  "league": "Premier League",
  "match_date": "2026-03-15",
  "kick_off": "20:00",
  "seats_available": 50,
  "price_per_seat": 25.0,
  "duration_minutes": 90,
  "booking_opens_at": "2026-03-10T10:00:00",
  "booking_closes_at": "2026-03-15T19:00:00",
  "field_name": "Field A",
  "venue_name": "Etihad Stadium"
}
```

Create a new match (unpublished by default).

**Validation:**
- home_team_id â‰  away_team_id
- match_date must be today or future
- All team IDs must exist

**Permission:** manage-matches

---

#### `GET` Get Match Detail

```
GET {{base_url}}/cafe-admin/matches/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

Full match detail with booking_stats, revenue, booking_timing.

**booking_stats:** total_booked, capacity_pct, checked_in, arrived_pct, pending, pending_pct, available, remaining_pct
**revenue:** total, bookings_count, average_per_booking

**Permission:** manage-matches

---

#### `PUT` Update Match

```
PUT {{base_url}}/cafe-admin/matches/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

**Body:**

```json
{
  "seats_available": 60,
  "price_per_seat": 30.0,
  "ticket_price": 75,
  "field_name": "Field B",
  "venue_name": "Old Trafford"
}
```

Update a match. Only works for upcoming matches.

**NEW FIELD:** `ticket_price` - Can be different from `price_per_seat` (use case: early bird pricing, promotions).

All fields optional. Returns 409 if match is live/finished.

**Permission:** manage-matches

---

#### `DELETE` Cancel Match

```
DELETE {{base_url}}/cafe-admin/matches/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

Cancel a match. Notifies all bookers, refunds confirmed bookings, releases seats.

Returns: bookings_cancelled, bookings_refunded, users_notified.

**Permission:** manage-matches

---

#### `POST` Publish Match

```
POST {{base_url}}/cafe-admin/matches/:id/publish
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

Publish a match (set is_published=true).

Fires MatchPublished event.

**Permission:** manage-matches

---

#### `PUT` Update Score

```
PUT {{base_url}}/cafe-admin/matches/:id/score
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

**Body:**

```json
{
  "home_score": 2,
  "away_score": 1
}
```

Update match score. Only for live or finished matches.

Broadcasts MatchScoreUpdated event.

**Permission:** manage-matches

---

#### `PUT` Update Status

```
PUT {{base_url}}/cafe-admin/matches/:id/status
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

**Body:**

```json
{
  "status": "live"
}
```

Update match status with strict transition validation.

**Valid transitions:** upcoming â†’ live â†’ finished (no skipping)

When going live, score auto-initializes to 0-0.

**Permission:** manage-matches

---

#### `POST` Send Reminder

```
POST {{base_url}}/cafe-admin/matches/:id/reminder
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Match ID

Send push notification reminder to all bookers.

**Rate limited:** 1 reminder per match per day.
Returns 429 if already sent today.

**Permission:** manage-matches

---

### Booking Management

#### `GET` List Bookings

```
GET {{base_url}}/cafe-admin/bookings?status=confirmed
```

**Auth:** Bearer token

**Query params:**
- `status=confirmed` — Filter: confirmed, pending, cancelled, checked_in

List bookings for your cafe. Includes customer, match, seats, payment.

**Filters:** status, match_id, date
**UI flags:** is_vip, is_returning, can_check_in, can_cancel

**Permission:** view-bookings

---

#### `GET` Booking Detail

```
GET {{base_url}}/cafe-admin/bookings/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Booking ID

Full booking detail with: customer, seat_numbers array, QR code, payment_summary { seat_price_unit, seat_count, seat_total, service_fee, total, payment_method_display }.

**Permission:** view-bookings

---

#### `POST` Check-In Booking

```
POST {{base_url}}/cafe-admin/bookings/:id/check-in
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Booking ID

Check in a customer. Sets status=checked_in with timestamp.

**Validates:** booking must be confirmed, match must be today or live.

**Permission:** check-in-customers

---

#### `POST` Cancel Booking

```
POST {{base_url}}/cafe-admin/bookings/:id/cancel
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Booking ID

Admin cancel booking with refund + release seats.

Returns: seats_released, refunded.

**Permission:** manage-bookings

---

#### `GET` Today's Summary

```
GET {{base_url}}/cafe-admin/bookings/today-summary
```

**Auth:** Bearer token

Today's booking summary: { total, active, pending, checked_in }.

**Permission:** view-bookings

---

### QR Scan

#### `POST` Scan QR Code (text)

```
POST {{base_url}}/cafe-admin/scan-qr
```

**Auth:** Bearer token

**Body:**

```json
{
  "qr_data": "BOOK-ABC123"
}
```

**UPDATED:** Improved QR scanning

**Changes:**
- Accepts both `qr_code` and `qr_data` fields
- Automatically performs check-in on scan
- Returns `check_in_time` in response
- Invalid QR returns 422 (was 404)
- Booking code format: BOOK-XXXXXX

**Request (either field works):**
```json
{
    "qr_data": "BOOK-ABC123"
}
```

**Permission:** scan-qr

---

#### `POST` Scan QR Code (image upload)

```
POST {{base_url}}/cafe-admin/scan-qr/upload
```

**Auth:** Bearer token

**Body (form-data):**

- `image` (file) — QR code image (jpeg, png, jpg, gif, bmp, max 5MB)

Upload QR code image to decode and find booking.

Requires QR decode library (khanamiryan/qrcode-detector-decoder).

**Permission:** scan-qr

---

#### `GET` Recent Scans

```
GET {{base_url}}/cafe-admin/scan-qr/recent
```

**Auth:** Bearer token

Last 10 recently scanned bookings.

Returns: [{ booking_code, scanned_at, result, status }]

**Permission:** scan-qr

---

#### `GET` Scan Stats

```
GET {{base_url}}/cafe-admin/scan-qr/stats
```

**Auth:** Bearer token

Today's scan statistics: { today_scans, success_rate, avg_speed_seconds }.

**Permission:** scan-qr

---

### Occupancy Tracking

#### `GET` Get Occupancy Dashboard

```
GET {{base_url}}/cafe-admin/occupancy
```

**Auth:** Bearer token

Get comprehensive occupancy dashboard with real-time metrics calculated from checked-in bookings.

**Returns:**
- occupied_percentage: Current occupancy rate
- current_guests: Number of checked-in guests
- capacity: Total branch capacity
- available: Available space
- reserved: Reserved seats count
- peak_times_today: Top 3 peak hour slots
- avg_stay_time: Average guest stay duration
- today_total_visitors: Total visitors today
- trend_percentage: Today vs yesterday comparison
- sections: Per-section breakdown with icons

**Caching:** 2 minutes
**Permission:** view-occupancy

---

#### `PUT` Update Branch Capacity

```
PUT {{base_url}}/cafe-admin/occupancy/capacity
```

**Auth:** Bearer token

**Body:**

```json
{
  "total_capacity": 200
}
```

Update branch total capacity (total_seats).

**Request:**
- total_capacity: integer (min: 1)

**Features:**
- Updates branch.total_seats
- Automatically busts occupancy caches
- Validates minimum 1 seat

**Permission:** manage-cafe-profile

---

#### `GET` Get Historical Peak Times

```
GET {{base_url}}/cafe-admin/occupancy/peak-times
```

**Auth:** Bearer token

Get historical peak times analysis for the last 7 days.

**Returns:**
- Array of hourly slots with:
  - time_range: Hour range (e.g., "11:00 - 12:00")
  - avg_guests: Average guest count for that hour
  - percentage: Percentage of capacity

**Analysis:**
- Hourly breakdown across last 7 days
- Identifies busiest time slots
- Helps with staffing planning

**Caching:** 5 minutes
**Permission:** view-occupancy

---

#### `GET` Get Section Occupancy

```
GET {{base_url}}/cafe-admin/occupancy/sections
```

**Auth:** Bearer token

Get per-section occupancy breakdown with current rates.

**Returns:**
- Array of sections with:
  - id: Section ID
  - name: Section name
  - occupied: Number of occupied seats
  - total: Total seats in section
  - percentage: Occupancy percentage
  - icon: UI icon (vipâ†’star, premiumâ†’premium, standardâ†’screen, loungeâ†’lounge, diningâ†’dining)

**Use Cases:**
- Visual heatmap of section occupancy
- Identify which sections are busiest
- Guide customer seating decisions

**Caching:** 2 minutes
**Permission:** view-occupancy

---

### Staff Management

#### `GET` List All Staff

```
GET {{base_url}}/cafe-admin/staff
```

**Auth:** Bearer token

List all staff members with roles, permissions, and invitation status.

**Returns:**
- Array of staff with:
  - id, cafe_id, role (admin/manager/staff)
  - invitation_status (pending/accepted/rejected)
  - user: id, name, email, avatar
  - invited_by: id, name
  - permissions: Array of permission names
  - status_badge: UI badge text
  - role_label: Formatted role name
  - can_resend_invite: boolean
  - permissions_count: integer
- meta: total, pending, accepted counts

**Permission:** manage-staff
**Features:** Spatie permission integration, UI helper flags

**Update:** the response now includes a `branches` array of `{ id, name }` — the staff member's assigned branches.

---

#### `POST` Add Staff Member (branch + credentials)

```
POST {{base_url}}/cafe-admin/staff
```

**Auth:** Bearer token

**Body:**

```json
{
  "name": "Sara Manager",
  "email": "sara@example.com",
  "password": "secret123",
  "role": "manager",
  "permissions": [
    "manage-bookings",
    "view-bookings",
    "view-analytics",
    "scan-qr",
    "check-in-customers"
  ],
  "branch_ids": [
    1,
    2
  ]
}
```

Add a new staff member to your cafe. The owner sets the email + password directly and the account is created **active immediately** — there is no invitation email and no accept step.

**Request:**
- name: string (required)
- email: string (required, must be a brand-new email — 422 if already registered)
- password: string (required, min 8)
- role: string (required: admin | manager | staff)
- permissions: array (optional; overrides role defaults)
- branch_ids: array (required, min 1; each branch must belong to your cafe — 422 otherwise)

**Response `data`** includes a `branches` array of `{ id, name }` plus role, permissions, and `invitation_status: accepted`. The password is never returned.

**Preconditions:** active subscription with staff allowance; permission `manage-staff`.

---

#### `GET` Get Staff Detail

```
GET {{base_url}}/cafe-admin/staff/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Staff member ID

Get detailed information about a specific staff member.

**Returns:**
- Full staff member details
- Complete permissions list
- User information
- Invited by information
- Cafe details (when loaded)
- UI helper flags

**Permission:** manage-staff

**Update:** the response now includes a `branches` array of `{ id, name }` — the staff member's assigned branches.

---

#### `PUT` Update Staff

```
PUT {{base_url}}/cafe-admin/staff/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Staff member ID

**Body:**

```json
{
  "role": "manager",
  "permissions": [
    "manage-bookings",
    "view-analytics"
  ],
  "branch_ids": [
    2
  ],
  "password": "newsecret123"
}
```

Update a staff member's role, permissions, branch assignments and/or password. All fields optional.

- role: string (optional: admin | manager | staff)
- permissions: array (optional)
- branch_ids: array (optional, min 1; re-syncs assignments — attaches new, detaches removed; each must belong to your cafe)
- password: string (optional, min 8; resets the staff member's password)

Permission: manage-staff.

---

#### `DELETE` Remove Staff

```
DELETE {{base_url}}/cafe-admin/staff/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Staff member ID

Remove staff member from your cafe.

**Actions:**
- Soft deletes staff member record
- Revokes all cafe-related permissions
- Preserves user account

**Revoked Permissions:**
manage-bookings, view-bookings, manage-matches, view-analytics, manage-offers, manage-menu, manage-branches, manage-seating, scan-qr, check-in-customers, view-occupancy, manage-staff

**Permission:** manage-staff

---

#### `POST` Resend Invitation

```
POST {{base_url}}/cafe-admin/staff/:id/resend-invite
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Staff member ID

Resend invitation email to pending staff member.

**Requirements:**
- Invitation status must be 'pending'
- Cannot resend to accepted invitations

**Features:**
- Generates new signed URL (7-day expiry)
- Sends fresh notification
- Uses StaffInvitationNotification

**Permission:** manage-staff

---

#### `GET` Get Roles & Permissions

```
GET {{base_url}}/cafe-admin/roles-permissions
```

**Auth:** Bearer token

Get all available roles with their default permissions.

**Use Case:** Staff invitation form dropdown

**Returns:**
- roles: Array with value, label, description, default_permissions
  - admin: 12 permissions
  - manager: 7 permissions
  - staff: 3 permissions
- available_permissions: Array with value, label for all permissions

**Available Permissions:**
manage-bookings, view-bookings, manage-matches, view-analytics, manage-offers, manage-menu, manage-branches, manage-seating, scan-qr, check-in-customers, view-occupancy, manage-staff

**Permission:** manage-staff

---

#### `POST` Accept Staff Invitation (Public)

```
POST {{base_url}}/auth/staff/accept-invite/:token
```

**Auth:** Public (no token)

**Path variables:**
- `:token` — Signed invitation token from email

Accept staff invitation via signed URL (Public endpoint).

**Authentication:** None required - validated by signed token

**Process:**
- Decodes invitation token
- Validates staff member exists
- Checks invitation not already accepted
- Updates status to 'accepted'

**Token Format:**
Base64 encoded JSON: {staff_member_id, cafe_id}

**Returns:**
- Staff member details
- Message: 'Staff invitation accepted successfully. You can now log in.'

**Note:** After acceptance, staff can login with their email/password

---

### Offers Management

#### `GET` List Offers

```
GET {{base_url}}/cafe-admin/offers?status=active
```

**Auth:** Bearer token

**Query params:**
- `status=active` — Optional: active, draft, expired

Get all offers for authenticated owner's cafe.

**Optional Filters:**
- status: active, draft, expired

**Includes:** usage_count

**Returns:** Array of offers with UI flags
- is_expiring_soon (within 3 days)
- days_remaining
- is_active
- type_label

**Permission:** manage-offers

---

#### `POST` Create offer

```
POST {{base_url}}/cafe-admin/offers
```

**Auth:** Bearer token

**Body:**

```json
{
  "title": "30% Match Day Special",
  "description": "Get 30% off on all bookings for Champions League matches. Valid for groups of 4 or more.",
  "type": "percentage",
  "discount_percent": 30,
  "original_price": 160.0,
  "offer_price": 112.0,
  "valid_until": "2026-05-15",
  "available_for": "all",
  "terms": "Valid for groups of 4+ guests only. Cannot be combined with other offers.",
  "is_featured": true
}
```

Create new offer (defaults to 'draft' status).

**Required Fields:**
- title (max 255)
- description
- type: percentage, bogo, free_item
- available_for: all, weekend, prime_time

**Optional Fields:**
- discount_percent (1-100)
- original_price, offer_price
- valid_until (date after today)
- terms
- is_featured

**Status:** Defaults to 'draft'

**Permission:** manage-offers

---

#### `GET` Get Offer Detail

```
GET {{base_url}}/cafe-admin/offers/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Offer ID

Get offer details including usage stats.

**Returns:**
- Full offer details
- usage_count
- UI helper flags:
  - is_expiring_soon
  - days_remaining
  - is_active
  - type_label

**Permission:** manage-offers

---

#### `PUT` Update Offer

```
PUT {{base_url}}/cafe-admin/offers/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Offer ID

**Body:**

```json
{
  "discount_percent": 40,
  "offer_price": 96.0
}
```

Update offer details.

**All fields optional:**
- title, description, type
- discount_percent, original_price, offer_price
- valid_until, available_for
- terms, is_featured

**Note:** Status updated via separate endpoint

**Permission:** manage-offers

---

#### `DELETE` Delete Offer

```
DELETE {{base_url}}/cafe-admin/offers/:id
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Offer ID

Delete offer (soft delete).

**Process:**
- Soft deletes offer record
- Deletes all image sizes (original, medium, thumbnail)
- Clears offer cache

**Note:** Deletes associated images from storage

**Permission:** manage-offers

---

#### `POST` Upload Offer Image

```
POST {{base_url}}/cafe-admin/offers/:id/upload-image
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Offer ID

**Body (form-data):**

- `image` (file)

Upload offer image with multi-size generation.

**Validation:**
- Required: image field
- Type: jpg, jpeg, png, webp
- Max size: 5MB

**Generated Sizes:**
- original: Full resolution
- medium: 300x300px
- thumbnail: 150x150px

**Storage:** Stored as JSON array in database

**Note:** Deletes old image if exists

**Permission:** manage-offers

---

#### `PUT` Update Offer Status

```
PUT {{base_url}}/cafe-admin/offers/:id/status
```

**Auth:** Bearer token

**Path variables:**
- `:id` — Offer ID

**Body:**

```json
{
  "status": "active"
}
```

Toggle offer status between active and draft.

**Allowed Values:**
- active
- draft

**Note:** 'expired' status is set automatically by scheduled command

**Scheduled Command:**
- Runs daily at midnight
- Command: php artisan offers:expire
- Sets status='expired' for offers past valid_until

**Permission:** manage-offers

---

### Dashboard

#### `GET` Dashboard Overview

```
GET {{base_url}}/cafe-admin/dashboard
```

**Auth:** Bearer token

Get dashboard overview for authenticated cafe owner.

**Returns:**
- today_date: Today's date
- todays_matches: Today's matches with live status
- todays_bookings_count: Count of today's bookings
- current_occupancy_percentage: Real-time occupancy
- quick_actions: Available actions (can_scan_qr, can_manage_matches)

**Cache:** 15 minutes TTL

**Permission:** view-bookings

---

#### `GET` Upcoming Matches

```
GET {{base_url}}/cafe-admin/dashboard/upcoming-matches
```

**Auth:** Bearer token

Get next 5 upcoming matches with booking counts.

**Returns:**
- Array of upcoming matches
- Each match includes:
  - Match details (teams, date, competition)
  - is_live: Boolean flag
  - bookings_count: Total bookings
  - occupancy_percentage: Calculated occupancy

**Cache:** 15 minutes TTL

**Permission:** view-bookings

---

#### `GET` Recent Bookings

```
GET {{base_url}}/cafe-admin/dashboard/recent-bookings
```

**Auth:** Bearer token

Get last 10 bookings for authenticated cafe owner.

**Returns:**
- Array of recent bookings
- Each booking includes:
  - customer: User details (name, email, phone)
  - match: Match details
  - status: Booking status with badge color
  - guests: Number of guests
  - created_at: Booking timestamp

**Cache:** 15 minutes TTL

**Permission:** view-bookings

---

### Analytics

#### `GET` Analytics Overview

```
GET {{base_url}}/cafe-admin/analytics/overview?period=this_week
```

**Auth:** Bearer token

**Query params:**
- `period=this_week` — this_week, this_month, last_month, last_3_months

Get analytics overview with 4 key metrics and period comparison.

**Returns:**
- period_label: Human-readable period (e.g., 'Jan 1 - Jan 7, 2024')
- metrics:
  - bookings: Count with change % vs previous period
  - revenue: Total with change % vs previous period
  - new_customers: Count with change % vs previous period
  - avg_rating: Average rating with change % vs previous period

**Comparison:**
- change: Percentage string (+12.5%, -5.2%, 0%)
- trend: up, down, neutral

**Cache:** 15 minutes TTL

**Permission:** view-analytics

---

#### `GET` Bookings Chart

```
GET {{base_url}}/cafe-admin/analytics/bookings?period=this_month
```

**Auth:** Bearer token

**Query params:**
- `period=this_month` — this_week, this_month, last_month, last_3_months

Get daily bookings count for chart visualization.

**Returns:**
- period_label: Human-readable period
- chart_data: Array of daily data points
  - date: YYYY-MM-DD
  - count: Number of bookings

**Use Case:** Line/bar chart showing booking trends

**Cache:** 15 minutes TTL

**Permission:** view-analytics

---

#### `GET` Revenue Chart

```
GET {{base_url}}/cafe-admin/analytics/revenue?period=this_month
```

**Auth:** Bearer token

**Query params:**
- `period=this_month` — this_week, this_month, last_month, last_3_months

Get daily revenue amounts for chart visualization.

**Returns:**
- period_label: Human-readable period
- chart_data: Array of daily data points
  - date: YYYY-MM-DD
  - amount: Revenue amount (decimal)

**Use Case:** Line/bar chart showing revenue trends

**Cache:** 15 minutes TTL

**Permission:** view-analytics

---

#### `GET` Peak Hours

```
GET {{base_url}}/cafe-admin/analytics/peak-hours?period=this_week
```

**Auth:** Bearer token

**Query params:**
- `period=this_week` — this_week, this_month, last_month, last_3_months

Get hourly distribution of bookings for peak hours analysis.

**Returns:**
- period_label: Human-readable period
- peak_hours: Array of 24 hourly data points (0-23)
  - hour: 0-23 (24-hour format)
  - count: Number of bookings in that hour

**Use Case:** Heatmap or bar chart showing busiest hours

**Cache:** 15 minutes TTL

**Permission:** view-analytics

---

#### `GET` Customer Analytics

```
GET {{base_url}}/cafe-admin/analytics/customers?period=this_month
```

**Auth:** Bearer token

**Query params:**
- `period=this_month` — this_week, this_month, last_month, last_3_months

Get customer segmentation (new vs returning customers).

**Returns:**
- period_label: Human-readable period
- customers:
  - new: Count of new customers
  - returning: Count of returning customers
  - new_percentage: % of new customers
  - returning_percentage: % of returning customers

**Calculation:**
- New: First booking at this cafe in the period
- Returning: Had bookings before the period

**Use Case:** Pie/donut chart showing customer retention

**Cache:** 15 minutes TTL

**Permission:** view-analytics

---

#### `GET` Best Performing Matches

```
GET {{base_url}}/cafe-admin/analytics/matches?period=this_month&limit=10
```

**Auth:** Bearer token

**Query params:**
- `period=this_month` — this_week, this_month, last_month, last_3_months
- `limit=10` — Number of matches to return (default: 10)

Get best performing matches ranked by revenue.

**Returns:**
- period_label: Human-readable period
- matches: Array of matches
  - Match details (teams, date, competition)
  - bookings_count: Total bookings
  - total_revenue: Total revenue generated
  - avg_revenue_per_booking: Average revenue per booking

**Sorting:** By total_revenue DESC

**Use Case:** Table showing top revenue-generating matches

**Cache:** 15 minutes TTL

**Permission:** view-analytics

---

#### `GET` Subscription Analytics

```
GET {{base_url}}/cafe-admin/analytics/subscription
```

**Auth:** Bearer token

Get subscription-level analytics for last 12 months.

**Returns:**
- last_12_months: Array of 12 monthly data points
  - month: YYYY-MM
  - month_label: Human-readable (e.g., 'Jan 2024')
  - bookings_count: Total bookings
  - revenue: Total revenue
  - avg_per_booking: Average revenue per booking

**Sorting:** Oldest to newest month

**Use Case:** Line chart showing yearly trends

**Cache:** 15 minutes TTL

**Permission:** manage-subscription

---

### Subscription & Billing

#### `GET` Get Current Subscription

```
GET {{base_url}}/cafe-admin/subscription
```

**Auth:** Bearer token

Get current subscription with features, expiry, days_left, and auto_renew status.

**Returns:**
- id: Subscription ID
- plan: Full plan details (name, price, features, limits)
- status: active, cancelled, expired
- starts_at: ISO 8601 timestamp
- expires_at: ISO 8601 timestamp
- days_left: Calculated days remaining (integer)
- auto_renew: Boolean flag
- payment_method: Card details (type, last 4 digits)

**Use Case:** Display subscription status on settings page

**Permission:** manage-subscription

---

#### `GET` Get All Plans (Public)

```
GET {{base_url}}/subscription/plans
```

**Auth:** Public (no token)

Get all subscription plans comparison - PUBLIC endpoint (no auth required).

**Returns:**
- Array of plans (Starter $49, Pro $99, Elite $199)
- Each plan includes:
  - id, name, slug, price, currency
  - features: Array of feature descriptions
  - max_bookings: Booking limit (null for unlimited)
  - has_analytics, has_branding, has_priority_support
  - is_most_popular: Boolean (Pro plan)
  - display_price: Formatted price string
  - bookings_label: Formatted booking limit

**Use Case:** Pricing page/comparison table

**Permission:** None (Public endpoint)
**Cache:** 1 hour TTL

---

#### `POST` Upgrade Subscription

```
POST {{base_url}}/cafe-admin/subscription/upgrade
```

**Auth:** Bearer token

**Body:**

```json
{
  "plan_id": 2,
  "payment_method_id": 1
}
```

Upgrade or change subscription plan.

**Request:**
- plan_id: integer (required, must exist in subscription_plans)
- payment_method_id: integer (required, must belong to user)

**Process:**
1. Validates plan and payment method
2. Cancels existing active subscription
3. Creates payment record (status: paid)
4. Creates new subscription (1 month duration)
5. Updates cafe.subscription_plan
6. Clears subscription cache

**Returns:**
- subscription: New subscription details
- payment: Payment confirmation (id, amount, status, gateway_ref)

**Permission:** manage-subscription

---

#### `POST` Cancel Subscription

```
POST {{base_url}}/cafe-admin/subscription/cancel
```

**Auth:** Bearer token

Cancel subscription at period end (doesn't expire immediately).

**Process:**
- Sets auto_renew to false
- Subscription remains active until expires_at date
- No immediate service interruption
- Can be re-enabled via auto-renew toggle

**Note:** Does NOT immediately expire the subscription. User retains access until period end.

**Permission:** manage-subscription

---

#### `PUT` Toggle Auto-Renew

```
PUT {{base_url}}/cafe-admin/subscription/auto-renew
```

**Auth:** Bearer token

**Body:**

```json
{
  "auto_renew": true
}
```

Toggle automatic renewal setting.

**Request:**
- auto_renew: boolean (required)

**Process:**
- Updates subscription.auto_renew flag
- If true: Subscription will auto-renew 1 day before expiry
- If false: Subscription expires naturally
- Clears subscription cache

**Auto-Renewal:**
- Scheduled command runs daily at 6:00 AM
- Processes subscriptions expiring within 1 day
- Creates payment and extends subscription by 1 month
- On failure: Marks as expired and disables auto-renew

**Permission:** manage-subscription

---

#### `GET` Get Transaction History

```
GET {{base_url}}/cafe-admin/billing?period=all_time&type=subscription
```

**Auth:** Bearer token

**Query params:**
- `period=all_time` — all_time, this_month, last_3_months
- `type=subscription` — booking, subscription, cafe_order (optional)

Get transaction history with filters and pagination.

**Query Parameters:**
- period (optional): all_time, this_month, last_3_months
- type (optional): booking, subscription, cafe_order

**Returns:**
- data: Array of transactions (15 per page)
  - id, title, subtitle, type, status (PAID/PENDING/FAILED)
  - amount, currency, date, time, gateway_ref
  - UI helpers: is_paid, is_pending, is_failed, is_refunded
  - formatted_amount, formatted_date, status_color
- meta: Pagination info (current_page, total, per_page, etc.)

**Transaction Titles:**
- Subscription: 'Subscription Payment'
- Booking: 'Booking Payment' with match details
- Cafe Order: 'Cafe Order Payment'

**Permission:** manage-subscription

---

#### `GET` Get Billing Summary

```
GET {{base_url}}/cafe-admin/billing/summary
```

**Auth:** Bearer token

Get billing summary for current month.

**Returns:**
- total_spent_this_month: Sum of all paid transactions
- total_transactions: Count of transactions
- currency: SAR

**Calculation Period:** Current month (start to end)

**Use Case:** Dashboard billing widget

**Cache:** 15 minutes TTL
**Permission:** manage-subscription

---

#### `GET` Export Billing History (CSV)

```
GET {{base_url}}/cafe-admin/billing/export?period=last_3_months
```

**Auth:** Bearer token

**Query params:**
- `period=last_3_months` — all_time, this_month, last_3_months (optional)

Export billing history as CSV file download.

**Query Parameters:**
- period (optional): all_time, this_month, last_3_months
- type (optional): booking, subscription, cafe_order

**CSV Columns:**
- ID, Title, Subtitle, Type, Status
- Amount, Currency, Date, Time, Gateway Reference

**Response:**
- Content-Type: text/csv
- Content-Disposition: attachment
- Filename: billing_history_YYYY-MM-DD_HHMMSS.csv

**Use Case:** Accounting/bookkeeping export

**Permission:** manage-subscription

---

#### `PUT` Update Payment Method

```
PUT {{base_url}}/cafe-admin/billing/payment-method
```

**Auth:** Bearer token

**Body:**

```json
{
  "payment_method_id": 2
}
```

Update default billing payment method.

**Request:**
- payment_method_id: integer (required, must belong to user)

**Process:**
1. Sets all user's payment methods to non-primary
2. Sets selected method as primary
3. Updates active subscription's payment_method_id
4. Future auto-renewals will use this method

**Use Case:** Change billing card for subscriptions

**Permission:** manage-subscription

---

## Rebook & Fan Room

### `POST` Rebook Match

```
POST {{base_url}}/bookings/1/rebook
```

**Auth:** Bearer token

Find next upcoming match with same teams for quick rebooking.

**Response:**
- If exact rematch found: { suggested_match, branch }
- If no exact match: { suggested_match: null, similar_matches: [...] }

**Auth:** Required

---

### `POST` Enter Fan Room

```
POST {{base_url}}/bookings/1/enter-fan-room
```

**Auth:** Bearer token

Enter or create fan room chat for a live match booking.

**Requirements:**
- Booking must be confirmed or checked_in
- Match must be live (is_live = true)

**Response:** { chat_room_id }

**Auth:** Required

---

## Support

### `POST` Submit Contact Ticket

```
POST {{base_url}}/support/contact
```

**Auth:** Bearer token

**Body:**

```json
{
  "subject": "Booking issue",
  "message": "I cannot find my booking confirmation email. My booking code is BK-12345."
}
```

Submit a contact/support message.

**Request:**
- subject: string (required, max 255)
- message: string (required, max 5000)

**Auth:** Required

---

### `POST` Report Issue

```
POST {{base_url}}/support/report-issue
```

**Auth:** Bearer token

**Body (form-data):**

- `title` (text)
- `description` (text)
- `screenshot` (file) — Optional screenshot (image, max 5MB)

Report an issue with optional screenshot upload.

**Request:**
- title: string (required, max 255)
- description: string (required, max 5000)
- screenshot: file (optional, image, max 5MB)

**Response:** Ticket with multi-size screenshot URLs { original, medium, thumbnail }

**Auth:** Required

---

### `GET` My Tickets

```
GET {{base_url}}/support/my-tickets
```

**Auth:** Bearer token

List all support tickets for the authenticated user, ordered by newest first.

**Auth:** Required

---

## FAQs

### `GET` List FAQs

```
GET {{base_url}}/faqs
```

**Auth:** Public (no token)

List all active FAQs ordered by sort_order.

**Response:** Array of FAQs with id, question, answer, category, sort_order.

**Auth:** Not required (public)

---

## Legal Pages

### `GET` Get Privacy Policy

```
GET {{base_url}}/pages/privacy-policy
```

**Auth:** Public (no token)

Get privacy policy page content.

**Auth:** Not required (public)

---

### `GET` Get Terms & Conditions

```
GET {{base_url}}/pages/terms-conditions
```

**Auth:** Public (no token)

Get terms and conditions page content.

**Auth:** Not required (public)

---

### `GET` Get Data Usage Policy

```
GET {{base_url}}/pages/data-usage
```

**Auth:** Public (no token)

Get data usage policy page content.

**Auth:** Not required (public)

---

### `GET` Get Cookie Policy

```
GET {{base_url}}/pages/cookie-policy
```

**Auth:** Public (no token)

Get cookie policy page content.

**Auth:** Not required (public)

---

### `GET` Get Page by Slug

```
GET {{base_url}}/pages/:slug
```

**Auth:** Public (no token)

**Path variables:**
- `:slug` — Page slug (privacy-policy, terms-conditions, data-usage, cookie-policy)

Get any legal/info page by its slug.

**Available slugs:** privacy-policy, terms-conditions, data-usage, cookie-policy

**Auth:** Not required (public)

---

## App Config

### `GET` Get App Version

```
GET {{base_url}}/app/version
```

**Auth:** Public (no token)

Get app version information for update checking.

**Response:**
- current_version: "1.0.0"
- min_version: "1.0.0"
- force_update: false

**Auth:** Not required (public)

---

### `GET` Get App Config

```
GET {{base_url}}/app/config
```

**Auth:** Public (no token)

Get app configuration settings.

**Response:**
- maintenance_mode: false
- support_email: "support@matchday.app"
- support_phone: "+966500000000"
- terms_url, privacy_url
- default_currency: "SAR"

**Auth:** Not required (public)

---

