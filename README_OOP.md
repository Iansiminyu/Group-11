# Smart Restaurant System - OOP Architecture

## Overview

This project has been refactored to use **Object-Oriented Programming (OOP) principles** effectively and consistently. The new architecture follows modern PHP best practices including:

- **SOLID Principles**
- **Dependency Injection**
- **Separation of Concerns**
- **MVC Pattern**
- **Service Layer Architecture**

## 🏗️ Architecture Overview

### Directory Structure

```
src/
├── Core/
│   └── Database.php          # Singleton database connection
├── Models/
│   └── User.php              # User model with CRUD operations
├── Services/
│   ├── AuthService.php       # Authentication business logic
│   ├── EmailService.php      # Email sending functionality
│   ├── SessionService.php    # Session management
│   └── TwoFactorAuthService.php # 2FA implementation
├── Controllers/
│   ├── BaseController.php    # Base controller with common functionality
│   ├── AuthController.php    # Authentication endpoints
│   └── DashboardController.php # Dashboard functionality
├── autoload.php              # PSR-4 autoloader
└── bootstrap.php             # Application initialization

views/
└── auth/
    ├── login.php             # Login form template
    ├── register.php          # Registration form template
    └── verify_2fa.php        # 2FA verification template

# New OOP entry points
login_oop.php                 # OOP-based login page
register_oop.php              # OOP-based registration page
```

## 🔧 Key Components

### 1. Database Layer (`Core/Database.php`)

**Singleton Pattern** implementation ensuring single database connection:

```php
$db = Database::getInstance();
$users = $db->fetchAll("SELECT * FROM accounts WHERE active = ?", [true]);
```

**Features:**
- Connection pooling
- Prepared statements
- Transaction support
- Error handling

### 2. User Model (`Models/User.php`)

**Active Record Pattern** for user operations:

```php
$user = new User();
$user->create([
    'username' => 'john_doe',
    'email' => 'john@example.com',
    'password' => 'secure_password'
]);

$foundUser = $user->findByEmail('john@example.com');
```

**Features:**
- CRUD operations
- Password hashing/verification
- 2FA settings management
- Data validation

### 3. Service Layer

#### AuthService (`Services/AuthService.php`)
Handles authentication business logic:

```php
$authService = new AuthService($twoFactorService, $sessionService);
$result = $authService->login('user@example.com', 'password');
```

#### EmailService (`Services/EmailService.php`)
Manages email communications:

```php
$emailService = new EmailService();
$emailService->send('user@example.com', 'Subject', 'Message');
```

#### TwoFactorAuthService (`Services/TwoFactorAuthService.php`)
Handles 2FA functionality:

```php
$twoFactorService = new TwoFactorAuthService($emailService);
$code = $twoFactorService->generateCode();
$twoFactorService->sendCodeByEmail('user@example.com', $code);
```

#### SessionService (`Services/SessionService.php`)
Manages user sessions:

```php
$sessionService = new SessionService();
$sessionService->loginUser($userId);
$sessionService->setSuccessMessage('Login successful!');
```

### 4. Controller Layer

#### BaseController (`Controllers/BaseController.php`)
Provides common functionality for all controllers:

```php
abstract class BaseController {
    protected function redirect(string $url): void;
    protected function render(string $view, array $data = []): void;
    protected function requireAuth(): void;
}
```

#### AuthController (`Controllers/AuthController.php`)
Handles authentication endpoints:

```php
$authController = new AuthController($authService, $sessionService);
$authController->login();  // Handles login form submission
$authController->showLogin();  // Shows login form
```

## 🚀 Usage Examples

### Basic Login Flow

```php
// login_oop.php
require_once __DIR__ . '/src/bootstrap.php';

$authController = app('authController');

if (isPost()) {
    $authController->login();
} else {
    $authController->showLogin();
}
```

### Creating a New User

```php
$user = new User();
$result = $user->create([
    'username' => 'newuser',
    'email' => 'newuser@example.com',
    'password' => 'securepassword',
    'two_factor_type' => 'email'
]);
```

