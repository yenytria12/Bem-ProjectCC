# BEM TEL-U API Documentation

## Overview

API backend untuk BEM TEL-U Management System menggunakan Laravel dengan JWT authentication.

## Authentication

API menggunakan JWT (JSON Web Token) untuk authentication.

### Login Endpoint

**POST** `/api/v1/login`

Request body:
```json
{
  "email": "admin@mail.com",
  "password": "password"
}
```

Response (Success):
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@mail.com",
    "ministry_id": null,
    "roles": ["Super Admin"]
  }
}
```

Response (Error):
```json
{
  "success": false,
  "message": "Invalid email or password"
}
```

### Get Current User

**GET** `/api/v1/me`

Headers:
```
Authorization: Bearer {access_token}
```

Response:
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@mail.com",
    "ministry_id": null,
    "roles": ["Super Admin"],
    "permissions": ["view_any_user", "create_user", ...]
  }
}
```

### Logout

**POST** `/api/v1/logout`

Headers:
```
Authorization: Bearer {access_token}
```

Response:
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

### Refresh Token

**POST** `/api/v1/refresh`

Headers:
```
Authorization: Bearer {access_token}
```

Response:
```json
{
  "success": true,
  "access_token": "new_token...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {...}
}
```

## Testing dengan Postman

### Setup

1. Start Laravel server:
```bash
php artisan serve
```

2. Test Login:
   - Method: POST
   - URL: http://localhost:8000/api/v1/login
   - Body (JSON):
     ```json
     {
       "email": "admin@mail.com",
       "password": "password"
     }
     ```

3. Copy `access_token` dari response

4. Test Protected Routes:
   - Method: GET
   - URL: http://localhost:8000/api/v1/me
   - Headers:
     ```
     Authorization: Bearer {paste_your_token_here}
     ```

## CORS Configuration

API sudah dikonfigurasi untuk menerima request dari mobile app dan web frontend.

## Tech Stack

- Laravel 11
- tymon/jwt-auth
- Laravel Sanctum (untuk future features)

## Security

- JWT tokens expire dalam 60 menit (bisa diubah di config/jwt.php)
- Passwords di-hash menggunakan bcrypt
- CORS enabled untuk security
- Token disimpan secara aman di database

## Default Users

Berikut adalah user default yang bisa digunakan untuk testing:

| Email | Password | Role |
|-------|----------|------|
| admin@mail.com | password | Super Admin |
| presiden@mail.com | password | Presiden BEM |
| wakilpresiden@mail.com | password | Wakil Presiden BEM |
| sekretaris@mail.com | password | Sekretaris |
| bendahara@mail.com | password | Bendahara |
| menteri@mail.com | password | Menteri |
| anggota@mail.com | password | Anggota |

## License

BEM TEL-U © 2025

