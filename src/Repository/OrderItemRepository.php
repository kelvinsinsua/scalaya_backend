<?php

namespace App\Repository;

use App\Entity\OrderItem;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, OrderItem::class);
        $this->paginator = $paginator;
    }

    public function save(OrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find order items by order
     */
    public function findByOrder(Order $order): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.order = :order')
            ->setParameter('order', $order)
            ->leftJoin('oi.product', 'p')
            ->addSelect('p')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find order items by product
     */
    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.product = :product')
            ->setParameter('product', $product)
            ->leftJoin('oi.order', 'o')
            ->addSelect('o')
            ->orderBy('oi.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total quantity sold for a product
     */
    public function getTotalQuantitySoldForProduct(Product $product): int
    {
        $result = $this->createQueryBuilder('oi')
            ->select('SUM(oi.quantity) as totalQuantity')
            ->andWhere('oi.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Get total revenue for a product
     */
    public function getTotalRevenueForProduct(Product $product): float
    {
        $result = $this->createQueryBuilder('oi')
            ->select('SUM(oi.lineTotal) as totalRevenue')
            ->andWhere('oi.product = :product')
            ->setParameter('product', $product)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }

    /**
     * Find best selling products
     */
    public function findBestSellingProducts(int $limit = 10): array
    {
        return $this->createQueryBuilder('oi')
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                'SUM(oi.quantity) as totalQuantity',
                'SUM(oi.lineTotal) as totalRevenue',
                'COUNT(DISTINCT oi.order) as orderCount'
            ])
            ->leftJoin('oi.product', 'p')
            ->groupBy('p.id')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find order items with high quantities (potential bulk orders)
     */
    public function findBulkOrderItems(int $minimumQuantity = 10): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.quantity >= :minimumQuantity')
            ->setParameter('minimumQuantity', $minimumQuantity)
            ->leftJoin('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->addSelect('o', 'p')
            ->orderBy('oi.quantity', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get order items statistics
     */
    public function getOrderItemStatistics(): array
    {
        return $this->createQueryBuilder('oi')
            ->select([
                'COUNT(oi.id) as totalItems',
                'SUM(oi.quantity) as totalQuantity',
                'SUM(oi.lineTotal) as totalRevenue',
                'AVG(oi.quantity) as averageQuantity',
                'AVG(oi.unitPrice) as averageUnitPrice',
                'AVG(oi.lineTotal) as averageLineTotal'
            ])
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * Find order items by date range
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('oi')
            ->leftJoin('oi.order', 'o')
            ->andWhere('o.createdAt >= :startDate')
            ->andWhere('o.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->leftJoin('oi.product', 'p')
            ->addSelect('o', 'p')
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find order items with specific unit price range
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): array
    {
        return $this->createQueryBuilder('oi')
            ->andWhere('oi.unitPrice >= :minPrice')
            ->andWhere('oi.unitPrice <= :maxPrice')
            ->setParameter('minPrice', $minPrice)
            ->setParameter('maxPrice', $maxPrice)
            ->leftJoin('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->addSelect('o', 'p')
            ->orderBy('oi.unitPrice', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find order items with pagination using KNP Paginator
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findWithPagination(array $filters = [], int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('oi')
            ->leftJoin('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.customer', 'c')
            ->addSelect('o', 'p', 'c');

        if (isset($filters['product'])) {
            $qb->andWhere('oi.product = :product')
               ->setParameter('product', $filters['product']);
        }

        if (isset($filters['order'])) {
            $qb->andWhere('oi.order = :order')
               ->setParameter('order', $filters['order']);
        }

        if (isset($filters['customer'])) {
            $qb->andWhere('o.customer = :customer')
               ->setParameter('customer', $filters['customer']);
        }

        if (isset($filters['startDate'])) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $filters['startDate']);
        }

        if (isset($filters['endDate'])) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $filters['endDate']);
        }

        if (isset($filters['minPrice'])) {
            $qb->andWhere('oi.unitPrice >= :minPrice')
               ->setParameter('minPrice', $filters['minPrice']);
        }

        if (isset($filters['maxPrice'])) {
            $qb->andWhere('oi.unitPrice <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (isset($filters['minQuantity'])) {
            $qb->andWhere('oi.quantity >= :minQuantity')
               ->setParameter('minQuantity', $filters['minQuantity']);
        }

        $qb->orderBy('o.createdAt', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get best selling products with pagination
     *
     * @param int $page
     * @param int $limit
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return PaginationInterface
     */
    public function getBestSellingProductsPaginated(int $page = 1, int $limit = 20, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('oi')
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                'p.sellingPrice',
                'SUM(oi.quantity) as totalQuantity',
                'SUM(oi.lineTotal) as totalRevenue',
                'COUNT(DISTINCT oi.order) as orderCount',
                'AVG(oi.unitPrice) as averagePrice'
            ])
            ->leftJoin('oi.product', 'p')
            ->leftJoin('oi.order', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $qb->groupBy('p.id')
           ->orderBy('totalQuantity', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get product performance report with pagination
     *
     * @param int $page
     * @param int $limit
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return PaginationInterface
     */
    public function getProductPerformanceReport(int $page = 1, int $limit = 20, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('oi')
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                'p.costPrice',
                'p.sellingPrice',
                'p.stockLevel',
                's.companyName as supplierName',
                'SUM(oi.quantity) as totalQuantitySold',
                'SUM(oi.lineTotal) as totalRevenue',
                'SUM(oi.quantity * p.costPrice) as totalCost',
                'SUM(oi.lineTotal) - SUM(oi.quantity * p.costPrice) as totalProfit',
                'COUNT(DISTINCT oi.order) as orderCount',
                'AVG(oi.unitPrice) as averageSellingPrice'
            ])
            ->leftJoin('oi.product', 'p')
            ->leftJoin('p.supplier', 's')
            ->leftJoin('oi.order', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $qb->groupBy('p.id', 's.id')
           ->orderBy('totalRevenue', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Find bulk order items with pagination
     *
     * @param int $minimumQuantity
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findBulkOrderItemsPaginated(int $minimumQuantity = 10, int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('oi')
            ->andWhere('oi.quantity >= :minimumQuantity')
            ->setParameter('minimumQuantity', $minimumQuantity)
            ->leftJoin('oi.order', 'o')
            ->leftJoin('oi.product', 'p')
            ->leftJoin('o.customer', 'c')
            ->addSelect('o', 'p', 'c')
            ->orderBy('oi.quantity', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get supplier sales report with pagination
     *
     * @param int $page
     * @param int $limit
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return PaginationInterface
     */
    public function getSupplierSalesReport(int $page = 1, int $limit = 20, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('oi')
            ->select([
                's.id',
                's.companyName',
                's.contactEmail',
                'COUNT(DISTINCT p.id) as productCount',
                'SUM(oi.quantity) as totalQuantitySold',
                'SUM(oi.lineTotal) as totalRevenue',
                'COUNT(DISTINCT oi.order) as orderCount',
                'AVG(oi.unitPrice) as averagePrice'
            ])
            ->leftJoin('oi.product', 'p')
            ->leftJoin('p.supplier', 's')
            ->leftJoin('oi.order', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        $qb->groupBy('s.id')
           ->orderBy('totalRevenue', 'DESC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }
}