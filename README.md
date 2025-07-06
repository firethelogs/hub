<?php
// README.md
# Dark PHP Wallet Store

A modern, dark-themed PHP website with user registration, authentication, admin panel, wallet, and locked content store. Built with pure PHP and SQLite.

## Features
- User registration, login, profile
- Secure password hashing and session management
- Admin panel: user management, credit management, store management
- Wallet system with transaction history
- Store with locked content (buy to unlock)
- Responsive, modern dark UI

## Setup
1. Run `php db/init_db.php` once to initialize the database.
2. Create an admin user directly in the database (set `is_admin=1` for a user in the `users` table).
3. Serve the project with a PHP server: `php -S localhost:8000`

## Security Notes
- All passwords are hashed
- Sessions are used for authentication
- Admin and user areas are separated

---

MIT License
