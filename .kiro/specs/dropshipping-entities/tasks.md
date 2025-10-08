# Implementation Plan

- [x] 1. Create base entity structure and Address entity
  - Create Address entity with proper Doctrine annotations and validation
  - Implement basic fields: firstName, lastName, company, addressLine1, addressLine2, city, state, postalCode, country, phone
  - Add Doctrine mapping annotations and validation constraints
  - _Requirements: 6.1, 7.4_

- [x] 2. Create Supplier entity with repository
  - Implement Supplier entity with all required and optional fields
  - Add Doctrine annotations for table mapping and relationships
  - Create SupplierRepository with methods for finding active suppliers and searching by name
  - Add validation constraints for required fields and email format
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 7.1, 7.2, 7.3_

- [x] 3. Create Product entity with supplier relationship
  - Implement Product entity with pricing, inventory, and metadata fields
  - Establish ManyToOne relationship with Supplier entity
  - Add JSON field handling for images and dimensions arrays
  - Create ProductRepository with filtering methods by supplier, category, and availability
  - Add validation for SKU uniqueness and price constraints
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 6.1, 6.3, 7.4_

- [x] 4. Create Customer entity with address relationships
  - Implement Customer entity with personal information fields
  - Establish OneToOne relationships with billing and shipping Address entities
  - Create CustomerRepository with search and filtering capabilities
  - Add email uniqueness validation and status choice constraints
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 6.1, 7.4_

- [x] 5. Create AdminUser entity with Symfony security integration
  - Implement AdminUser entity implementing UserInterface
  - Add password hashing and role-based access control
  - Create AdminUserRepository with authentication methods
  - Add validation for email uniqueness and role constraints
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 7.4_

- [x] 6. Create Order and OrderItem entities with relationships
  - Implement Order entity with totals calculation and status tracking
  - Create OrderItem entity with product and pricing information
  - Establish proper relationships: Order->Customer, Order->OrderItems, OrderItem->Product
  - Add order number generation and status choice constraints
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 6.1, 6.2_

- [x] 7. Create and run database migrations
  - Generate Doctrine migrations for all entities
  - Review migration files for proper foreign key constraints
  - Execute migrations to create database schema
  - Verify table structure and relationships in database
  - _Requirements: 6.1, 6.2, 7.3_

- [x] 8. Add entity validation and serialization
  - Add comprehensive validation constraints to all entities
  - Implement Symfony Serializer groups for API responses
  - Add custom validation for business rules (stock levels, order totals)
  - Test validation with invalid data scenarios
  - _Requirements: 7.4, 7.5_

- [ ] 9. Create repository methods and custom queries
  - Implement custom finder methods in all repository classes
  - Add complex queries for reporting and filtering
  - Optimize queries with proper joins and indexing
  - Add pagination support for list queries
  - _Requirements: 1.4, 2.4, 3.4, 6.3_

- [ ] 10. Write comprehensive entity tests
  - Create unit tests for entity validation and business logic
  - Write integration tests for repository methods
  - Test entity relationships and cascading operations
  - Add tests for custom validation rules and constraints
  - _Requirements: 6.2, 6.3, 7.4_