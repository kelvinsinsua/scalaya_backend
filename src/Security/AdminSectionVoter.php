<?php

namespace App\Security;

use App\Entity\AdminUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminSectionVoter extends Voter
{
    // Define the permissions/attributes this voter handles
    public const VIEW_PRODUCTS = 'VIEW_PRODUCTS';
    public const MANAGE_PRODUCTS = 'MANAGE_PRODUCTS';
    public const VIEW_CUSTOMERS = 'VIEW_CUSTOMERS';
    public const MANAGE_CUSTOMERS = 'MANAGE_CUSTOMERS';
    public const VIEW_ORDERS = 'VIEW_ORDERS';
    public const MANAGE_ORDERS = 'MANAGE_ORDERS';
    public const VIEW_SUPPLIERS = 'VIEW_SUPPLIERS';
    public const MANAGE_SUPPLIERS = 'MANAGE_SUPPLIERS';
    public const VIEW_ADMIN_USERS = 'VIEW_ADMIN_USERS';
    public const MANAGE_ADMIN_USERS = 'MANAGE_ADMIN_USERS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // This voter handles the admin section permissions
        return in_array($attribute, [
            self::VIEW_PRODUCTS,
            self::MANAGE_PRODUCTS,
            self::VIEW_CUSTOMERS,
            self::MANAGE_CUSTOMERS,
            self::VIEW_ORDERS,
            self::MANAGE_ORDERS,
            self::VIEW_SUPPLIERS,
            self::MANAGE_SUPPLIERS,
            self::VIEW_ADMIN_USERS,
            self::MANAGE_ADMIN_USERS,
        ]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // If the user is not an AdminUser, deny access
        if (!$user instanceof AdminUser) {
            return false;
        }

        // If the user is inactive, deny access
        if (!$user->isActive()) {
            return false;
        }

        // Check permissions based on role and attribute
        return match ($attribute) {
            self::VIEW_PRODUCTS, self::MANAGE_PRODUCTS => $this->canAccessProducts($user),
            self::VIEW_CUSTOMERS, self::MANAGE_CUSTOMERS => $this->canAccessCustomers($user),
            self::VIEW_ORDERS, self::MANAGE_ORDERS => $this->canAccessOrders($user),
            self::VIEW_SUPPLIERS, self::MANAGE_SUPPLIERS => $this->canAccessSuppliers($user),
            self::VIEW_ADMIN_USERS, self::MANAGE_ADMIN_USERS => $this->canAccessAdminUsers($user),
            default => false,
        };
    }

    /**
     * Products are accessible by ROLE_ADMIN and ROLE_MANAGER
     */
    private function canAccessProducts(AdminUser $user): bool
    {
        return $user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_MANAGER');
    }

    /**
     * Customers are accessible by all admin roles
     */
    private function canAccessCustomers(AdminUser $user): bool
    {
        return $user->hasRole('ROLE_ADMIN') || 
               $user->hasRole('ROLE_MANAGER') || 
               $user->hasRole('ROLE_OPERATOR');
    }

    /**
     * Orders are accessible by all admin roles
     */
    private function canAccessOrders(AdminUser $user): bool
    {
        return $user->hasRole('ROLE_ADMIN') || 
               $user->hasRole('ROLE_MANAGER') || 
               $user->hasRole('ROLE_OPERATOR');
    }

    /**
     * Suppliers are accessible by ROLE_ADMIN and ROLE_MANAGER
     */
    private function canAccessSuppliers(AdminUser $user): bool
    {
        return $user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_MANAGER');
    }

    /**
     * Admin Users are accessible only by ROLE_ADMIN
     */
    private function canAccessAdminUsers(AdminUser $user): bool
    {
        return $user->hasRole('ROLE_ADMIN');
    }
}