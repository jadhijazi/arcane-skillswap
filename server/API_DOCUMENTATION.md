# SkillSwap Backend - API Documentation

## Base URL
```
http://localhost:8080/api
```

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {}
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {}
}
```

## Authentication
All protected endpoints require a Bearer token in the Authorization header:
```
Authorization: Bearer <access_token>
```

---

## AUTH ENDPOINTS

### POST /auth/register
Register a new user.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "faculty": "Science",
  "year": "2"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": { "id": 1, "email": "user@example.com", "first_name": "John" },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "abc123def456"
  }
}
```

---

### POST /auth/login
Authenticate user.

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { "id": 1, "email": "user@example.com", "first_name": "John" },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "abc123def456"
  }
}
```

---

### POST /auth/refresh
Refresh access token using refresh token.

**Request Body:**
```json
{
  "refresh_token": "abc123def456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Token refreshed",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
  }
}
```

---

### POST /auth/logout
Logout user (revoke refresh token).

**Request Body:**
```json
{
  "refresh_token": "abc123def456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out",
  "data": {}
}
```

---

## USER ENDPOINTS

### GET /users/me
Get current user's profile. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "User profile retrieved",
  "data": {
    "user": { "id": 1, "email": "user@example.com", "first_name": "John", ... },
    "average_rating": 4.5,
    "total_reviews": 10
  }
}
```

---

### GET /users/{id}
Get user profile by ID.

**Response (200):**
```json
{
  "success": true,
  "message": "User profile retrieved",
  "data": {
    "user": { "id": 1, "first_name": "John", ... },
    "average_rating": 4.5,
    "total_reviews": 10
  }
}
```

---

### PATCH /users/me
Update current user's profile. **[Protected]**

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Doe",
  "bio": "Math enthusiast",
  "faculty": "Science",
  "year": "3",
  "profile_photo": "https://..."
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated",
  "data": {
    "user": { ... }
  }
}
```

---

### POST /users/change-password
Change password. **[Protected]**

**Request Body:**
```json
{
  "old_password": "oldpass123",
  "new_password": "newpass123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password changed",
  "data": {}
}
```

---

## SKILLS ENDPOINTS

### POST /skills
Create a skill. **[Protected]**

**Request Body:**
```json
{
  "name": "Python Programming",
  "category": "Tech"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Skill created",
  "data": {
    "skill": { "id": 1, "name": "Python Programming", "category": "Tech" }
  }
}
```

---

### GET /skills
List all skills with pagination.

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Skills retrieved",
  "data": {
    "skills": [...],
    "total": 100,
    "page": 1,
    "per_page": 50,
    "pages": 2
  }
}
```

---

### GET /skills/search?q=python
Search skills.

**Query Params:**
- `q` (string): Search query
- `page` (int)
- `per_page` (int)

**Response (200):**
```json
{
  "success": true,
  "message": "Skills found",
  "data": {
    "skills": [...],
    "query": "python",
    "page": 1,
    "per_page": 50
  }
}
```

---

### GET /skills/filter?category=Tech
Filter skills by category.

**Query Params:**
- `category` (string): Category to filter by
- `page` (int)
- `per_page` (int)

**Response (200):**
```json
{
  "success": true,
  "message": "Skills filtered",
  "data": {
    "skills": [...],
    "category": "Tech",
    "page": 1,
    "per_page": 50
  }
}
```

---

### GET /skills/{id}
Get skill by ID.

**Response (200):**
```json
{
  "success": true,
  "message": "Skill found",
  "data": {
    "skill": { "id": 1, "name": "Python Programming", "category": "Tech" }
  }
}
```

---

### PATCH /skills/{id}
Update skill. **[Protected]**

**Request Body:**
```json
{
  "name": "Advanced Python",
  "category": "Tech"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Skill updated",
  "data": {
    "skill": { ... }
  }
}
```

---

### DELETE /skills/{id}
Delete skill. **[Protected]**

**Response (204):**
```json
{
  "success": true,
  "message": "Skill deleted",
  "data": {}
}
```

---

## USER SKILLS ENDPOINTS

### POST /user-skills
Create skill offering (tutor). **[Protected]**

**Request Body:**
```json
{
  "skill_id": 1,
  "hourly_rate": 25.00,
  "experience_level": "Expert",
  "description": "I have 5 years of teaching experience"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Skill offering created",
  "data": {
    "user_skill": { "id": 1, "user_id": 2, "skill_id": 1, "hourly_rate": 25.00 }
  }
}
```

---

### GET /user-skills/{id}
Get skill offering.

**Response (200):**
```json
{
  "success": true,
  "message": "Skill offering found",
  "data": {
    "user_skill": { ... }
  }
}
```

---

### GET /users/{user_id}/skills
Get all skills offered by user.

**Response (200):**
```json
{
  "success": true,
  "message": "User skills retrieved",
  "data": {
    "user_skills": [...]
  }
}
```

---

### PATCH /user-skills/{id}
Update skill offering. **[Protected]**

