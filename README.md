# ConsuTrade

A C-2-C (consumer-to-consumer) e-commerce platform built for South Africa's informal economy.

## About This Project

South Africa's informal sector is valued at close to R900 billion annually and employs nearly 20% of the working population. Despite this, most informal traders have no access to digital tools built for their context. They rely on WhatsApp groups and Facebook posts to conduct business — not because those are the best options, but because nothing better has been built for them.

ConsuTrade is an attempt to change that. It is a locally built platform that gives informal traders and township entrepreneurs a secure, affordable, and accessible digital space to buy and sell goods directly with one another.

## Who It Is For

- Informal traders and street vendors across South Africa
- Township entrepreneurs running small home-based businesses
- Buyers looking to support local sellers directly

## Key Features

- **User Management:** Registration, login, and role-based access (Buyer, Seller, Admin)
- **Seller Verification:** Identity verification system for trusted sellers
- **Product Management:** List, edit, and manage products with image upload (WebP conversion)
- **Shopping Cart:** Add, remove, and update quantities with stock awareness
- **Secure Payments:** PayFast integration (South Africa's trusted local gateway)
- **Order Management:** Track orders, update status, and manage history
- **Review System:** Rate and review sellers after completed orders
- **Admin Dashboard:** Full platform oversight with user and product management
- **Responsive Design:** Works on desktop, tablet, and mobile devices

## Technology Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (jQuery) |
| Backend | PHP 8.x (Object-Oriented) |
| Database | MySQL |
| Payment Gateway | PayFast (Sandbox/Production) |
| Image Processing | PHP GD Library (WebP conversion) |

## Project Structure



## Object-Oriented Architecture

The platform follows a clean OOP architecture with separation of concerns:

### Domain Classes
- `User`, `Product`, `ProductImage`, `Order`, `Cart`, `Review` — represent business entities
- Contain business logic and validation rules

### Repository Pattern
- `UserRepository`, `ProductRepository`, `OrderRepository`, `CartRepository`, `ReviewRepository`, `CategoryRepository`, `ProductImageRepository`
- Handle all database operations
- Each repository is responsible for a single entity

### Authentication
- `Auth` class manages all session handling and login/logout logic
- Separate session namespaces for buyers, sellers, and admins
- Role-based access control (RBAC)

### API Endpoints
- All AJAX requests go to `php/endpoints/`
- Endpoints are thin controllers that call repository methods
- Return JSON responses for frontend consumption

## Key Features Implemented

### For Buyers
- Browse and search products with filters (category, price, location)
- Add to cart with stock validation
- Secure checkout with PayFast payment gateway
- View order history and track status
- Leave reviews for completed orders

### For Sellers
- Seller dashboard with sales statistics
- Product management (add, edit, delete, suspend/activate)
- Order management with status updates (pending → processing → shipped → completed)
- View buyer information and order details
- Store profile page

### For Admins
- Admin dashboard with platform statistics
- User management (view, suspend, ban)
- Product moderation (view all products)
- Order management across all sellers
- Seller verification approval

## Security Features

- Password hashing using `password_hash()` (BCRYPT)
- Session regeneration on login
- HTTP-only cookies
- Prepared statements for all database queries (prevents SQL injection)
- XSS protection with `htmlspecialchars()` and `escapeHtml()`
- Input validation on all forms
- Role-based access control for admin and seller areas

## Payment Integration

- PayFast payment gateway integration
- Sandbox mode for testing
- ITN (Instant Transaction Notification) handling
- Automatic order status updates upon payment confirmation

## Image Optimisation

- Automatic WebP conversion on upload
- Image resizing to max 1200x1200 pixels
- Optimised file sizes for low-data users
- Default fallback images when none available

## Installation

1. Clone the repository to your web server (XAMPP/WAMP/LAMP)
2. Import `database/consutrade.sql` into MySQL
3. Configure database credentials in `php/config.php`
4. Set up PayFast merchant credentials (sandbox for testing)
5. Ensure `uploads/` directory has write permissions
6. Configure your web server to point to the project root
7. Access the application via `http://localhost/consutrade/`

## Default Test Accounts

| Role | Email | Password |
|------|-------|----------|
| Buyer | buyer@consutrade.co.za | password123 |
| Seller | seller@consutrade.co.za | password123 |
| Admin | kamogelo@consutrade.co.za | password@123 |

## Development Timeline

This project follows a 14-week development schedule across three deliverables:

- **Deliverable 1** (Weeks 1–4): Research and project proposal
- **Deliverable 2** (Weeks 5–10): Design, development, and testing
- **Deliverable 3** (Weeks 11–14): User manual and live presentation

## Legal Compliance

The platform is designed to comply with:
- Electronic Communications and Transactions Act (ECTA) No. 25 of 2002
- Protection of Personal Information Act (POPIA) No. 4 of 2013

## Future Enhancements

- Mobile application (React Native)
- WhatsApp integration for order notifications
- Multi-language support (isiZulu, Afrikaans)
- Offline-first mode for low-connectivity areas
- Advanced analytics for sellers

## Module Information

- **Module:** ITECA3-12 — Web Development and e-Commerce
- **Institution:** Eduvos
- **Student:** Kamogelo Phale
- **Student Number:** EDUV4810351
- **Year:** 2026

## License

This project is for educational purposes as part of the ITECA3-12 module at Eduvos.