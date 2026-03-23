# Petroleum Station - PHP Web Application Stack

> [!NOTE]
> This folder contains the original PHP & MySQL implementation of the Petroleum Station Management System.

> A modern, web-based management platform for petroleum stations built with PHP & MySQL

---

## 👨‍💼 About the Developer

**Name:** Erneste Gisubizo  
**Role:** Programmer & University Student  
**Institution:** University of Rwanda  
**Organization:** [Erneste304Tech](#erneste304tech)

---

## 🏢 About Erneste304Tech

**Erneste304Tech** is a forward-thinking foundation dedicated to:
- 🚀 Building innovative technology solutions
- 💡 Creating educational and practical tools for the community
- 👥 Fostering a community of talented developers and tech enthusiasts
- 🌍 Developing scalable applications for real-world problems

### Our Activities
- Web & Software Development
- Database Design & Management
- System Architecture & Planning
- Technical Training & Mentorship
- Open-source Project Contributions

---

## 📋 Project Overview

The **Petroleum Station Management System** is a comprehensive, user-friendly application designed to streamline operations at petroleum fuel stations. It provides an efficient way to manage sales, inventory, employees, customers, and stations all in one integrated platform.

This project demonstrates professional software development practices including:
- Secure user authentication & authorization
- Relational database design
- RESTful-style navigation
- Responsive Bootstrap UI
- Session management

---

## ✨ Key Features

### 🔐 Authentication & Security
- User registration & login system
- Password hashing using PHP's `password_hash()`
- Session & Role-Based Access Control
  - **Admin**: Full control over management metrics, HR, fuel inventory, and sales.
  - **Customer**: Restricted access with a simplified dashboard view.
- Secure logout functionality

### 📊 Dashboard & Analytics
- Real-time statistics (stations, employees, customers, fuel types)
- Daily & monthly sales tracking
- Revenue monitoring in RWF (Rwandan Franc)
- Low stock alerts for fuel tanks
- Recent sales activity log

### 🛢️ Inventory Management
- Track fuel types and tank levels
- Monitor fuel deliveries
- Low stock notifications (below 20% capacity)
- Capacity planning tools

### 👥 Personnel Management
- Employee records with positions and contact info
- Assign employees to stations
- Track employee activity in sales

### 💼 Customer Management
- Maintain customer database
- Associate customers with sales
- Support for walk-in customers (anonymous sales)

### 🚙 Sales Processing
- Create and log fuel sales
- Track payment methods
- Link sales to customers, employees, and fuel types
- Generate sales reports & history

### 📈 Reporting
- View recent sales transactions
- Filter by date range, employee, customer, or fuel type
- Export-ready data structure

---

## 🛠️ Technology Stack

| Technology | Purpose |
|------------|---------|
| **PHP 7.4+** | Backend server-side scripting |
| **MySQL 5.7+** | Relational database |
| **Bootstrap 5.3** | Responsive UI framework |
| **DataTables** | Advanced table interactions |
| **Bootstrap Icons** | Icon library |
| **PDO** | Database abstraction layer |

---

## 📦 Installation & Setup

### Prerequisites
- **PHP 7.4** or higher
- **MySQL 5.7** or higher
- **XAMPP** or similar local development environment
- Modern web browser (Chrome, Firefox, Edge, Safari)

### Step 1: Clone or Download
```bash
# Navigate to your web root (e.g., htdocs)
cd C:\xampp\htdocs
# Clone or extract the project
git clone https://github.com/yourusername/petroleum-station-ms.git
# OR download and extract the ZIP file
```

### Step 2: Database Setup

#### Create the Database
```sql
CREATE DATABASE petroleum_station_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petroleum_station_db;
```

#### Create the Users Table (Authentication)
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Create Other Tables
Import the remaining tables using phpMyAdmin or execute SQL scripts containing:
- `station` – Fuel station locations
- `employee` – Employee records
- `customer` – Customer information
- `fuel_type` – Types of fuel available
- `tank` – Fuel storage tanks
- `pump` – Fuel pumps linked to stations
- `fuel_delivery` – Inbound fuel deliveries
- `sale` – Fuel sales transactions
- `payment` – Payment methods and records
- `supplier` – Fuel suppliers

### Step 3: Configure Database Connection

Edit `config/database.php`:
```php
<?php
$host = 'localhost';
$dbname = 'petroleum_station_db';
$username = 'your_db_username';    // Change this
$password = 'your_db_password';    // Change this
// ... rest of connection code
?>
```

### Step 4: Start the Application

1. Start **XAMPP** (Apache + MySQL)
2. Open your browser and navigate to:
   ```
   http://localhost:8000/index.php
   ```
   or
   ```
   http://localhost/petroleum-station-ms/
   ```
3. You'll be redirected to the **Login Page** if not authenticated

---

## 🚀 Usage Guide

### First-Time Setup

1. **Register a New Account**
   - Click "Register here" on the login page
   - Enter a username and password
   - Confirm your password
   - Click "Register"
   - You'll be redirected to log in

2. **Log In**
   - Enter your username and password
   - Click "Log In"
   - You'll be taken to the dashboard

### Dashboard Navigation

Once logged in, the navbar provides quick access to:

| Menu Item | Options |
|-----------|---------|
| **Dashboard** | Main overview & statistics |
| **Stations** | View all / Add new station |
| **Employees** | View all / Add new employee |
| **Customers** | View all / Add new customer |
| **Fuel** | View all / Add new fuel type |
| **Sales** | View all / New sale |

### Key Operations

#### Adding a New Fuel Sale
1. Navigate to **Sales** → **New Sale**
2. Select or create a pump location
3. Enter fuel quantity and price
4. Link to customer (optional) or mark as walk-in
5. Select payment method
6. Submit to log the sale

#### Checking Inventory
1. Go to **Fuel** section
2. View current tank levels
3. Receive alerts for tanks below 20% capacity
4. Manage fuel deliveries as needed

#### Viewing Reports
1. Click **View Reports** on the dashboard
2. Filter by date, employee, customer, or fuel type
3. Export data for analysis

---

## 🔐 Security Notes

- **Passwords** are hashed using `password_hash()` with the BCRYPT algorithm
- **Sessions** are managed server-side with `session_start()`
- **SQL Injection** is prevented using PDO prepared statements
- **User Input** is sanitized and validated
- **Logout** properly destroys sessions

For production deployment:
- Use HTTPS (SSL/TLS certificates)
- Store database credentials in environment variables (use `.env`)
- Implement role-based access control (RBAC)
- Enable database backup & disaster recovery
- Use a Web Application Firewall (WAF)

---

## 📁 Project Structure

```text
petroleum-station-ms/
├── config/                   # System configuration & database setup
├── css/                      # UI styles and design assets
├── includes/                 # Shared template components (header, footer)
├── [feature_modules]/        # Core application modules:
│   ├── customers/            # Client management operations
│   ├── employees/            # Staff records & assignments
│   ├── fuel/                 # Fuel inventory & tank monitoring
│   ├── sales/                # Transaction processing logic
│   └── stations/             # Station location management
├── index.php                 # Main authenticated dashboard
├── login.php                 # Secure login gateway
├── register.php              # New user registration
├── logout.php                # Authentication termination
└── README.md                 # Project documentation
```

---

## 🧹 Removing Unused Files

Over time, you may accumulate files no longer needed. To clean up:

1. **Search for references**
   ```bash
   # Use VS Code's search or grep
   grep -r "filename" ./
   ```

2. **Verify it's safe to delete**
   - Confirm no links in navigation
   - Check headers/includes don't reference it
   - Ensure no database foreign keys depend on it

3. **Delete the file**
   ```bash
   rm path/to/file.php
   # OR use File Explorer / Finder
   ```

4. **Clean up related data**
   - Remove any corresponding database records
   - Update any routes or links

Examples of potentially removable files:
- `diagnose.php` – if diagnostic tools aren't needed
- `test_connection.php` – once deployment is verified
- Unused `/employees`, `/customers` folders if features aren't used

---

## 📝 Example SQL Queries

### Check User Accounts
```sql
SELECT user_id, username, created_at FROM users;
```

### View Today's Sales
```sql
SELECT * FROM sale WHERE DATE(sale_date) = CURDATE();
```

### Check Low Stock
```sql
SELECT t.tank_id, f.fuel_name, t.current_stock, t.capacity
FROM tank t
JOIN fuel_type f ON t.fuel_id = f.fuel_id
WHERE (t.current_stock / t.capacity * 100) < 20;
```

### Employee Performance
```sql
SELECT e.first_name, e.last_name, COUNT(s.sale_id) as sales_count
FROM employee e
LEFT JOIN sale s ON e.employee_id = s.employee_id
GROUP BY e.employee_id
ORDER BY sales_count DESC;
```

---

## 🐛 Troubleshooting

| Issue | Solution |
|-------|----------|
| **Login redirects to login.php** | Check the `users` table exists & has data |
| **Database connection fails** | Verify credentials in `config/database.php` |
| **"Table not found" error** | Run table creation SQL in phpMyAdmin |
| **Navbar links return 404** | Ensure all subdirectories exist |
| **Bootstrap styling missing** | Check CDN URLs in `includes/header.php` are accessible |

---

## 📞 Support & Contributions

### Get in Touch
- **Developer:** Erneste Gisubizo
- **Organization:** Erneste304Tech
- **Email:** erneste304tech@gmail.com *(update as needed)*
- **GitHub:** https://github.com/Erneste304 *(update as needed)*

### Contributing
Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/YourFeature`)
3. Commit changes (`git commit -m 'Add feature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

---

## 📄 License

This project is property of **Erneste304Tech**. Please contact for licensing details.

---

## 🎓 Learning Resources

Built as part of:
- University of Rwanda coursework
- Professional web development training
- Real-world application architecture

---

## ✅ Changelog

### Version 1.0.0 (Current)
- ✨ Initial release
- 🔐 User authentication system (login/register)
- 📊 Dashboard with real-time statistics
- 💼 Full CRUD operations for all modules
- 📱 Responsive Bootstrap UI
- 🔔 Low stock alerts

---

**Made with ❤️ by Erneste Gisubizo | Erneste304Tech**

*Last Updated: March 9, 2026*
