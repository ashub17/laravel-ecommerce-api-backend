# 🛒 Laravel E-commerce API Backend

A scalable and maintainable **E-commerce REST API** built with Laravel, following **MVC, OOP, and Service-Repository architecture**.
Designed to power a Vue.js admin panel and storefront frontend.

---

## 🚀 Features

### 🔐 Authentication & Authorization

* Laravel Sanctum token-based authentication
* Register / Login / Logout
* Protected routes with `auth:sanctum`
* Role-based access (`admin` vs `user`)

---

### 📂 Categories

* Admin CRUD operations
* Public category listing

---

### 🛍️ Products

* Full CRUD (admin)
* Slug-based product URLs
* Featured image + gallery images
* Stock management
* Price & sale price support
* Pagination & filtering ready

---

### 🛒 Cart System

* One cart per user
* Add / update / remove items
* Auto price snapshot
* Stock validation
* Cart subtotal & total items

---

### 📦 Orders & Checkout

* Checkout from cart
* Shipping & billing addresses
* Order item snapshot (price locked)
* Stock deduction
* Cart auto-clear after checkout
* Customer order history

---

### 🧾 Admin Order Management

* View all orders
* View single order
* Update order status
* Update payment status

---

### 🧱 Content Management System (CMS)

#### Banners

* Create / update / delete banners
* Image upload support
* Sort order + active toggle

#### Content Blocks

* Dynamic content via unique keys
* JSON meta support
* Used for homepage, footer, etc.

---

### 📊 Admin Dashboard

* Total users
* Total products
* Total categories
* Total orders
* Total revenue
* Low stock products
* Recent orders
* Top selling products

---

## 🧠 Architecture

This project follows clean architecture principles:

* MVC (Model-View-Controller)
* Service Layer (business logic)
* Repository Layer (data access)
* Form Requests (validation)
* Eloquent ORM
* RESTful API design

---

## 🛠️ Tech Stack

* Laravel 12+
* PHP 8.2+
* MySQL
* Laravel Sanctum
* Eloquent ORM
* Storage (public disk)

---

## 📁 Project Structure (Key Parts)

```text
app/
 ├── Http/
 │   ├── Controllers/
 │   │   ├── Api/
 │   │   └── Admin/
 │   ├── Requests/
 │
 ├── Models/
 ├── Services/
 ├── Repositories/

routes/
 ├── api.php

storage/
 └── app/public/
```

---

## ⚙️ Installation

### 1. Clone project

```bash
git clone <your-repo-url>
cd ecommerce-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Setup environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure database

Edit `.env`:

```env
DB_DATABASE=your_db
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Storage link

```bash
php artisan storage:link
```

### 7. Run server

```bash
php artisan serve
```

---

## 🔑 Authentication

Use Bearer token in headers:

```text
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

---

## 📡 API Overview

### Auth

```text
POST   /api/auth/register
POST   /api/auth/login
GET    /api/auth/me
POST   /api/auth/logout
```

---

### Products

```text
GET    /api/products
GET    /api/products/{slug}
```

---

### Categories

```text
GET    /api/categories
```

---

### Cart

```text
GET    /api/cart
POST   /api/cart
PUT    /api/cart/{id}
DELETE /api/cart/{id}
DELETE /api/cart
```

---

### Orders (User)

```text
POST   /api/orders
GET    /api/orders
GET    /api/orders/{id}
```

---

### Admin Routes

(All require admin role)

#### Categories & Products

```text
/api/admin/categories
/api/admin/products
```

#### Orders

```text
GET    /api/admin/orders
GET    /api/admin/orders/{order}
PUT    /api/admin/orders/{order}
```

#### Dashboard

```text
GET    /api/admin/dashboard
```

#### Banners

```text
/api/admin/banners
```

#### Content Blocks

```text
/api/admin/content-blocks
```

---

### Public CMS

```text
GET /api/banners
GET /api/content-blocks
GET /api/content-blocks/{key}
```

---

## 🖼️ File Storage

Uploaded files are stored in:

```text
storage/app/public/
```

Accessible via:

```text
http://127.0.0.1:8000/storage/...
```

---

## 🧪 Testing

Use Postman or any API client.

Make sure to:

* Include `Accept: application/json`
* Include Bearer token for protected routes

---

## 📌 Future Improvements

* Payment integration (Stripe / SSLCommerz)
* Wishlist system
* Product reviews & ratings
* Advanced search & filters
* Notifications (email/SMS)
* API Resource transformers
* Caching (Redis)

---

## 🤝 Contribution

Feel free to fork and extend the project.

---

## 📄 License

This project is open-source and available under the MIT License.