### Sending 2FA Code

```php
$twoFactorService = app('twoFactorService');
$result = $twoFactorService->generateAndSend(
    $userId, 
    'user@example.com', 
    'email'
);
```

## 🔒 Security Features

### 1. Password Security
- **bcrypt hashing** with `password_hash()`
- **Salt generation** automatic
- **Timing attack protection** with `password_verify()`

### 2. Session Security
- **Session regeneration** on login
- **CSRF protection** with tokens
- **Session timeout** management

### 3. Input Validation
- **HTML escaping** with `htmlspecialchars()`
- **SQL injection prevention** with prepared statements
- **XSS protection** in templates

### 4. Two-Factor Authentication
- **Time-based codes** (10-minute expiry)
- **Email and SMS support**
- **Code cleanup** after use

## 🎯 SOLID Principles Implementation

### Single Responsibility Principle (SRP)
- Each class has one reason to change
- `EmailService` only handles emails
- `AuthService` only handles authentication
- `SessionService` only manages sessions

### Open/Closed Principle (OCP)
- Controllers extend `BaseController`
- Services can be extended without modification
- New authentication methods can be added easily

### Liskov Substitution Principle (LSP)
- All controllers can replace `BaseController`
- Service interfaces are consistent

### Interface Segregation Principle (ISP)
- Small, focused service classes
- No forced dependencies on unused methods

### Dependency Inversion Principle (DIP)
- Controllers depend on service abstractions
- Services are injected via constructor
- Database abstraction layer

## 🔄 Dependency Injection

The application uses a simple **DI Container**:

```php
// bootstrap.php
$container->singleton('authService', function($container) {
    return new AuthService(
        $container->get('twoFactorService'),
        $container->get('sessionService')
    );
});

// Usage
$authService = app('authService');
```

## 📊 Benefits of OOP Refactoring

### Before (Procedural)
- ❌ Code scattered across files
- ❌ Global variables and functions
- ❌ Difficult to test
- ❌ Hard to maintain
- ❌ No code reusability

### After (OOP)
- ✅ **Organized code structure**
- ✅ **Encapsulated functionality**
- ✅ **Easy unit testing**
- ✅ **Maintainable codebase**
- ✅ **Reusable components**
- ✅ **Clear separation of concerns**
- ✅ **Dependency injection**
- ✅ **SOLID principles**

## 🧪 Testing

The OOP structure makes unit testing straightforward:

```php
// Example test for AuthService
class AuthServiceTest extends PHPUnit\Framework\TestCase {
    public function testLoginWithValidCredentials() {
        $mockTwoFactorService = $this->createMock(TwoFactorAuthService::class);
        $mockSessionService = $this->createMock(SessionService::class);
        
        $authService = new AuthService($mockTwoFactorService, $mockSessionService);
        $result = $authService->login('test@example.com', 'password');
        
        $this->assertTrue($result['success']);
    }
}
```

## 🚀 Getting Started

1. **Use the new OOP files:**
   - `login_oop.php` instead of `login.php`
   - `register_oop.php` instead of `register.php`

2. **All dependencies are auto-loaded:**
   ```php
   require_once __DIR__ . '/src/bootstrap.php';
   ```

3. **Access services via the container:**
   ```php
   $authService = app('authService');
   $sessionService = app('sessionService');
   ```

## 🔮 Future Enhancements

- **Repository Pattern** for data access
- **Event System** for decoupled notifications
- **Middleware** for request processing
- **API Controllers** for REST endpoints
- **Validation Classes** for input validation
- **Cache Layer** for performance
- **Logging Service** for debugging

## 📝 Migration Guide

To migrate from procedural to OOP:

1. Replace `login.php` with `login_oop.php`
2. Replace `register.php` with `register_oop.php`
3. Update any direct database calls to use the `Database` class
4. Replace helper functions with service classes
5. Use the container to access services

The OOP architecture is **backward compatible** - existing files continue to work while you gradually migrate to the new structure.
