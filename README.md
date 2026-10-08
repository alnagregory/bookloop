# BOOKLOOP – Online Book Exchange & Marketplace

> **“Buy. Sell. Exchange. Learn.”**

A modern, fully functional, college-focused web marketplace where students can buy, sell, and exchange used academic textbooks with their peers. Built with native PHP (PDO), MySQL, HTML5, CSS3, and JavaScript for seamless execution under **XAMPP on Windows**.

---

## 📖 Table of Contents

1. [Overview](#overview)
2. [Key Features](#key-features)
3. [Technology Stack](#technology-stack)
4. [Folder Structure](#folder-structure)
5. [Database Architecture](#database-architecture)
6. [Installation & Setup with XAMPP](#installation--setup-with-xampp)
7. [Default User & Admin Accounts](#default-user--admin-accounts)
8. [End-to-End Workflow Demonstration](#end-to-end-workflow-demonstration)
9. [Security Highlights](#security-highlights)
10. [Troubleshooting Guide](#troubleshooting-guide)
11. [Academic Viva / Demonstration Tips](#academic-viva--demonstration-tips)

---

## 1. Overview

Every semester, college students spend substantial money on textbooks that are only needed for a few months. Once finals finish, those books remain idle while incoming juniors scramble to purchase expensive copies. 

**BookLoop** creates a secure, hyper-local peer-to-peer campus marketplace. Students can:
* Sell textbooks at student-friendly prices.
* Swap current-semester books directly for upcoming semester syllabus books.
* Communicate privately without exposing personal phone numbers or email addresses publicly.
* Propose official purchase and exchange requests, meet safely on campus, and leave seller feedback.

---

## 2. Key Features

### For Students
* **Authentication & Profile:** Secure registration, encrypted passwords, roll ID verification, and profile management with avatar uploads.
* **Textbook Listings:** Add, edit, and delete listings with course metadata (Department, Subject, Semester, Edition, Condition, Meeting Spot) and live cover photo preview.
* **Marketplace Discovery:** Live search by title, author, ISBN, or subject; filter by department, semester, condition, and price; sort by newest, oldest, or price.
* **Wishlist:** One-click save books to wishlist with live availability status tracking.
* **Purchase System:** Send official purchase requests with counter-offers and campus meeting location preferences.
* **Barter / Exchange System:** Select an approved textbook from your own inventory to offer in barter trade.
* **Private Chat System:** Real-time in-app conversation thread linked directly to the textbook listing with instant messaging and message read status.
* **Trust & Safety:** Block abusive users, report suspicious listings, and rate sellers with 1–5 stars and verified feedback after completed transactions.
* **Notification Center:** Real-time alerts for incoming requests, status acceptances, new messages, and approval updates.

### For Administrators
* **Dedicated Admin Portal (`/admin`):** Separate credentials with role-based authorization guard.
* **Listing Moderation Queue:** Review student book listings with `Approve`, `Reject`, or `Delete` controls before public marketplace display.
* **User Management:** Search, inspect, activate, deactivate, or suspend student accounts.
* **Incident Reports:** Review flagged listings, offensive messages, and scam grievances with quick resolution actions.
* **Category Management:** Manage campus academic departments, subjects, and semesters dynamically from the database.
* **System Diagnostics:** Live server health checks, directory write permissions, and admin password management.

---

## 3. Technology Stack

* **Frontend:** HTML5, Modern CSS3 (CSS Variables, Flexbox, Grid, Responsive Media Queries), Vanilla JavaScript (No React/Node/Webpack dependencies).
* **Backend:** PHP 8+ (Pure Procedural & Modular PHP, PDO with Prepared Statements).
* **Database:** MySQL / MariaDB (Normalized schema with foreign keys and indexes).
* **Server Environment:** Apache & MySQL via **XAMPP for Windows**.

---

## 4. Folder Structure

```text
bookloop/
│
├── index.php                 # Modern Homepage (Hero, Search, Categories, Featured Books, Stats)
├── login.php                 # Secure Student / General Login
├── register.php              # Student Registration with Validation
├── logout.php                # Session Destruction & Clean Logout
├── forgot-password.php       # Password Reset Flow
├── books.php                 # Marketplace Browser, Filter & Search
├── book-details.php          # Detailed Textbook View & Buy/Exchange Requests
├── about.php                 # About BookLoop Mission & Values
├── contact.php               # Campus Help Desk & Contact Form
├── api-wishlist.php          # Smooth AJAX Wishlist Endpoint
│
├── dashboard.php             # Student Dashboard with Live Metrics & Recent Feeds
├── profile.php               # Public & Personal Student Profile View
├── edit-profile.php          # Edit Student Info, Avatar & Password
├── add-book.php              # Create New Textbook Listing (Image Upload + Preview)
├── edit-book.php             # Edit Own Book Listing & Availability Status
├── my-listings.php           # Manage Own Listings (Edit, Delete, Mark Sold, Mark Exchanged)
├── wishlist.php              # Saved Wishlist Items with Real-time Status
├── my-requests.php           # Sent Requests Tracker & Cancellation
├── received-requests.php     # Received Requests Manager (Accept, Reject, Complete Deal)
├── messages.php              # Conversations Inbox Overview
├── conversation.php          # Private Chat Thread, Message History, Block & Report
├── notifications.php         # Real-time Activity Alerts Center
├── my-reviews.php            # Seller Ratings Received & Transaction Review Form
│
├── config/
│   └── database.php          # PDO Database Connection & Global Constants
│
├── includes/
│   ├── header.php            # Reusable HTML Header & Resource Inclusions
│   ├── footer.php            # Reusable HTML Footer & Scripts
│   ├── navbar.php            # Responsive Navbar with Notification Badges & User Dropdown
│   ├── auth.php              # Role & Login Guard Functions
│   └── functions.php         # Reusable Helper Functions (CSRF, Badges, TimeAgo, etc.)
│
├── css/
│   ├── style.css             # Unified Modern Academic CSS Design System
│   └── responsive.css        # Mobile, Tablet & Desktop Media Queries
│
├── js/
│   └── script.js             # Client-side Interactions, Validation, Image Preview & AJAX
│
├── uploads/
│   ├── books/                # Uploaded Textbook Cover Images (SVGs / JPGs / PNGs)
│   └── profiles/             # Uploaded Student Profile Avatars
│
├── admin/
│   ├── login.php             # Dedicated Administrator Login
│   ├── dashboard.php         # Admin Statistics & Recent Logs
│   ├── users.php             # Student User Management & Suspensions
│   ├── user-details.php      # User Audit, Listings & Filed Reports
│   ├── books.php             # Listings Queue & Approval Controls
│   ├── book-details.php      # Full Listing Moderation & Status Updates
│   ├── requests.php          # Platform-wide Transaction Monitoring
│   ├── reports.php           # Student Grievances & Moderation
│   ├── categories.php        # Manage Departments, Subjects & Semesters
│   ├── settings.php          # Admin Password, Diagnostics & Directory Checks
│   └── includes/
│       ├── header.php        # Admin Sidebar & Topbar Component
│       └── footer.php        # Admin Layout Footer
│
├── database/
│   └── bookloop.sql          # Complete Database Schema, Relationships & Seed Data
│
└── README.md                 # Complete Project Documentation
```

---

## 5. Database Architecture

The MySQL database is named **`bookloop`** and contains 10 normalized tables:

1. **`users`** (`user_id`, `name`, `email`, `password`, `phone`, `college`, `department`, `semester`, `student_id`, `profile_image`, `role`, `status`, `created_at`)
2. **`categories`** (`category_id`, `category_name`, `category_type`, `status`)
3. **`books`** (`book_id`, `user_id`, `title`, `author`, `isbn`, `subject`, `department`, `semester`, `edition`, `publisher`, `original_price`, `price`, `condition`, `listing_type`, `description`, `image`, `location`, `preferred_contact`, `status`, `approval_status`, `created_at`, `updated_at`)
4. **`requests`** (`request_id`, `book_id`, `buyer_id`, `seller_id`, `request_type`, `offered_price`, `offered_book_id`, `meeting_place`, `contact_method`, `message`, `status`, `created_at`, `updated_at`)
5. **`wishlist`** (`wishlist_id`, `user_id`, `book_id`, `created_at`)
6. **`messages`** (`message_id`, `sender_id`, `receiver_id`, `book_id`, `message`, `is_read`, `created_at`)
7. **`notifications`** (`notification_id`, `user_id`, `message`, `type`, `related_id`, `is_read`, `created_at`)
8. **`reviews`** (`review_id`, `reviewer_id`, `seller_id`, `request_id`, `rating`, `review`, `created_at`)
9. **`reports`** (`report_id`, `reporter_id`, `book_id`, `reported_user_id`, `reason`, `description`, `status`, `created_at`)
10. **`blocked_users`** (`block_id`, `blocker_id`, `blocked_id`, `created_at`)

---

## 6. Installation & Setup with XAMPP

### Step 1: Install & Launch XAMPP
1. Download and install **XAMPP** from [apachefriends.org](https://www.apachefriends.org/).
2. Open the **XAMPP Control Panel**.
3. Click **Start** for both **Apache** and **MySQL**.

### Step 2: Place Project in `htdocs`
Ensure the project folder is placed at:
```text
C:\xampp\htdocs\bookloop
```

### Step 3: Import the Database
1. Open your browser and navigate to **phpMyAdmin**:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click **New** on the left menu and create a database named:
   ```text
   bookloop
   ```
   (Collation: `utf8mb4_unicode_ci` or default).
3. Click the newly created `bookloop` database.
4. Click on the **Import** tab at the top.
5. Choose the SQL file located at:
   ```text
   C:\xampp\htdocs\bookloop\database\bookloop.sql
   ```
6. Click **Import** (or **Go**). All 10 tables and pre-populated sample records will be imported successfully!

### Step 4: Verify Database Configuration
The database connection settings are located in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');       // Default password in XAMPP is empty
define('DB_NAME', 'bookloop');
```

### Step 5: Launch the Application
Open your web browser and navigate to:
```text
http://localhost/bookloop/
```

---

## 7. Default User & Admin Accounts

The database comes pre-seeded with verified test accounts so the platform is ready for immediate viva/demo evaluation:

### Administrator Account
* **URL:** `http://localhost/bookloop/admin/login.php`
* **Email:** `admin@bookloop.com`
* **Password:** `Admin@123`

### Sample Student Accounts
All student accounts use the password: `Student@123`

| Name | Email | Department & Sem | Primary Role |
| :--- | :--- | :--- | :--- |
| **Rahul Sharma** | `rahul@college.edu` | CSE (Semester 4) | Has listings (DBMS, OS, DSA) |
| **Priya Patel** | `priya@college.edu` | IT (Semester 5) | Has listings (Computer Networks, OOP) |
| **Amit Verma** | `amit@college.edu` | Electronics (Sem 3) | Has listings (Digital Electronics, Maths) |
| **Sneha Reddy** | `sneha@college.edu` | CSE (Semester 6) | Has listings (Compiler Design, AI, SE) |

---

## 8. End-to-End Workflow Demonstration

### Workflow A: Purchasing a Textbook
1. Log in as **Priya Patel** (`priya@college.edu` / `Student@123`).
2. Search for `Database System Concepts` on `books.php` or the homepage.
3. Open the book details page.
4. Click **Contact Seller** to discuss the condition privately with Rahul.
5. Click **Request to Buy**, enter an offered price (e.g., `₹420`), specify a campus meeting spot (e.g., `Library Lawn`), and submit.
6. Log out and log in as **Rahul Sharma** (`rahul@college.edu` / `Student@123`).
7. Observe the notification badge in the navbar and open **Received Requests**.
8. Click **Accept** on Priya's request.
9. Meet Priya on campus and click **Complete Deal**. The textbook is automatically marked **Sold** and any other pending requests are closed.
10. Log back in as Priya, go to **Sent Requests**, and click **Rate Seller** to award 5 stars and post feedback!

### Workflow B: Book Barter / Exchange
1. Log in as **Rahul Sharma** (`rahul@college.edu` / `Student@123`).
2. Browse to **Compilers: Principles, Techniques, & Tools** listed by **Sneha Reddy**.
3. Click **Request Exchange**. Select one of Rahul's available textbooks to offer in trade.
4. Sneha receives an exchange alert, reviews the offered book, and accepts.
5. Upon meeting, the seller marks the deal as **Complete**, updating both textbooks to **Exchanged**.

### Workflow C: Administrative Moderation
1. As a student, list a new textbook via `add-book.php`.
2. Notice that the listing initial status is **Pending**.
3. Log in to the Admin Portal (`/admin/login.php`) using `admin@bookloop.com`.
4. Inspect the pending queue on **Textbook Listings**, view full details, and click **Approve**.
5. The student receives an instant in-app notification and the book appears live on the public marketplace.

---

## 9. Security Highlights

* **Prepared Statements (PDO):** 100% of SQL queries utilize parameterized placeholders, preventing SQL Injection vulnerabilities.
* **Password Hashing:** Passwords are encrypted using PHP's industry-standard `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt) and checked with `password_verify()`.
* **XSS Sanitization:** All user-generated content rendered in HTML is sanitized with `htmlspecialchars()` using UTF-8 flags.
* **CSRF Token Validation:** All critical POST actions (registration, password change, book editing, transactions, reports) validate cryptographic anti-CSRF session tokens.
* **Role-Based Access Control (RBAC):** Admin controllers enforce `requireAdmin()`; unauthenticated requests to student sections are redirected with `requireLogin()`.
* **Strict File Upload Validation:** File uploads are inspected for size (max 5MB), MIME type (`image/jpeg`, `image/png`, `image/webp`, `image/svg+xml`), and safe extensions with unique generated filenames.
* **Privacy by Default:** Student phone numbers, emails, and roll numbers are NEVER publicly displayed on listing pages.

---

## 10. Troubleshooting Guide

### Q1: "Database Connection Error" appears on the website.
* Verify that **MySQL** is started and running in the XAMPP Control Panel.
* Check that the database name is `bookloop` in `config/database.php`.

### Q2: Images or covers are not displaying.
* Ensure folder permissions in `uploads/books/` and `uploads/profiles/` are writable.
* Verify that files exist in `uploads/books/` (sample SVGs are included).

### Q3: Cannot log in with sample accounts.
* Ensure you imported `database/bookloop.sql` using phpMyAdmin.
* Re-check sample credentials: password is case-sensitive: `Student@123` or `Admin@123`.

---

## 11. Academic Viva / Demonstration Tips

When presenting this project for your college faculty or viva examiner:

1. **Architecture:** Highlight the MVC-inspired architecture separating database configuration (`config/`), global components and guards (`includes/`), assets (`css/`, `js/`), and admin views (`admin/`).
2. **Database Normalization:** Explain the 3rd Normal Form (3NF) relational design of the 10 tables, with foreign keys handling cascade deletions and composite indexes on frequently searched fields (`title`, `author`, `department`, `subject`).
3. **Privacy Design:** Explain that student contact numbers are protected and transactions are coordinated via the internal messaging and request system.
4. **State Machine:** Demonstrate the transaction lifecycle: `Available` &rarr; `Request Pending` &rarr; `Accepted` &rarr; `Sold / Exchanged` &rarr; `Review & Rating`.

---

&copy; 2026 **BookLoop** – Academic Web Application. All rights reserved.
