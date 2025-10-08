<?php

namespace App\Repository;

use App\Entity\Supplier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Supplier>
 *
 * @method Supplier|null find($id, $lockMode = null, $lockVersion = null)
 * @method Supplier|null findOneBy(array $criteria, array $orderBy = null)
 * @method Supplier[]    findAll()
 * @method Supplier[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SupplierRepository extends ServiceEntityRepository
{
    private PaginatorInterface $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Supplier::class);
        $this->paginator = $paginator;
    }

    /**
     * Find supplier by contact email (exact match)
     *
     * @param string $contactEmail
     * @return Supplier|null
     */
    public function findByContactEmail(string $contactEmail): ?Supplier
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.contactEmail = :contactEmail')
            ->setParameter('contactEmail', $contactEmail)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active suppliers
     *
     * @return Supplier[]
     */
    public function findActiveSuppliers(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find suppliers by status
     *
     * @param string $status
     * @return Supplier[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search suppliers by company name
     *
     * @param string $searchTerm
     * @return Supplier[]
     */
    public function searchByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.companyName LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search active suppliers by company name
     *
     * @param string $searchTerm
     * @return Supplier[]
     */
    public function searchActiveByName(string $searchTerm): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.companyName LIKE :searchTerm')
            ->andWhere('s.status = :status')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('status', 'active')
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find suppliers with filtering and search capabilities
     *
     * @param array $filters
     * @return Supplier[]
     */
    public function findWithFilters(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('s');

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere('s.companyName LIKE :search OR s.contactEmail LIKE :search OR s.contactPerson LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->orderBy('s.companyName', 'ASC')
                  ->getQuery()
                  ->getResult();
    }

    /**
     * Count suppliers by status
     *
     * @param string $status
     * @return int
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->andWhere('s.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Find suppliers with pagination using KNP Paginator
     *
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return PaginationInterface
     */
    public function findWithPagination(array $filters = [], int $page = 1, int $limit = 20): PaginationInterface
    {
        $qb = $this->createQueryBuilder('s');

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere('s.companyName LIKE :search OR s.contactEmail LIKE :search OR s.contactPerson LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        $qb->orderBy('s.companyName', 'ASC');

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }

    /**
     * Count suppliers with filters
     *
     * @param array $filters
     * @return int
     */
    public function countWithFilters(array $filters = []): int
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)');

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['search']) && !empty($filters['search'])) {
            $qb->andWhere('s.companyName LIKE :search OR s.contactEmail LIKE :search OR s.contactPerson LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get supplier statistics
     *
     * @return array
     */
    public function getSupplierStatistics(): array
    {
        $statusCounts = $this->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as count')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult();

        $productCounts = $this->createQueryBuilder('s')
            ->select('s.id, s.companyName, COUNT(p.id) as productCount')
            ->leftJoin('s.products', 'p')
            ->groupBy('s.id')
            ->orderBy('productCount', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return [
            'statusCounts' => $statusCounts,
            'topSuppliersByProducts' => $productCounts
        ];
    }

    /**
     * Find suppliers with their product counts
     *
     * @return array
     */
    public function findWithProductCounts(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s, COUNT(p.id) as productCount')
            ->leftJoin('s.products', 'p')
            ->groupBy('s.id')
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find suppliers created within date range
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return array
     */
    public function findCreatedBetween(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.createdAt >= :startDate')
            ->andWhere('s.createdAt <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find suppliers with active products
     *
     * @return array
     */
    public function findWithActiveProducts(): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.products', 'p')
            ->andWhere('p.status = :productStatus')
            ->andWhere('s.status = :supplierStatus')
            ->setParameter('productStatus', 'available')
            ->setParameter('supplierStatus', 'active')
            ->distinct()
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find suppliers without products
     *
     * @return array
     */
    public function findWithoutProducts(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.products', 'p')
            ->andWhere('p.id IS NULL')
            ->orderBy('s.companyName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get suppliers with revenue data
     *
     * @param \DateTimeInterface|null $startDate
     * @param \DateTimeInterface|null $endDate
     * @return array
     */
    public function getSuppliersWithRevenue(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select([
                's.id',
                's.companyName',
                's.contactEmail',
                'COUNT(DISTINCT p.id) as productCount',
                'COUNT(DISTINCT oi.id) as orderItemCount',
                'SUM(oi.lineTotal) as totalRevenue',
                'AVG(oi.unitPrice) as averagePrice'
            ])
            ->leftJoin('s.products', 'p')
            ->leftJoin('p.orderItems', 'oi')
            ->leftJoin('oi.order', 'o');

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :startDate')
               ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :endDate')
               ->setParameter('endDate', $endDate);
        }

        return $qb->groupBy('s.id')
                  ->orderBy('totalRevenue', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}