# Real-time Poll & Voting System — API Documentation

**Base URL (local dev):** `http://127.0.0.1:8000/api`
**Auth type:** Bearer token (Laravel Sanctum) for poll creators. Voting is open to anyone — no account required.
**Real-time:** Laravel Reverb (WebSocket). Must be running alongside the app via `php artisan reverb:start --debug`.

All authenticated endpoints require an `Authorization: Bearer {token}` header. Every request/response body is JSON — send `Content-Type: application/json` and `Accept: application/json` on all requests.

Every response follows the same envelope, via the shared `ApiResponse` trait:
```json
{
    "success": true,
    "message": "Optional human-readable message",
    "data": { }
}
```
Error responses follow the same shape, with `success: false` and an `errors` field in place of `data` where relevant.

---

## Authentication

### Register a new poll creator

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/register`
- **Auth required:** No

**Request body**
```json
{
    "name": "Poll Creator",
    "email": "creator@example.com",
    "password": "password123"
}
```

**Validation rules:** `name` required. `email` required, valid email, unique in `users`. `password` required, minimum 8 characters.

**Response `201 Created`**
```json
{
    "success": true,
    "message": "Registered successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "Poll Creator",
            "email": "creator@example.com"
        },
        "token": "1|abcdef123456..."
    }
}
```

---

### Log in

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/login`
- **Auth required:** No

**Request body**
```json
{
    "email": "creator@example.com",
    "password": "password123"
}
```

**Response `200 OK`** — same shape as register, with `message: "Logged in successfully"`.

**Errors:** `401` if email/password don't match an existing account.

---

### Log out

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/logout`
- **Auth required:** Yes

**Response:** `204 No Content`

Revokes the token used to make this request. Other tokens/devices remain logged in.

---

### Get current user

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/user`
- **Auth required:** Yes

**Response `200 OK`** — the authenticated user's profile.

---

## Polls (creator-facing, authenticated)

These endpoints are for the logged-in poll creator managing their own polls.

### List my polls

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/polls`
- **Auth required:** Yes

**Response `200 OK`** — array of polls belonging to the current user, each with its `options` nested.

---

### Create a poll

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/polls`
- **Auth required:** Yes

**Request body**
```json
{
    "question": "What's your favorite programming language?",
    "options": ["JavaScript", "Python", "PHP"],
    "expires_at": "2026-12-31T23:59:00"
}
```

**Validation rules:** `question` required, string. `options` required, array, minimum 2 entries. Each entry in `options` required, string. `expires_at` optional — if provided, must be a valid date in the future. Omit it (or send `null`) for a poll that never expires.

**Response `201 Created`**
```json
{
    "success": true,
    "message": "Created successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "question": "What's your favorite programming language?",
        "slug": "x7f3k9pz",
        "expires_at": "2026-12-31T23:59:00.000000Z",
        "options": [
            { "id": 1, "poll_id": 1, "option_text": "JavaScript", "votes_count": 0 },
            { "id": 2, "poll_id": 1, "option_text": "Python", "votes_count": 0 },
            { "id": 3, "poll_id": 1, "option_text": "PHP", "votes_count": 0 }
        ]
    }
}
```

Note the `slug` — this is the public identifier to share, not the numeric `id`. Use it for the public endpoints below.

---

## Public Polls (unauthenticated — anyone with the link)

These two endpoints require no login. They identify guest voters via a session cookie, so requests must support cookies (Postman does this automatically; clear cookies between requests to simulate a different voter).

### View a poll and its live results

- **Method:** `GET`
- **URL:** `http://127.0.0.1:8000/api/public/polls/{slug}`
- **Auth required:** No

**Response `200 OK`**
```json
{
    "success": true,
    "message": null,
    "data": {
        "question": "What's your favorite programming language?",
        "slug": "x7f3k9pz",
        "expires_at": "2026-12-31T23:59:00.000000Z",
        "is_expired": false,
        "options": [
            { "id": 1, "text": "JavaScript", "votes_count": 4 },
            { "id": 2, "text": "Python", "votes_count": 7 },
            { "id": 3, "text": "PHP", "votes_count": 2 }
        ]
    }
}
```

No creator-identifying fields are exposed here — only what a public visitor needs to see and vote.

---

### Cast a vote

- **Method:** `POST`
- **URL:** `http://127.0.0.1:8000/api/public/polls/{slug}/vote`
- **Auth required:** No (but if a valid Bearer token is sent, the vote is also linked to that user)

**Request body**
```json
{
    "poll_option_id": 2
}
```

**Validation rules:** `poll_option_id` required, integer, must belong to the poll in the URL.

**Response `201 Created`**
```json
{
    "success": true,
    "message": "Vote recorded",
    "data": null
}
```

**Errors:**
- `403 Forbidden` — the poll's `expires_at` has already passed.
- `409 Conflict` — this voter (by user account or by session) has already voted in this poll.
- `422 Unprocessable Entity` — missing or invalid `poll_option_id`.

**Side effect:** on success, the targeted option's `votes_count` is incremented, and a `vote.cast` event is broadcast in real time to anyone listening on this poll's channel.

---

## Real-time updates (WebSocket, via Reverb)

Results update live without polling or refreshing, over a public broadcast channel — no authentication needed to listen.

**Channel name:** `poll.{slug}`
**Event name:** `vote.cast`

