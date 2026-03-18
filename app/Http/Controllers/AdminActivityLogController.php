<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $limit = (int) $request->input('limit', 100);

        if ($limit < 1 || $limit > 200) {
            $limit = 100;
        }

        $query = $this->buildFilteredQuery($request)->latest('created_at');

        return response([
            'logs' => $query->limit($limit)->get(),
        ], 200);
    }

    public function print(Request $request)
    {
        $filters = $this->extractFilters($request);
        $query = $this->buildFilteredQuery($request)->latest('created_at');
        $logs = $query->get();

        $pdf = Pdf::loadView('admin-activity-logs.report', [
            'logs' => $logs,
            'generatedAt' => now(),
            'filters' => $filters,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('admin-activity-logs-report.pdf');
    }

    private function buildFilteredQuery(Request $request)
    {
        $searchTerm = trim((string) $request->input('search', ''));
        $severity = trim((string) $request->input('severity', ''));
        $actionType = strtolower(trim((string) $request->input('action_type', '')));
        $statusFilter = trim((string) $request->input('status_filter', ''));
        $statusCode = (string) $request->input('status_code', '');
        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));

        [$startDate, $endDate] = $this->normalizeDateRange($startDate, $endDate);

        $query = AdminActivityLog::with('actor:id,first_name,middle_name,last_name,email');

        if (in_array($severity, ['high', 'medium', 'low'], true)) {
            $query->where('severity', $severity);
        }

        if (in_array($actionType, ['add', 'edit', 'delete', 'approve'], true)) {
            $actionTypeFilters = [
                'add' => ['add', 'added', 'create', 'created'],
                'edit' => ['edited', 'edit', 'updated', 'update', 'modify', 'modified'],
                'delete' => ['deleted', 'delete', 'removed', 'remove'],
                'approve' => ['approved', 'approve', 'approval'],
            ];

            $query->where(function ($actionQuery) use ($actionType, $actionTypeFilters) {
                $keywords = $actionTypeFilters[$actionType] ?? [];

                $actionQuery->where(function ($keywordQuery) use ($keywords) {
                    foreach ($keywords as $index => $keyword) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $keywordQuery->{$method}('action', 'like', '%' . $keyword . '%');
                    }
                });
            });
        }

        if (in_array($statusFilter, ['failed', 'success'], true)) {
            if ($statusFilter === 'failed') {
                $query->whereNotNull('status_code')->where('status_code', '>=', 400);
            } else {
                $query->whereNotNull('status_code')->where('status_code', '<', 400);
            }
        }

        if (ctype_digit($statusCode)) {
            $query->where('status_code', (int) $statusCode);
        }

        if ($startDate !== null) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate !== null) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($searchTerm !== '') {
            $like = '%' . $searchTerm . '%';
            $query->where(function ($query) use ($like) {
                $query->where('action', 'like', $like)
                    ->orWhere('method', 'like', $like)
                    ->orWhere('path', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('actor', function ($actorQuery) use ($like) {
                        $actorQuery->where('first_name', 'like', $like)
                            ->orWhere('middle_name', 'like', $like)
                            ->orWhere('last_name', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
            });
        }

        return $query;
    }

    private function extractFilters(Request $request): array
    {
        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));

        [$normalizedStartDate, $normalizedEndDate] = $this->normalizeDateRange($startDate, $endDate);

        return [
            'search' => trim((string) $request->input('search', '')),
            'severity' => trim((string) $request->input('severity', '')),
            'action_type' => strtolower(trim((string) $request->input('action_type', ''))),
            'status_filter' => strtolower(trim((string) $request->input('status_filter', ''))),
            'status_code' => trim((string) $request->input('status_code', '')),
            'start_date' => $normalizedStartDate,
            'end_date' => $normalizedEndDate,
        ];
    }

    private function normalizeDateRange(string $startDate, string $endDate): array
    {
        $normalizedStartDate = $this->isValidDate($startDate) ? $startDate : null;
        $normalizedEndDate = $this->isValidDate($endDate) ? $endDate : null;

        if (
            $normalizedStartDate !== null &&
            $normalizedEndDate !== null &&
            $normalizedStartDate > $normalizedEndDate
        ) {
            return [$normalizedEndDate, $normalizedStartDate];
        }

        return [$normalizedStartDate, $normalizedEndDate];
    }

    private function isValidDate(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
