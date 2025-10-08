# Design Document

## Overview

This design implements JWT-based authentication for Customers and Suppliers using LexikJWTAuthenticationBundle. The system will extend existing Customer and Supplier entities to support authentication while maintaining their current functionality. The design includes API endpoints for registration, login, password recovery, and password management.

## Architecture

### Authentication Flow
1. **Registration**: Users register with email/password, creating Customer or Supplier records
2. **Login**: Users authenticate with email/password, receiving JWT tokens
3. **Token Validation**: JWT tokens are validated on protected API endpoints
4. **Password Recovery**: Email-based password reset using secure tokens
5. **Password Change**: Authenticated users can change passwords

### Security Model
- JWT tokens contain user ID, email, and role (ROLE_CUSTOMER or ROLE_SUPPLIER)
- Separate authentication contexts for customers and suppliers
- Password hashing using Symfony's password hasher
- Recovery tokens with expiration for password reset

## Components and Interfaces

### 1. Entity Extensions

#### Customer Entity Updates
- Add `password` field (hashed)
- Add `passwordResetToken` field (nullable)
- Add `passwordResetTokenExpiresAt` field (nullable)
- Implement `UserInterface` and `PasswordAuthenticatedUserInterface`
- Add methods: `getUserIdentifier()`, `getRoles()`, `getPassword()`, `eraseCredentials()`

#### Supplier Entity Updates
- Add `password` field (hashed)
- Add `passwordResetToken` field (nullable)
- Add `passwordResetTokenExpiresAt` field (nullable)
- Implement `UserInterface` and `PasswordAuthenticatedUserInterface`
- Add methods: `getUserIdentifier()`, `getRoles()`, `getPassword()`, `eraseCredentials()`

### 2. API Controllers

#### CustomerAuthController
- `POST /api/customer/register` - Customer registration
- `POST /api/customer/login` - Customer login
- `POST /api/customer/password-recovery` - Request password reset
- `POST /api/customer/password-reset` - Reset password with token
- `PUT /api/customer/password-change` - Change password (authenticated)

#### SupplierAuthController
- `POST /api/supplier/register` - Supplier registration
- `POST /api/supplier/login` - Supplier login
- `POST /api/supplier/password-recovery` - Request password reset
- `POST /api/supplier/password-reset` - Reset password with token
- `PUT /api/supplier/password-change` - Change password (authenticated)

### 3. Services

#### CustomerAuthService
- `registerCustomer(array $data): Customer`
- `authenticateCustomer(string $email, string $password): ?Customer`
- `generatePasswordResetToken(Customer $customer): string`
- `resetPassword(string $token, string $newPassword): bool`
- `changePassword(Customer $customer, string $currentPassword, string $newPassword): bool`

#### SupplierAuthService
- `registerSupplier(array $data): Supplier`
- `authenticateSupplier(string $email, string $password): ?Supplier`
- `generatePasswordResetToken(Supplier $supplier): string`
- `resetPassword(string $token, string $newPassword): bool`
- `changePassword(Supplier $supplier, string $currentPassword, string $newPassword): bool`

#### TokenService
- `generatePasswordResetToken(): string`
- `isTokenExpired(\DateTimeImmutable $expiresAt): bool`
- `hashToken(string $token): string`

### 4. User Providers

#### CustomerUserProvider
- Implements `UserProviderInterface`
- Loads customers by email for authentication
- Refreshes customer user objects

#### SupplierUserProvider
- Implements `UserProviderInterface`
- Loads suppliers by email for authentication
- Refreshes supplier user objects

### 5. JWT Event Listeners

#### JWTCreatedListener
- Adds custom claims to JWT tokens (user_type, user_id)
- Sets token expiration

#### JWTAuthenticatedListener
- Handles post-authentication logic
- Sets user context

## Data Models

### Customer Entity Extensions
```php
// New fields
private ?string $password = null;
private ?string $passwordResetToken = null;
private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

// New methods
public function getUserIdentifier(): string
public function getRoles(): array
public function getPassword(): ?string
public function eraseCredentials(): void
```

### Supplier Entity Extensions
```php
// New fields
private ?string $password = null;
private ?string $passwordResetToken = null;
private ?\DateTimeImmutable $passwordResetTokenExpiresAt = null;

// New methods
public function getUserIdentifier(): string
public function getRoles(): array
public function getPassword(): ?string
public function eraseCredentials(): void
```

### API Request/Response Models

#### Registration Request
```json
{
  "email": "user@example.com",
  "password": "securePassword123",
  "firstName": "John", // Customer only
  "lastName": "Doe", // Customer only
  "companyName": "ACME Corp", // Supplier only
  "contactPerson": "John Doe", // Supplier only
  "phone": "+1234567890"
}
```

#### Login Request
```json
{
  "email": "user@example.com",
  "password": "securePassword123"
}
```

#### Login Response
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "type": "customer"
  }
}
```

#### Password Recovery Request
```json
{
  "email": "user@example.com"
}
```

#### Password Reset Request
```json
{
  "token": "reset-token-here",
  "password": "newSecurePassword123"
}
```

#### Password Change Request
```json
{
  "currentPassword": "oldPassword123",
  "newPassword": "newSecurePassword123"
}
```

## Error Handling

### Authentication Errors
- Invalid credentials: 401 Unauthorized
- Account inactive/blocked: 403 Forbidden
- Invalid/expired JWT: 401 Unauthorized
- Missing JWT: 401 Unauthorized

### Validation Errors
- Invalid email format: 400 Bad Request
- Password too weak: 400 Bad Request
- Email already exists: 409 Conflict
- Required fields missing: 400 Bad Request

### Password Recovery Errors
- Email not found: 404 Not Found
- Invalid reset token: 400 Bad Request
- Expired reset token: 400 Bad Request

### Error Response Format
```json
{
  "error": {
    "code": "INVALID_CREDENTIALS",
    "message": "Invalid email or password",
    "details": []
  }
}
```

## Testing Strategy

### Unit Tests
- Entity method testing (UserInterface implementation)
- Service method testing (authentication, password operations)
- Token generation and validation
- Password hashing and verification

### Integration Tests
- API endpoint testing
- JWT token creation and validation
- Database operations
- Email sending for password recovery

### Security Tests
- JWT token tampering detection
- Password strength validation
- Rate limiting on authentication endpoints
- SQL injection prevention

### Test Data
- Valid and invalid user credentials
- Expired and valid JWT tokens
- Password reset scenarios
- Edge cases for user status validation

## Configuration Requirements

### LexikJWTAuthenticationBundle Installation
- Install via Composer: `lexik/jwt-authentication-bundle`
- Generate JWT keys for token signing
- Configure JWT settings (expiration, algorithm)

### Security Configuration
- Configure firewalls for customer and supplier authentication
- Set up user providers for each entity type
- Configure JWT authentication for API routes

### Database Migration
- Add password fields to customers and suppliers tables
- Add password reset token fields
- Create indexes for performance

### Environment Variables
- JWT_SECRET_KEY path
- JWT_PUBLIC_KEY path
- JWT_PASSPHRASE
- Password reset token expiration time