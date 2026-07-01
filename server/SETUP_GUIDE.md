# SkillSwap Backend - Complete Setup Guide

## Prerequisites

Ensure you have the following installed:

- **PHP 8.3+** - [Download](https://www.php.net/downloads)
- **MySQL 8.0+** - [Download](https://dev.mysql.com/downloads/mysql/)
- **Composer 2.0+** - [Download](https://getcomposer.org/download/)
- **Git** - [Download](https://git-scm.com/downloads)
- **A code editor** (VS Code recommended)

### Verify Installations

```bash
# Check PHP version
php -v

# Check MySQL version
mysql --version

# Check Composer version
composer --version

# Check Git version
git --version
```

---

## Step 1: Clone Repository

```bash
git clone https://github.com/arcane/skillswap.git
cd skillswap/server
```

---

## Step 2: Install PHP Dependencies

```bash
composer install
```

This will install all required packages:
- slim/slim (4.x)
- slim/psr7
- php-di/php-di
- firebase/php-jwt
- vlucas/phpdotenv
- monolog/monolog
- And testing frameworks

---

## Step 3: Create Database

### Option A: Using Command Line (Recommended)

```bash
# Connect to MySQL
mysql -u root -p

# Enter your MySQL password when prompted
```

In MySQL console:

```sql
CREATE DATABASE skillswap;
USE skillswap;
source db/schema.sql;
source db/seeders.sql;
```

### Option B: Using GUI (MySQL Workbench, phpMyAdmin)

1. Create new database named `skillswap`
2. Run SQL script `db/schema.sql` to create tables
3. Run SQL script `db/seeders.sql` to add sample data

---

## Step 4: Configure Environment

Create `.env` file in project root by copying `.env.example`:

```bash
cp .env.example .env
```

Edit `.env` with your settings:

```env
# Application
APP_ENV=development
APP_NAME=SkillSwap
APP_DEBUG=true

# Database Configuration
DB_HOST=localhost          # MySQL server host
DB_PORT=3306              # MySQL port (default: 3306)
DB_DATABASE=skillswap     # Database name
DB_USERNAME=root          # MySQL username
DB_PASSWORD=password      # MySQL password (change this!)

# JWT Configuration
JWT_SECRET=change-me-to-a-random-string-in-production
JWT_ISSUER=skillswap.local
JWT_AUDIENCE=skillswap.local
JWT_ACCESS_TTL=900        # 15 minutes in seconds
JWT_REFRESH_TTL=604800    # 7 days in seconds

# Business Configuration
PLATFORM_COMMISSION=0.10  # 10% platform commission

# Timezone
TIMEZONE=UTC
```

### Important: Generate Secure JWT Secret

For production, generate a random JWT secret:

**On Linux/Mac:**
```bash
openssl rand -base64 32
```

**On Windows (PowerShell):**
```powershell
[System.Convert]::ToBase64String([System.Security.Cryptography.RandomNumberGenerator]::GetBytes(32))
```

Then paste the output into `JWT_SECRET` in `.env`

---

## Step 5: Verify Database Connection

Create a test PHP file to verify database connection:

```php
<?php
// test-db.php
require 'vendor/autoload.php';
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

try {
    $pdo = new PDO(
        'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Database connection successful!\n";
    
    // Check tables
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Found " . count($tables) . " tables\n";
    print_r($tables);
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

Run test:
```bash
php test-db.php
```

---

## Step 6: Start Development Server

```bash
composer start
```

Output should be:
```
➜  Local:   http://localhost:8080
```

---

## Step 7: Test API Endpoints

### Test 1: Health Check (No Auth Required)

```bash
curl http://localhost:8080/api/skills
```

### Test 2: Register User

```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "TestPassword123!",
    "first_name": "Test",
    "last_name": "User",
    "faculty": "Science",
    "year": "2"
  }'
```

Response:
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 5,
      "email": "testuser@example.com",
      "first_name": "Test",
      "last_name": "User",
      "faculty": "Science",
      "year": "2"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "abc123..."
  }
}
```

### Test 3: Login with Sample User

```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "alice@example.com",
    "password": "password"
  }'
```

### Test 4: Protected Endpoint (Using JWT Token)

Use the `access_token` from login response:

```bash
curl http://localhost:8080/api/users/me \
  -H "Authorization: Bearer <YOUR_ACCESS_TOKEN>"
```

---

## Step 8: Import Postman Collection

1. Download and install [Postman](https://www.postman.com/downloads/)
2. Open Postman
3. Click **Import**
4. Select `postman_collection.json` from project root
5. Create new Environment:
   - **Name**: SkillSwap Dev
   - **base_url**: http://localhost:8080/api
   - **access_token**: (leave empty, will be auto-populated after login)
   - **refresh_token**: (leave empty, will be auto-populated after login)

Now you can test all API endpoints from Postman!

---

## Sample User Credentials

After running seeders, these users are available:

```
Email: alice@example.com
Password: password
Role: Learner

Email: bob@example.com
Password: password
Role: Tutor

Email: carol@example.com
Password: password
Role: Tutor

Email: admin@example.com
Password: password
Role: Admin
```

---

## Common Issues & Solutions

### Issue 1: "Access denied for user 'root'@'localhost'"

**Solution:**
- Check MySQL is running: `sudo systemctl status mysql` (Linux) or check Services (Windows)
- Verify credentials in `.env` file
- Try: `mysql -u root -p` to test connection

### Issue 2: "SQLSTATE[HY000]: General error: 1030 Got error..."

**Solution:**
- Check disk space: `df -h` (Linux)
- Verify MySQL has permission to write: `ls -la /var/lib/mysql` (Linux)
- Restart MySQL: `sudo systemctl restart mysql` (Linux)

### Issue 3: "Error: listen EADDRINUSE :::8080"

**Solution:**
- Port 8080 is already in use
- Find process: `lsof -i :8080` (Linux) or `netstat -ano | findstr :8080` (Windows)
- Kill process or use different port: `composer start -- --port 8081`

### Issue 4: "Composer dependencies not installed"

**Solution:**
```bash
# Clear composer cache
composer clear-cache

# Reinstall dependencies
rm -rf vendor composer.lock
composer install
```

### Issue 5: JWT Token Expired or Invalid

**Solution:**
- Tokens expire after 15 minutes (configurable in `.env`)
- Use refresh token: `POST /api/auth/refresh`
- Request: `{"refresh_token": "your_refresh_token"}`

### Issue 6: "Call to undefined function PDO"

**Solution:**
- PHP PDO extension not installed
- Linux: `sudo apt install php8.3-mysql`
- Windows: Enable `php_pdo_mysql.dll` in php.ini

---

## Development Workflow

### 1. Edit Code
Make changes to files in `src/` directory

### 2. Reload Server
- Development server auto-reloads on file changes
- If not, restart with `Ctrl+C` and `composer start`

### 3. Test Changes
Use Postman or cURL to test endpoints

### 4. Check Logs
```bash
# View PHP errors
tail -f logs/app.log

# View recent entries
cat logs/app.log | tail -20
```

### 5. Debug with Breakpoints
Add breakpoints using error_log or var_dump:

```php
error_log("Debug: " . json_encode($data));
var_dump($variable);
```

---

## Project File Organization

Key files to understand:

| File | Purpose |
|------|---------|
| `public/index.php` | Entry point |
| `app/dependencies.php` | Dependency injection setup |
| `app/routes.php` | Route definitions (loads src/Routes/api.php) |
| `src/Routes/api.php` | All API endpoints |
| `src/Controllers/*` | Request handlers |
| `src/Services/*` | Business logic |
| `src/Repositories/*` | Database access |
| `src/Models/*` | Data models |
| `db/schema.sql` | Database schema |
| `db/seeders.sql` | Sample data |

---

## Next Steps

1. **Explore API** - Test all endpoints using Postman collection
2. **Read API Documentation** - See [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
3. **Study Code Structure** - Understand the Clean Architecture pattern
4. **Make Changes** - Start implementing new features
5. **Deploy** - Use [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for production

---

## Useful Commands

```bash
# Run tests
composer test

# Code quality check
composer phpstan
composer phpcs

# Auto-fix code style
composer phpcs-fix

# Development server with specific port
composer start -- --port 3000

# View installed packages
composer show

# Update packages
composer update

# Clear autoloader cache
composer dump-autoload

# Check composer.json validity
composer validate
```

---

## Directory Permissions

After installation, ensure correct permissions:

```bash
# Linux/Mac
chmod -R 755 .
chmod -R 777 logs/
chmod -R 777 var/

# Check ownership (should be your user)
ls -la logs/
ls -la var/
```

---

## Environment Checklist

- [ ] PHP 8.3+ installed and in PATH
- [ ] MySQL 8.0+ installed and running
- [ ] Composer installed and in PATH
- [ ] Git installed and in PATH
- [ ] Repository cloned
- [ ] Dependencies installed (`composer install`)
- [ ] Database created
- [ ] `.env` file created and configured
- [ ] Server started (`composer start`)
- [ ] Sample users can login
- [ ] Postman collection imported and tested

---

## Support

- **Documentation**: See [README.md](README.md) and [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Issues**: Check existing issues on GitHub
- **Community**: Join SkillSwap development discussions

---

## Next: Production Deployment

When ready to deploy to production, see [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for:
- Server setup
- Nginx configuration
- SSL certificates
- Database optimization
- Security hardening
- Monitoring setup

---

## Quick Command Reference

```bash
# Clone & Setup
git clone https://github.com/arcane/skillswap.git
cd skillswap/server
composer install

# Database
mysql -u root -p < db/schema.sql
mysql -u root -p < db/seeders.sql

# Develop
composer start

# Test
composer test

# Build
composer phpstan
composer phpcs

# Production
./deploy.sh (see DEPLOYMENT_GUIDE.md)
```

Happy coding! 🚀
