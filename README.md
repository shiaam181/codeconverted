# ShopMart PHP - E-commerce Platform

A PHP conversion of the React.js (TanStack Router + Supabase) e-commerce application.

## Overview

This is a Flipkart-style online shopping platform built with pure PHP. It connects to Supabase as the backend database via REST API, maintaining the same functionality as the original React.js application.

## Features

- **Homepage** — Dynamic layout with banner carousel, category strip, and infinite product grid
- **Product Detail** — Full product page with image gallery, variants (color/size), pricing, delivery info
- **Cart** — Session-based shopping cart with quantity management and price summary
- **Checkout** — Multi-step checkout with address form, order summary, and UPI payment
- **Search** — Real-time product search with suggestions
- **Categories** — Category-based product browsing with sorting options
- **Order Status** — Order tracking with UPI payment integration
- **Multi-tenant** — Support for multiple store tenants via URL routing (`/t/{slug}/...`)

## Tech Stack

- **Language:** PHP 8.0+
- **Backend:** Supabase REST API (PostgreSQL)
- **Frontend:** Vanilla HTML/CSS (Flipkart-style responsive design)
- **Routing:** Single entry-point (`index.php`) with URL rewriting
- **Cart:** PHP Sessions
- **Payment:** UPI deep-link integration (PhonePe, GPay, Paytm, BHIM)

## Project Structure

```
codeconverted/
├── index.php                 # Main router / entry point
├── .htaccess                 # Apache URL rewrite rules
├── .env.example              # Environment variables template
├── config/
│   ├── app.php              # Application constants
│   └── database.php         # Supabase API client
├── includes/
│   ├── helpers.php          # Utility functions (format, UPI, etc.)
│   ├── cart.php             # Cart management (session-based)
│   └── models.php           # Data access layer (Supabase queries)
├── pages/
│   ├── home.php             # Homepage
│   ├── product.php          # Product detail page
│   ├── cart.php             # Shopping cart page
│   ├── checkout.php         # Checkout page
│   ├── place-order.php      # Order placement handler
│   ├── search.php           # Search page
│   ├── category.php         # Category listing page
│   ├── order.php            # Order confirmation page
│   └── 404.php              # Not found page
├── templates/
│   ├── header.php           # Site header template
│   ├── footer.php           # Site footer template
│   └── components/
│       ├── banner-carousel.php   # Banner slider component
│       ├── category-strip.php    # Category navigation
│       ├── product-card.php      # Product card component
│       └── product-grid.php      # Product grid layout
└── assets/
    └── css/
        └── style.css        # All styles (Flipkart-inspired)
```

## Setup

### Requirements

- PHP 8.0+
- cURL extension enabled
- Apache with mod_rewrite OR PHP built-in server
- Active Supabase project (same database as the React app)

### Installation

1. Clone this repository
2. Copy `.env.example` to `.env` and fill in your Supabase credentials:
   ```
   SUPABASE_URL=https://your-project.supabase.co
   SUPABASE_KEY=your-anon-key
   ```
3. Start the PHP development server:
   ```bash
   php -S localhost:8000
   ```
4. Open `http://localhost:8000` in your browser

### Apache Setup

If using Apache, ensure mod_rewrite is enabled:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

The `.htaccess` file handles all URL routing automatically.

### Nginx Setup

For Nginx, add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Route Mapping

| React Route | PHP Route | Description |
|---|---|---|
| `/` | `/` | Homepage |
| `/product/:slug` | `/product/{slug}` | Product detail |
| `/cart` | `/cart` | Shopping cart |
| `/checkout` | `/checkout` | Checkout |
| `/search` | `/search?q=term` | Search |
| `/category/:slug` | `/category/{slug}` | Category page |
| `/order/:id` | `/order/{id}` | Order status |
| `/t/:slug/*` | `/t/{slug}/*` | Tenant routes |

## Conversion Notes

- **React State → PHP Sessions:** Cart data stored in `$_SESSION` instead of `localStorage`
- **TanStack Query → Direct API calls:** Supabase queries made directly via cURL
- **Client-side routing → Server-side routing:** All routing handled in `index.php`
- **Tailwind CSS → Custom CSS:** Equivalent styles written in vanilla CSS
- **React Components → PHP includes:** Components converted to reusable PHP template files
- **UPI Payment Logic:** All UPI deep-link generation logic preserved exactly from React
