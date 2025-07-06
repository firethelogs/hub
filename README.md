<?php
// README.md
# JaxxyCC Store

A modern, dark-themed PHP website with user registration, authentication, admin panel, wallet system, and premium content store. Built with pure PHP and SQLite.

## Features
- User registration, login, profile management
- Secure password hashing and session management
- Admin panel: user management, credit management, store management
- Wallet system with transaction history
- Premium content store with limited/unlimited stock items
- Responsive, modern dark UI with beautiful animations

## Setup
1. Run `php db/init_db.php` once to initialize the database.
2. Run `php db/create_admin.php` to create an admin user.
3. Run `php db/create_test_data.php` to add test data (optional).
4. Serve the project with a PHP server: `php -S localhost:8000`

## Login Credentials
**Admin:**
- Username: `admin`
- Password: `admin123`

**Test User:**
- Username: `testuser`
- Password: `password123`

## Security Notes
- All passwords are hashed using PHP's password_hash()
- Sessions are used for authentication
- Admin and user areas are separated
- Stock limits prevent overselling

---

© 2025 JaxxyCC Store - Premium Digital Content Platform
