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

- Seller registration with identity verification
- Product listing with image upload
- Secure payments via PayFast (South Africa's trusted local gateway)
- Shopping cart and order management
- Low-data optimisation (under 3MB per transaction)
- Admin dashboard with Role-Based Access Control (RBAC)
- Mobile-responsive design

## Technology Stack

| Layer | Technology |
|---|---|
| Structure | HTML5 |
| Styling | CSS3 |
| Interactivity | JavaScript |
| Server-side logic | PHP |
| Database | MySQL |
| Payment Gateway | PayFast |
| Hosting | InfinityFree (pilot phase) |

## Project Structure

```
└───consutrade
    │   index.html
    │   README.md
    │
    ├───css
    │       style.css
    │
    ├───database
    │       consutrade.sql
    │
    ├───design
    │   └───wireframes
    │       ├───admin-website
    │       │       AdminDashboard - Desktop.png
    │       │       AdminDashboard - Phone.png
    │       │       AdminDashboard - Tablet.png
    │       │
    │       └───main-website
    │               Homepage_Prototype - Desktop.png
    │               HomePage_Prototype - Tablet.png
    │               Homepage_Prototype- Phone.png
    │               LoginModal - Desktop.png
    │               LoginModal - Phone.png
    │               LoginModal - Tablet.png
    │               Product Detail - Desktop.png
    │               Product Detail - Phone.png
    │               Product Detail - Tablet.png
    │               Product Listing - Desktop.png
    │               Product Listing - Phone.png
    │               Product Listing - Tablet.png
    │               Register Modal - Desktop.png
    │               Register Modal - Phone.png
    │               Register Modal - Tablet.png
    │               Sellerdashboard - Desktop.png
    │               Sellerdashboard - Phone.png
    │               Sellerdashboard - Tablet.png
    │
    ├───images
    │   └───icons
    │           search-svgrepo-com.svg
    │           shopping-cart-01-svgrepo-com.svg
    │
    ├───js
    │       main.js
    │
    └───php
            config.php
            login.php
            register.php
```

## Development Timeline

This project follows a 14-week development schedule across three deliverables:

- **Deliverable 1** (Weeks 1–4): Research and project proposal
- **Deliverable 2** (Weeks 5–14): Design, development, and testing
- **Deliverable 3** (Final week): User manual and live presentation

## Legal Compliance

The platform is designed to comply with:
- Electronic Communications and Transactions Act (ECTA) No. 25 of 2002
- Protection of Personal Information Act (POPIA) No. 4 of 2013

## Module Information

- **Module:** ITECA3-12 — Web Development and e-Commerce
- **Institution:** Eduvos
- **Student:** Kamogelo Phale
- **Student Number:** EDUV4810351
