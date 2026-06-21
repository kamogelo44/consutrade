# ConsuTrade

A C-2-C e-commerce platform I built for South Africa's informal traders.

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

### Sellers can:

- Manage products (add, edit, delete, suspend)
- Upload up to 4 images per product (first image = main photo)
- Track orders and update status (pending → processing → shipped → completed)
- View earnings and store stats
- Get verified by uploading ID or proof of address

### Admins can:

- View platform stats (revenue, users, products)
- Manage users (suspend, ban, verify sellers)
- Moderate all products
- Review flagged reports from buyers

## Tech Stack

| Layer    | What I Used                     |
| -------- | ------------------------------- |
| Frontend | HTML5, CSS3, JavaScript, jQuery |
| Backend  | PHP 7.4 (OOP)                   |
| Database | MySQL                           |
| Payments | PayFast (sandbox/live)          |
| Images   | GD Library (WebP conversion)    |
| Hosting  | InfinityFree (free tier)        |

## Project Structure

consutrade/
├── admin/ # Seller & admin dashboard
│ ├── css/ # Dashboard styles
│ ├── includes/ # Sidebar component
│ └── _.php # Dashboard pages
├── css/ # Main stylesheets
│ ├── components.css # Reusable components
│ ├── layout.css # Header, footer, hero
│ └── variables.css # CSS variables
├── includes/ # PHP components
│ ├── header.php
│ ├── footer.php
│ ├── breadcrumb.php
│ └── flash-message.php
├── js/ # JavaScript
│ ├── main.js # Core functionality
│ ├── products.js # Product listings & details
│ └── dashboard.js # Admin/seller dashboard
├── php/
│ ├── classes/ # All PHP classes
│ │ ├── Domain/ # Business logic (Product, User, Order)
│ │ └── Repository/ # Database operations
│ ├── endpoints/ # AJAX handlers
│ └── config.php # Configuration
├── uploads/
│ ├── products/ # Product images (WebP)
│ └── profiles/ # Profile pictures
└── _.php # Public pages

## OOP Architecture

I used the Repository Pattern to separate business logic from database code:

**Domain classes:** `Product`, `User`, `Order`, `Cart`, `Review`, `Report` — contain business rules only. No SQL.

**Repository classes:** `ProductRepository`, `OrderRepository`, `UserRepository` — handle all database queries.

**Service classes:** `Auth` handles login/session, `PayFastService` handles payments.

**Example:** Instead of writing SQL in 5 different files, I call `$productRepo->getProductObject($id)` everywhere. If the query changes, I update it once.

## Security

- Prepared statements for all database queries (SQL injection protection)
- Password hashing with BCRYPT
- HTTP-only cookies
- XSS protection with `htmlspecialchars()`
- Role-based access control (buyer/seller/admin)
- User status: active, suspended, or banned

## What I Learned (The Hard Way)

- Plan the database schema before coding. I kept adding tables mid-build and it cost me time.
- Use the repository pattern from day one. Refactoring into it halfway through was painful.
- Test with real users early. Reviews and product reporting came from feedback — they weren't in my original plan.

## What's Missing (For Now)

- Email verification on registration
- Forgot password / password reset
- Admin document viewer (can't see uploaded verification files)
- Auto-cleanup for expired pending orders
- Multi-language support (Zulu, Afrikaans, Xhosa)

These are all planned for future updates. I know how to build each one — I just ran out of time.

## Installation

1. Place the folder in your web server's document root (htdocs for XAMPP)
2. Import the provided SQL file into MySQL
3. Create a `.env` file in the root directory:

DB_HOST=localhost
DB_NAME=consutrade
DB_USER=your_username
DB_PASS=your_password
BASE_URL=http://localhost/consutrade/
PAYFAST_MERCHANT_ID=your_id
PAYFAST_MERCHANT_KEY=your_key
PAYFAST_SANDBOX=true

4. Make sure `uploads/products/` and `uploads/profiles/` are writable
5. Access the site at `http://localhost/consutrade/`

## Module Info

- **Module:** ITECA3-12 — Web Development and e-Commerce
- **Institution:** Eduvos
- **Student:** Kamogelo Phale
- **Student Number:** EDUV4810351
- **Year:** 2026

## License

This project is for educational purposes as part of the ITECA3-12 module at Eduvos.