**Request Body:**
```json
{
  "hourly_rate": 30.00,
  "experience_level": "Expert",
  "description": "Updated description"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Skill offering updated",
  "data": {
    "user_skill": { ... }
  }
}
```

---

### DELETE /user-skills/{id}
Delete skill offering. **[Protected]**

**Response (204):**
```json
{
  "success": true,
  "message": "Skill offering deleted",
  "data": {}
}
```

---

## TUTOR DISCOVERY ENDPOINTS

### GET /tutors/search?skill_id=1&sort=rating
Search tutors with filters and sorting.

**Query Params:**
- `skill_id` (int, required): Skill to search for
- `faculty` (string, optional): Filter by faculty
- `min_rating` (float, optional): Minimum rating
- `max_rate` (float, optional): Maximum hourly rate
- `min_rate` (float, optional): Minimum hourly rate
- `experience_level` (string, optional): Filter by experience level
- `sort` (string, default: "rating"): Sort by rating|price|popular
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Tutors found",
  "data": {
    "tutors": [
      {
        "id": 2,
        "first_name": "Bob",
        "last_name": "Johnson",
        "profile_photo": "...",
        "faculty": "Science",
        "avg_rating": 4.8,
        "total_sessions": 50,
        "hourly_rate": 25.00
      }
    ],
    "total": 10,
    "page": 1,
    "per_page": 50,
    "pages": 1,
    "sort": "rating"
  }
}
```

---

## AVAILABILITY SLOTS ENDPOINTS

### POST /availability-slots
Create availability slot. **[Protected]**

**Request Body:**
```json
{
  "start_time": "2024-12-20 14:00:00",
  "end_time": "2024-12-20 16:00:00"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Availability slot created",
  "data": {
    "slot": { "id": 1, "user_id": 2, "start_time": "2024-12-20 14:00:00", "end_time": "2024-12-20 16:00:00" }
  }
}
```

---

### GET /availability-slots/{id}
Get availability slot.

**Response (200):**
```json
{
  "success": true,
  "message": "Availability slot found",
  "data": {
    "slot": { ... }
  }
}
```

---

### GET /users/{user_id}/availability-slots
Get all availability slots for user.

**Response (200):**
```json
{
  "success": true,
  "message": "Availability slots retrieved",
  "data": {
    "slots": [...]
  }
}
```

---

### PATCH /availability-slots/{id}
Update availability slot. **[Protected]**

**Request Body:**
```json
{
  "start_time": "2024-12-20 15:00:00",
  "end_time": "2024-12-20 17:00:00"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Availability slot updated",
  "data": {
    "slot": { ... }
  }
}
```

---

### DELETE /availability-slots/{id}
Delete availability slot. **[Protected]**

**Response (204):**
```json
{
  "success": true,
  "message": "Availability slot deleted",
  "data": {}
}
```

---

## BOOKING ENDPOINTS

### POST /bookings
Request a booking. **[Protected]**

**Request Body:**
```json
{
  "user_skill_id": 1,
  "start_time": "2024-12-20 14:00:00",
  "end_time": "2024-12-20 15:00:00"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Booking requested",
  "data": {
    "booking": {
      "id": 1,
      "learner_id": 1,
      "tutor_id": 2,
      "user_skill_id": 1,
      "start_time": "2024-12-20 14:00:00",
      "end_time": "2024-12-20 15:00:00",
      "status": "pending",
      "amount": 25.00
    }
  }
}
```

---

### GET /bookings/{id}
Get booking details.

**Response (200):**
```json
{
  "success": true,
  "message": "Booking found",
  "data": {
    "booking": { ... }
  }
}
```

---

### GET /bookings/learner
Get current user's bookings as learner. **[Protected]**

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Bookings retrieved",
  "data": {
    "bookings": [...],
    "page": 1,
    "per_page": 50
  }
}
```

---

### GET /bookings/tutor
Get current user's bookings as tutor. **[Protected]**

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Bookings retrieved",
  "data": {
    "bookings": [...],
    "page": 1,
    "per_page": 50
  }
}
```

---

### PATCH /bookings/{id}/accept
Accept booking (tutor). **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Booking accepted",
  "data": {
    "booking": { "status": "accepted", ... }
  }
}
```

---

### PATCH /bookings/{id}/decline
Decline booking (tutor). **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Booking declined",
  "data": {
    "booking": { "status": "declined", ... }
  }
}
```

---

### PATCH /bookings/{id}/confirm
Confirm booking. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Booking confirmed",
  "data": {
    "booking": { "status": "confirmed", ... }
  }
}
```

---

### PATCH /bookings/{id}/complete
Complete booking. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Booking completed",
  "data": {
    "booking": { "status": "completed", ... }
  }
}
```

---

### PATCH /bookings/{id}/cancel
Cancel booking. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Booking cancelled",
  "data": {
    "booking": { "status": "cancelled", ... }
  }
}
```

---

## WALLET ENDPOINTS

