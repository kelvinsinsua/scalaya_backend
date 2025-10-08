# Requirements Document

## Introduction

This feature implements JWT-based authentication for Customers and Suppliers, allowing them to access API endpoints through secure token-based authentication. The system will provide registration, login, password recovery, and password change functionality using LexikJWTAuthenticationBundle.

## Requirements

### Requirement 1

**User Story:** As a Customer, I want to register for an account, so that I can access the system's API endpoints.

#### Acceptance Criteria

1. WHEN a customer submits valid registration data THEN the system SHALL create a new customer account
2. WHEN a customer submits registration data with an existing email THEN the system SHALL return an error message
3. WHEN a customer submits invalid registration data THEN the system SHALL return validation errors
4. WHEN a customer successfully registers THEN the system SHALL return a success message

### Requirement 2

**User Story:** As a Supplier, I want to register for an account, so that I can access the system's API endpoints.

#### Acceptance Criteria

1. WHEN a supplier submits valid registration data THEN the system SHALL create a new supplier account
2. WHEN a supplier submits registration data with an existing email THEN the system SHALL return an error message
3. WHEN a supplier submits invalid registration data THEN the system SHALL return validation errors
4. WHEN a supplier successfully registers THEN the system SHALL return a success message

### Requirement 3

**User Story:** As a Customer, I want to login with my credentials, so that I can receive a JWT token for API access.

#### Acceptance Criteria

1. WHEN a customer submits valid login credentials THEN the system SHALL return a JWT token
2. WHEN a customer submits invalid credentials THEN the system SHALL return an authentication error
3. WHEN a customer's account is inactive THEN the system SHALL deny access
4. WHEN a JWT token is generated THEN it SHALL contain the customer's role and identifier

### Requirement 4

**User Story:** As a Supplier, I want to login with my credentials, so that I can receive a JWT token for API access.

#### Acceptance Criteria

1. WHEN a supplier submits valid login credentials THEN the system SHALL return a JWT token
2. WHEN a supplier submits invalid credentials THEN the system SHALL return an authentication error
3. WHEN a supplier's account is inactive THEN the system SHALL deny access
4. WHEN a JWT token is generated THEN it SHALL contain the supplier's role and identifier

### Requirement 5

**User Story:** As a Customer, I want to recover my password, so that I can regain access to my account.

#### Acceptance Criteria

1. WHEN a customer requests password recovery with a valid email THEN the system SHALL send a recovery token
2. WHEN a customer submits a valid recovery token with a new password THEN the system SHALL update the password
3. WHEN a customer submits an invalid recovery token THEN the system SHALL return an error
4. WHEN a recovery token expires THEN the system SHALL reject the password reset request

### Requirement 6

**User Story:** As a Supplier, I want to recover my password, so that I can regain access to my account.

#### Acceptance Criteria

1. WHEN a supplier requests password recovery with a valid email THEN the system SHALL send a recovery token
2. WHEN a supplier submits a valid recovery token with a new password THEN the system SHALL update the password
3. WHEN a supplier submits an invalid recovery token THEN the system SHALL return an error
4. WHEN a recovery token expires THEN the system SHALL reject the password reset request

### Requirement 7

**User Story:** As a Customer, I want to change my password while logged in, so that I can maintain account security.

#### Acceptance Criteria

1. WHEN a customer provides current password and new password THEN the system SHALL update the password
2. WHEN a customer provides an incorrect current password THEN the system SHALL return an error
3. WHEN a customer provides an invalid new password THEN the system SHALL return validation errors
4. WHEN password is successfully changed THEN the system SHALL return a success confirmation

### Requirement 8

**User Story:** As a Supplier, I want to change my password while logged in, so that I can maintain account security.

#### Acceptance Criteria

1. WHEN a supplier provides current password and new password THEN the system SHALL update the password
2. WHEN a supplier provides an incorrect current password THEN the system SHALL return an error
3. WHEN a supplier provides an invalid new password THEN the system SHALL return validation errors
4. WHEN password is successfully changed THEN the system SHALL return a success confirmation

### Requirement 9

**User Story:** As a system, I want to validate JWT tokens on protected endpoints, so that only authenticated users can access resources.

#### Acceptance Criteria

1. WHEN a request includes a valid JWT token THEN the system SHALL allow access to protected endpoints
2. WHEN a request includes an invalid JWT token THEN the system SHALL return an authentication error
3. WHEN a request includes an expired JWT token THEN the system SHALL return an authentication error
4. WHEN a request lacks a JWT token for protected endpoints THEN the system SHALL return an authentication error

### Requirement 10

**User Story:** As a system administrator, I want different user types to have appropriate access levels, so that security is maintained.

#### Acceptance Criteria

1. WHEN a customer accesses customer-specific endpoints THEN the system SHALL allow access
2. WHEN a supplier accesses supplier-specific endpoints THEN the system SHALL allow access
3. WHEN a user tries to access endpoints for a different user type THEN the system SHALL deny access
4. WHEN role-based access is checked THEN the system SHALL use JWT token claims for authorization