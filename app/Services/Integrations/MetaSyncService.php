<?php

namespace App\Services\Integrations;

use App\Events\CampaignLeadReceived;
use App\Models\Campaign;
use App\Models\Integration;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Organization;
use App\Services\CampaignService;
use App\Services\LeadRoutingService;
use App\Services\LeadService;
use Carbon\Carbon;
use RuntimeException;
use Throwable;

class MetaSyncService
{
    public function __construct(
        protected MetaOAuthService $meta,
        protected LeadService $leads,
        protected LeadRoutingService $routing,
        protected CampaignService $campaigns,
    ) {
    }

    public function sync(Integration $integration): array
    {
        if ($integration->provider !== 'meta' || $integration->status !== 'connected') {
            throw new RuntimeException('Meta Ads is not connected.');
        }

        $token = (string) data_get($integration->credentials, 'token', '');
        if ($token === '') {
            throw new RuntimeException('Meta access token is missing. Reconnect Meta Ads.');
        }

        $organization = $integration->organization;
        $warnings = [];
        $scopes = $this->meta->grantedScopes($token);
        $integration->settings = array_merge($integration->settings ?? [], [
            'granted_scopes' => $scopes,
        ]);

        if ($scopes && ! $this->hasAnyScope($scopes, ['ads_read', 'ads_management'])) {
            throw new RuntimeException(
                'This Meta login does not include ads_read / ads_management. Add those permissions to your Facebook Login configuration, then click Reconnect and approve ads access.'
            );
        }

        $source = LeadSource::forOrganization($organization->id)->where('slug', 'meta')->first();
        $catalog = $this->listBusinesses($integration, $warnings);
        $adAccounts = $this->adAccountsFromCatalog($integration, $catalog);
        $campaignsImported = $this->importCampaigns($organization, $integration, $source, $token, $adAccounts, $warnings);
        $leadsImported = $this->importLeads($organization, $source, $token, $integration, $adAccounts, $warnings);

        $summary = [
            'ad_accounts' => count($adAccounts),
            'campaigns_imported' => $campaignsImported,
            'leads_imported' => $leadsImported,
            'businesses' => $this->selectedBusinesses($integration),
            'warnings' => array_values(array_unique($warnings)),
            'synced_at' => now()->toIso8601String(),
        ];

        $error = null;
        if ($campaignsImported === 0 && $adAccounts === []) {
            $error = $this->selectedBusinessIds($integration)
                ? 'No ad accounts were found for the selected businesses. Open Businesses, confirm Green Aura / SMR are ticked, then Save and sync. If they show 0 ad accounts, Reconnect and approve ads_read plus business_management.'
                : 'No ad accounts were returned. Confirm ads_read is granted, then Reconnect and approve the ads permissions.';
        } elseif ($leadsImported === 0 && $this->hasAnyScope($scopes, ['leads_retrieval']) === false && $scopes) {
            $warnings[] = 'Lead Ads were skipped. Add leads_retrieval and pages_show_list, then Reconnect.';
            $summary['warnings'] = array_values(array_unique($warnings));
        }

        $integration->update([
            'status' => 'connected',
            'last_error' => $error,
            'last_synced_at' => now(),
            'settings' => array_merge($integration->settings ?? [], [
                'sync' => $summary,
            ]),
        ]);

        return $summary;
    }

    public function listBusinesses(Integration $integration, array &$warnings = [], bool $forceRefresh = false): array
    {
        $live = $this->fetchBusinessCatalog($integration, $warnings, $forceRefresh);
        $cached = $this->cachedCatalog($integration);
        $catalog = $this->mergeCatalogs($cached, $live);

        if (count($live) > count($cached)
            || ($cached === [] && $live !== [])
            || (count($live) === count($cached) && $this->catalogAccountCount($live) >= $this->catalogAccountCount($cached))) {
            $this->rememberCatalog($integration, $catalog);
        }

        if ($cached && $this->catalogAccountCount($live) === 0 && $this->catalogAccountCount($catalog) > 0) {
            $warnings = array_values(array_filter($warnings, fn ($message) => ! str_contains(strtolower((string) $message), 'request limit')));
            $warnings[] = 'Facebook temporarily limited requests. Showing the last saved business list. Wait a few minutes and refresh to reload every Business Manager.';
        }

        return $catalog;
    }

