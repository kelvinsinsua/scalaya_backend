# Design Document

## Overview

This design implements API Platform bundle to provide comprehensive REST API endpoints for the dropshipping platform. The solution will expose all entities (Customer, Supplier, Product, Order, OrderItem, Address, AdminUser) through standardized REST APIs with automatic OpenAPI documentation, proper authentication/authorization, and advanced features like filtering, sorting, and pagination.

## Architecture

### API Platform Integration
- **Bundle Installation**: API Platform bundle will be installed via Composer
- **Configuration**: Central configuration in `config/packages/api_platform.yaml`
- **Entity Annotations**: Entities will use API Platform attributes for resource configuration
- **Serialization Groups**: Leverage existing Symfony serializer groups for data exposure control
- **Security Integration**: Integrate with existing JWT authentication and Symfony security

### API Structure
```
/api/
├── customers/          # Customer CRUD operations
├── suppliers/          # Supplier CRUD operations  
├── products/           # Product CRUD operations
├── orders/             # Order CRUD operations
├── order_items/        # OrderItem CRUD operations
├── addresses/          # Address CRUD operations
├── admin_users/        # AdminUser CRUD operations
└── docs/               # OpenAPI documentation
```

### Authentication & Authorization
- **JWT Integration**: Reuse existing JWT authentication system
- **Role-Based Access**: Different access levels for different user types
- **Security Voters**: Leverage existing security voters for fine-grained access control

## Components and Interfaces

### 1. Entity Resource Configuration

Each entity will be configured as an API Platform resource using PHP attributes:

```php
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Patch(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['entity:read']],
    denormalizationContext: ['groups' => ['entity:write']],
    security: "is_granted('ROLE_USER')"
)]
```

### 2. Serialization Groups Strategy

**Customer Entity:**
- `customer:list` - Basic info for listings (id, name, email, status)
- `customer:detail` - Full details including addresses and orders
- `customer:write` - Fields allowed for create/update operations

**Supplier Entity:**
- `supplier:list` - Basic supplier info (id, company name, contact email, status)
- `supplier:detail` - Full details including products and contact information
- `supplier:write` - Fields allowed for create/update operations

**Product Entity:**
- `product:list` - Basic product info (id, name, sku, price, stock)
- `product:detail` - Full product details including supplier and images
- `product:write` - Fields allowed for create/update operations

**Order Entity:**
- `order:list` - Basic order info (id, order number, total, status, customer)
- `order:detail` - Full order details including items and addresses
- `order:write` - Fields allowed for create/update operations

### 3. Security Configuration

**Access Control Matrix:**
- **ROLE_ADMIN**: Full access to all endpoints
- **ROLE_MANAGER**: Read/write access to customers, suppliers, products, orders
- **ROLE_OPERATOR**: Read access to all, write access to orders only
- **ROLE_CUSTOMER**: Access to own customer data and orders only
- **ROLE_SUPPLIER**: Access to own supplier data and related products only

### 4. Custom Operations

**Product Operations:**
- `GET /api/products/low-stock` - Products with low stock levels
- `GET /api/products/by-supplier/{supplierId}` - Products by specific supplier

**Order Operations:**
- `POST /api/orders/{id}/ship` - Mark order as shipped
- `POST /api/orders/{id}/deliver` - Mark order as delivered
- `GET /api/orders/by-status/{status}` - Orders by status

**Customer Operations:**
- `GET /api/customers/{id}/orders` - Customer's order history

## Data Models

### API Resource Attributes Configuration

**Customer Resource:**
```php
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_MANAGER')"),
        new Get(security: "is_granted('ROLE_MANAGER') or object == user"),
        new Post(security: "is_granted('ROLE_MANAGER')"),
        new Put(security: "is_granted('ROLE_MANAGER') or object == user"),
        new Patch(security: "is_granted('ROLE_MANAGER') or object == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['customer:read']],
    denormalizationContext: ['groups' => ['customer:write']],
    paginationItemsPerPage: 20
)]
```

**Product Resource:**
```php
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_MANAGER')"),
        new Put(security: "is_granted('ROLE_MANAGER')"),
        new Patch(security: "is_granted('ROLE_MANAGER')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['product:read']],
    denormalizationContext: ['groups' => ['product:write']],
    paginationItemsPerPage: 30
)]
```

### Filtering and Sorting Configuration

**Product Filters:**
- Search by name, SKU, category
- Filter by supplier, status, stock level range
- Price range filtering

**Order Filters:**
- Filter by status, customer, date range
- Search by order number

**Customer Filters:**
- Search by name, email
- Filter by status, registration date

## Error Handling

### Validation Error Format
```json
{
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Validation failed",
    "violations": [
        {
            "propertyPath": "email",
            "message": "This value is not a valid email address."
        }
    ]
}
```

### Authentication Error Format
```json
{
    "code": 401,
    "message": "JWT Token not found"
}
```

### Authorization Error Format
```json
{
    "type": "https://tools.ietf.org/html/rfc2616#section-10",
    "title": "An error occurred",
    "detail": "Access Denied."
}
```

## Testing Strategy

### Unit Tests
- Test entity resource configuration
- Test serialization groups
- Test custom operations
- Test security constraints

### Integration Tests
- Test complete CRUD operations for each entity
- Test authentication and authorization
- Test filtering, sorting, and pagination
- Test error handling scenarios

### API Documentation Tests
- Verify OpenAPI schema generation
- Test documentation completeness
- Validate example requests/responses

## Configuration Files

### API Platform Configuration (`config/packages/api_platform.yaml`)
```yaml
api_platform:
    title: 'Dropshipping Platform API'
    version: '1.0.0'
    description: 'REST API for dropshipping platform management'
    
    # OpenAPI configuration
    openapi:
        contact:
            name: 'API Support'
            email: 'api-support@example.com'
        license:
            name: 'Proprietary'
    
    # Default pagination
    collection:
        pagination:
            items_per_page: 20
            maximum_items_per_page: 100
            page_parameter_name: 'page'
            items_per_page_parameter_name: 'itemsPerPage'
    
    # Formats
    formats:
        jsonld: ['application/ld+json']
        json: ['application/json']
        html: ['text/html']
    
    # Error handling
    show_webby: false
    
    # Security
    defaults:
        stateless: true
```

### Security Configuration Updates
```yaml
# config/packages/security.yaml additions
security:
    firewalls:
        api:
            pattern: ^/api/
            stateless: true
            jwt: ~
```

### Routing Configuration
```yaml
# config/routes/api_platform.yaml
api_platform:
    resource: .
    type: api_platform
    prefix: /api
```

## Performance Considerations

### Caching Strategy
- HTTP caching headers for GET requests
- Entity-level caching for frequently accessed data
- Query result caching for complex filters

### Database Optimization
- Proper indexing on filterable fields
- Eager loading for related entities in detail views
- Query optimization for collection endpoints

### Serialization Optimization
- Minimal serialization groups for list views
- Lazy loading for related entities
- Conditional field inclusion based on user roles

## Monitoring and Logging

### API Metrics
- Request/response times
- Error rates by endpoint
- Authentication failure rates
- Most accessed endpoints

### Logging Strategy
- API request/response logging
- Authentication/authorization events
- Validation error logging
- Performance bottleneck identification

## Migration Strategy

### Deployment Steps
1. Install API Platform bundle
2. Configure entity resources
3. Update serialization groups
4. Configure security rules
5. Test all endpoints
6. Deploy documentation
7. Monitor API usage

### Backward Compatibility
- Existing admin panel remains functional
- Current authentication system unchanged
- Database schema remains the same
- Existing services continue to work