# 🌾 AgriPulse

### Agriculture Supply Chain Management System (PHP & MySQL)

---

## 📌 Overview

**AgriPulse** is a web-based Agriculture Supply Chain Management System developed using **PHP and MySQL**. It aims to efficiently manage and track the flow of agricultural products from farmers to consumers by digitizing key supply chain operations.

---

## 🎯 Objectives

* Streamline agricultural supply chain processes
* Maintain centralized data for farmers, vendors, and products
* Improve transparency and traceability
* Reduce manual work and errors
* Enable efficient inventory and logistics management

---

## 🚀 Features

* 👨‍🌾 Farmer Registration & Management
* 🏪 Vendor/Buyer Management
* 🌾 Product Listing & Categorization
* 📦 Inventory Management System
* 🚚 Order & Distribution Tracking
* 📊 Admin Dashboard for monitoring operations
* 🔐 Secure Login & Authentication System

---

## 🛠 Tech Stack

**Frontend:**

* HTML
* CSS
* JavaScript

**Backend:**

* PHP

**Database:**

* MySQL

**Server:**

* XAMPP / WAMP / LAMP

---

## 🏗 System Architecture

1. **User Interface (Frontend)**

   * Forms for data entry (farmers, products, orders)
   * Dashboard for admin and users

2. **Backend (PHP)**

   * Handles business logic
   * Processes requests and interacts with database

3. **Database (MySQL)**

   * Stores user data, product details, orders, and inventory

---

## 📂 Project Structure

```
AgriPulse/
│── index.php
│── login.php
│── dashboard.php
│── config/
│   └── db.php
│── modules/
│   ├── farmers.php
│   ├── products.php
│   ├── orders.php
│── assets/
│   ├── css/
│   ├── js/
│── database/
│   └── agripulse.sql
```

---

## ⚙ Installation & Setup

1. Install XAMPP/WAMP
2. Clone or download the project

```bash
git clone https://github.com/Hetshah1203/AgriPulse.git
```

3. Move project folder to `htdocs`

4. Start Apache and MySQL from XAMPP

5. Import database:

   * Open **phpMyAdmin**
   * Create a database (e.g., `agripulse`)
   * Import `agripulse.sql` file

6. Configure database connection:

   * Open `config/db.php`
   * Update username, password, database name

7. Run project in browser:

```
http://localhost/AgriPulse
```

---

## 🔐 Default Login (if applicable)

* **Admin Username:** admin
* **Password:** admin123

---

## 📊 Future Enhancements

* Mobile-responsive UI improvements
* Integration with payment gateway
* Real-time tracking system
* Advanced reporting & analytics

---

## 🤝 Contribution

Feel free to fork this repository and contribute.

---

## 📄 License

This project is for educational purposes.

---

## 👨‍💻 Author

Developed by *Het Shah*

---

## 🌟 Acknowledgements

* Open-source community
* PHP & MySQL documentation