    protected function fetchBusinessCatalog(Integration $integration, array &$warnings = [], bool $forceRefresh = false): array
    {
        $token = $this->token($integration);
        $byBusiness = [];

        try {
            foreach ($this->meta->graphPaginate('/me/businesses', $token, [
                'fields' => 'id,name,permitted_roles',
                'limit' => 50,
            ], 8) as $business) {
                $businessId = (string) ($business['id'] ?? '');
                if ($businessId === '') {
                    continue;
                }
                $byBusiness[$businessId] = [
                    'id' => $businessId,
                    'name' => $business['name'] ?? ('Business '.$businessId),
                    'ad_accounts' => [],
                ];
            }
        } catch (Throwable $e) {
            if (str_contains(strtolower($e->getMessage()), 'request limit')) {
                $warnings[] = 'Facebook rate-limited this app, so only businesses tied to your ad accounts are listed. Wait a few minutes, then click Reload from Facebook.';
            } else {
                $warnings[] = 'Business Manager accounts could not be listed: '.$e->getMessage();
            }
        }

        try {
            foreach ($this->meta->graphPaginate('/me/adaccounts', $token, [
                'fields' => 'id,account_id,name,currency,account_status,business{id,name}',
            ]) as $account) {
                $accountId = $account['id'] ?? null;
                $businessId = (string) data_get($account, 'business.id', '');
                $businessName = (string) data_get($account, 'business.name', '');
                if ($businessId !== '') {
                    $this->attachAccount($byBusiness, $account, $businessId, $businessName);
                    continue;
                }
                if ($accountId && $this->accountIsAttached($byBusiness, $accountId)) {
                    continue;
                }
                $this->attachAccount($byBusiness, $account, 'personal', 'Personal ad accounts');
            }
        } catch (Throwable $e) {
            if ($byBusiness === []) {
                throw new RuntimeException($this->permissionHint($e->getMessage(), 'ad accounts'));
            }
            $warnings[] = $this->permissionHint($e->getMessage(), 'ad accounts');
        }

        foreach ($byBusiness as $id => $row) {
            $byBusiness[$id]['ad_accounts'] = array_values($row['ad_accounts'] ?? []);
        }

        return array_values(array_filter($byBusiness, function ($row) {
            return $row['id'] !== 'personal' || ($row['ad_accounts'] ?? []) !== [];
        }));
    }

    protected function cachedCatalog(Integration $integration): array
    {
        foreach (['business_catalog', 'available_businesses'] as $key) {
            $rows = data_get($integration->settings, $key, []);
            if (is_array($rows) && $rows !== []) {
                return array_values($rows);
            }
        }

        return [];
    }

    protected function rememberCatalog(Integration $integration, array $catalog): void
    {
        $compact = collect($catalog)->map(fn ($row) => [
            'id' => (string) ($row['id'] ?? ''),
            'name' => $row['name'] ?? '',
            'ad_accounts' => collect($row['ad_accounts'] ?? [])->map(fn ($account) => [
                'id' => $account['id'] ?? null,
                'name' => $account['name'] ?? ($account['id'] ?? null),
                'currency' => $account['currency'] ?? null,
            ])->filter(fn ($account) => ! empty($account['id']))->values()->all(),
        ])->filter(fn ($row) => $row['id'] !== '')->values()->all();

        $integration->settings = array_merge($integration->settings ?? [], [
            'business_catalog' => $compact,
        ]);
        $integration->save();
    }

    protected function mergeCatalogs(array $cached, array $live): array
    {
        $merged = [];
        foreach (array_merge($cached, $live) as $row) {
            $id = (string) ($row['id'] ?? '');
            if ($id === '') {
                continue;
            }
            if (! isset($merged[$id])) {
                $merged[$id] = [
                    'id' => $id,
                    'name' => $row['name'] ?? $id,
                    'ad_accounts' => [],
                ];
            }
            if (! empty($row['name'])) {
                $merged[$id]['name'] = $row['name'];
            }
            foreach ($row['ad_accounts'] ?? [] as $account) {
                $accountId = $account['id'] ?? null;
                if ($accountId) {
                    $merged[$id]['ad_accounts'][$accountId] = $account;
                }
            }
        }

        foreach ($merged as $id => $row) {
            $merged[$id]['ad_accounts'] = array_values($row['ad_accounts']);
        }

        return array_values($merged);
    }

