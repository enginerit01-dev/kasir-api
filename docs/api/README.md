# Dokumentasi API Kasir


## Autentikasi

### POST /api/auth/login
Login user (email/username + password)

**Body:**
- login: string (email/username)
- password: string

### GET /api/auth/me
Cek profil user (token)

### POST /api/auth/logout
Logout user (token)

---

## Produk

### GET /api/produk
List produk (pagination, search/filter)
- q: string (opsional, search nama)
- kategori_id: int (opsional)

### POST /api/produk
Tambah produk (admin)
- nama: string
- kategori_id: int
- harga: int
- stok: int
- kode_produk: string
- is_active: bool (opsional)

### GET /api/produk/{id}
Detail produk

### PUT /api/produk/{id}
Update produk (admin)

### DELETE /api/produk/{id}
Hapus produk (admin)

---

## Kategori Produk

### GET /api/kategori-produk
List kategori produk (pagination, search)
- q: string (opsional, search nama kategori)

### POST /api/kategori-produk
Tambah kategori produk (admin)
- kategori: string (unik, max 50)

### GET /api/kategori-produk/{id}
Detail kategori produk

### PUT /api/kategori-produk/{id}
Update kategori produk (admin)

### DELETE /api/kategori-produk/{id}
Hapus kategori produk (admin)

---

## User

### GET /api/user
List user
### POST /api/user
Tambah user (hanya admin)

> Hanya user dengan role **admin** yang bisa mengakses endpoint ini untuk menambah user baru (admin/kasir). Jika bukan admin, akan mendapat response 403 Forbidden.
### GET /api/user/{id}
Detail user
### PUT /api/user/{id}
Update user
### DELETE /api/user/{id}
Hapus user

---

## Transaksi

### POST /api/transaksi
Buat transaksi (kasir/admin)
**Body:**
- items: array of {produk_id, jumlah}
- metode_pembayaran: string (cash/debit/dll)
- nominal_bayar: int

**Response:**
- transaksi, detail, kembalian

---

## Laporan

### GET /api/laporan/kasir
Laporan transaksi kasir
### GET /api/laporan/keuangan
Laporan keuangan
### GET /api/laporan/produk-terlaris
Produk terlaris

---

## Dashboard

### GET /api/dashboard
Ringkasan omzet & transaksi

---

## Pengaturan Toko

### GET /api/pengaturan-toko
Lihat pengaturan toko
### PUT /api/pengaturan-toko
Update pengaturan toko

---

**Semua endpoint (kecuali login) butuh Authorization: Bearer {token}**

Silakan gunakan endpoint di atas untuk test di Postman.
