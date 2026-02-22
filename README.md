<h1 align="center">PHP Appointment Booking System</h1>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-blue.svg" alt="PHP Version">
  <img src="https://img.shields.io/badge/MySQL-Supported-orange.svg" alt="MySQL">
  <img src="https://img.shields.io/badge/Bootstrap-5.3-purple.svg" alt="Bootstrap">
  <img src="https://img.shields.io/badge/License-Open%20Source-green.svg" alt="License">
</p>

<p align="center">
  A fully responsive, beautiful, and secure appointment booking and management system built with pure PHP, MySQL, and Bootstrap 5. 
  <br />
  Features a comprehensive admin dashboard, email verification, automated mail notifications, and an automated installer.
</p>

---

## 📸 Core Features

- **Automated Installation Wizard:** Setup your database and SMTP configurations in seconds via a GUI installer without touching any code.
- **Admin Dashboard:** Effortlessly manage user bookings, cancel appointments, approve pending reservations, and modify available time slots visually.
- **Email Notifications via PHPMailer:** Automated triggers that email users their booking confirmations and admins when a new reservation is requested.
- **Secure Email Verification:** Automatic flow requiring new sign-ups to click a 24-hr token in their email before booking.
- **Profile Management:** Users have full control over their booked appointments, account passwords, and history.
- **Responsive Calendar UI:** Mobile-friendly and interactive Bootstrap 5 frontend.
- **Modern Security Defaults:** Passwords are hashed automatically via `password_hash()`. Polyfill added for raw MySQLi compatibility. 

## 🚀 Quick Start Guide

### Prerequisites
- A web server running PHP 7.4 or higher (e.g., Apache, Nginx, XAMPP, LAMP).
- A MySQL / MariaDB database.
- An SMTP Email Account (Gmail App Password, Titan Mail, Mailgun, etc.).

### Installation Steps
1. **Clone or Download the Repository:**
   ```bash
   git clone https://github.com/yourusername/appointment-booking-system.git
   cd appointment-booking-system
   ```

2. **Upload Files:**
   Move all files to your web server's public directory (`htdocs`, `public_html`, or `/var/www/html`).

3. **Install Vendor Dependencies (If applicable):**
   This project relies on PHPMailer. If the `vendor/` folder is missing, run:
   ```bash
   composer install
   ```

4. **Run the Automated Web Installer:**
   - Open your web browser and navigate to `http://your-domain.com/install.php`
   - The interactive wizard will ask for your MySQL database credentials and your SMTP Email settings.
   - Click "Install" — the wizard will build your schemas from `database.sql` and dynamically create your `includes/config.php` file automatically.

5. **Security Cleanup (Crucial):**
   - For security reasons, the installer will lock itself after completion.
   - However, it is **highly recommended** to physically delete `install.php` and `database.sql` from your server root after a successful installation.

## ⚙️ Manual Configuration (Alternative)

If you prefer not to use the automated `install.php` GUI, you can set up the system manually:
1. Import `database.sql` into your MySQL server via phpMyAdmin or the command line.
2. Rename `includes/config.sample.php` to `includes/config.php`.
3. Open `includes/config.php` in a code editor and manually input your database credentials and SMTP details.

## 📧 SMTP Configuration Note
To ensure email verifications and notifications send reliably, you must configure SMTP fields correctly during installation. If using a free Gmail account, you must generate an **[App Password](https://support.google.com/accounts/answer/185833)**.

## 🤝 Contributing
Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](https://github.com/yourusername/appointment-booking-system/issues) to support the project.

## 📜 License
This project is open source and available under the MIT License. Feel free to fork, modify, and use it freely.

---

<p align="center">
  <i>Developed and crafted carefully by <a href="https://mrhussnainofficial.com" target="_blank">Hussnain Raza</a>.</i>
</p>