**Event payload**
```json
{
    "slug": "x7f3k9pz",
    "total_votes": 13,
    "options": [
        { "id": 1, "text": "JavaScript", "votes_count": 4 },
        { "id": 2, "text": "Python", "votes_count": 7 },
        { "id": 3, "text": "PHP", "votes_count": 2 }
    ]
}
```

A client subscribes to `poll.{slug}` and listens for `vote.cast`. Every time any visitor casts a valid vote on that poll, this event fires and every subscriber receives the updated counts immediately — no refresh, no re-fetching `GET /public/polls/{slug}`.

**Running Reverb locally:** the WebSocket server is a separate process from the main app and must be running for real-time updates to work:
```bash
php artisan reverb:start --debug
```
Run this alongside `php artisan serve` in a second terminal. `--debug` prints connection and message activity, useful for confirming votes are actually broadcasting while there's no frontend yet.

---

---

## Getting started (local)

1. Install dependencies: `composer install`
2. Copy `.env.example` to `.env`, then run `php artisan key:generate`.
3. Use SQLite (or your preferred database) and run `php artisan migrate`.
4. Set the broadcasting values in `.env`. Each key must appear only once, because duplicate lines silently override each other:
```
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=123456
REVERB_APP_KEY=localkey123456
REVERB_APP_SECRET=localsecret123456
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
```
5. Run `php artisan config:clear`.
6. Start the API: `php artisan serve`
7. In a second terminal start the WebSocket server: `php artisan reverb:start --debug`

---

## Data model

- **users** — poll creators: id, name, email, password.
- **polls** — id, user_id (creator), question, slug (unique, public identifier), expires_at (nullable).
- **poll_options** — id, poll_id, option_text, votes_count (kept up to date on every vote so results need no counting query).
- **votes** — id, poll_id, poll_option_id, user_id (nullable), voter_identifier (hashed session ID), ip_address. Unique on `(poll_id, user_id)` and on `(poll_id, voter_identifier)`.

A poll has many options and many votes. An option belongs to a poll and has many votes. A vote belongs to a poll, an option and, optionally, a user.

---

## Testing the real-time feature

1. Create a poll and copy its `slug`.
2. Start `php artisan reverb:start --debug`. Subscribe to the channel `poll.{slug}` for the event `vote.cast` with a WebSocket client such as Laravel Echo or a Pusher-protocol tester, using host `localhost`, port `8080` and your `REVERB_APP_KEY`.
3. Send `POST http://127.0.0.1:8000/api/public/polls/{slug}/vote` from Postman. The event appears in the subscriber and in the Reverb debug output.
4. To vote again as a different guest, clear Postman's cookies. Without clearing them the second vote returns `409`.
5. Without a frontend, the `--debug` output alone confirms that votes broadcast.

---

## Vote uniqueness — how it's enforced

A voter can only vote once per poll. This is enforced at two layers:

- **Database constraints** — the `votes` table has two composite unique constraints: one on `(poll_id, user_id)` for logged-in voters, and one on `(poll_id, voter_identifier)` for guests. A duplicate vote is rejected by the database itself, not just by application logic, which closes any race-condition window between two near-simultaneous requests.
- **Voter identification** — logged-in voters are identified by their `user_id`. Guests are identified by a hashed session ID (`voter_identifier`), established via a session cookie on the public routes. Even a logged-in voter's `voter_identifier` is recorded, which prevents voting once while logged in and again after logging out.
- **IP address** is also recorded on every vote as a secondary signal for abuse review, though it does not currently block a repeat vote on its own.

---

## Poll expiry

- A poll's `expires_at` is optional. `null` means the poll never closes.
- Voting is blocked once `expires_at` has passed — checked at the moment a vote is submitted, returning `403 Forbidden`.
- Results remain visible on the public `GET` endpoint regardless of expiry status; only voting is blocked. The `is_expired` field tells the client whether to show a "voting closed" state.

---

## Authorization model

- `Poll` management endpoints (list, create, and future update/delete) are protected by `PollPolicy`, following the same ownership pattern used throughout: a creator can only manage polls they created.
- Public endpoints (`show`, `vote`) intentionally bypass this policy — they're designed for anonymous access by definition.

---

## Standard error responses

- **`401 Unauthorized`** — missing/invalid Bearer token on a protected creator route, or incorrect login credentials.
- **`403 Forbidden`** — authenticated but not allowed to manage this poll, or attempting to vote on an expired poll.
- **`404 Not Found`** — poll slug doesn't exist, or wrong HTTP verb for a route.
- **`409 Conflict`** — this voter has already voted in this poll.
- **`422 Unprocessable Entity`** — validation failed (e.g. fewer than 2 options, missing question, invalid `poll_option_id`).

---

## Architecture notes

The codebase separates concerns deliberately:

- **Controllers** (`PollController`, `PublicPollController`, `AuthController`) only validate input and translate between HTTP and the service layer — no business logic lives here.
- **Services** (`PollService`, `VoteService`, `AuthService`) hold the actual business logic — slug generation, vote casting with concurrency-safe counting, token issuance.
- **`VoterResolver`** is an interface, currently implemented by `SessionVoterResolver`. The voting logic depends on the interface, not the concrete class, so a different voter-identification strategy can be swapped in later by writing a new implementation and changing one binding in `AppServiceProvider`.
- **Domain exceptions** (`PollClosedException`, `AlreadyVotedException`) let services report *what* went wrong without knowing *how* that should be communicated over HTTP — that translation happens only in the controller.
- **`ApiResponse` trait** keeps every endpoint's JSON shape consistent, regardless of which controller handles it.
