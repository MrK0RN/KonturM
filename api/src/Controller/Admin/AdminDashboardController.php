<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AsController]
#[Route('/api/admin/dashboard')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController
{
    public function __construct(
        private readonly Connection $conn,
    ) {
    }

    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $dayStart = $now->setTime(0, 0, 0);
        $dayEnd = $dayStart->modify('+1 day');
        $monthStart = $dayStart->modify('first day of this month')->setTime(0, 0, 0);
        $nextMonthStart = $monthStart->modify('+1 month');

        $seriesFrom = $dayStart->modify('-13 days');

        $dayStartStr = $dayStart->format('Y-m-d H:i:s');
        $dayEndStr = $dayEnd->format('Y-m-d H:i:s');
        $monthStartStr = $monthStart->format('Y-m-d H:i:s');
        $nextMonthStr = $nextMonthStart->format('Y-m-d H:i:s');
        $seriesFromStr = $seriesFrom->format('Y-m-d H:i:s');

        $visitsToday = (int) $this->conn->fetchOne(
            'SELECT COUNT(*)::int FROM page_visits WHERE created_at >= :d0 AND created_at < :d1',
            ['d0' => $dayStartStr, 'd1' => $dayEndStr]
        );

        $ordersToday = (int) $this->conn->fetchOne(
            'SELECT COUNT(*)::int FROM orders WHERE created_at >= :d0 AND created_at < :d1',
            ['d0' => $dayStartStr, 'd1' => $dayEndStr]
        );

        $revenueTodayRow = $this->conn->fetchAssociative(
            <<<'SQL'
            SELECT
              COALESCE(SUM(total_amount::numeric), 0)::numeric AS sum,
              COUNT(*) FILTER (WHERE total_amount IS NOT NULL)::int AS with_amount
            FROM orders
            WHERE created_at >= :d0 AND created_at < :d1
            SQL,
            ['d0' => $dayStartStr, 'd1' => $dayEndStr]
        );

        $monthRow = $this->conn->fetchAssociative(
            <<<'SQL'
            SELECT
              COUNT(*)::int AS orders_count,
              COALESCE(SUM(total_amount::numeric), 0)::numeric AS revenue_sum,
              COUNT(*) FILTER (WHERE total_amount IS NOT NULL)::int AS with_amount,
              AVG(total_amount::numeric) FILTER (WHERE total_amount IS NOT NULL)::numeric AS avg_amount
            FROM orders
            WHERE created_at >= :m0 AND created_at < :m1
            SQL,
            ['m0' => $monthStartStr, 'm1' => $nextMonthStr]
        );

        $visitSeriesRows = $this->conn->fetchAllAssociative(
            <<<'SQL'
            SELECT to_char(date_trunc('day', created_at AT TIME ZONE 'UTC'), 'YYYY-MM-DD') AS d, COUNT(*)::int AS c
            FROM page_visits
            WHERE created_at >= :from
            GROUP BY 1
            ORDER BY 1 ASC
            SQL,
            ['from' => $seriesFromStr]
        );

        $orderSeriesRows = $this->conn->fetchAllAssociative(
            <<<'SQL'
            SELECT
              to_char(date_trunc('day', created_at AT TIME ZONE 'UTC'), 'YYYY-MM-DD') AS d,
              COUNT(*)::int AS oc,
              COALESCE(SUM(total_amount::numeric), 0)::numeric AS rev
            FROM orders
            WHERE created_at >= :from
            GROUP BY 1
            ORDER BY 1 ASC
            SQL,
            ['from' => $seriesFromStr]
        );

        $visitByDate = [];
        foreach ($visitSeriesRows as $row) {
            $visitByDate[(string) $row['d']] = (int) $row['c'];
        }

        $orderByDate = [];
        foreach ($orderSeriesRows as $row) {
            $orderByDate[(string) $row['d']] = [
                'orders' => (int) $row['oc'],
                'revenue' => (string) $row['rev'],
            ];
        }

        $series = [];
        for ($i = 0; $i < 14; ++$i) {
            $d = $seriesFrom->modify('+' . $i . ' days')->format('Y-m-d');
            $o = $orderByDate[$d] ?? ['orders' => 0, 'revenue' => '0'];
            $series[] = [
                'date' => $d,
                'visits' => $visitByDate[$d] ?? 0,
                'orders' => $o['orders'],
                'revenue' => $this->formatMoney($o['revenue']),
            ];
        }

        $revenueToday = isset($revenueTodayRow['sum']) ? (string) $revenueTodayRow['sum'] : '0';
        $ordersTodayWithAmount = isset($revenueTodayRow['with_amount']) ? (int) $revenueTodayRow['with_amount'] : 0;

        $ordersMonth = isset($monthRow['orders_count']) ? (int) $monthRow['orders_count'] : 0;
        $revenueMonth = isset($monthRow['revenue_sum']) ? (string) $monthRow['revenue_sum'] : '0';
        $withAmountMonth = isset($monthRow['with_amount']) ? (int) $monthRow['with_amount'] : 0;
        $avgRaw = $monthRow['avg_amount'] ?? null;
        $avgOrderMonth = $avgRaw !== null && $avgRaw !== '' ? (string) $avgRaw : null;

        $pathSharePeriodDays = 30;
        $pathsFrom = $now->modify('-' . $pathSharePeriodDays . ' days')->setTime(0, 0, 0);
        $pathsFromStr = $pathsFrom->format('Y-m-d H:i:s');

        $pathShareTotal = (int) $this->conn->fetchOne(
            'SELECT COUNT(*)::int FROM page_visits WHERE created_at >= :from',
            ['from' => $pathsFromStr]
        );

        $pathTopLimit = 8;
        $pathTopRows = $this->conn->fetchAllAssociative(
            <<<SQL
            SELECT path, COUNT(*)::int AS c
            FROM page_visits
            WHERE created_at >= :from
            GROUP BY path
            ORDER BY c DESC
            LIMIT {$pathTopLimit}
            SQL,
            ['from' => $pathsFromStr]
        );

        $pathShareSegments = [];
        $pathTopSum = 0;
        foreach ($pathTopRows as $row) {
            $cnt = (int) $row['c'];
            $pathTopSum += $cnt;
            $pathShareSegments[] = [
                'path' => (string) $row['path'],
                'count' => $cnt,
                'percent' => $pathShareTotal > 0 ? round($cnt / $pathShareTotal * 100, 1) : 0.0,
            ];
        }
        if ($pathShareTotal > $pathTopSum) {
            $other = $pathShareTotal - $pathTopSum;
            $pathShareSegments[] = [
                'path' => '__other__',
                'label' => 'Прочее',
                'count' => $other,
                'percent' => $pathShareTotal > 0 ? round($other / $pathShareTotal * 100, 1) : 0.0,
            ];
        }

        return new JsonResponse([
            'timezone' => 'UTC',
            'date_utc' => $dayStart->format('Y-m-d'),
            'month_utc' => $monthStart->format('Y-m'),
            'visits_today' => $visitsToday,
            'orders_today' => $ordersToday,
            'orders_today_with_amount' => $ordersTodayWithAmount,
            'revenue_today' => $this->formatMoney($revenueToday),
            'orders_month' => $ordersMonth,
            'orders_month_with_amount' => $withAmountMonth,
            'revenue_month' => $this->formatMoney($revenueMonth),
            'avg_order_amount_month' => $avgOrderMonth !== null ? $this->formatMoney($avgOrderMonth) : null,
            'series_days' => 14,
            'series' => $series,
            'path_share' => [
                'period_days' => $pathSharePeriodDays,
                'total_visits' => $pathShareTotal,
                'segments' => $pathShareSegments,
            ],
        ]);
    }

    private function formatMoney(string $numeric): string
    {
        $n = $numeric;
        if ($n === '' || !is_numeric($n)) {
            return '0.00';
        }

        return number_format((float) $n, 2, '.', '');
    }
}
