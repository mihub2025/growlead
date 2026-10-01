<?php

return [
    'token_name' => 'growlead-agent',

    'activity_status_map' => [
        'qualified' => 'qualified',
        'not_interested' => 'unqualified',
        'interested' => 'working_deal',
        'no_answer' => 'did_not_respond',
    ],

    'new_lead_statuses' => ['new', 'not_contacted', 'new-lead', 'Not Contacted'],
    'follow_up_tab_statuses' => ['future_prospect', 'did_not_respond'],
    'qualified_statuses' => ['qualified', 'Qualified'],
    'working_statuses' => ['working_deal', 'Working Deal', 'opportunity', 'proposal', 'negotiation'],
    'closed_statuses' => ['deal_closed', 'Deal Closed', 'won', 'closed-won', 'closed_won'],
    'lost_statuses' => ['lost_deal', 'Lost Deal', 'lost', 'closed-lost', 'closed_lost', 'unqualified', 'Unqualified'],
    'lost_deal_statuses' => ['lost_deal', 'Lost Deal', 'lost', 'closed-lost', 'closed_lost'],
    'future_statuses' => ['future_prospect', 'Future Prospect'],
    'did_not_respond_statuses' => ['did_not_respond', 'Did Not Respond'],
    'unqualified_statuses' => ['unqualified', 'Unqualified'],
    'pending_statuses' => ['new', 'not_contacted', 'new-lead', 'Not Contacted', 'did_not_respond', 'Did Not Respond'],
    'answered_outcomes' => ['connected', 'interested', 'qualified', 'callback_requested', 'not_interested'],
    'no_answer_outcomes' => ['no_answer'],
    'dead_number_outcomes' => ['other'],
    'pipeline_rows' => [
        ['key' => 'qualified', 'label' => 'Qualified leads', 'statuses' => 'qualified_statuses'],
        ['key' => 'working', 'label' => 'Working deals', 'statuses' => 'working_statuses'],
        ['key' => 'closed', 'label' => 'Deals closed', 'statuses' => 'closed_statuses'],
        ['key' => 'future', 'label' => 'Future prospect', 'statuses' => 'future_statuses'],
        ['key' => 'lost', 'label' => 'Lost deals', 'statuses' => 'lost_deal_statuses'],
        ['key' => 'no_response', 'label' => 'Did not respond', 'statuses' => 'did_not_respond_statuses'],
        ['key' => 'unqualified', 'label' => 'Unqualified leads', 'statuses' => 'unqualified_statuses'],
    ],

    'reminder_offsets' => [
        'none' => null,
        '15m' => 15,
        '30m' => 30,
        '1h' => 60,
        '1d' => 1440,
    ],
];
