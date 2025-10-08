<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Customer>
 *
 * @method Customer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Customer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Customer[]    findAll()
 * @method Customer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CustomerRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Customer::class);
        $this->paginator = $paginator;
    }

    public function save(Customer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Customer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find customers by status
     *
     * @param string $status
     * @return Customer[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active customers
     *
     * @return Customer[]
     */
    public function findActive(): array
    {
        return $this->findByStatus(Customer::STATUS_ACTIVE);
    }

    /**
     * Search customers by email
     *
     * @param string $email
     * @return Customer[]
     */
    public function searchByEmail(string $email): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.email LIKE :email')
            ->setParameter('email', '%' . $email . '%')
            ->orderBy('c.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search customers by name (first name or last name)
     *
     * @param string $name
     * @return Customer[]
     */
    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.firstName LIKE :name OR c.lastName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search customers by email or name
     *
     * @param string $searchTerm
     * @return Customer[]
     */
    public function search(string $searchTerm): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.email LIKE :term OR c.firstName LIKE :term OR c.lastName LIKE :term')
            ->setParameter('term', '%' . $searchTerm . '%')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find customers with filtering options
     *
     * @param array $filters
     * @return Customer[]
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('c');

        if (isset($filters['status'])) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $qb->andWhere('c.email LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['email'])) {
            $qb->andWhere('c.email LIKE :email')
               ->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (isset($filters['name'])) {
            $qb->andWhere('c.firstName LIKE :name OR c.lastName LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        return $qb->orderBy('c.lastName', 'ASC')
                  ->addOrderBy('c.firstName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find customer by email (exact match)
     *
     * @param string $email
     * @return Customer|null
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Count customers by status
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get customers with their addresses
     *
     * @return Customer[]
     */
    public function findWithAddresses(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.billingAddress', 'ba')
            ->leftJoin('c.shippingAddress', 'sa')
            ->addSelect('ba', 'sa')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find customers created within date range
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Customer[]
     */
    public function findCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdAt >= :startDate')
            ->andWhere('c.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find customers with pagination using KNP Paginator
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findWithPagination(array $filters = [], int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.billingAddress', 'ba')
            ->leftJoin('c.shippingAddress', 'sa')
            ->addSelect('ba', 'sa');

        if (isset($filters['status'])) {
            $qb->andWhere('c.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $qb->andWhere('c.email LIKE :search OR c.firstName LIKE :search OR c.lastName LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['email'])) {
            $qb->andWhere('c.email LIKE :email')
               ->setParameter('email', '%' . $filters['email'] . '%');
        }

        if (isset($filters['name'])) {
            $qb->andWhere('c.firstName LIKE :name OR c.lastName LIKE :name')
               ->setParameter('name', '%' . $filters['name'] . '%');
        }

        if (isset($filters['createdAfter'])) {
            $qb->andWhere('c.createdAt >= :createdAfter')
               ->setParameter('createdAfter', $filters['createdAfter']);
        }

        if (isset($filters['createdBefore'])) {
            $qb->andWhere('c.createdAt <= :createdBefore')
               ->setParameter('createdBefore', $filters['createdBefore']);
        }

        $qb->orderBy('c.lastName', 'ASC')
           ->addOrderBy('c.firstName', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get customers with order statistics and pagination
     *
     * @param int $page
     * @param int $limit
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return PaginationInterface
     */
    public function getCustomersWithOrderStats(int $page = 1, int $limit = 20, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('c')
            ->select([
                'c.id',
                'c.email',
                'c.firstName',
                'c.lastName',
                'c.status',
                'c.createdAt',
                'COUNT(DISTINCT o.id) as orderCount',
                'SUM(o.totalAmount) as totalSpent',
                'AVG(o.totalAmount) as averageOrderValue',
                'MAX(o.createdAt) as lastOrderDate'
            ])
            ->leftJoin('c.orders', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate OR o.createdAt IS NULL')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate OR o.createdAt IS NULL')
               ->setParameter('endDate', $endDate);
        }

        $qb->groupBy('c.id')
           ->orderBy('totalSpent', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Find customers without orders
     *
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findCustomersWithoutOrders(int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.orders', 'o')
            ->andWhere('o.id IS NULL')
            ->orderBy('c.createdAt', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Find top customers by spending
     *
     * @param int $page
     * @param int $limit
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return PaginationInterface
     */
    public function findTopCustomersBySpending(int $page = 1, int $limit = 20, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('c')
            ->select([
                'c',
                'SUM(o.totalAmount) as totalSpent',
                'COUNT(o.id) as orderCount'
            ])
            ->innerJoin('c.orders', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $qb->groupBy('c.id')
           ->having('SUM(o.totalAmount) > 0')
           ->orderBy('totalSpent', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }
}