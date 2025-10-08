# Implementation Plan

- [x] 1. Install and configure LexikJWTAuthenticationBundle
  - Install LexikJWTAuthenticationBundle via Composer
  - Generate JWT keys for token signing and verification
  - Configure basic JWT settings in security.yaml
  - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [x] 2. Extend Customer entity for authentication
  - [x] 2.1 Add authentication fields to Customer entity
    - Add password, passwordResetToken, and passwordResetTokenExpiresAt fields
    - Implement UserInterface and PasswordAuthenticatedUserInterface
    - Add getUserIdentifier(), getRoles(), getPassword(), and eraseCredentials() methods
    - _Requirements: 1.1, 3.1, 3.4, 5.1, 7.1_

  - [x] 2.2 Create database migration for Customer authentication fields
    - Generate migration for new Customer authentication fields
    - Apply migration to update database schema
    - _Requirements: 1.1, 3.1_

- [x] 3. Extend Supplier entity for authentication
  - [x] 3.1 Add authentication fields to Supplier entity
    - Add password, passwordResetToken, and passwordResetTokenExpiresAt fields
    - Implement UserInterface and PasswordAuthenticatedUserInterface
    - Add getUserIdentifier(), getRoles(), getPassword(), and eraseCredentials() methods
    - _Requirements: 2.1, 4.1, 4.4, 6.1, 8.1_

  - [x] 3.2 Create database migration for Supplier authentication fields
    - Generate migration for new Supplier authentication fields
    - Apply migration to update database schema
    - _Requirements: 2.1, 4.1_

- [x] 4. Create user providers for JWT authentication
  - [x] 4.1 Implement CustomerUserProvider
    - Create CustomerUserProvider implementing UserProviderInterface
    - Implement loadUserByIdentifier() and refreshUser() methods
    - Handle user loading and refreshing for JWT authentication
    - _Requirements: 3.1, 3.2, 9.1_

  - [x] 4.2 Implement SupplierUserProvider
    - Create SupplierUserProvider implementing UserProviderInterface
    - Implement loadUserByIdentifier() and refreshUser() methods
    - Handle user loading and refreshing for JWT authentication
    - _Requirements: 4.1, 4.2, 9.1_

- [x] 5. Create authentication services
  - [x] 5.1 Implement CustomerAuthService
    - Create service for customer registration, authentication, and password management
    - Implement registerCustomer(), authenticateCustomer(), generatePasswordResetToken() methods
    - Implement resetPassword() and changePassword() methods
    - _Requirements: 1.1, 1.2, 1.3, 3.1, 3.2, 5.1, 5.2, 5.3, 7.1, 7.2, 7.3_

  - [x] 5.2 Implement SupplierAuthService
    - Create service for supplier registration, authentication, and password management
    - Implement registerSupplier(), authenticateSupplier(), generatePasswordResetToken() methods
    - Implement resetPassword() and changePassword() methods
    - _Requirements: 2.1, 2.2, 2.3, 4.1, 4.2, 6.1, 6.2, 6.3, 8.1, 8.2, 8.3_

  - [x] 5.3 Implement TokenService
    - Create service for password reset token generation and validation
    - Implement generatePasswordResetToken(), isTokenExpired(), and hashToken() methods
    - Handle secure token generation and expiration logic
    - _Requirements: 5.1, 5.2, 5.3, 6.1, 6.2, 6.3_

- [x] 6. Create Customer API authentication endpoints
  - [x] 6.1 Implement CustomerAuthController registration endpoint
    - Create POST /api/customer/register endpoint
    - Handle customer registration with validation
    - Return appropriate success/error responses
    - _Requirements: 1.1, 1.2, 1.3, 1.4_

  - [x] 6.2 Implement CustomerAuthController login endpoint
    - Create POST /api/customer/login endpoint
    - Handle customer authentication and JWT token generation
    - Return JWT token and user information on success
    - _Requirements: 3.1, 3.2, 3.3, 3.4_

  - [x] 6.3 Implement CustomerAuthController password recovery endpoints
    - Create POST /api/customer/password-recovery endpoint for requesting reset
    - Create POST /api/customer/password-reset endpoint for resetting with token
    - Handle token generation, validation, and password updates
    - _Requirements: 5.1, 5.2, 5.3, 5.4_

  - [x] 6.4 Implement CustomerAuthController password change endpoint
    - Create PUT /api/customer/password-change endpoint
    - Handle authenticated password changes with current password verification
    - Return success confirmation on password update
    - _Requirements: 7.1, 7.2, 7.3, 7.4_

- [x] 7. Create Supplier API authentication endpoints
  - [x] 7.1 Implement SupplierAuthController registration endpoint
    - Create POST /api/supplier/register endpoint
    - Handle supplier registration with validation
    - Return appropriate success/error responses
    - _Requirements: 2.1, 2.2, 2.3, 2.4_

  - [x] 7.2 Implement SupplierAuthController login endpoint
    - Create POST /api/supplier/login endpoint
    - Handle supplier authentication and JWT token generation
    - Return JWT token and user information on success
    - _Requirements: 4.1, 4.2, 4.3, 4.4_

  - [x] 7.3 Implement SupplierAuthController password recovery endpoints
    - Create POST /api/supplier/password-recovery endpoint for requesting reset
    - Create POST /api/supplier/password-reset endpoint for resetting with token
    - Handle token generation, validation, and password updates
    - _Requirements: 6.1, 6.2, 6.3, 6.4_

  - [x] 7.4 Implement SupplierAuthController password change endpoint
    - Create PUT /api/supplier/password-change endpoint
    - Handle authenticated password changes with current password verification
    - Return success confirmation on password update
    - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [x] 8. Configure JWT security and firewalls
  - [x] 8.1 Configure security firewalls for API authentication
    - Set up separate firewalls for customer and supplier API routes
    - Configure JWT authentication for protected endpoints
    - Set up user providers for each entity type
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 10.1, 10.2, 10.3, 10.4_

  - [x] 8.2 Create JWT event listeners
    - Implement JWTCreatedListener to add custom claims (user_type, user_id)
    - Implement JWTAuthenticatedListener for post-authentication logic
    - Configure listeners in services.yaml
    - _Requirements: 3.4, 4.4, 9.1, 10.4_

- [x] 9. Add validation and error handling
  - [x] 9.1 Implement request validation
    - Create validation constraints for registration and login requests
    - Add password strength validation
    - Implement email format and uniqueness validation
    - _Requirements: 1.2, 1.3, 2.2, 2.3, 7.3, 8.3_

  - [x] 9.2 Implement comprehensive error handling
    - Create standardized error response format
    - Handle authentication errors (401, 403)
    - Handle validation errors (400, 409)
    - Handle password recovery errors (404, 400)
    - _Requirements: 3.2, 4.2, 5.3, 6.3, 7.2, 8.2_

- [ ]* 10. Create comprehensive test suite
  - [ ]* 10.1 Write unit tests for authentication services
    - Test CustomerAuthService and SupplierAuthService methods
    - Test TokenService functionality
    - Test entity UserInterface implementation
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 8.1_

  - [ ]* 10.2 Write integration tests for API endpoints
    - Test all authentication endpoints with various scenarios
    - Test JWT token generation and validation
    - Test error handling and edge cases
    - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1, 6.1, 7.1, 8.1, 9.1, 10.1_

  - [ ]* 10.3 Write security tests
    - Test JWT token tampering detection
    - Test password hashing and verification
    - Test rate limiting and brute force protection
    - _Requirements: 9.1, 9.2, 9.3, 9.4_