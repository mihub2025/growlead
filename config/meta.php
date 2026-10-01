<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta Graph API
    |--------------------------------------------------------------------------
    |
    | Prefer services.meta.graph_version (META_GRAPH_VERSION). This fallback
    | keeps outbound CAPI aligned if services config is incomplete.
    |
    */
    'graph_version' => env('META_GRAPH_VERSION', 'v21.0'),

    'capi' => [
        'timeout' => (int) env('META_CAPI_TIMEOUT', 15),
        'retry_attempts' => (int) env('META_CAPI_RETRY_ATTEMPTS', 5),
        'default_lead_event_source' => env('META_CAPI_LEAD_EVENT_SOURCE', 'GrowLead'),

        /*
        | Map CRM lead status slugs → Meta Conversion Leads event_name values.
        | Meta treats event_name as a free-form CRM stage label for CRM CAPI.
        | Phase 1: automatic send for Qualified only.
        */
        'status_event_map' => [
            'qualified' => 'Qualified',
            // Future examples (not auto-sent until enabled in map + listener rules):
            // 'not_contacted' => 'Lead',
            // 'working_deal' => 'Appointment Scheduled',
            // 'deal_closed' => 'Converted',
            // 'lost_deal' => 'Lost',
        ],

        /*
        | Status slugs that should enqueue CRM CAPI events when transitioned TO.
        | Keep in sync with status_event_map keys you want automated.
        */
        'auto_send_statuses' => [
            'qualified',
        ],
    ],
];
