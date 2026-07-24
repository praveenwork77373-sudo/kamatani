# MySQL Database Setup Guide

## Prerequisites
1. MySQL Server must be installed and running
2. MySQL root user credentials (default: root with no password, or your configured password)

## Step 1: Start MySQL Server

### Windows:
- Open Services (services.msc)
- Find "MySQL" or "MySQL80" service
- Right-click and select "Start"

Or use command line:
```powershell
net start MySQL80
```

### Linux/Mac:
```bash
sudo systemctl start mysql
# or
sudo service mysql start
```

## Step 2: Create Database

### Option A: Using MySQL Command Line
```bash
mysql -u root -p
```

Then run:
```sql
CREATE DATABASE IF NOT EXISTS uniform_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Option B: Using Laravel (if MySQL is running)
The migrations will attempt to create the database automatically if the user has CREATE DATABASE privileges.

## Step 3: Update .env File

The .env file has been updated with:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uniform_system
DB_USERNAME=root
DB_PASSWORD=
```

**Important:** If your MySQL root user has a password, update `DB_PASSWORD` in the .env file.

## Step 4: Run Migrations and Seeders

Once MySQL is running and the database is created:

```bash
php artisan migrate:fresh --seed
```

This will:
1. Drop all existing tables
2. Run all migrations
3. Seed the database with test data including:
   - Medical Institution
   - Departments, Job Types, Delivery Locations
   - Uniform Products
   - Employees
   - 6 User Accounts (all roles)

## Troubleshooting

### Error: "Can't connect to MySQL server"
- Ensure MySQL service is running
- Check if MySQL is listening on port 3306
- Verify DB_HOST in .env matches your MySQL configuration

### Error: "Access denied for user"
- Check DB_USERNAME and DB_PASSWORD in .env
- Verify the user has CREATE DATABASE privileges

### Error: "Unknown database"
- Create the database manually using Step 2
- Or grant CREATE DATABASE privilege to the MySQL user

## Login Credentials After Seeding

All users have password: `password123`

1. **Institution Admin**: hospital-admin@example.com
2. **Department Admin**: department-admin@example.com
3. **System Admin**: kamatani-admin@example.com
4. **Operations Admin**: operations-admin@example.com
5. **Sales Admin**: sales-admin@example.com
6. **Accounting Admin**: accounting-admin@example.com

See `LOGIN_CREDENTIALS.md` for full details.


