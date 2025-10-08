<?php

namespace App\Tests\Entity;

use App\Entity\AdminUser;
use PHPUnit\Framework\TestCase;

class AdminUserTest extends TestCase
{
    public function testValidAdminUser(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setEmail('admin@example.com')
            ->setPassword('hashedpassword')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setRoles(['ROLE_ADMIN'])
            ->setStatus('active');

        $this->assertEquals('admin@example.com', $adminUser->getEmail());
        $this->assertEquals('hashedpassword', $adminUser->getPassword());
        $this->assertEquals('John', $adminUser->getFirstName());
        $this->assertEquals('Doe', $adminUser->getLastName());
        $this->assertEquals('active', $adminUser->getStatus());
        $this->assertContains('ROLE_ADMIN', $adminUser->getRoles());
    }

    public function testEmailValidation(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setEmail('test@example.com');
        
        $this->assertEquals('test@example.com', $adminUser->getEmail());
        $this->assertEquals('test@example.com', $adminUser->getUserIdentifier());
    }

    public function testRequiredFieldsSetters(): void
    {
        $adminUser = new AdminUser();
        
        $adminUser->setEmail('test@example.com');
        $adminUser->setPassword('password123');
        $adminUser->setFirstName('John');
        $adminUser->setLastName('Doe');
        
        $this->assertEquals('test@example.com', $adminUser->getEmail());
        $this->assertEquals('password123', $adminUser->getPassword());
        $this->assertEquals('John', $adminUser->getFirstName());
        $this->assertEquals('Doe', $adminUser->getLastName());
    }

    public function testStatusValidation(): void
    {
        $adminUser = new AdminUser();
        
        // Test valid statuses
        $adminUser->setStatus('active');
        $this->assertEquals('active', $adminUser->getStatus());
        
        $adminUser->setStatus('inactive');
        $this->assertEquals('inactive', $adminUser->getStatus());
        
        // Test invalid status throws exception
        $this->expectException(\InvalidArgumentException::class);
        $adminUser->setStatus('invalid_status');
    }

    public function testUserInterface(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setEmail('admin@example.com');

        $this->assertEquals('admin@example.com', $adminUser->getUserIdentifier());
    }

    public function testRoles(): void
    {
        $adminUser = new AdminUser();
        
        // Test default roles
        $roles = $adminUser->getRoles();
        $this->assertContains('ROLE_USER', $roles);

        // Test setting roles
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_MANAGER']);
        $roles = $adminUser->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_MANAGER', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testInvalidRoles(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid role "ROLE_INVALID"');

        $adminUser = new AdminUser();
        $adminUser->setRoles(['ROLE_INVALID']);
    }

    public function testRoleCheckers(): void
    {
        $adminUser = new AdminUser();
        
        $adminUser->setRoles(['ROLE_ADMIN']);
        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($adminUser->isManager());
        $this->assertFalse($adminUser->isOperator());

        $adminUser->setRoles(['ROLE_MANAGER']);
        $this->assertFalse($adminUser->isAdmin());
        $this->assertTrue($adminUser->isManager());
        $this->assertFalse($adminUser->isOperator());

        $adminUser->setRoles(['ROLE_OPERATOR']);
        $this->assertFalse($adminUser->isAdmin());
        $this->assertFalse($adminUser->isManager());
        $this->assertTrue($adminUser->isOperator());
    }

    public function testHasRole(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setRoles(['ROLE_ADMIN', 'ROLE_MANAGER']);

        $this->assertTrue($adminUser->hasRole('ROLE_ADMIN'));
        $this->assertTrue($adminUser->hasRole('ROLE_MANAGER'));
        $this->assertTrue($adminUser->hasRole('ROLE_USER')); // Always present
        $this->assertFalse($adminUser->hasRole('ROLE_OPERATOR'));
    }

    public function testStatus(): void
    {
        $adminUser = new AdminUser();
        
        // Test default status
        $this->assertEquals('active', $adminUser->getStatus());
        $this->assertTrue($adminUser->isActive());

        // Test setting status
        $adminUser->setStatus('inactive');
        $this->assertEquals('inactive', $adminUser->getStatus());
        $this->assertFalse($adminUser->isActive());
    }

    public function testInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Status must be either "active" or "inactive"');

        $adminUser = new AdminUser();
        $adminUser->setStatus('invalid');
    }

    public function testFullName(): void
    {
        $adminUser = new AdminUser();
        $adminUser->setFirstName('John')
            ->setLastName('Doe');

        $this->assertEquals('John Doe', $adminUser->getFullName());
    }

    public function testLastLogin(): void
    {
        $adminUser = new AdminUser();
        
        $this->assertNull($adminUser->getLastLoginAt());

        $adminUser->updateLastLogin();
        $this->assertInstanceOf(\DateTimeInterface::class, $adminUser->getLastLoginAt());
        
        $now = new \DateTime();
        $this->assertEquals($now->format('Y-m-d H:i'), $adminUser->getLastLoginAt()->format('Y-m-d H:i'));
    }

    public function testTimestamps(): void
    {
        $adminUser = new AdminUser();
        
        // Test that timestamps are null initially
        $this->assertNull($adminUser->getCreatedAt());
        $this->assertNull($adminUser->getUpdatedAt());

        // Test PrePersist callback
        $adminUser->setCreatedAtValue();
        $this->assertInstanceOf(\DateTimeInterface::class, $adminUser->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $adminUser->getUpdatedAt());

        $createdAt = $adminUser->getCreatedAt();
        
        // Wait a moment and test PreUpdate callback
        sleep(1);
        $adminUser->setUpdatedAtValue();
        $this->assertEquals($createdAt, $adminUser->getCreatedAt()); // Should not change
        $this->assertGreaterThan($createdAt, $adminUser->getUpdatedAt()); // Should be updated
    }

    public function testPasswordInterface(): void
    {
        $adminUser = new AdminUser();
        $password = 'hashed_password_123';
        
        $adminUser->setPassword($password);
        $this->assertEquals($password, $adminUser->getPassword());

        // Test that eraseCredentials doesn't throw an error
        $adminUser->eraseCredentials();
        $this->assertEquals($password, $adminUser->getPassword()); // Password should remain
    }

    public function testFieldLengthValidation(): void
    {
        $adminUser = new AdminUser();
        
        // Test that we can set normal length names
        $adminUser->setFirstName('John');
        $adminUser->setLastName('Doe');
        
        $this->assertEquals('John', $adminUser->getFirstName());
        $this->assertEquals('Doe', $adminUser->getLastName());
        
        // Test that very long names can be set (validation happens at form/API level)
        $longName = str_repeat('a', 101);
        $adminUser->setFirstName($longName);
        $this->assertEquals($longName, $adminUser->getFirstName());
    }
}