<?php
declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $rows = [];
        $colTotals = array_fill_keys(Leitner::LEVELS, 0);
        foreach ($this->repo->matrix() as $r) {
            $id = $r['id'];
            $rows[$id] ??= ['titre' => $r['titre'], 'levels' => array_fill_keys(Leitner::LEVELS, 0), 'total' => 0];
            if ($r['niveau'] !== null) {
                $rows[$id]['levels'][$r['niveau']] = (int) $r['nb'];
                $rows[$id]['total'] += (int) $r['nb'];
                $colTotals[$r['niveau']] += (int) $r['nb'];
            }
        }
        $total = array_sum($colTotals);
        $this->render('dashboard', [
            'title' => 'Tableau de bord',
            'rows' => $rows,
            'colTotals' => $colTotals,
            'total' => $total,
            'pctM5' => $total > 0 ? round($colTotals['M5'] * 100 / $total, 1) : 0.0,
        ]);
    }
}
