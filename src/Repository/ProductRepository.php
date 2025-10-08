<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Product::class);
        $this->paginator = $paginator;
    }

    /**
     * Find products by supplier
     *
     * @param Supplier $supplier
     * @return Product[]
     */
    public function findBySupplier(Supplier $supplier): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products by category
     *
     * @param string $category
     * @return Product[]
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.category = :category')
            ->setParameter('category', $category)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find available products (status = available and stock > 0)
     *
     * @return Product[]
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.stockLevel > 0')
            ->setParameter('status', 'available')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products by availability status
     *
     * @param string $status
     * @return Product[]
     */
    public function findByAvailability(string $status): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', $status)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products with low stock (below threshold)
     *
     * @param int $threshold
     * @return Product[]
     */
    public function findLowStock(int $threshold = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stockLevel <= :threshold')
            ->andWhere('p.status != :discontinued')
            ->setParameter('threshold', $threshold)
            ->setParameter('discontinued', 'discontinued')
            ->orderBy('p.stockLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search products by name or SKU
     *
     * @param string $searchTerm
     * @return Product[]
     */
    public function searchByNameOrSku(string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.name LIKE :searchTerm OR p.sku LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find products with filters
     *
     * @param array $filters
     * @return Product[]
     */
    public function findWithFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.supplier', 's');

        if (isset($filters['supplier'])) {
            $qb->andWhere('p.supplier = :supplier')
               ->setParameter('supplier', $filters['supplier']);
        }

        if (isset($filters['category'])) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['available']) && $filters['available']) {
            $qb->andWhere('p.status = :availableStatus')
               ->andWhere('p.stockLevel > 0')
               ->setParameter('availableStatus', 'available');
        }

        if (isset($filters['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.sku LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['minPrice'])) {
            $qb->andWhere('p.sellingPrice >= :minPrice')
               ->setParameter('minPrice', $filters['minPrice']);
        }

        if (isset($filters['maxPrice'])) {
            $qb->andWhere('p.sellingPrice <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        return $qb->orderBy('p.name', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Get all unique categories
     *
     * @return array
     */
    public function getCategories(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('DISTINCT p.category')
            ->andWhere('p.category IS NOT NULL')
            ->orderBy('p.category', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'category');
    }

    /**
     * Count products by status
     *
     * @return array
     */
    public function countByStatus(): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p.id) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($result as $row) {
            $counts[$row['status']] = (int) $row['count'];
        }

        return $counts;
    }

    /**
     * Find products by supplier with pagination using KNP Paginator
     *
     * @param Supplier $supplier
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findBySupplierPaginated(Supplier $supplier, int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('p.name', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Find products with pagination and filters using KNP Paginator
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findWithPagination(array $filters = [], int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.supplier', 's')
            ->addSelect('s');

        if (isset($filters['supplier'])) {
            $qb->andWhere('p.supplier = :supplier')
               ->setParameter('supplier', $filters['supplier']);
        }

        if (isset($filters['category'])) {
            $qb->andWhere('p.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (isset($filters['status'])) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['available']) && $filters['available']) {
            $qb->andWhere('p.status = :availableStatus')
               ->andWhere('p.stockLevel > 0')
               ->setParameter('availableStatus', 'available');
        }

        if (isset($filters['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.sku LIKE :search OR s.companyName LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['minPrice'])) {
            $qb->andWhere('p.sellingPrice >= :minPrice')
               ->setParameter('minPrice', $filters['minPrice']);
        }

        if (isset($filters['maxPrice'])) {
            $qb->andWhere('p.sellingPrice <= :maxPrice')
               ->setParameter('maxPrice', $filters['maxPrice']);
        }

        if (isset($filters['lowStock']) && $filters['lowStock']) {
            $threshold = $filters['stockThreshold'] ?? 10;
            $qb->andWhere('p.stockLevel <= :threshold')
               ->setParameter('threshold', $threshold);
        }

        $qb->orderBy('p.name', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Get products with sales data and pagination
     *
     * @param int $page
     * @param int $limit
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return PaginationInterface
     */
    public function getProductsWithSalesData(int $page = 1, int $limit = 20, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): PaginationInterface
    {
        $qb = $this->createQueryBuilder('p')
            ->select([
                'p.id',
                'p.name',
                'p.sku',
                'p.sellingPrice',
                'p.stockLevel',
                'p.status',
                's.companyName as supplierName',
                'COUNT(DISTINCT oi.id) as orderItemCount',
                'SUM(oi.quantity) as totalQuantitySold',
                'SUM(oi.lineTotal) as totalRevenue',
                'AVG(oi.unitPrice) as averagePrice'
            ])
            ->leftJoin('p.supplier', 's')
            ->leftJoin('p.orderItems', 'oi')
            ->leftJoin('oi.order', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate OR o.createdAt IS NULL')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate OR o.createdAt IS NULL')
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

    public function save(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Product $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}