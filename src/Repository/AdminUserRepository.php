<?php

namespace App\Repository;

use App\Entity\AdminUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<AdminUser>
 */
class AdminUserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, AdminUser::class);
        $this->paginator = $paginator;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof AdminUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find an admin user by email for authentication
     */
    public function findByEmail(string $email): ?AdminUser
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find an active admin user by email
     */
    public function findActiveByEmail(string $email): ?AdminUser
    {
        return $this->findOneBy([
            'email' => $email,
            'status' => 'active'
        ]);
    }

    /**
     * Find all active admin users
     */
    public function findActive(): array
    {
        return $this->findBy(['status' => 'active'], ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    /**
     * Find admin users by role
     */
    public function findByRole(string $role): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM admin_users WHERE roles::text LIKE ? ORDER BY last_name ASC, first_name ASC';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, '%"' . $role . '"%');
        $result = $stmt->executeQuery();
        
        $users = [];
        while ($row = $result->fetchAssociative()) {
            $user = new AdminUser();
            $user->setEmail($row['email'])
                ->setPassword($row['password'])
                ->setFirstName($row['first_name'])
                ->setLastName($row['last_name'])
                ->setRoles(json_decode($row['roles'], true))
                ->setStatus($row['status']);
            
            if ($row['last_login_at']) {
                $user->setLastLoginAt(new \DateTime($row['last_login_at']));
            }
            $user->setCreatedAt(new \DateTime($row['created_at']));
            $user->setUpdatedAt(new \DateTime($row['updated_at']));
            
            // Use reflection to set the ID
            $reflection = new \ReflectionClass($user);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($user, $row['id']);
            
            $users[] = $user;
        }
        
        return $users;
    }

    /**
     * Find active admin users by role
     */
    public function findActiveByRole(string $role): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT * FROM admin_users WHERE roles::text LIKE ? AND status = ? ORDER BY last_name ASC, first_name ASC';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, '%"' . $role . '"%');
        $stmt->bindValue(2, 'active');
        $result = $stmt->executeQuery();
        
        $users = [];
        while ($row = $result->fetchAssociative()) {
            $user = new AdminUser();
            $user->setEmail($row['email'])
                ->setPassword($row['password'])
                ->setFirstName($row['first_name'])
                ->setLastName($row['last_name'])
                ->setRoles(json_decode($row['roles'], true))
                ->setStatus($row['status']);
            
            if ($row['last_login_at']) {
                $user->setLastLoginAt(new \DateTime($row['last_login_at']));
            }
            $user->setCreatedAt(new \DateTime($row['created_at']));
            $user->setUpdatedAt(new \DateTime($row['updated_at']));
            
            // Use reflection to set the ID
            $reflection = new \ReflectionClass($user);
            $idProperty = $reflection->getProperty('id');
            $idProperty->setAccessible(true);
            $idProperty->setValue($user, $row['id']);
            
            $users[] = $user;
        }
        
        return $users;
    }

    /**
     * Search admin users by name or email
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.firstName LIKE :query')
            ->orWhere('a.lastName LIKE :query')
            ->orWhere('a.email LIKE :query')
            ->orWhere('CONCAT(a.firstName, \' \', a.lastName) LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.lastName', 'ASC')
            ->addOrderBy('a.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find admin users with recent login activity
     */
    public function findRecentlyActive(\DateTimeInterface $since): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.lastLoginAt >= :since')
            ->andWhere('a.status = :status')
            ->setParameter('since', $since)
            ->setParameter('status', 'active')
            ->orderBy('a.lastLoginAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count admin users by status
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count admin users by role
     */
    public function countByRole(string $role): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT COUNT(*) FROM admin_users WHERE roles::text LIKE ?';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(1, '%"' . $role . '"%');
        $result = $stmt->executeQuery();
        
        return (int) $result->fetchOne();
    }

    /**
     * Find admin users created within a date range
     */
    public function findCreatedBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.createdAt >= :start')
            ->andWhere('a.createdAt <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Update last login timestamp for a user
     */
    public function updateLastLogin(AdminUser $user): void
    {
        $user->updateLastLogin();
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Save an admin user entity
     */
    public function save(AdminUser $adminUser, bool $flush = false): void
    {
        $this->getEntityManager()->persist($adminUser);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an admin user entity
     */
    public function remove(AdminUser $adminUser, bool $flush = false): void
    {
        $this->getEntityManager()->remove($adminUser);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find admin users with pagination using KNP Paginator
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findWithPagination(array $filters = [], int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('a');

        if (isset($filters['status'])) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['role'])) {
            $qb->andWhere('JSON_CONTAINS(a.roles, :role) = 1')
               ->setParameter('role', json_encode($filters['role']));
        }

        if (isset($filters['search'])) {
            $qb->andWhere('a.firstName LIKE :search OR a.lastName LIKE :search OR a.email LIKE :search OR CONCAT(a.firstName, \' \', a.lastName) LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['lastLoginAfter'])) {
            $qb->andWhere('a.lastLoginAt >= :lastLoginAfter')
               ->setParameter('lastLoginAfter', $filters['lastLoginAfter']);
        }

        if (isset($filters['createdAfter'])) {
            $qb->andWhere('a.createdAt >= :createdAfter')
               ->setParameter('createdAfter', $filters['createdAfter']);
        }

        $qb->orderBy('a.lastName', 'ASC')
           ->addOrderBy('a.firstName', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get admin user activity report
     *
     * @param int $page
     * @param int $limit
     * @param int $daysSince
     * @return PaginationInterface
     */
    public function getActivityReport(int $page = 1, int $limit = 20, int $daysSince = 30): PaginationInterface
    {
        $sinceDate = new \DateTimeImmutable('-' . $daysSince . ' days');
        
        $qb = $this->createQueryBuilder('a')
            ->select([
                'a.id',
                'a.email',
                'a.firstName',
                'a.lastName',
                'a.roles',
                'a.status',
                'a.lastLoginAt',
                'a.createdAt',
                'CASE WHEN a.lastLoginAt >= :sinceDate THEN 1 ELSE 0 END as isRecentlyActive'
            ])
            ->setParameter('sinceDate', $sinceDate)
            ->orderBy('a.lastLoginAt', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Find inactive admin users (no recent login)
     *
     * @param int $daysSinceLastLogin
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findInactiveUsers(int $daysSinceLastLogin = 90, int $page = 1, int $limit = 20): PaginationInterface
    {
        $cutoffDate = new \DateTimeImmutable('-' . $daysSinceLastLogin . ' days');
        
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.lastLoginAt < :cutoffDate OR a.lastLoginAt IS NULL')
            ->andWhere('a.status = :status')
            ->setParameter('cutoffDate', $cutoffDate)
            ->setParameter('status', 'active')
            ->orderBy('a.lastLoginAt', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get role distribution statistics
     *
     * @return array
     */
    public function getRoleDistribution(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Get all possible roles from the database
        $sql = 'SELECT DISTINCT JSON_UNQUOTE(JSON_EXTRACT(roles, CONCAT("$[", numbers.n, "]"))) as role
                FROM admin_users 
                CROSS JOIN (
                    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                ) numbers
                WHERE JSON_EXTRACT(roles, CONCAT("$[", numbers.n, "]")) IS NOT NULL';
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $roles = $result->fetchAllAssociative();
        
        $distribution = [];
        foreach ($roles as $roleData) {
            $role = $roleData['role'];
            if ($role && $role !== 'null') {
                $distribution[$role] = $this->countByRole($role);
            }
        }
        
        return $distribution;
    }
}