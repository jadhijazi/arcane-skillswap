# SkillSwap Backend API

A complete peer-to-peer tutoring marketplace backend built with Slim 4 Framework, PHP 8.3, and MySQL.

## Features

### 🔐 Authentication
- User registration and login with JWT
- Refresh token mechanism
- Password hashing with bcrypt
- Secure token-based authentication

### 👤 User Management
- User profiles (learner & tutor)
- Profile updates and password changes
- User ratings and reviews
- Faculty and year tracking

### 📚 Skills Management
- Create, read, update, delete skills
- Search and filter skills by category
- Skill categorization (Tech, Academic, Languages, etc.)
- Tutor skill offerings with hourly rates

### 🔍 Tutor Discovery
- Advanced tutor search with filters
- Filter by faculty, rating, hourly rate, experience level
- Sorting: by rating, price, popularity
- Pagination support
- Detailed tutor profiles with ratings

### 📅 Availability Slots
- Create and manage availability slots
- Prevent overlapping time slots
- Support for multiple availability slots per tutor

### 📦 Bookings
- Request booking with automatic quote calculation
- Booking status workflow:
  - **pending** → **accepted** → **confirmed** → **completed**
  - Alternative: **declined**, **cancelled**
- Learner and tutor booking history
- Payment calculation based on hourly rate

### 💰 Wallet System
- User wallet for balance tracking
- Transaction history (credits/debits)
- Automatic tutor earnings on booking completion
- Platform commission (10% by default)
- USD currency support

### ⭐ Reviews & Ratings
- Leave reviews only after completed bookings
- 1-5 star rating system
- Review comments
- Tutor average rating calculation
- Review history per tutor

### 💬 Messaging
- Send and receive messages
- Conversation history with pagination
- Mark messages/conversations as read
- Unread message count
- Full message management

### 🔔 Notifications
- System notifications for bookings, reviews, messages
- Mark as read / read all
- Notification history with pagination
- Unread notification count

### 🛡️ Middleware & Security
- CORS middleware for cross-origin requests
- Rate limiting (200 requests/60 seconds)
- JWT authentication middleware
- Error handling and validation

---

## Tech Stack

- **Framework**: Slim 4
- **Language**: PHP 8.3
- **Database**: MySQL 8.0
- **Authentication**: JWT (Firebase PHP-JWT)
- **Dependency Injection**: PHP-DI
- **Environment**: PHPDotenv

---

## Quick Start

### Prerequisites
- PHP 8.3+
- MySQL 8.0+
- Composer 2.0+

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/arcane/skillswap.git
   cd skillswap/server
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Create environment file**
   ```bash
   cp .env.example .env
   ```

4. **Configure database**
   Edit `.env` with your database credentials:
   ```env
   DB_HOST=localhost
   DB_DATABASE=skillswap
   DB_USERNAME=root
   DB_PASSWORD=password
   JWT_SECRET=your-secret-key
   ```

5. **Create database**
   ```bash
   mysql -u root -p
   CREATE DATABASE skillswap;
   USE skillswap;
   source db/schema.sql;
   source db/seeders.sql;
   ```

6. **Start development server**
   ```bash
   composer start
   ```

   Server will run on `http://localhost:8080`

---

## API Endpoints Overview

### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login user
- `POST /api/auth/refresh` - Refresh access token
- `POST /api/auth/logout` - Logout user

### Users
- `GET /api/users/me` - Get current user profile [Protected]
- `GET /api/users/{id}` - Get user profile
- `PATCH /api/users/me` - Update profile [Protected]
- `POST /api/users/change-password` - Change password [Protected]

### Skills
- `POST /api/skills` - Create skill [Protected]
- `GET /api/skills` - List all skills
- `GET /api/skills/search?q={query}` - Search skills
- `GET /api/skills/filter?category={category}` - Filter by category
- `GET /api/skills/{id}` - Get skill details
- `PATCH /api/skills/{id}` - Update skill [Protected]
- `DELETE /api/skills/{id}` - Delete skill [Protected]

### User Skills (Tutor Offerings)
- `POST /api/user-skills` - Create skill offering [Protected]
- `GET /api/user-skills/{id}` - Get skill offering
- `GET /api/users/{user_id}/skills` - Get user's skills
- `PATCH /api/user-skills/{id}` - Update offering [Protected]
- `DELETE /api/user-skills/{id}` - Delete offering [Protected]

### Tutor Discovery
- `GET /api/tutors/search?skill_id={id}&sort=rating` - Search tutors

### Availability Slots
- `POST /api/availability-slots` - Create slot [Protected]
- `GET /api/availability-slots/{id}` - Get slot
- `GET /api/users/{user_id}/availability-slots` - Get user's slots
- `PATCH /api/availability-slots/{id}` - Update slot [Protected]
- `DELETE /api/availability-slots/{id}` - Delete slot [Protected]

### Bookings
- `POST /api/bookings` - Request booking [Protected]
- `GET /api/bookings/{id}` - Get booking details
- `GET /api/bookings/learner` - Get learner bookings [Protected]
- `GET /api/bookings/tutor` - Get tutor bookings [Protected]
- `PATCH /api/bookings/{id}/accept` - Accept booking [Protected]
- `PATCH /api/bookings/{id}/decline` - Decline booking [Protected]
- `PATCH /api/bookings/{id}/confirm` - Confirm booking [Protected]
- `PATCH /api/bookings/{id}/complete` - Complete booking [Protected]
- `PATCH /api/bookings/{id}/cancel` - Cancel booking [Protected]