    protected function catalogAccountCount(array $catalog): int
    {
        return collect($catalog)->sum(fn ($row) => count($row['ad_accounts'] ?? []));
    }

    protected function attachAccount(array &$byBusiness, array $account, string $businessId, ?string $businessName): void
    {
        $accountId = $account['id'] ?? null;
        if (! $accountId || $businessId === '') {
            return;
        }

        if (! isset($byBusiness[$businessId])) {
            $byBusiness[$businessId] = [
                'id' => $businessId,
                'name' => $businessName ?: ($businessId === 'personal' ? 'Personal ad accounts' : 'Business '.$businessId),
                'ad_accounts' => [],
            ];
        } elseif (filled($businessName) && $businessId !== 'personal') {
            $byBusiness[$businessId]['name'] = $businessName;
        }

        $byBusiness[$businessId]['ad_accounts'][$accountId] = $account;
    }

    protected function accountIsAttached(array $byBusiness, string $accountId): bool
    {
        foreach ($byBusiness as $id => $row) {
            if ($id === 'personal') {
                continue;
            }
            if (isset($row['ad_accounts'][$accountId])) {
                return true;
            }
        }

        return false;
    }

    public function needsBusinessPicker(Integration $integration, ?array $catalog = null): bool
    {
        $catalog ??= $this->listBusinesses($integration);
        if (count($catalog) <= 1) {
            return false;
        }

        return $this->selectedBusinessIds($integration) === [];
    }

    public function selectedBusinesses(Integration $integration): array
    {
        $rows = data_get($integration->settings, 'selected_businesses', []);

        return is_array($rows) ? array_values(array_filter($rows, fn ($row) => is_array($row) && ! empty($row['id']))) : [];
    }

    public function selectedBusinessIds(Integration $integration): array
    {
        return array_values(array_map(fn ($row) => (string) $row['id'], $this->selectedBusinesses($integration)));
    }

