# Baakh mobile notifications — Android brief

Use this to implement in-app notifications and device registration. The inbox and device APIs are live. The server stores FCM tokens but does **not** send Firebase pushes yet.

## Base URL

- Production: `https://baakh.com`
- Beta: same paths on the beta host

## Auth

- Optional on all notification endpoints
- If the user is logged in, send: `Authorization: Bearer {sanctum_token}`
- Logged-in users also receive `signed_in` campaigns and keep read/opened state
- Guests only see `everyone` + `guests`

### Headers

```
Accept: application/json
Content-Type: application/json
Authorization: Bearer {token}   // if logged in
```

---

## 1. Register this device

Call after the FCM token is ready, and again after login.

`POST /api/v1/mobile/devices`

```json
{
  "token": "FCM_DEVICE_TOKEN",
  "platform": "android",
  "provider": "fcm",
  "device_id": "stable-app-install-id",
  "app_version": "1.0.0",
  "locale": "sd",
  "push_enabled": true
}
```

| Field | Required | Notes |
|---|---|---|
| `token` | yes | FCM device token |
| `platform` | yes | Must be `"android"` |
| `provider` | no | Defaults to `"fcm"` on Android |
| `device_id` | recommended | One UUID per install. Keep it. |
| `app_version` | no | e.g. `"1.0.0"` |
| `locale` | no | `"sd"` or `"en"`. Default `"sd"` |
| `push_enabled` | no | Default `true` |

### Response

```json
{
  "message": "Device registered",
  "data": {
    "id": 1,
    "platform": "android",
    "provider": "fcm",
    "push_enabled": true
  }
}
```

### Unregister

Call on logout, or when the user turns notifications off.

`DELETE /api/v1/mobile/devices`

```json
{ "token": "FCM_DEVICE_TOKEN" }
```

or:

```json
{ "device_id": "stable-app-install-id" }
```

---

## 2. Inbox

`GET /api/v1/mobile/notifications?lang=sd&platform=android`

| Query | Values | Notes |
|---|---|---|
| `lang` | `sd` (default), `en` | Title, body, and CTA are already localized |
| `platform` | `android` | Hides iOS-only campaigns |

- Max 50 items
- Draft, expired, and future-scheduled items are not returned

### Response

```json
{
  "unread_count": 2,
  "data": [
    {
      "id": 1,
      "type": "daily_verse",
      "title": "آڄ جو بيت",
      "body": "هڪ ننڍو بيت، هڪ وڏو خيال. اڄ جو چونڊيل بيت پڙهو.",
      "cta": "هاڻي پڙهو",
      "image_url": null,
      "icon": "BookOpen",
      "color": "amber",
      "platforms": ["android", "ios"],
      "audience": "everyone",
      "deep_link": "baakh://couplets",
      "web_path": "/sd/couplets",
      "priority": "normal",
      "data": null,
      "created_at": "2026-08-30T01:20:00.000000Z",
      "schedule_at": null,
      "read_at": null,
      "opened_at": null
    }
  ]
}
```

- `lang=sd` → RTL, Sindhi font
- Unread = `read_at === null`
- Fetch on app open, pull-to-refresh, and when returning to foreground

---

## 3. Mark read / opened

Seen in the list:

`POST /api/v1/mobile/notifications/{id}/read`

```json
{ "device_id": "stable-app-install-id" }
```

User tapped it:

`POST /api/v1/mobile/notifications/{id}/opened`

```json
{ "device_id": "stable-app-install-id" }
```

Then open `deep_link`. If the app cannot handle that scheme, open `https://baakh.com` + `web_path`.

---

## Types and navigation

| `type` | Meaning | Typical `deep_link` |
|---|---|---|
| `daily_verse` | Daily couplet | `baakh://couplets` |
| `new_poetry` | New poem | `baakh://poetry` or `baakh://poetry/{slug}` |
| `new_poet` | New poet | `baakh://poets` or `baakh://poet/{slug}` |
| `featured` | Staff pick | `baakh://featured` |
| `word_of_the_day` | Dictionary | `baakh://dictionary` |
| `new_lyrics` | New lyrics | `baakh://lyrics` |
| `reminder` | Reading reminder | `baakh://home` |
| `app_update` | App update | `baakh://settings` |
| `announcement` | General note | `baakh://announcements` |
| `event` | Literary event | `baakh://events` |
| `bookmark_nudge` | Continue reading | `baakh://bookmarks` |

Unknown types: still show the card and follow `deep_link` / `web_path`.

### Suggested Android deep links

```
baakh://couplets
baakh://poetry
baakh://poetry/{slug}
baakh://poets
baakh://poet/{slug}
baakh://lyrics
baakh://lyrics/{slug}
baakh://dictionary
baakh://home
baakh://featured
baakh://settings
baakh://announcements
baakh://events
baakh://bookmarks
```

---

## What to build now

1. Get FCM token → `POST /api/v1/mobile/devices`
2. Notification inbox screen from `GET /api/v1/mobile/notifications`
3. Badge from `unread_count`
4. Tap → `/opened` + navigate via `deep_link`
5. Re-register the token after login
6. Unregister on logout / opt-out

Sindhi (`lang=sd`) must be RTL.

---

## Not ready yet

The server **stores** FCM tokens but does **not** send Firebase pushes yet.

Do not wait for a push payload. Use the inbox API.

When push is enabled later, the data payload will reuse the same fields: `id`, `type`, `title`, `body`, `deep_link`.

---

## Quick test (no login)

```bash
curl "https://baakh.com/api/v1/mobile/notifications?lang=sd&platform=android"

curl -X POST "https://baakh.com/api/v1/mobile/devices" \
  -H "Content-Type: application/json" \
  -d '{"token":"test-token","platform":"android","device_id":"android-dev-1","locale":"sd"}'
```

Admin creates campaigns at `/admin/mobile-notifications`. After a campaign is **Published**, it appears in this inbox.
