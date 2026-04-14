# Dokumentasi Auth API

Dokumen ini menjelaskan cara memakai endpoint autentikasi pada proyek `kasir-api` menggunakan Laravel Sanctum.

## Base URL

Saat development lokal dengan `php artisan serve`, gunakan base URL berikut:

```text
http://127.0.0.1:8000
```

Semua endpoint auth berada di bawah prefix:

```text
/api/auth
```

## Daftar Endpoint

| Method | Endpoint | Auth | Keterangan |
| --- | --- | --- | --- |
| `POST` | `/api/auth/login` | Tidak | Login dengan `email` atau `username` |
| `GET` | `/api/auth/me` | Ya | Mengambil data user yang sedang login |
| `POST` | `/api/auth/logout` | Ya | Logout dan menghapus semua token user |

Catatan keamanan:

- Registrasi publik tidak disediakan.
- Penambahan user baru hanya melalui endpoint `POST /api/user` dan wajib login sebagai `admin`.

## Header Umum

Untuk request JSON di Postman atau Insomnia, gunakan header berikut:

```http
Accept: application/json
Content-Type: application/json
```

Untuk endpoint yang membutuhkan autentikasi, tambahkan juga:

```http
Authorization: Bearer TOKEN_KAMU
```


## 2. Login

Endpoint:

```http
POST /api/auth/login
```

Request body:

```json
{
  "login": "admin",
  "password": "password123"
}
```

Field `login` bisa diisi:

- `username`
- `email`

Contoh response sukses:

```json
{
  "message": "Login berhasil.",
  "token": "2|xyz123456789",
  "user": {
    "id": 1,
    "name": "Admin Kasir",
    "email": "admin@example.com",
    "username": "admin",
    "is_active": true,
    "role": "admin",
    "toko_id": 1,
    "toko": {
      "id": 1,
      "nama": "Toko Demo",
      "alamat": "Jl. Demo No. 1",
      "telepon": "081234567890",
      "is_active": true
    }
  }
}
```

Contoh response gagal jika credential salah:

```json
{
  "message": "Username/email atau password tidak sesuai.",
  "errors": {
    "login": [
      "Username/email atau password tidak sesuai."
    ]
  }
}
```

Contoh response gagal jika akun nonaktif:

```json
{
  "message": "Akun ini sedang nonaktif.",
  "errors": {
    "login": [
      "Akun ini sedang nonaktif."
    ]
  }
}
```

Catatan:

- Setiap login akan membuat token baru.
- Token lama tidak otomatis terhapus saat login.

## 3. Me

Endpoint:

```http
GET /api/auth/me
```

Wajib mengirim bearer token.

Contoh response sukses:

```json
{
  "user": {
    "id": 1,
    "name": "Admin Kasir",
    "email": "admin@example.com",
    "username": "admin",
    "is_active": true,
    "role": "admin",
    "toko_id": 1,
    "toko": {
      "id": 1,
      "nama": "Toko Demo",
      "alamat": "Jl. Demo No. 1",
      "telepon": "081234567890",
      "is_active": true
    }
  }
}
```

Jika token tidak dikirim atau tidak valid, response akan `401 Unauthorized`.

## 4. Logout

Endpoint:

```http
POST /api/auth/logout
```

Wajib mengirim bearer token.

Contoh response sukses:

```json
{
  "message": "Logout berhasil."
}
```

Catatan penting:

- Implementasi saat ini menghapus semua token milik user:

```php
$request->user()->tokens()->delete();
```

- Artinya, jika user login di beberapa device atau browser, semua sesi token akan ikut logout.

## Langkah Uji di Postman

## Siapkan Request Login

1. Buat request `POST`.
2. Isi URL:

```text
http://127.0.0.1:8000/api/auth/login
```

3. Isi header JSON yang sama.
4. Isi body login.
5. Klik `Send`.
6. Copy field `token` dari response.

## Siapkan Request Me

1. Buat request `GET`.
2. Isi URL:

```text
http://127.0.0.1:8000/api/auth/me
```

3. Buka tab `Authorization`.
4. Pilih `Bearer Token`.
5. Paste token hasil login.
6. Klik `Send`.

## Siapkan Request Logout

1. Buat request `POST`.
2. Isi URL:

```text
http://127.0.0.1:8000/api/auth/logout
```

3. Di tab `Authorization`, pilih `Bearer Token`.
4. Gunakan token yang sama.
5. Klik `Send`.

## Langkah Uji di Insomnia

## Login

1. Buat request baru.
2. Pilih method `POST`.
3. Isi URL endpoint login.
4. Pilih body type `JSON`.
5. Isi payload JSON.
6. Tambahkan header:
- `Accept: application/json`

## Memakai Bearer Token

1. Buka tab `Auth`.
2. Pilih `Bearer Token`.
3. Paste token hasil login.
4. Pakai token itu untuk endpoint `/api/auth/me` dan `/api/auth/logout`.

## Penyebab Umum Jika yang Muncul HTML

Kalau response yang muncul berupa HTML, biasanya penyebabnya:

- URL salah, misalnya tidak memakai `/api/auth/...`
- Method salah, misalnya `GET` padahal harus `POST`
- Header `Accept: application/json` tidak dikirim
- Body tidak dikirim dalam format JSON
- Validasi gagal lalu request diarahkan ulang

Target response yang benar untuk endpoint auth adalah JSON, bukan HTML.

## Contoh cURL

Register:

```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name":"Admin Kasir",
    "email":"admin@example.com",
    "username":"admin",
    "password":"password123",
    "password_confirmation":"password123",
    "toko_id":1,
    "role":"admin"
  }'
```

Login:

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "login":"admin",
    "password":"password123"
  }'
```

Me:

```bash
curl http://127.0.0.1:8000/api/auth/me \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_KAMU"
```

Logout:

```bash
curl -X POST http://127.0.0.1:8000/api/auth/logout \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN_KAMU"
```
