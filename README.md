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
| Backend  | PHP (OOP)                       |
| Database | MySQL                           |
| Payments | PayFast (sandbox/live)          |
| Images   | GD Library (WebP conversion)    |
| Hosting  | InfinityFree (free tier)        |

## Project Structure

C:.
└───consutrade
│ .env
│ .gitignore
│ about.php
│ cart.php
│ checkout.php
│ index.php
│ init.php
│ jsconfig.json
│ my-orders.php
│ order-confirmation.php
│ product-details.php
│ product-listings.php
│ profile.php
│ README.md
│ search-results.php
│ sell.php
│ seller-profile-public.php
│  
 ├───admin
│ │ add-product.php
│ │ admin-dashboard.php
│ │ admin-profile.php
│ │ all-orders.php
│ │ all-products.php
│ │ edit-product.php
│ │ flagged-listings.php
│ │ login.php
│ │ my-products.php
│ │ seller-dashboard.php
│ │ seller-orders.php
│ │ seller-profile.php
│ │ users.php
│ │  
 │ ├───css
│ │ dashboard-layout.css
│ │ sidebar.css
│ │  
 │ ├───includes
│ │ sidebar.php
│ │  
 │ └───js
│ dashboard.js
│  
 ├───css
│ animations.css
│ components.css
│ forms.css
│ layout.css
│ main.css
│ old-style.css
│ orders.css
│ products.css
│ reset.css
│ responsive.css
│ variables.css
│  
 ├───design
│ └───wireframes
│ │ AdminDashboard - Desktop.png
│ │ AdminDashboard - Phone.png
│ │ AdminDashboard - Tablet.png
│ │  
 │ ├───admin-website
│ └───main-website
│ Homepage_Prototype - Desktop.png
│ HomePage_Prototype - Tablet.png
│ Homepage_Prototype- Phone.png
│ LoginModal - Desktop.png
│ LoginModal - Phone.png
│ LoginModal - Tablet.png
│ Product Detail - Desktop.png
│ Product Detail - Phone.png
│ Product Detail - Tablet.png
│ Product Listing - Desktop.png
│ Product Listing - Phone.png
│ Product Listing - Tablet.png
│ Register Modal - Desktop.png
│ Register Modal - Phone.png
│ Register Modal - Tablet.png
│ Sellerdashboard - Desktop.png
│ Sellerdashboard - Phone.png
│ Sellerdashboard - Tablet.png
│  
 ├───fonts
│ Poppins-Bold.ttf
│ Poppins-Regular.ttf
│  
 ├───images
│ │ default-product.png
│ │ hero-img-phones.webp
│ │ hero-img-tablets.png
│ │ hero-img-tablets.webp
│ │ hero-img.webp
│ │  
 │ └───icons
│ add-svgrepo-com.svg
│ ban-svgrepo-com.svg
│ buy-cash-finance-svgrepo-com.svg
│ camera-svgrepo-com.svg
│ cart-check-svgrepo-com.svg
│ cash-atm-svgrepo-com.svg
│ chevron-down-svgrepo-com.svg
│ clock-svgrepo-com.svg
│ comment-svgrepo-com.svg
│ contact-location.svg
│ continue-svgrepo-com.svg
│ dashboard-svgrepo-com.svg
│ delete-svgrepo-com.svg
│ delivery-svgrepo-com.svg
│ dismiss-svgrepo-com.svg
│ edit-svgrepo-com.svg
│ email-svgrepo-com.svg
│ eye-close-svgrepo-com.svg
│ eye-open-svgrepo-com.svg
│ facebook-svgrepo-com.svg
│ filter-svgrepo-com.svg
│ form-close-svgrepo-com.svg
│ hide-svgrepo-com.svg
│ instagram-svgrepo-com.svg
│ linkedin-svgrepo-com.svg
│ location-svgrepo-com.svg
│ logout-svgrepo-com.svg
│ money-total-line-svgrepo-com.svg
│ not-verified-svgrepo-com.svg
│ noun-on-stock-7633735.svg
│ Payfast logo.svg
│ phone-call-svgrepo-com.svg
│ phone-number.svg
│ photos-filled-svgrepo-com.svg
│ pin-location-svgrepo-com.svg
│ product-catalog-svgrepo-com.svg
│ products-svgrepo-com.svg
│ profile-svgrepo-com.svg
│ register-svgrepo-com.svg
│ right-arrow-1-svgrepo-com.svg
│ search-svgrepo-com.svg
│ secure-card-svgrepo-com.svg
│ sell-svgrepo-com.svg
│ shopping-cart-01-svgrepo-com.svg
│ show-svgrepo-com.svg
│ twitter-svgrepo-com.svg
│ users-svgrepo-com.svg
│ valid-document-svgrepo-com.svg
│ verified-svgrepo-com.svg
│ warning-svgrepo-com.svg
│ whatsapp-svgrepo-com.svg
│  
 ├───includes
│ breadcrumb.php
│ empty-state.php
│ flash-message.php
│ footer.php
│ functions.php
│ header.php
│ modal-errors.php
│ order-amount.php
│ order-card.php
│ order-party-info.php
│ order-status-badge.php
│ product-helpers.php
│ search-bar.php
│ session-vars.php
│  
 ├───js
│ jquery-3.7.1.min.js
│ main.js
│ products.js
│  
 ├───php
│ │ config.php
│ │  
 │ ├───classes
│ │ Admin.php
│ │ Auth.php
│ │ Buyer.php
│ │ Cart.php
│ │ CartRepository.php
│ │ Category.php
│ │ CategoryRepository.php
│ │ Database.php
│ │ Order.php
│ │ OrderItem.php
│ │ OrderRepository.php
│ │ PayFastService.php
│ │ Product.php
│ │ ProductImage.php
│ │ ProductImageRepository.php
│ │ ProductImageService.php
│ │ ProductRepository.php
│ │ Report.php
│ │ ReportRepository.php
│ │ Review.php
│ │ ReviewRepository.php
│ │ Seller.php
│ │ SellerVerification.php
│ │ Transaction.php
│ │ TransactionRepository.php
│ │ User.php
│ │ UserRepository.php
│ │  
 │ └───endpoints
│ add-product.php
│ add-to-cart.php
│ cancel-order.php
│ change-password.php
│ delete-account.php
│ delete-product.php
│ edit-product.php
│ get-all-orders.php
│ get-all-products.php
│ get-cart.php
│ get-flagged-listings.php
│ get-my-orders.php
│ get-order-details.php
│ get-order-status.php
│ get-product.php
│ get-products.php
│ get-recent-orders.php
│ get-reviews.php
│ get-seller-products.php
│ get-seller-recent-orders.php
│ get-user-stats.php
│ get-users.php
│ login.php
│ logout.php
│ payfast-notify.php
│ place-order.php
│ register.php
│ remove-from-cart.php
│ remove-gallery-image.php
│ report-product.php
│ search-products.php
│ set-primary-image.php
│ submit-review.php
│ update-cart.php
│ update-order-status.php
│ update-product-status.php
│ update-profile.php
│ update-report-status.php
│ update-review.php
│ update-user-status.php
│ update-user-verification.php
│ upload-verification.php
│ verify-seller.php
│  
 └───uploads
├───products
│  
 └───profiles

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
