-- Migration: add password reset columns (run if upgrading existing database)
ALTER TABLE users
  ADD COLUMN IF NOT EXISTS password_reset_token VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS password_reset_expires DATETIME NULL;

-- Enforce one review per booking
ALTER TABLE reviews
  ADD UNIQUE INDEX IF NOT EXISTS uq_reviews_booking (booking_id);
