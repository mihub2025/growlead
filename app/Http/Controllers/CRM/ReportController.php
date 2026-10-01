<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\AI\ReportInsightService;
use App\Services\ExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports, ReportInsightService $insights)
    {
        abort_unless($request->user()->hasPermission('reports.view'), 403);
        $org = $request->user()->organization;
        $tab = $request->get('tab', 'executive');
        $allowed = ['executive', 'sales', 'marketing', 'users', 'pipeline', 'custom'];
        if (! in_array($tab, $allowed, true)) {
            $tab = 'executive';
        }

        $filters = $request->only(['from', 'to', 'source_id', 'campaign_id', 'team_id', 'user_id', 'interested_in', 'group_by']);
        $data = $reports->forTab($org, $filters, $tab);

        return view('crm.reports.index', [
            'data' => $data,
            'tab' => $tab,
            'takeaways' => $insights->takeaways($data),
            'actions' => $insights->actions($data),
            'sources' => $org->leadSources,
            'campaigns' => $org->campaigns()->orderBy('name')->get(['id', 'name']),
            'teams' => $org->teams,
            'users' => $org->users,
            'types' => Lead::forOrganization($org->id)->whereNotNull('interested_in')->where('interested_in', '!=', '')->distinct()->orderBy('interested_in')->pluck('interested_in'),
            'filterCount' => collect(['source_id', 'team_id', 'campaign_id', 'interested_in', 'user_id'])->filter(fn ($key) => filled($request->get($key)))->count(),
        ]);
    }

    public function export(Request $request, ReportService $reports, ExportService $export)
    {
        abort_unless($request->user()->hasPermission('reports.export'), 403);
        $data = $reports->executive($request->user()->organization, $request->all());

        return $export->csv('report.csv', ['Metric', 'Value'], collect($data['kpis'])->map(fn ($k) => [$k['label'], $k['value']]));
    }
}