### Wallet
- `GET /api/wallet` - Get wallet balance [Protected]
- `GET /api/wallet/transactions` - Get transaction history [Protected]

### Reviews
- `POST /api/reviews` - Create review [Protected]
- `GET /api/reviews/{id}` - Get review
- `GET /api/tutors/{tutor_id}/reviews` - Get tutor reviews

### Messages
- `POST /api/messages` - Send message [Protected]
- `GET /api/messages/{id}` - Get message
- `GET /api/conversations/{other_user_id}` - Get conversation [Protected]
- `GET /api/messages/unread-count` - Get unread count [Protected]
- `PATCH /api/messages/{id}/read` - Mark as read
- `PATCH /api/conversations/{sender_id}/read` - Mark conversation as read [Protected]

### Notifications
- `GET /api/notifications` - List notifications [Protected]
- `GET /api/notifications/{id}` - Get notification
- `GET /api/notifications/unread-count` - Get unread count [Protected]
- `PATCH /api/notifications/{id}/read` - Mark as read
- `PATCH /api/notifications/read-all` - Mark all as read [Protected]

[Protected] = Requires JWT authentication

For detailed API documentation, see [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

---

## Project Structure

```
server/
├── app/
│   ├── dependencies.php          # Dependency injection container
│   ├── middleware.php            # Global middleware setup
│   ├── routes.php                # Route registration
│   └── settings.php              # App settings
├── bootstrap/
│   └── app.php                   # App factory
├── db/
│   ├── schema.sql                # Database schema
│   └── seeders.sql               # Sample data
├── public/
│   └── index.php                 # Entry point
├── src/
│   ├── Application/
│   ├── Config/
│   │   └── Database.php          # Database configuration
│   ├── Controllers/              # API controllers
│   ├── Domain/
│   ├── Helpers/
│   │   ├── JwtHelper.php         # JWT token handling
│   │   └── ResponseHelper.php    # Response formatting
│   ├── Infrastructure/
│   ├── Middleware/               # Middleware classes
│   ├── Models/                   # Data models
│   ├── Repositories/             # Data access layer
│   ├── Routes/
│   │   └── api.php               # API route definitions
│   └── Services/                 # Business logic layer
├── var/
│   └── cache/                    # Cache directory
├── vendor/                       # Composer dependencies
├── .env.example                  # Environment template
├── composer.json                 # PHP dependencies
├── API_DOCUMENTATION.md          # Full API reference
├── DEPLOYMENT_GUIDE.md           # Production deployment
├── postman_collection.json       # Postman collection
├── phpunit.xml                   # Test configuration
└── README.md                     # This file
```

---

## Environment Configuration

Create `.env` file in root:

```env
# App
APP_ENV=development
APP_NAME=SkillSwap

# Database
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=skillswap
DB_USERNAME=root
DB_PASSWORD=password

# JWT
JWT_SECRET=super-secret-key-change-this
JWT_ISSUER=skillswap.local
JWT_AUDIENCE=skillswap.local
JWT_ACCESS_TTL=900          # 15 minutes
JWT_REFRESH_TTL=604800      # 7 days

# Business
PLATFORM_COMMISSION=0.10    # 10%

# Misc
TIMEZONE=UTC
```

---

## Sample Users (After Seeding)

| Email | Password | Role | Status |
|-------|----------|------|--------|
| alice@example.com | password | Learner | Active |
| bob@example.com | password | Tutor | Active |
| carol@example.com | password | Tutor | Active |
| admin@example.com | password | Admin | Active |

---

## Testing

Run all tests:
```bash
./vendor/bin/phpunit
```

Run specific test:
```bash
./vendor/bin/phpunit tests/Feature/AuthTest.php
```

---

## Code Quality

### Static Analysis
```bash
./vendor/bin/phpstan analyse src/
```

### Code Style
```bash
./vendor/bin/phpcs src/
./vendor/bin/phpcbf src/  # Auto-fix
```

---

## Production Deployment

See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for complete production setup instructions including:
- Server requirements
- Nginx configuration
- MySQL optimization
- SSL/TLS setup
- Security checklist
- Monitoring & logging
- Backup & restore
- Performance tuning

---

## API Testing

### Postman Collection

Import `postman_collection.json` into Postman:

1. Open Postman
2. Click "Import"
3. Select `postman_collection.json`
4. Set environment variables:
   - `base_url`: http://localhost:8080/api
   - `access_token`: (obtained from login)
   - `refresh_token`: (obtained from login)

### cURL Examples

**Register:**
```bash
curl -X POST http://localhost:8080/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "first_name": "John",
    "last_name": "Doe",
    "faculty": "Science",
    "year": "2"
  }'
```

**Login:**
```bash
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Get User Profile (Protected):**
```bash
curl -X GET http://localhost:8080/api/users/me \
  -H "Authorization: Bearer <access_token>"
```

---

## Security Notes

- All passwords are hashed with bcrypt
- JWT tokens expire after configured TTL
- Rate limiting prevents abuse (200 req/60s per IP)
- CORS properly configured
- SQL injection prevented with parameterized queries
- XSS protection via proper response encoding

---

## Contributing

1. Fork the repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

---

## License

This project is licensed under the MIT License - see LICENSE file for details.

---

## Support

For support, open an issue on GitHub: https://github.com/arcane/skillswap/issues

---

## Roadmap

- [ ] Admin dashboard endpoints
- [ ] Email notifications
- [ ] Real-time notifications (WebSocket)
- [ ] Payment integration (Stripe, PayPal)
- [ ] Video call integration
- [ ] Advanced analytics
- [ ] Mobile app API
- [ ] GraphQL API layer

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and updates.