    public function saveSelectedBusinesses(Integration $integration, array $ids, array $catalog): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $ids))));
        $byId = collect($catalog)->keyBy(fn ($row) => (string) $row['id']);
        $selected = [];
        foreach ($ids as $id) {
            if (! $byId->has($id)) {
                continue;
            }
            $row = $byId->get($id);
            $selected[] = [
                'id' => (string) $row['id'],
                'name' => $row['name'] ?? $id,
                'ad_account_count' => count($row['ad_accounts'] ?? []),
            ];
        }

        if ($selected === [] && $ids !== []) {
            throw new RuntimeException('Select at least one Facebook business from the list.');
        }

        if ($selected === [] && count($catalog) === 1) {
            $row = $catalog[0];
            $selected[] = [
                'id' => (string) $row['id'],
                'name' => $row['name'] ?? $row['id'],
                'ad_account_count' => count($row['ad_accounts'] ?? []),
            ];
        }

        $integration->update([
            'settings' => array_merge($integration->settings ?? [], [
                'selected_businesses' => $selected,
                'available_businesses' => collect($catalog)->map(fn ($row) => [
                    'id' => $row['id'],
                    'name' => $row['name'] ?? $row['id'],
                    'ad_account_count' => count($row['ad_accounts'] ?? []),
                ])->values()->all(),
            ]),
        ]);

        return $selected;
    }

    protected function token(Integration $integration): string
    {
        $token = (string) data_get($integration->credentials, 'token', '');
        if ($token === '') {
            throw new RuntimeException('Meta access token is missing. Reconnect Meta Ads.');
        }

        return $token;
    }

    protected function adAccountsFromCatalog(Integration $integration, array $catalog): array
    {
        $selectedIds = $this->selectedBusinessIds($integration);
        $rows = $catalog;
        if ($selectedIds) {
            $rows = array_values(array_filter($catalog, fn ($row) => in_array((string) $row['id'], $selectedIds, true)));
        }

        $accounts = [];
        foreach ($rows as $business) {
            foreach ($business['ad_accounts'] ?? [] as $account) {
                $accountId = $account['id'] ?? null;
                if (! $accountId) {
                    continue;
                }
                $account['_business_id'] = (string) $business['id'];
                $account['_business_name'] = $business['name'] ?? null;
                $accounts[$accountId] = $account;
            }
        }

        return array_values($accounts);
    }

    protected function importCampaigns(
        Organization $organization,
        Integration $integration,
        ?LeadSource $source,
        string $token,
        array $adAccounts,
        array &$warnings
    ): int {
        $imported = 0;

        foreach ($adAccounts as $account) {
            $accountId = $account['id'] ?? null;
            if (! $accountId) {
                continue;
            }

            try {
                $rows = $this->meta->graphPaginate('/'.$accountId.'/campaigns', $token, [
                    'fields' => 'id,name,status,effective_status,objective,daily_budget,lifetime_budget,start_time,stop_time',
                    'limit' => 50,
                ]);
            } catch (Throwable $e) {
                $warnings[] = 'Could not load campaigns for '.($account['name'] ?? $accountId).': '.$e->getMessage();
                continue;
            }

            $spendByCampaign = $this->campaignSpend($accountId, $token, $warnings);

            foreach ($rows as $row) {
                $externalId = (string) ($row['id'] ?? '');
                if ($externalId === '') {
                    continue;
                }

                $daily = $this->centsToAmount($row['daily_budget'] ?? null);
                $lifetime = $this->centsToAmount($row['lifetime_budget'] ?? null);
                $payload = [
                    'name' => $row['name'] ?? ('Meta campaign '.$externalId),
                    'source_id' => $source?->id,
                    'integration_id' => $integration->id,
                    'objective' => $this->mapObjective($row['objective'] ?? null),
                    'budget_type' => $daily ? 'daily' : 'total',
                    'daily_budget' => $daily,
                    'budget' => $lifetime ?: $daily,
                    'currency' => $organization->currencyCode(),
                    'start_date' => $this->toDate($row['start_time'] ?? null),
                    'end_date' => $this->toDate($row['stop_time'] ?? null),
                    'status' => $this->mapCampaignStatus($row['effective_status'] ?? $row['status'] ?? null),
                    'total_spend' => $spendByCampaign[$externalId] ?? 0,
                    'sync_status' => 'synced',
                    'last_synced_at' => now(),
                    'meta_business_id' => $account['_business_id'] ?? null,
                    'meta_business_name' => $account['_business_name'] ?? null,
                    'meta_ad_account_id' => $accountId,
                    'meta_ad_account_name' => $account['name'] ?? null,
                    'settings' => [
                        'meta_ad_account_id' => $accountId,
                        'meta_ad_account_name' => $account['name'] ?? null,
                        'meta_account_currency' => $account['currency'] ?? null,
                        'meta_business_id' => $account['_business_id'] ?? null,
                        'meta_business_name' => $account['_business_name'] ?? null,
                        'meta_status' => $row['effective_status'] ?? $row['status'] ?? null,
                        'meta_objective' => $row['objective'] ?? null,
                    ],
                ];

                $campaign = Campaign::forOrganization($organization->id)
                    ->where('external_campaign_id', $externalId)
                    ->first();

                if ($campaign) {
                    $payload['settings'] = array_merge($campaign->settings ?? [], $payload['settings']);
                    $campaign->update($payload);
                } else {
                    Campaign::create($payload + [
                        'organization_id' => $organization->id,
                        'external_campaign_id' => $externalId,
                        'routing_method' => 'round_robin',
                    ]);
                }

                $imported++;
            }
        }

        return $imported;
    }

    protected function campaignSpend(string $accountId, string $token, array &$warnings): array
    {
        $spend = [];

        try {
            $rows = $this->meta->graphPaginate('/'.$accountId.'/insights', $token, [
                'level' => 'campaign',
                'fields' => 'campaign_id,spend',
                'date_preset' => 'maximum',
                'limit' => 50,
            ], 8);
        } catch (Throwable $e) {
            $warnings[] = 'Campaign spend could not be synced for '.$accountId.': '.$e->getMessage();

            return $spend;
        }

        foreach ($rows as $row) {
            $id = (string) ($row['campaign_id'] ?? '');
            if ($id !== '') {
                $spend[$id] = (float) ($row['spend'] ?? 0);
            }
        }

        return $spend;
    }

    protected function importLeads(
        Organization $organization,
        ?LeadSource $source,
        string $token,
        Integration $integration,
        array $adAccounts,
        array &$warnings
    ): int {
        $imported = 0;
        $since = $this->leadsSince($integration);
        $pages = $this->leadPages($integration, $token, $adAccounts, $warnings);
        if ($pages === []) {
            return 0;
        }

        $allowedCampaignIds = Campaign::forOrganization($organization->id)
            ->whereNotNull('external_campaign_id')
            ->when($this->selectedBusinessIds($integration), function ($query, $ids) {
                $query->whereIn('meta_business_id', $ids);
            })
            ->pluck('external_campaign_id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $touchedCampaigns = [];

        foreach ($pages as $page) {
            $pageToken = $page['access_token'] ?? $token;
            $pageId = $page['id'] ?? null;
            if (! $pageId) {
                continue;
            }

            try {
                $forms = $this->meta->graphPaginate('/'.$pageId.'/leadgen_forms', $pageToken, [
                    'fields' => 'id,name,status,leads_count',
                ], 5);
            } catch (Throwable $e) {
                $warnings[] = 'Lead forms for '.($page['name'] ?? $pageId).' could not be loaded: '.$e->getMessage();
                continue;
            }

            foreach ($forms as $form) {
                $formId = $form['id'] ?? null;
                if (! $formId) {
                    continue;
                }

                try {
                    $query = [
                        'fields' => 'id,created_time,ad_id,adset_id,campaign_id,form_id,field_data',
                        'limit' => 50,
                    ];
                    if ($since) {
                        $query['filtering'] = json_encode([[
                            'field' => 'time_created',
                            'operator' => 'GREATER_THAN',
                            'value' => $since,
                        ]]);
                    }
                    $rows = $this->meta->graphPaginate('/'.$formId.'/leads', $pageToken, $query, 8);
                } catch (Throwable $e) {
                    $warnings[] = 'Leads for form '.($form['name'] ?? $formId).' could not be loaded: '.$e->getMessage();
                    continue;
                }

                foreach ($rows as $row) {
                    $metaCampaignId = (string) ($row['campaign_id'] ?? '');
                    if ($allowedCampaignIds && $metaCampaignId !== '' && ! in_array($metaCampaignId, $allowedCampaignIds, true)) {
                        continue;
                    }
                    $lead = $this->storeLead($organization, $source, $row, $form, $page);
                    if ($lead) {
                        $imported++;
                        if ($lead->campaign_id) {
                            $touchedCampaigns[$lead->campaign_id] = true;
                        }
                    }
                }
            }
        }

        foreach (array_keys($touchedCampaigns) as $campaignId) {
            $campaign = Campaign::find($campaignId);
            if ($campaign) {
                $this->campaigns->refreshStats($campaign);
            }
        }

        return $imported;
    }

    protected function leadPages(Integration $integration, string $token, array $adAccounts, array &$warnings): array
    {
        $pages = [];
        $selectedIds = $this->selectedBusinessIds($integration);
        $businessIds = $selectedIds ?: array_values(array_unique(array_filter(array_map(
            fn ($account) => (string) ($account['_business_id'] ?? ''),
            $adAccounts
        ))));

        foreach ($businessIds as $businessId) {
            if ($businessId === '' || $businessId === 'personal') {
                continue;
            }
            foreach (['owned_pages', 'client_pages'] as $edge) {
                try {
                    foreach ($this->meta->graphPaginate('/'.$businessId.'/'.$edge, $token, [
                        'fields' => 'id,name,access_token',
                    ], 5) as $page) {
                        if (! empty($page['id'])) {
                            $pages[$page['id']] = $page;
                        }
                    }
                } catch (Throwable $e) {
                    $warnings[] = 'Pages for business '.$businessId.' ('.$edge.') could not be listed: '.$e->getMessage();
                }
            }
        }

        $needsPersonal = $businessIds === [] || in_array('personal', $businessIds, true) || $pages === [];
        if ($needsPersonal) {
            try {
                foreach ($this->meta->graphPaginate('/me/accounts', $token, [
                    'fields' => 'id,name,access_token',
                ], 8) as $page) {
                    if (! empty($page['id'])) {
                        $pages[$page['id']] = $page;
                    }
                }
            } catch (Throwable $e) {
                if ($pages === []) {
                    $warnings[] = $this->permissionHint($e->getMessage(), 'Pages / Lead Ads');
                }
            }
        }

        return array_values($pages);
    }

    protected function storeLead(Organization $organization, ?LeadSource $source, array $row, array $form, array $page): ?Lead
    {
        $externalId = (string) ($row['id'] ?? '');
        if ($externalId === '') {
            return null;
        }

        $fields = $this->fieldMap($row['field_data'] ?? []);
        $name = $this->splitName($fields);
        $campaign = $this->matchCampaign($organization, $row);
        $phone = $fields['phone_number'] ?? $fields['phone'] ?? $fields['mobile_number'] ?? $fields['mobile'] ?? null;
        $email = $fields['email'] ?? $fields['work_email'] ?? $fields['email_address'] ?? null;
        $payload = [
            'lead_id' => $externalId,
            'form_id' => $form['id'] ?? ($row['form_id'] ?? null),
            'form_name' => $form['name'] ?? null,
            'page_id' => $page['id'] ?? null,
            'page_name' => $page['name'] ?? null,
            'ad_id' => $row['ad_id'] ?? null,
            'adset_id' => $row['adset_id'] ?? null,
            'campaign_id' => $row['campaign_id'] ?? null,
            'campaign_name' => $campaign?->name,
            'created_time' => $row['created_time'] ?? null,
            'field_data' => is_array($row['field_data'] ?? null) ? $row['field_data'] : [],
        ];

        $existing = Lead::forOrganization($organization->id)->where('external_id', $externalId)->first();
        if ($existing) {
            if (empty($existing->meta_payload)) {
                $existing->update(['meta_payload' => $payload]);
            }

            return null;
        }

        $lead = $this->leads->create($organization, [
            'first_name' => $name['first'] ?: 'Facebook',
            'last_name' => $name['last'],
            'email' => $email,
            'phone' => $phone,
            'whatsapp' => $phone,
            'company' => $fields['company_name'] ?? $fields['company'] ?? null,
            'job_title' => $fields['job_title'] ?? $fields['work_title'] ?? null,
            'city' => $fields['city'] ?? null,
            'country' => $fields['country'] ?? null,
            'source_id' => $source?->id,
            'campaign_id' => $campaign?->id,
            'interested_in' => $form['name'] ?? null,
            'medium' => 'paid',
            'utm_source' => 'facebook',
            'utm_medium' => 'paid',
            'utm_campaign' => $campaign?->name ?: ($form['name'] ?? null),
            'external_id' => $externalId,
            'meta_created_at' => $this->toDateTime($row['created_time'] ?? null),
            'notes' => $this->leadNotes($fields, $form, $page),
            'meta_payload' => $payload,
        ]);

        if ($campaign) {
            $this->routing->assign($lead, $campaign);
            CampaignLeadReceived::dispatch($lead->fresh(), $campaign);
        }

        return $lead;
    }

    protected function matchCampaign(Organization $organization, array $row): ?Campaign
    {
        $metaCampaignId = (string) ($row['campaign_id'] ?? '');
        if ($metaCampaignId === '') {
            return null;
        }

        return Campaign::forOrganization($organization->id)
            ->where('external_campaign_id', $metaCampaignId)
            ->first();
    }

    protected function fieldMap(array $fieldData): array
    {
        $out = [];
        foreach ($fieldData as $item) {
            $name = strtolower(preg_replace('/[^a-z0-9]+/i', '_', (string) ($item['name'] ?? '')));
            $name = trim($name, '_');
            if ($name === '') {
                continue;
            }
            $out[$name] = $item['values'][0] ?? null;
        }

        return $out;
    }

    protected function splitName(array $fields): array
    {
        $first = trim((string) ($fields['first_name'] ?? ''));
        $last = trim((string) ($fields['last_name'] ?? ''));
        if ($first || $last) {
            return ['first' => $first, 'last' => $last ?: null];
        }

        $full = trim((string) ($fields['full_name'] ?? $fields['name'] ?? ''));
        if ($full === '') {
            return ['first' => '', 'last' => null];
        }

        $parts = preg_split('/\s+/', $full, 2);

        return ['first' => $parts[0], 'last' => $parts[1] ?? null];
    }

    protected function leadNotes(array $fields, array $form, array $page): ?string
    {
        $known = [
            'first_name', 'last_name', 'full_name', 'name', 'email', 'work_email', 'email_address',
            'phone_number', 'phone', 'mobile_number', 'mobile', 'company_name', 'company',
            'job_title', 'work_title', 'city', 'country',
        ];
        $extra = [];
        foreach ($fields as $key => $value) {
            if ($value && ! in_array($key, $known, true)) {
                $extra[] = str_replace('_', ' ', $key).': '.$value;
            }
        }

        $lines = array_filter([
            'Imported from Facebook Lead Ads',
            ($page['name'] ?? null) ? 'Page: '.$page['name'] : null,
            ($form['name'] ?? null) ? 'Form: '.$form['name'] : null,
            ...$extra,
        ]);

        return $lines ? implode("\n", $lines) : null;
    }

    protected function leadsSince(Integration $integration): ?int
    {
        $last = data_get($integration->settings, 'sync.synced_at');
        if ($last) {
            try {
                return Carbon::parse($last)->subHour()->timestamp;
            } catch (Throwable) {
                // Fall through to the first-sync window.
            }
        }

        return now()->subDays(90)->timestamp;
    }

    protected function mapCampaignStatus(?string $status): string
    {
        $status = strtoupper((string) $status);

        return match (true) {
            in_array($status, ['ACTIVE'], true) => 'active',
            in_array($status, ['PAUSED', 'CAMPAIGN_PAUSED', 'ADSET_PAUSED', 'WITH_ISSUES'], true) => 'paused',
            in_array($status, ['DELETED', 'ARCHIVED'], true) => 'archived',
            default => 'draft',
        };
    }

    protected function mapObjective(?string $objective): ?string
    {
        if (! $objective) {
            return null;
        }

        $map = [
            'OUTCOME_LEADS' => 'leads',
            'LEAD_GENERATION' => 'leads',
            'OUTCOME_SALES' => 'conversions',
            'CONVERSIONS' => 'conversions',
            'OUTCOME_TRAFFIC' => 'traffic',
            'LINK_CLICKS' => 'traffic',
            'OUTCOME_ENGAGEMENT' => 'engagement',
            'OUTCOME_AWARENESS' => 'awareness',
            'BRAND_AWARENESS' => 'awareness',
            'REACH' => 'awareness',
        ];

        return $map[$objective] ?? strtolower($objective);
    }

    protected function centsToAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round(((float) $value) / 100, 2);
    }

    protected function toDate(mixed $value): ?string
    {
        $date = $this->toDateTime($value);

        return $date?->toDateString();
    }

    protected function toDateTime(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function hasAnyScope(array $scopes, array $needed): bool
    {
        $scopes = array_map('strtolower', $scopes);

        foreach ($needed as $scope) {
            if (in_array(strtolower($scope), $scopes, true)) {
                return true;
            }
        }

        return false;
    }

    protected function permissionHint(string $message, string $what): string
    {
        if (preg_match('/permission|#200|#10|oauth/i', $message)) {
            return 'Meta denied access to '.$what.'. Add ads_read, ads_management, pages_show_list, and leads_retrieval, then Reconnect and approve them.';
        }

        return $message;
    }
}
