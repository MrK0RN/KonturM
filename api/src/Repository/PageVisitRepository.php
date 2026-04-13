<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PageVisit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageVisit>
 */
final class PageVisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageVisit::class);
    }

    /**
     * @return array{
     *   period_days: int,
     *   total_in_period: int,
     *   first_record_at: string|null,
     *   by_day: list<array{date: string, count: int}>,
     *   top_paths: list<array{path: string, count: int}>
     * }
     */
    public function getAdminStats(int $periodDays, int $topLimit = 25): array
    {
        $periodDays = max(1, min(366, $periodDays));
        $topLimit = max(1, min(100, $topLimit));

        $conn = $this->getEntityManager()->getConnection();
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $from = $now->modify('-' . $periodDays . ' days')->setTime(0, 0, 0);
        $fromStr = $from->format('Y-m-d H:i:s');

        $minRow = $conn->fetchAssociative('SELECT MIN(created_at) AS m FROM page_visits');
        $firstAt = null;
        if (is_array($minRow) && isset($minRow['m']) && $minRow['m'] !== null) {
            $firstAt = (string) $minRow['m'];
        }

        $totalInPeriod = (int) $conn->fetchOne(
            'SELECT COUNT(*)::int FROM page_visits WHERE created_at >= :from',
            ['from' => $fromStr]
        );

        $byDayRows = $conn->fetchAllAssociative(
            <<<'SQL'
            SELECT to_char(date_trunc('day', created_at AT TIME ZONE 'UTC'), 'YYYY-MM-DD') AS d, COUNT(*)::int AS c
            FROM page_visits
            WHERE created_at >= :from
            GROUP BY 1
            ORDER BY 1 ASC
            SQL,
            ['from' => $fromStr]
        );

        $byDay = [];
        foreach ($byDayRows as $row) {
            $byDay[] = [
                'date' => (string) $row['d'],
                'count' => (int) $row['c'],
            ];
        }

        $lim = $topLimit;
        $topRows = $conn->fetchAllAssociative(
            <<<SQL
            SELECT path, COUNT(*)::int AS c
            FROM page_visits
            WHERE created_at >= :from
            GROUP BY path
            ORDER BY c DESC
            LIMIT {$lim}
            SQL,
            ['from' => $fromStr]
        );

        $topPaths = [];
        foreach ($topRows as $row) {
            $topPaths[] = [
                'path' => (string) $row['path'],
                'count' => (int) $row['c'],
            ];
        }

        return [
            'period_days' => $periodDays,
            'total_in_period' => $totalInPeriod,
            'first_record_at' => $firstAt,
            'by_day' => $byDay,
            'top_paths' => $topPaths,
        ];
    }
}
