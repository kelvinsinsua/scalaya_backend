<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Order::class);
        $this->paginator = $paginator;
    }

    public function save(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Order $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find orders by status
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orders by customer
     */
    public function findByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orders within date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.createdAt >= :startDate')
            ->andWhere('o.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orders by multiple statuses
     */
    public function findByStatuses(array $statuses): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', $statuses)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending orders
     */
    public function findPendingOrders(): array
    {
        return $this->findByStatus(Order::STATUS_PENDING);
    }

    /**
     * Find processing orders
     */
    public function findProcessingOrders(): array
    {
        return $this->findByStatus(Order::STATUS_PROCESSING);
    }

    /**
     * Find shipped orders
     */
    public function findShippedOrders(): array
    {
        return $this->findByStatus(Order::STATUS_SHIPPED);
    }

    /**
     * Search orders by order number or customer email
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->andWhere('o.orderNumber LIKE :query OR c.email LIKE :query OR CONCAT(c.firstName, \' \', c.lastName) LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get orders with total amount greater than specified value
     */
    public function findByMinimumTotal(float $minimumTotal): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.totalAmount >= :minimumTotal')
            ->setParameter('minimumTotal', $minimumTotal)
            ->orderBy('o.totalAmount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent orders (last 30 days)
     */
    public function findRecentOrders(int $days = 30): array
    {
        $startDate = new \DateTimeImmutable('-' . $days . ' days');
        
        return $this->createQueryBuilder('o')
            ->andWhere('o.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get order statistics
     */
    public function getOrderStatistics(): array
    {
        $qb = $this->createQueryBuilder('o');
        
        return $qb
            ->select([
                'o.status',
                'COUNT(o.id) as count',
                'SUM(o.totalAmount) as totalAmount',
                'AVG(o.totalAmount) as averageAmount'
            ])
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find orders that need to be shipped (processing status)
     */
    public function findOrdersToShip(): array
    {
        return $this->findByStatus(Order::STATUS_PROCESSING);
    }

    /**
     * Create base query builder with common joins
     */
    private function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->leftJoin('o.shippingAddress', 'sa')
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p');
    }

    /**
     * Find orders with pagination using KNP Paginator
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findWithPagination(array $filters = [], int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createBaseQueryBuilder()
            ->addSelect('c', 'sa', 'oi', 'p');

        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $qb->andWhere('o.status IN (:statuses)')
                   ->setParameter('statuses', $filters['status']);
            } else {
                $qb->andWhere('o.status = :status')
                   ->setParameter('status', $filters['status']);
            }
        }

        if (isset($filters['customer'])) {
            $qb->andWhere('o.customer = :customer')
               ->setParameter('customer', $filters['customer']);
        }

        if (isset($filters['search'])) {
            $qb->andWhere('o.orderNumber LIKE :search OR c.email LIKE :search OR CONCAT(c.firstName, \' \', c.lastName) LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['startDate'])) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $filters['startDate']);
        }

        if (isset($filters['endDate'])) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $filters['endDate']);
        }

        if (isset($filters['minTotal'])) {
            $qb->andWhere('o.totalAmount >= :minTotal')
               ->setParameter('minTotal', $filters['minTotal']);
        }

        if (isset($filters['maxTotal'])) {
            $qb->andWhere('o.totalAmount <= :maxTotal')
               ->setParameter('maxTotal', $filters['maxTotal']);
        }

        $qb->orderBy('o.createdAt', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get orders by customer with pagination
     *
     * @param Customer $customer
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findByCustomerPaginated(Customer $customer, int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->leftJoin('o.orderItems', 'oi')
            ->leftJoin('oi.product', 'p')
            ->addSelect('oi', 'p')
            ->orderBy('o.createdAt', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get daily sales report with pagination
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function getDailySalesReport(\DateTimeInterface $startDate, \DateTimeInterface $endDate, int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->select([
                'DATE(o.createdAt) as orderDate',
                'COUNT(o.id) as orderCount',
                'SUM(o.totalAmount) as totalRevenue',
                'AVG(o.totalAmount) as averageOrderValue',
                'SUM(o.subtotal) as totalSubtotal',
                'SUM(o.taxAmount) as totalTax',
                'SUM(o.shippingAmount) as totalShipping'
            ])
            ->andWhere('o.createdAt >= :startDate')
            ->andWhere('o.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('orderDate')
            ->orderBy('orderDate', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get orders requiring attention (pending/processing)
     *
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findOrdersRequiringAttention(int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->leftJoin('o.orderItems', 'oi')
            ->addSelect('c', 'oi')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', [Order::STATUS_PENDING, Order::STATUS_PROCESSING])
            ->orderBy('o.createdAt', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get revenue by status report
     *
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return array
     */
    public function getRevenueByStatusReport(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select([
                'o.status',
                'COUNT(o.id) as orderCount',
                'SUM(o.totalAmount) as totalRevenue',
                'AVG(o.totalAmount) as averageOrderValue'
            ]);

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->groupBy('o.status')
                  ->orderBy('totalRevenue', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Find orders with high value (above threshold)
     *
     * @param float $threshold
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findHighValueOrders(float $threshold = 1000.0, int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.customer', 'c')
            ->addSelect('c')
            ->andWhere('o.totalAmount >= :threshold')
            ->setParameter('threshold', $threshold)
            ->orderBy('o.totalAmount', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }
}