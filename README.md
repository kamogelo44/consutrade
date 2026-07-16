# ConsuTrade

A C2C e-commerce platform for South Africa's informal traders.

## Why I Built This

South Africa's informal economy is worth nearly R900 billion and employs about 20% of the workforce. But most informal traders still sell through WhatsApp and Facebook — not because it's the best option, but because nobody built a proper platform for them.

This project was my attempt to fix that. I built ConsuTrade as a C2C marketplace specifically for township traders and home-based sellers. It gives them a digital storefront, a way to get verified, and a secure payment system through PayFast.

## Who It's For

- Informal traders who currently sell through social media
- Township entrepreneurs running small businesses
- Buyers who want to support local sellers

## What I Built

### Buyers can:

- Browse and search products (filter by category, price, location)
- Add items to cart with stock validation
- Checkout securely with PayFast
- View order history and track status
- Leave reviews for completed orders
- Report problematic products
- Register and verify their email address
- Reset forgotten passwords

### Sellers can:

- Manage products (add, edit, delete, suspend)
- Upload up to 4 images per product (first image = main photo, rest = gallery)
- Track orders and update status (pending → processing → shipped → completed)
- View earnings and store stats
- Get verified by uploading ID or proof of address

### Admins can:

- View platform stats (revenue, users, products)
- Manage users (suspend, ban, verify sellers)
- Review uploaded verification documents
- Moderate all products
- Review flagged reports from buyers

## Tech Stack

| Layer    | What I Used                                 |
| -------- | ------------------------------------------- |
| Frontend | HTML5, CSS3, JavaScript, jQuery             |
| Backend  | PHP 7.4 (OOP)                               |
| Database | MySQL                                       |
| Payments | PayFast (sandbox/live)                      |
| Images   | Client-side WebP compression via JavaScript |
| Email    | PHPMailer with SMTP (Gmail)                 |
| Hosting  | InfinityFree (free tier)                    |

## Project Structure

```
consutrade/
├── admin/                      # Seller & admin dashboard
│   ├── css/                    # Dashboard styles
│   ├── includes/               # Sidebar component
│   └── *.php                   # Dashboard pages
├── css/                        # Main stylesheets
│   ├── components.css          # Reusable components
│   ├── layout.css              # Header, footer, hero
│   └── variables.css           # CSS variables
├── includes/                   # PHP components
│   ├── header.php
│   ├── footer.php
│   ├── breadcrumb.php
│   └── flash-message.php
├── js/                         # JavaScript
│   ├── main.js                 # Core functionality
│   ├── products.js             # Product listings & details
│   ├── dashboard.js            # Admin/seller dashboard
│   └── image-compressor.js     # Client-side WebP conversion
├── php/
│   ├── classes/                # All PHP classes
│   │   ├── Domain/             # Business logic (Product, User, Order)
│   │   ├── Repository/         # Database operations
│   │   ├── Services/           # Business services
│   │   └── core/               # Auth, Database, RateLimiter
│   ├── endpoints/              # AJAX handlers
│   ├── cron/                   # Scheduled jobs (cleanup-orders.php)
│   └── config.php              # Configuration
├── uploads/
│   ├── products/               # Product images (WebP)
│   └── profiles/               # Profile pictures
└── *.php                       # Public pages
```

## OOP Architecture

I used the Repository Pattern to separate business logic from database code:

**Domain classes:** `Product`, `User`, `Order`, `Cart`, `Review`, `Report`, `Transaction` — contain business rules only. No SQL.

**Repository classes:** `ProductRepository`, `OrderRepository`, `UserRepository` — handle all database queries.

**Service classes:** `Auth` handles login/session, `PayFastService` handles payments, `EmailService` handles SMTP email delivery, `CartService` handles cart operations, `OrderService` handles order processing.

**Example:** Instead of writing SQL in 5 different files, I call `$productRepo->findById($id)` everywhere. If the query changes, I update it once.

## Security

- Prepared statements for all database queries (SQL injection protection)
- Password hashing with BCRYPT
- HTTP-only cookies
- XSS protection with `htmlspecialchars()`
- Role-based access control (buyer/seller/admin)
- User status: active, suspended, or banned
- Email verification via SMTP (Gmail)
- Rate limiting on API endpoints
- Session timeout

## Multi-Language Support

The platform supports all 11 official South African languages:

- English, Afrikaans, isiZulu, isiXhosa, Sepedi, Setswana, Sesotho, Xitsonga, siSwati, Tshivenda, isiNdebele

Users can switch languages via the dropdown in the header, and the language preference is remembered. The entire UI auto-translates using PHP output buffering.

## Image Management

All product images are:

- Compressed on the client-side using JavaScript (WebP format)
- Uploaded with a max resolution of 1200x1200px
- Stored with the first image as the main product photo
- Up to 4 images allowed per product

## What I Learned (The Hard Way)

- **Plan the database schema before coding.** I kept adding tables mid-build and it cost me time rewriting queries.
- **Use the repository pattern from day one.** Refactoring into it halfway through was painful and took 2 days.
- **Test with real users early.** Reviews and product reporting came from feedback — they weren't in my original plan.
- **Client-side image compression saves server resources.** Instead of relying on GD PHP, I compress images in the browser.

## Installation

This project is for portfolio/demonstration purposes. The SQL schema is not included in this repository.

To run this project locally:

1. Place the folder in your web server's document root (htdocs for XAMPP)
2. Create a MySQL database with the appropriate schema (the structure is defined in the code)
3. Create a `.env` file in the root directory:

```
# Database credentials
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_username
DB_PASS=your_password

# Base URL
BASE_URL=http://localhost/consutrade/

# PayFast Credentials
PAYFAST_MERCHANT_ID=your_id
PAYFAST_MERCHANT_KEY=your_key
PAYFAST_SANDBOX=true

# Email (SMTP) - For verification and password reset emails
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password
SMTP_FROM=your_email@gmail.com
SMTP_FROM_NAME=ConsuTrade

# Maintenance Mode Allowed IPs (optional)
MAINTENANCE_ALLOWED_IPS=your_ip_address
```

4. Make sure `uploads/products/` and `uploads/profiles/` are writable (chmod 755)
5. Access the site at `http://localhost/consutrade/`

**Note:** The database structure and sample data are not included. This is for portfolio demonstration purposes only.

## Module Info

- **Module:** ITECA3-12 — Web Development and e-Commerce
- **Institution:** Eduvos
- **Student:** Kamogelo Phale
- **Student Number:** EDUV4810351
- **Year:** 2026

## License

This project is for educational purposes as part of the ITECA3-12 module at Eduvos. The SQL schema is not included in this repository.
