# Admin Panel Design Document

## Overview

The admin panel will be built using Symfony's EasyAdmin bundle, which provides a robust and customizable admin interface. The system will leverage the existing AdminUser entity for authentication and implement role-based access control. The design focuses on creating an intuitive interface for managing dropshipping entities while maintaining security and performance.

## Architecture

### Bundle Integration
- **EasyAdmin Bundle**: Primary admin interface framework
- **Symfony Security**: Authentication and authorization
- **Doctrine ORM**: Data persistence and relationships
- **Twig Templates**: Custom template overrides when needed

### Security Layer
- **Authentication**: Custom authenticator using AdminUser entity
- **Authorization**: Role-based access control with three levels:
  - `ROLE_ADMIN`: Full access to all sections
  - `ROLE_MANAGER`: Access to business entities (products, customers, orders, suppliers)
  - `ROLE_OPERATOR`: Limited access to orders and customers only

### URL Structure
```
/admin                    # Login page (if not authenticated)
/admin/dashboard         # Main dashboard
/admin/products          # Product management
/admin/customers         # Customer management  
/admin/orders           # Order management
/admin/suppliers        # Supplier management
/admin/admin-users      # Admin user management (ROLE_ADMIN only)
```

## Components and Interfaces

### 1. Authentication System

#### AdminAuthenticator
- Custom authenticator implementing `AbstractLoginFormAuthenticator`
- Validates credentials against AdminUser entity
- Checks user status (active/inactive)
- Updates last login timestamp

#### Security Configuration
```yaml
security:
    providers:
        admin_provider:
            entity:
                class: App\Entity\AdminUser
                property: email
    firewalls:
        admin:
            pattern: ^/admin
            provider: admin_provider
            custom_authenticator: App\Security\AdminAuthenticator
            logout:
                path: admin_logout
                target: admin_login
```

### 2. EasyAdmin Configuration

#### DashboardController
- Main entry point for admin panel
- Configures menu items based on user roles
- Displays dashboard statistics and widgets
- Handles role-based navigation visibility

#### Entity Controllers (CRUD Controllers)
Each entity will have a dedicated CRUD controller:

**ProductCrudController**
- Configures product fields and forms
- Implements custom actions for stock management
- Handles image upload and display
- Filters by supplier and category

**CustomerCrudController** 
- Displays customer information and order history
- Handles address management
- Implements customer status changes
- Search functionality for name, email, phone

**OrderCrudController**
- Displays order details with item breakdown
- Status management with automatic timestamp updates
- Customer and shipping address display
- Order item management

**SupplierCrudController**
- Supplier information management
- Product relationship display
- Status management with product impact warnings

**AdminUserCrudController**
- Admin user management (ROLE_ADMIN only)
- Role assignment and validation
- Password hashing and security
- Status management

### 3. Dashboard Widgets

#### Statistics Widget
- Total counts for each entity type
- Recent activity summaries
- Low stock alerts
- Pending order notifications

#### Recent Activity Widget
- Latest orders with status
- Recent customer registrations
- Product updates
- System alerts

## Data Models

### EasyAdmin Field Configurations

#### Product Fields
```php
// List view
TextField::new('name')
TextField::new('sku')
MoneyField::new('sellingPrice')->setCurrency('USD')
IntegerField::new('stockLevel')
ChoiceField::new('status')
AssociationField::new('supplier')

// Detail/Form view
TextField::new('name')
TextField::new('sku')
TextField::new('supplierReference')
TextareaField::new('description')
ArrayField::new('images')
MoneyField::new('costPrice')->setCurrency('USD')
MoneyField::new('sellingPrice')->setCurrency('USD')
NumberField::new('weight')
ArrayField::new('dimensions')
TextField::new('category')
IntegerField::new('stockLevel')
ChoiceField::new('status')
AssociationField::new('supplier')
```

#### Customer Fields
```php
// List view
TextField::new('firstName')
TextField::new('lastName')
EmailField::new('email')
TelephoneField::new('phone')
ChoiceField::new('status')
DateTimeField::new('createdAt')

// Detail view
TextField::new('firstName')
TextField::new('lastName')
EmailField::new('email')
TelephoneField::new('phone')
AssociationField::new('billingAddress')
AssociationField::new('shippingAddress')
ChoiceField::new('status')
AssociationField::new('orders')
```

#### Order Fields
```php
// List view
TextField::new('orderNumber')
AssociationField::new('customer')
MoneyField::new('totalAmount')->setCurrency('USD')
ChoiceField::new('status')
DateTimeField::new('createdAt')

// Detail view
TextField::new('orderNumber')
AssociationField::new('customer')
MoneyField::new('subtotal')->setCurrency('USD')
MoneyField::new('taxAmount')->setCurrency('USD')
MoneyField::new('shippingAmount')->setCurrency('USD')
MoneyField::new('totalAmount')->setCurrency('USD')
ChoiceField::new('status')
AssociationField::new('shippingAddress')
CollectionField::new('orderItems')
DateTimeField::new('shippedAt')
DateTimeField::new('deliveredAt')
```

## Error Handling

### Authentication Errors
- Invalid credentials: Display user-friendly error message
- Inactive user: Specific message about account status
- Session timeout: Redirect to login with timeout message

### Authorization Errors
- Access denied: Custom error page with role information
- Insufficient permissions: Contextual error messages

### Data Validation Errors
- Form validation: Field-level error display
- Entity constraints: Database constraint violation handling
- Business logic errors: Custom validation messages

### System Errors
- Database connection: Graceful degradation
- File upload errors: Clear error messages
- Performance issues: Loading indicators and timeouts

## Testing Strategy

### Unit Tests
- Authentication logic testing
- Role-based access control validation
- Entity CRUD operations
- Custom field configurations

### Integration Tests
- Login flow testing
- Navigation and menu visibility
- Entity relationship handling
- Form submission and validation

### Functional Tests
- Complete admin workflows
- Role-based feature access
- Dashboard functionality
- Search and filtering operations

### Security Tests
- Authentication bypass attempts
- Authorization escalation testing
- CSRF protection validation
- Session security testing

## Performance Considerations

### Database Optimization
- Eager loading for entity relationships
- Pagination for large datasets
- Indexed fields for search operations
- Query optimization for dashboard statistics

### Caching Strategy
- Dashboard statistics caching (5-minute TTL)
- Menu configuration caching
- Entity metadata caching
- Template compilation caching

### Frontend Optimization
- Asset minification and compression
- Image optimization for product images
- Lazy loading for large lists
- Progressive enhancement for JavaScript features

## Security Measures

### Authentication Security
- Password hashing using Symfony's password hasher
- Session management with secure cookies
- CSRF protection on all forms
- Rate limiting for login attempts

### Authorization Security
- Role-based access control at controller level
- Menu item visibility based on permissions
- Entity-level access restrictions
- Audit logging for sensitive operations

### Data Protection
- Input sanitization and validation
- SQL injection prevention through Doctrine ORM
- XSS protection through Twig auto-escaping
- File upload security for product images

## Deployment Considerations

### Environment Configuration
- Separate admin panel subdomain option
- SSL/TLS enforcement for admin routes
- Environment-specific security settings
- Database connection optimization

### Monitoring and Logging
- Admin user activity logging
- Performance monitoring for admin operations
- Error tracking and alerting
- Security event logging

### Backup and Recovery
- Regular database backups
- Admin user data protection
- Configuration backup procedures
- Disaster recovery planning