### GET /wallet
Get wallet balance. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Wallet retrieved",
  "data": {
    "wallet": { "id": 1, "user_id": 1, "balance": 150.00, "currency": "USD" },
    "balance": 150.00
  }
}
```

---

### GET /wallet/transactions
Get wallet transaction history. **[Protected]**

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Transactions retrieved",
  "data": {
    "transactions": [
      {
        "id": 1,
        "wallet_id": 1,
        "amount": 25.00,
        "type": "credit",
        "description": "Booking payment",
        "created_at": "2024-12-20 14:30:00"
      }
    ],
    "balance": 150.00,
    "page": 1,
    "per_page": 50
  }
}
```

---

## REVIEW ENDPOINTS

### POST /reviews
Create review (after completed booking). **[Protected]**

**Request Body:**
```json
{
  "booking_id": 1,
  "rating": 5,
  "comment": "Great session! Very knowledgeable tutor."
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Review created",
  "data": {
    "review": {
      "id": 1,
      "booking_id": 1,
      "reviewer_id": 1,
      "rating": 5,
      "comment": "Great session!",
      "created_at": "2024-12-20 15:00:00"
    }
  }
}
```

---

### GET /reviews/{id}
Get review.

**Response (200):**
```json
{
  "success": true,
  "message": "Review found",
  "data": {
    "review": { ... }
  }
}
```

---

### GET /tutors/{tutor_id}/reviews
Get reviews for tutor.

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Tutor reviews retrieved",
  "data": {
    "reviews": [...],
    "average_rating": 4.7,
    "total_reviews": 15,
    "page": 1,
    "per_page": 50
  }
}
```

---

## MESSAGE ENDPOINTS

### POST /messages
Send message. **[Protected]**

**Request Body:**
```json
{
  "recipient_id": 2,
  "content": "Hi, when are you available for the session?"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Message sent",
  "data": {
    "message": {
      "id": 1,
      "sender_id": 1,
      "recipient_id": 2,
      "content": "Hi, when are you available...",
      "is_read": false,
      "created_at": "2024-12-20 15:00:00"
    }
  }
}
```

---

### GET /messages/{id}
Get message.

**Response (200):**
```json
{
  "success": true,
  "message": "Message found",
  "data": {
    "message": { ... }
  }
}
```

---

### GET /conversations/{other_user_id}
Get conversation with another user. **[Protected]**

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Conversation retrieved",
  "data": {
    "messages": [...],
    "other_user_id": 2,
    "page": 1,
    "per_page": 50
  }
}
```

---

### GET /messages/unread-count
Get unread message count. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Unread count retrieved",
  "data": {
    "unread_count": 3
  }
}
```

---

### PATCH /messages/{id}/read
Mark message as read.

**Response (200):**
```json
{
  "success": true,
  "message": "Message marked as read",
  "data": {}
}
```

---

### PATCH /conversations/{sender_id}/read
Mark all messages from sender as read. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Conversation marked as read",
  "data": {}
}
```

---

## NOTIFICATION ENDPOINTS

### GET /notifications
Get notifications. **[Protected]**

**Query Params:**
- `page` (int, default: 1)
- `per_page` (int, default: 50)

**Response (200):**
```json
{
  "success": true,
  "message": "Notifications retrieved",
  "data": {
    "notifications": [
      {
        "id": 1,
        "user_id": 1,
        "type": "booking",
        "data": { "booking_id": 1 },
        "is_read": false,
        "created_at": "2024-12-20 14:00:00"
      }
    ],
    "page": 1,
    "per_page": 50
  }
}
```

---

### GET /notifications/{id}
Get notification.

**Response (200):**
```json
{
  "success": true,
  "message": "Notification found",
  "data": {
    "notification": { ... }
  }
}
```

---

### GET /notifications/unread-count
Get unread notification count. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "Unread count retrieved",
  "data": {
    "unread_count": 2
  }
}
```

---

### PATCH /notifications/{id}/read
Mark notification as read.

**Response (200):**
```json
{
  "success": true,
  "message": "Notification marked as read",
  "data": {}
}
```

---

### PATCH /notifications/read-all
Mark all notifications as read. **[Protected]**

**Response (200):**
```json
{
  "success": true,
  "message": "All notifications marked as read",
  "data": {}
}
```

---

## Error Responses

### 400 Bad Request
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { "email": "Email is required" }
}
```

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized",
  "errors": {}
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Forbidden",
  "errors": {}
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Resource not found",
  "errors": {}
}
```

### 429 Too Many Requests
```json
{
  "success": false,
  "message": "Too many requests",
  "errors": {}
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Internal server error",
  "errors": {}
}
```

---

## Notes

- All timestamps are in `YYYY-MM-DD HH:MM:SS` format
- All monetary amounts are in USD (by default)
- Platform commission is 10% of booking amount
- JWT tokens expire after 15 minutes (access token) and 7 days (refresh token)
- Rate limiting: 200 requests per 60 seconds per IP
- All protected endpoints require valid JWT token in Authorization header
