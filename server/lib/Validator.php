<?php
/**
 * ============================================================================
 * Input Validation Utility
 * ============================================================================
 * 
 * Validates user input for security and data integrity.
 * Implements whitelist approach: only allow known-good data patterns.
 * 
 * Author: Muhammad Ibrahim Khan (Database & Security Lead)
 */

class Validator {
    private array $errors = [];
    
    /**
     * Validate email format
     */
    public static function email(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     * Requirements:
     * - Minimum 8 characters
     * - At least one uppercase letter
     * - At least one lowercase letter
     * - At least one digit
     * - At least one special character
     */
    public static function password(string $password): bool {
        if (strlen($password) < 8) {
            return false;
        }
        
        return preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password) === 1;
    }
    
    /**
     * Validate name (letters, spaces, hyphens only)
     */
    public static function name(string $name): bool {
        return preg_match("/^[a-zA-Z\s\-']{2,100}$/", $name) === 1;
    }
    
    /**
     * Validate UTM email format
     */
    public static function utmEmail(string $email): bool {
        return preg_match('/@utm\.edu\.my$/', $email) === 1;
    }
    
    /**
     * Validate faculty name
     */
    public static function faculty(string $faculty): bool {
        $validFaculties = ['Computing', 'Engineering', 'Business', 'Science', 'Education'];
        return in_array($faculty, $validFaculties, true);
    }
    
    /**
     * Validate role
     */
    public static function role(string $role): bool {
        return in_array($role, ['Learner', 'Tutor', 'Admin'], true);
    }
    
    /**
     * Validate skill ID (positive integer)
     */
    public static function skillId(int $skillId): bool {
        return $skillId > 0;
    }
    
    /**
     * Validate hourly rate (positive decimal, max 1000)
     */
    public static function hourlyRate(float $rate): bool {
        return $rate > 0 && $rate <= 1000;
    }
    
    /**
     * Validate proficiency level
     */
    public static function proficiencyLevel(string $level): bool {
        $validLevels = ['Beginner', 'Intermediate', 'Advanced', 'Expert'];
        return in_array($level, $validLevels, true);
    }
    
    /**
     * Validate booking duration (in minutes: 30, 60, 90, 120)
     */
    public static function duration(int $minutes): bool {
        return in_array($minutes, [30, 60, 90, 120, 150, 180], true);
    }
    
    /**
     * Validate future datetime (for scheduling sessions)
     */
    public static function futureDateTime(string $dateTime): bool {
        try {
            $dt = new DateTime($dateTime);
            return $dt > new DateTime();
        } catch (Exception) {
            return false;
        }
    }
    
    /**
     * Validate bio text length (max 500 characters)
     */
    public static function bio(string $bio): bool {
        $length = mb_strlen($bio);
        return $length >= 0 && $length <= 500;
    }
    
    /**
     * Validate review rating (1-5 stars)
     */
    public static function rating(int $rating): bool {
        return $rating >= 1 && $rating <= 5;
    }
    
    /**
     * Validate review comment (max 1000 characters)
     */
    public static function reviewComment(string $comment): bool {
        $length = mb_strlen($comment);
        return $length >= 0 && $length <= 1000;
    }
    
    /**
     * Validate message content (max 2000 characters)
     */
    public static function message(string $message): bool {
        $length = mb_strlen($message);
        return $length > 0 && $length <= 2000;
    }
    
    /**
     * Validate phone number (optional, Malaysian format)
     */
    public static function phone(string $phone): bool {
        // Malaysian phone format: +60 or 0, followed by 9-10 digits
        return preg_match('/^(\+60|0)[0-9]{9,10}$/', str_replace([' ', '-'], '', $phone)) === 1;
    }
    
    /**
     * Validate URL
     */
    public static function url(string $url): bool {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate that value is a positive integer
     */
    public static function positiveInt(int $value): bool {
        return $value > 0;
    }
    
    /**
     * Validate that value is a non-negative integer
     */
    public static function nonNegativeInt(int $value): bool {
        return $value >= 0;
    }
    
    /**
     * Validate string length
     */
    public static function stringLength(string $value, int $minLength, int $maxLength): bool {
        $length = mb_strlen($value);
        return $length >= $minLength && $length <= $maxLength;
    }
    
    /**
     * Validate against regex pattern
     */
    public static function pattern(string $value, string $pattern): bool {
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Batch validation with error tracking
     * 
     * Usage:
     *   $validator = new Validator();
     *   $validator->validate('email', $email, 'email');
     *   $validator->validate('password', $password, 'password');
     *   if (!$validator->passes()) {
     *       echo $validator->errors();
     *   }
     */
    public function validate(string $field, mixed $value, string $rule): self {
        $ruleMethod = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $ruleMethod)) {
            if (!$this->$ruleMethod($value)) {
                $this->errors[$field] = "Invalid {$field}";
            }
        } elseif (method_exists(self::class, $rule)) {
            if (!self::$rule($value)) {
                $this->errors[$field] = "Invalid {$field}";
            }
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes(): bool {
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function errors(): array {
        return $this->errors;
    }
    
    /**
     * Get first error for a field
     */
    public function firstError(string $field): ?string {
        return $this->errors[$field] ?? null;
    }
}