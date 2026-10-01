# CAMPAIGNPILOT CRM — COMPLETE LARAVEL 10 IMPLEMENTATION

You are working inside an **already-created fresh Laravel 10 project**.

Build the complete application inside this existing project.

Do NOT create another Laravel project.

Application name:

# CampaignPilot CRM

CampaignPilot CRM is a **generic multi-business Campaign + Lead Management CRM**.

It must NOT be restricted to real estate.

It should work for:

* Real Estate
* Automotive
* Education
* Insurance
* Solar
* SaaS
* Digital Agencies
* B2B Sales
* Retail
* Ecommerce
* Healthcare businesses
* Travel
* Financial Services
* Contractors
* Professional Services
* Home Services
* Any lead-driven business

Real estate is only one optional business configuration.

---

# 1. IMPORTANT UI REQUIREMENT

I will provide CampaignPilot CRM screenshots.

Use those screenshots as the **visual source of truth**.

Maintain the same:

* dark navy sidebar
* blue primary color
* yellow CTA/accent
* light gray page background
* white cards
* rounded corners
* subtle shadows
* typography hierarchy
* table styling
* filters
* badges
* KPI cards
* charts
* drawers
* forms
* AI panels
* responsive behavior

Brand must always be:

# CampaignPilot CRM

Do NOT use:

`Karam Properties`

anywhere.

Use the same CampaignPilot-style blue/yellow logo from the supplied references.

All screens must use the same application shell.

Do not randomly redesign different modules.

---

# 2. TECHNOLOGY STACK

Use:

* Laravel 10
* MySQL
* Blade
* Bootstrap 5
* Bootstrap Icons
* Vanilla JavaScript
* Laravel Eloquent
* Laravel Form Requests
* Laravel Policies/Gates
* Laravel Events/Listeners
* Laravel Jobs/Queues
* Laravel Scheduler
* Laravel Notifications
* Laravel Storage
* Laravel Mail
* Chart.js through CDN
* Leaflet + OpenStreetMap through CDN where maps are needed

Do NOT use:

* React
* Vue
* Tailwind
* Inertia
* frontend SPA architecture

---

# 3. IMPORTANT — NO NPM / NO BUILD PROCESS

The application must NOT depend on:

```text
npm install
npm run dev
npm run build
```

Do NOT require Node.js to run the CRM.

Do NOT use:

```blade
@vite(...)
```

Load Bootstrap using CDN.

Use a structure such as:

```blade
<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/css/bootstrap.min.css"
    rel="stylesheet"
>

<link
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
    rel="stylesheet"
>
```

Load Bootstrap JS through CDN:

```blade
<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5/dist/js/bootstrap.bundle.min.js"
></script>
```

Create custom CRM CSS:

```text
public/assets/css/crm.css
```

Create custom JS:

```text
public/assets/js/crm.js
```

Load directly using:

```blade
<link rel="stylesheet" href="{{ asset('assets/css/crm.css') }}">
<script src="{{ asset('assets/js/crm.js') }}"></script>
```

Use CDN for Chart.js.

Use CDN for Leaflet.

---

# 4. FIRST STEP

Before building anything:

1. Inspect project files.
2. Confirm Laravel 10.
3. Inspect `.env`.
4. Inspect database configuration.
5. Inspect existing routes.
6. Inspect existing migrations.
7. Preserve existing Laravel installation.
8. Start implementation inside this project.

Do NOT create another Laravel installation.

---

# 5. AUTHENTICATION — BOOTSTRAP ONLY

Build Laravel session-based authentication manually using Laravel's built-in authentication functionality and Bootstrap views.

Do NOT install auth UI that requires NPM compilation.

Required screens:

```text
/login
/register
/forgot-password
/reset-password/{token}
/email/verify
```

Required functionality:

* Login
* Registration
* Logout
* Remember Me
* Forgot Password
* Reset Password
* Email Verification
* CSRF protection
* Auth middleware
* Guest middleware

Use Laravel features such as:

```php
Auth
Hash
Password
MustVerifyEmail
```

Create controllers under:

```text
App\Http\Controllers\Auth
```

Suggested:

```text
LoginController
RegisterController
ForgotPasswordController
ResetPasswordController
EmailVerificationController
```

Create:

```text
resources/views/auth/login.blade.php
resources/views/auth/register.blade.php
resources/views/auth/forgot-password.blade.php
resources/views/auth/reset-password.blade.php
resources/views/auth/verify-email.blade.php
```

Create:

```text
resources/views/layouts/auth.blade.php
```

Use Bootstrap 5.

---

# 6. LOGIN DESIGN

Match CampaignPilot branding.

Layout:

### Left Side

* CampaignPilot CRM logo
* navy background
* marketing/campaign illustration or design
* message such as:

`Manage campaigns. Convert more leads.`

### Right Side

Login card:

* Email
* Password
* Remember Me
* Login
* Forgot Password
* Create Account

After successful login:

```text
/crm/dashboard
```

Logout should use:

```text
POST /logout
```

Never use GET logout.

---

# 7. REGISTER ORGANIZATION

Registration fields:

* Full Name
* Organization Name
* Industry
* Email
* Phone
* Country
* Currency
* Timezone
* Password
* Confirm Password

On registration automatically create:

1. Organization
2. Administrator user
3. Administrator role
4. Default permissions
5. Default generic pipeline
6. Default pipeline stages
7. Default tags
8. Default settings
9. Default lead sources

Default generic stages:

```text
New Lead
Contacted
Qualified
Opportunity
Proposal
Negotiation
Closed Won
Closed Lost
```

---

# 8. INDUSTRY TYPES

Support optional business presets:

* Generic CRM
* Real Estate
* Automotive
* Education
* Insurance
* Solar
* Agency
* SaaS
* Other

Industry selection should ONLY configure defaults.

Never create separate application code for every industry.

---

# 9. ORGANIZATION / MULTI-TENANT ARCHITECTURE

Create:

## organizations

Suggested fields:

```text
id
name
slug
industry
logo
currency
country
timezone
language
date_format
phone_country
status
settings JSON
created_at
updated_at
deleted_at
```

Every important business table should include:

```text
organization_id
```

Examples:

* users
* teams
* leads
* campaigns
* opportunities
* tasks
* activities
* tags
* pipelines
* automations
* integrations
* custom fields
* offerings

A user must NEVER access another organization's data.

Never trust `organization_id` sent from the browser.

Always resolve organization from authenticated user/context.

---

# 10. CRM MASTER LAYOUT

Create:

```text
resources/views/layouts/crm.blade.php
```

Sidebar:

```text
Dashboard
Leads
Campaigns
AI Copilot
Reports
Users
Automations
Integrations
Settings
```

Bottom area:

* Logged-in user
* Role
* Profile
* Logout

Sidebar should:

* highlight active module
* collapse on tablet
* become Bootstrap offcanvas on mobile

---

# 11. SHARED COMPONENTS

Create reusable Blade partials/components for:

* sidebar
* page header
* breadcrumb
* KPI card
* chart card
* status badge
* score badge
* sentiment badge
* avatar
* table
* pagination
* filters
* search
* tabs
* modal
* offcanvas drawer
* toast
* alerts
* empty state
* loading state
* form section
* AI insight card
* activity timeline
* file uploader
* confirmation dialog

Avoid copied UI code.

---

# 12. ROLES AND PERMISSIONS

Create default roles:

```text
Administrator
Manager
Team Lead
Agent
Viewer
```

Create permissions.

Examples:

```text
dashboard.view

leads.view
leads.create
leads.edit
leads.delete
leads.assign
leads.export

campaigns.view
campaigns.create
campaigns.edit
campaigns.delete

reports.view
reports.export

users.view
users.create
users.edit

teams.manage

automations.view
automations.manage

integrations.view
integrations.manage

settings.view
settings.manage
```

Use:

* Policies
* Gates
* middleware

Do NOT only hide buttons.

Backend routes must also enforce permissions.

---

# 13. TEAMS

Create:

## teams

Fields:

```text
id
organization_id
name
description
manager_id
status
timestamps
```

Create:

```text
team_user
```

Teams can contain:

* Manager
* Team Lead
* Agents

---

# 14. DASHBOARD

Route:

```text
GET /crm/dashboard
```

Build according to supplied Dashboard reference.

Filters:

* Date Range
* Source
* Campaign
* Team
* Agent

All dashboard metrics must change based on filters.

---

# 15. DASHBOARD KPI CARDS

Create:

* Total Leads
* Qualified Leads
* Active Opportunities
* Cost Per Lead
* Closed Revenue
* Response SLA

Each card:

* icon
* current value
* previous period comparison
* percentage increase/decrease
* sparkline

Use actual database data.

---

# 16. DASHBOARD LEAD PIPELINE

Display funnel.

Default stages:

```text
New Lead
Contacted
Qualified
Opportunity
Proposal
Negotiation
Closed Won
```

Show:

* count
* percentage
* conversion rate

Stages must come from database.

---

# 17. DASHBOARD LEAD SOURCES

Display source performance.

Supported source architecture:

* Meta
* Facebook
* Instagram
* TikTok
* Google
* LinkedIn
* Website
* Web Form
* WhatsApp
* API
* Webhook
* CSV
* Manual
* Referral
* Bayut
* Dubizzle
* Other

Display:

* Leads
* Qualified
* Conversion
* Percentage
* Trend

---

# 18. DASHBOARD LOCATION / HOTSPOTS

When geographic data exists, show heatmap using Leaflet/OpenStreetMap.

If no geographic data:

show:

`Top Lead Locations`

Never break dashboard due to missing coordinates.

---

# 19. DASHBOARD AI INSIGHTS

Display:

* Best Performing Campaign
* Hottest Lead Source
* Leads Requiring Attention
* Best Follow-up Time
* Conversion Opportunities
* Cold Lead Risk
* Duplicate Risk
* User Performance Warning
* Campaign Opportunity

---

# 20. DASHBOARD TOP USERS

Show:

* User
* Leads
* Qualified
* Opportunities
* Closed
* Revenue
* Conversion Rate

---

# 21. DASHBOARD RECENT ACTIVITY

Show:

* Lead Created
* Lead Assigned
* Phone Call
* WhatsApp
* Email
* Note
* Meeting
* Appointment
* Stage Change
* Campaign Lead Received
* Opportunity Won

---

# 22. EXPECTED CLOSINGS

Show:

* Lead
* Interest
* Opportunity Value
* Probability
* Expected Close Date
* Assigned User

---

# 23. SMART TASK SUMMARY

Show:

* Active Tasks
* Unread Messages
* Unresponded Leads
* Overdue Follow-ups

---

# 24. LEADS MODULE

Routes:

```text
GET /crm/leads
GET /crm/leads/create
POST /crm/leads
GET /crm/leads/{lead}
GET /crm/leads/{lead}/edit
PUT /crm/leads/{lead}
DELETE /crm/leads/{lead}
```

---

# 25. LEADS DATABASE

Create:

## leads

Suggested fields:

```text
id
uuid
organization_id

first_name
last_name
email

phone
phone_normalized

whatsapp
whatsapp_normalized

alternate_phone

company
job_title

source_id
campaign_id

pipeline_id
pipeline_stage_id

status
priority

assigned_user_id
assigned_team_id

interested_in
category
requirement

min_budget
max_budget
currency

country
city
area

latitude
longitude

preferred_contact_method
preferred_language
preferred_contact_time

email_opt_in
sms_opt_in
whatsapp_opt_in
do_not_contact

external_id

lead_score
sentiment
engagement_score
conversion_probability

first_response_at
last_activity_at
next_followup_at

created_by

timestamps
softDeletes
```

Do not create real-estate-only mandatory fields.

---

# 26. LEAD INTELLIGENCE LIST

Create screen matching supplied Lead Intelligence screenshot.

Filters:

* Source
* Campaign
* Stage
* Status
* Assigned User
* Team
* Date Range
* Lead Score
* Interested In
* Category
* Location
* Communication Status
* Labels
* Priority
* Last Activity
* Follow-up Date

Allow multiple filters simultaneously.

---

# 27. LEAD QUICK TABS

Create:

```text
All Leads
My Leads
Hot Leads
High Intent
Duplicates
Unassigned
Overdue
Cold Leads
```

Show counts.

---

# 28. LEAD SEARCH

Search by:

* Name
* Phone
* WhatsApp
* Email
* Company
* Interest
* Location

Use server-side search.

---

# 29. LEAD TABLE

Columns:

```text
Checkbox
Name
Phone
Source
Interested In
Budget
Location
AI Lead Score
Sentiment
Last Activity
Next Task
Assigned User
Status
```

Support:

* sorting
* pagination
* bulk selection
* bulk assignment
* bulk status
* bulk stage
* bulk tags
* export
* row actions

---

# 30. ADD / EDIT LEAD

Create supplied Add/Edit Lead form.

## Section 1 — Lead Information

* Stage
* Status
* Priority
* Tags
* Lead Score

## Section 2 — Contact Details

* First Name
* Last Name
* Email
* Phone
* WhatsApp
* Alternate Phone
* Company
* Job Title
* Preferred Contact Method

## Section 3 — Interest / Requirement

* Interested In
* Offering
* Category
* Requirement
* Quantity
* Purpose
* Decision Timeline

## Section 4 — Budget / Location

* Minimum Budget
* Maximum Budget
* Currency
* Country
* City
* Area
* Latitude
* Longitude

## Section 5 — Source / Campaign

* Source
* Campaign
* Medium
* UTM Source
* UTM Medium
* UTM Campaign
* External Lead ID

## Section 6 — Communication

* Preferred Language
* Best Contact Time
* Email Updates
* SMS Updates
* WhatsApp Updates
* Do Not Contact

## Section 7 — Notes

## Section 8 — Attachments

## Section 9 — Assignment / Follow-up

* Assigned User
* Team
* Co-assigned User
* Next Follow-up
* Reminder

---

# 31. LEAD FORM AI PANEL

Right side panel:

* AI Lead Quality
* Duplicate Check
* Sentiment
* Recommended First Action
* AI Summary

AI failure must NOT stop lead creation.

---

# 32. LEAD 360 PROFILE

Route:

```text
GET /crm/leads/{lead}
```

Build according to supplied Lead 360 screenshot.

Header:

* Avatar
* Name
* Status
* Phone
* WhatsApp
* Email
* Company
* Source
* Campaign
* Assigned User
* Budget
* Interested In
* Location
* Pipeline Stage

---

# 33. LEAD 360 METRICS

Show:

* Lead Score
* Sentiment
* Engagement
* Duplicate Risk
* Conversion Probability

---

# 34. LEAD TAGS

Create customizable tags.

Examples:

* Hot Lead
* High Intent
* Decision Maker
* Investor
* Urgent
* Follow-up Required
* Demo Requested
* Buy Soon

Allow multiple tags.

---

# 35. LEAD ACTIVITIES

Create:

## lead_activities

Fields:

```text
id
organization_id
lead_id
user_id
type
channel
subject
description
metadata JSON
activity_at
timestamps
```

Types:

```text
lead_created
assignment
reassignment
stage_changed
status_changed
call
whatsapp
sms
email
meeting
appointment
demo
visit
note
task
form_submission
website_activity
campaign_activity
file
custom
```

---

# 36. LEAD NOTES

Create:

## lead_notes

Fields:

* organization_id
* lead_id
* user_id
* note
* timestamps

CRUD notes.

---

# 37. ATTACHMENTS

Create generic attachments.

Support:

* Leads
* Campaigns
* Opportunities

Store:

* Original Filename
* Stored Filename
* MIME
* Size
* Uploaded By

Use Laravel Storage.

Validate files.

---

# 38. TASKS

Create:

## tasks

Fields:

```text
organization_id
lead_id nullable
campaign_id nullable
opportunity_id nullable

assigned_user_id
created_by

type
title
description

priority
status

due_at
reminder_at
completed_at

timestamps
```

Task types:

* Call
* Follow-up
* WhatsApp
* SMS
* Email
* Meeting
* Appointment
* Demo
* Visit
* Send Information
* Send Proposal
* Custom

---

# 39. OFFERINGS

Create generic:

## offerings

Can represent:

* Property
* Car
* Course
* Software Plan
* Insurance Plan
* Solar Package
* Service
* Product

Fields:

```text
organization_id
name
type
category
description
price
currency
status
metadata
timestamps
```

---

# 40. LEAD INTERESTS

Create:

## lead_interests

Fields:

```text
organization_id
lead_id
offering_id nullable
name
category
estimated_value
notes
metadata
timestamps
```

---

# 41. OPPORTUNITIES / DEALS

Create:

## opportunities

Fields:

```text
organization_id
lead_id
campaign_id nullable
pipeline_id
pipeline_stage_id
assigned_user_id

name
estimated_value
currency
probability
status

expected_close_date
closed_at
lost_reason
notes

timestamps
softDeletes
```

Statuses:

```text
open
won
lost
```

---

# 42. CAMPAIGNS MODULE

Routes:

```text
GET /crm/campaigns
GET /crm/campaigns/create
POST /crm/campaigns
GET /crm/campaigns/{campaign}
GET /crm/campaigns/{campaign}/edit
PUT /crm/campaigns/{campaign}
DELETE /crm/campaigns/{campaign}
```

---

# 43. CAMPAIGN DATABASE

Create:

## campaigns

Fields:

```text
organization_id

name
source_id
integration_id nullable
external_campaign_id nullable

objective
description

budget_type
budget
currency

start_date
end_date

status
routing_method

total_spend
total_leads
qualified_leads
conversions
revenue

last_synced_at

created_by
settings JSON

timestamps
softDeletes
```

---

# 44. CAMPAIGN CENTER

Match supplied Campaign Center screenshot.

KPI cards:

* Active Campaigns
* Leads This Week
* Qualified Rate
* Total Spend
* ROI

Filters:

* Source
* Team
* User
* Status
* Date
* Objective

Table:

```text
Campaign
Channel
Budget
Leads
Qualified
Cost / Lead
Assigned Users
Sync Status
Status
AI Score
```

---

# 45. CAMPAIGN SOURCES

Support:

* Meta / Facebook
* Instagram
* TikTok
* Google
* LinkedIn
* Website
* Web Form
* WhatsApp
* API
* Webhook
* CSV
* Manual
* Referral
* Bayut
* Dubizzle
* Other

---

# 46. CAMPAIGN WIZARD

Route:

```text
/crm/campaigns/create
```

Steps:

```text
1. Source
2. Connect Account
3. Configure Campaign
4. Assign Users
5. Routing Rules
6. Budget & Goals
7. Review
8. Activate
```

Persist draft.

Allow:

`Save Draft`

---

# 47. CAMPAIGN ROUTING

Create:

```text
App\Services\LeadRoutingService
```

Methods:

### Round Robin

### Least Assigned

### Performance Based

### Location Based

### Weighted

Example:

```text
User A = 50%
User B = 30%
User C = 20%
```

---

# 48. CAMPAIGN BUDGET / GOALS

Fields:

* Daily Budget
* Total Budget
* Target Leads
* Target Qualified Leads
* Target CPL
* Target Revenue

---

# 49. CAMPAIGN AI RECOMMENDATIONS

Show:

* Suggested Budget
* Best Posting Time
* Suggested Audience
* Expected CPL
* Expected Leads
* Expected Qualified Rate
* Conversion Estimate

---

# 50. CSV IMPORT

Build proper CSV import.

Steps:

1. Upload
2. Preview
3. Map Columns
4. Map Custom Fields
5. Choose Campaign
6. Assign Team/User
7. Duplicate Handling
8. Import

Use queues/chunks for large CSV files.

Show progress and result summary.

---

# 51. WEBHOOK LEADS

Create endpoint architecture:

```text
POST /api/v1/leads/webhook/{source}
```

Secure with:

* Token
* Signature where supported
* Rate limit

Create:

## webhook_logs

Track:

* organization
* source
* payload
* status
* result
* error
* processed_at

Queue webhook processing.

---

# 52. LEAD SOURCES

Create:

## lead_sources

Fields:

```text
organization_id nullable
name
slug
icon
type
status
timestamps
```

Seed global defaults.

---

# 53. AI COPILOT MODULE

Route:

```text
GET /crm/ai-copilot
```

Match supplied AI Copilot UI.

Cards:

* Priority Leads Today
* Suggested Follow-ups
* AI Message Drafts
* Best Time to Contact
* Objection Risk

---

# 54. AI HOTSPOTS

Analyze:

* locations
* sources
* campaigns
* categories

---

# 55. AI CONVERSION PREDICTIONS

Classify:

* High
* Medium
* Low

---

# 56. AI SMART TASKS

Recommend:

* Call
* WhatsApp
* Follow-up
* Email
* Meeting
* Demo
* Send Information

---

# 57. AI ASSISTANT

Chat panel questions should support:

```text
Which leads should I focus on today?

Which campaigns are performing best?

Which users need attention?

Which leads are likely to convert?

Which campaigns have high CPL?

Draft a WhatsApp message.

Draft an email.

Summarize this lead.

Why is conversion falling?

Which source has best ROI?

What should I do today?
```

---

# 58. AI ARCHITECTURE

Create:

```text
App\Services\AI
```

Create:

```text
AIProviderInterface
LeadScoringService
LeadSummaryService
SentimentService
IntentDetectionService
ConversionPredictionService
RecommendedActionService
MessageSuggestionService
CampaignRecommendationService
ReportInsightService
DuplicateAnalysisService
```

Environment:

```env
AI_ENABLED=false
AI_PROVIDER=
AI_API_KEY=
AI_MODEL=
```

Never hardcode keys.

If AI is disabled:

CRM must work normally.

---

# 59. AI INSIGHTS DATABASE

Create:

## ai_insights

Fields:

```text
organization_id
subject_type
subject_id
type
provider
model
content
score
metadata
generated_at
expires_at
timestamps
```

Cache AI results.

Do not regenerate every request.

---

# 60. REPORTS

Route:

```text
GET /crm/reports
```

Match supplied reports UI.

Tabs:

* Executive Summary
* Sales Performance
* Marketing Performance
* User Performance
* Pipeline Health
* Custom Report Builder

---

# 61. REPORT FILTERS

* Date
* Source
* Team
* User
* Campaign
* Category
* Offering
* Pipeline

---

# 62. REPORT KPI CARDS

* Total Revenue
* Qualified Rate
* Cost Per Qualified Lead
* Average Response Time
* Conversion Rate
* Forecasted Closings

---

# 63. SALES FUNNEL REPORT

Show:

```text
Total Leads
Contacted
Qualified
Opportunity
Proposal
Negotiation
Closed Won
```

---

# 64. SOURCE ROI REPORT

Calculate:

* Spend
* Leads
* Qualified
* CPL
* CPQL
* Revenue
* ROI
* Conversion

---

# 65. CAMPAIGN PERFORMANCE REPORT

Show:

* Campaign
* Spend
* Leads
* Qualified
* CPL
* CPQL
* Conversions
* Revenue
* ROI

---

# 66. USER PERFORMANCE

Show:

* Assigned Leads
* Contacted
* Qualified
* Opportunities
* Closed
* Revenue
* Response Rate
* Avg Response Time
* Conversion Rate

---

# 67. RESPONSE TIME TREND

Create Chart.js line chart.

---

# 68. LOCATION DEMAND

Use Leaflet where lead locations exist.

Otherwise use ranked locations table/chart.

---

# 69. DEAL FORECAST

Calculate:

* Forecast Revenue
* Forecast Deals
* Expected Close Date
* Probability

---

# 70. PIPELINE HEALTH

Classify:

* Healthy
* At Risk
* Stalled

---

# 71. AI REPORT INSIGHTS

Display:

### Key Takeaways

### Recommended Actions

---

# 72. EXPORTS

Support:

* CSV
* Printable HTML

Create ExportService.

Large exports should use queues.

---

# 73. USERS MODULE

Route:

```text
GET /crm/users
```

Match supplied Users & Teams screenshot.

Summary cards:

* Total Users
* Active Agents
* Managers
* Average SLA

Tabs:

* Active
* Paused
* Archived
* Invited
* Teams

---

# 74. USERS TABLE

Columns:

```text
User
Email
Role
Team
Campaigns Assigned
Productivity Score
Response Rate
Last Active
Status
```

---

# 75. INVITE USER

Use Bootstrap offcanvas.

Fields:

* Full Name
* Email
* Role
* Team
* Campaigns
* Permissions
* Message

Create secure invitation token.

Send email invitation.

User sets password after accepting.

---

# 76. USER STATUS

Support:

* Active
* Paused
* Archived
* Invited

Avoid deleting users with history.

---

# 77. AUTOMATIONS MODULE

Route:

```text
GET /crm/automations
```

Create:

## automation_rules

Fields:

```text
organization_id
name
trigger
conditions JSON
actions JSON
status
created_by
last_run_at
timestamps
```

Create:

## automation_runs

Fields:

```text
organization_id
automation_rule_id
subject_type
subject_id
status
input
output
error
started_at
finished_at
timestamps
```

---

# 78. AUTOMATION TRIGGERS

* Lead Created
* Lead Updated
* Lead Assigned
* Stage Changed
* Status Changed
* Score Changed
* Task Overdue
* No Response
* Message Received
* Campaign Lead Received
* Date Reached

---

# 79. AUTOMATION CONDITIONS

* Source
* Campaign
* Team
* User
* Stage
* Status
* Priority
* Score
* Budget
* Location
* Tag
* Custom Field
* Days Since Contact
* Activity Count

---

# 80. AUTOMATION ACTIONS

* Assign User
* Assign Team
* Change Stage
* Change Status
* Add Tag
* Remove Tag
* Create Task
* Send Notification
* Generate AI Message
* Send Email
* Send WhatsApp when configured
* Trigger Webhook
* Notify Manager

Log every automation run.

---

# 81. INTEGRATIONS

Route:

```text
GET /crm/integrations
```

Cards:

* Meta Ads
* Instagram
* TikTok
* Google
* LinkedIn
* Website Forms
* Webhook/API
* WhatsApp
* Email
* Bayut
* Dubizzle

Status:

* Connected
* Disconnected
* Error
* Syncing

---

# 82. INTEGRATION DATABASE

Create:

## integrations

Fields:

```text
organization_id
provider
name
status
credentials
settings
last_synced_at
last_error
timestamps
```

Encrypt credentials.

Never expose secrets.

---

# 83. INTEGRATION SERVICES

Create:

```text
App\Services\Integrations
```

Possible services:

```text
MetaIntegrationService
TikTokIntegrationService
GoogleIntegrationService
WhatsAppIntegrationService
WebsiteIntegrationService
EmailIntegrationService
```

Controllers must not contain external API logic.

---

# 84. WHATSAPP

Create provider abstraction.

Do not pretend a message was sent if integration is not connected.

Show:

`WhatsApp integration required`

until configured.

---

# 85. SETTINGS

Route:

```text
GET /crm/settings
```

Tabs:

```text
Organization
Users & Teams
Integrations
Lead Routing
Automations
Custom Fields
Duplicates
Permissions
```

---

# 86. ORGANIZATION SETTINGS

Fields:

* Organization Name
* Logo
* Industry
* Currency
* Country
* Timezone
* Language
* Phone Country
* Date Format

---

# 87. PIPELINES

Create:

## pipelines

Fields:

```text
organization_id
name
entity_type
status
is_default
timestamps
```

Create:

## pipeline_stages

Fields:

```text
pipeline_id
name
slug
color
position
type
probability
active
timestamps
```

Admin can:

* create
* edit
* reorder
* set color
* activate/deactivate
* safely remove

Support multiple pipelines.

---

# 88. CUSTOM FIELDS

Create:

## custom_fields

Fields:

```text
organization_id
entity_type
section
name
slug
type
required
options JSON
default_value
position
active
timestamps
```

Supported types:

```text
text
textarea
number
currency
percentage
email
phone
url
dropdown
multi_select
radio
checkbox
toggle
date
datetime
```

Apply to:

* Leads
* Campaigns
* Opportunities
* Offerings

Create:

## custom_field_values

Use polymorphic design.

---

# 89. TAGS

Create:

## tags

Fields:

```text
organization_id
name
color
active
timestamps
```

Create pivot:

```text
lead_tag
```

---

# 90. DUPLICATE MANAGEMENT

Detect duplicates using:

* Normalized Phone
* WhatsApp
* Email
* External ID

Create:

## duplicate_candidates

Fields:

```text
organization_id
lead_id
possible_duplicate_id
confidence
matching_fields JSON
status
reviewed_by
reviewed_at
timestamps
```

Statuses:

* pending
* ignored
* merged

---

# 91. LEAD MERGE

When merging preserve:

* Activities
* Notes
* Tasks
* Attachments
* Tags
* Opportunities
* Custom Fields
* Campaign relations
* Source IDs

Create merge audit/history.

---

# 92. SLA MANAGEMENT

Create organization SLA settings.

Example:

```text
New lead must be contacted within 15 minutes.
```

Track:

* Lead Created
* First Response
* SLA Deadline
* SLA Status

Statuses:

* Safe
* Warning
* Breached

Show SLA in:

* Dashboard
* Lead List
* User Performance
* Reports

---

# 93. NOTIFICATIONS

Use Laravel database notifications.

Notifications:

* New Lead Assigned
* Lead Reassigned
* Follow-up Due
* Task Overdue
* SLA Warning
* SLA Breach
* CSV Import Complete
* Campaign Sync Error
* Automation Error
* User Invitation

---

# 94. AUDIT LOG

Create:

## audit_logs

Fields:

```text
organization_id
user_id
action
subject_type
subject_id
before JSON
after JSON
ip
user_agent
created_at
```

Audit important actions:

* Lead Merge
* Lead Delete
* Lead Assignment
* User Role Change
* Integration Change
* Automation Change
* Pipeline Change
* Campaign Change

---

# 95. APPLICATION SEARCH

Support global search for:

* Leads
* Campaigns
* Users

Use indexes.

---

# 96. BUSINESS SERVICES

Keep controllers thin.

Create services such as:

```text
LeadService
ActivityService
TaskService
LeadAssignmentService
LeadRoutingService
DuplicateService
OpportunityService
CampaignService
DashboardService
ReportService
ExportService
AutomationService
IntegrationService
```

---

# 97. EVENTS

Create events where useful:

```text
LeadCreated
LeadAssigned
LeadStageChanged
LeadStatusChanged
TaskOverdue
CampaignLeadReceived
OpportunityWon
```

Listeners can:

* create activities
* trigger automation
* notify users
* update statistics

---

# 98. FORM REQUESTS

Use Form Request classes.

Examples:

```text
StoreLeadRequest
UpdateLeadRequest
StoreCampaignRequest
UpdateCampaignRequest
StoreTeamRequest
StoreUserRequest
StoreAutomationRequest
StoreIntegrationRequest
```

Do not place huge validation arrays inside controllers.

---

# 99. QUEUES

Use Laravel jobs for:

* CSV Import
* Large Export
* Webhook Processing
* External Campaign Sync
* AI Processing
* Automated Follow-up
* Report Generation

Use database queue as default if needed.

---

# 100. SCHEDULER

Create scheduled processes for:

* SLA Monitoring
* Follow-up Reminders
* Overdue Tasks
* Stale Lead Detection
* Campaign Synchronization
* Automation Processing
* Cleanup

Document scheduler requirements.

---

# 101. INDUSTRY TEMPLATES

Create presets.

### Generic

Generic fields.

### Real Estate

Custom fields:

* Property Type
* Bedrooms
* Area
* Possession Date

### Automotive

* Make
* Model
* Year
* Vehicle Type

### Education

* Program
* Intake
* Campus
* Qualification

### Insurance

* Policy Type
* Coverage
* Existing Policy

### Solar

* Property Type
* Electricity Bill
* Desired System Size

Core application remains identical.

---

# 102. SECURITY

Implement:

* Authentication
* CSRF
* Authorization
* Policies
* Organization Scoping
* Input Validation
* Upload Validation
* Webhook Rate Limiting
* Secure Tokens
* Encrypted Integration Credentials
* Audit Logs

Never expose:

* API keys
* passwords
* integration tokens

---

# 103. PERFORMANCE

The CRM may have thousands/millions of leads.

Therefore:

* use database indexes
* server-side pagination
* eager loading
* query scopes
* aggregate queries
* caching
* queue imports
* queue exports
* avoid N+1
* avoid loading all records into browser

---

# 104. DATABASE INDEXES

At minimum consider indexing:

```text
organization_id
assigned_user_id
assigned_team_id
campaign_id
source_id
pipeline_stage_id
status
priority
phone_normalized
whatsapp_normalized
email
external_id
created_at
last_activity_at
next_followup_at
```

Use proper composite indexes where useful.

---

# 105. CACHE

Cache dashboard/report aggregates temporarily.

Invalidate where necessary.

---

# 106. SEED DATA

Create development seeders.

Generate:

```text
1 Organization
1 Administrator
2 Managers
8 Agents
3 Teams
10 Campaigns
10 Lead Sources
150+ Leads
30+ Opportunities
Activities
Tasks
Notes
Tags
Offerings
Custom Fields
Automations
Integration placeholders
```

Use fictional data.

Development password:

```text
password
```

Document local credentials.

---

# 107. TESTING

Create feature tests.

## Authentication

* Register
* Login
* Logout
* Reset Password
* Protected Routes

## Organization

* Data Isolation

## Leads

* Create
* Edit
* View
* Search
* Filter
* Assign
* Permission

## Campaigns

* Create
* Edit
* Wizard
* Assignment

## Users

* Invite
* Permissions

## Duplicate Detection

## Automation

## Webhook

## Reports Authorization

## Opportunities

Use factories.

---

# 108. ROUTES

Use organization such as:

```php
Route::middleware(['auth'])->prefix('crm')->group(function () {

    // Dashboard

    // Leads

    // Campaigns

    // AI Copilot

    // Reports

    // Users

    // Teams

    // Automations

    // Integrations

    // Settings

});
```

Protect using policies/permission middleware.

---

# 109. CONTROLLERS

Suggested:

```text
App\Http\Controllers\CRM\DashboardController
App\Http\Controllers\CRM\LeadController
App\Http\Controllers\CRM\CampaignController
App\Http\Controllers\CRM\OpportunityController
App\Http\Controllers\CRM\ReportController
App\Http\Controllers\CRM\UserController
App\Http\Controllers\CRM\TeamController
App\Http\Controllers\CRM\AutomationController
App\Http\Controllers\CRM\IntegrationController
App\Http\Controllers\CRM\SettingsController
App\Http\Controllers\CRM\TaskController
```

---

# 110. VIEW STRUCTURE

Use:

```text
resources/views/crm/

layouts/
components/

dashboard/

leads/
campaigns/
ai/
reports/
users/
teams/
automations/
integrations/
settings/
```

---

# 111. REQUIRED AUTH SCREENS

Implement:

* Login
* Register
* Forgot Password
* Reset Password
* Verify Email

---

# 112. REQUIRED DASHBOARD SCREEN

Implement:

* Main Dashboard

---

# 113. REQUIRED LEAD SCREENS

Implement:

* Lead Intelligence
* Lead Quick View Drawer
* Lead 360
* Add Lead
* Edit Lead
* Bulk Actions
* Duplicate Review
* Merge Lead
* Tasks
* Activities
* Notes

---

# 114. REQUIRED CAMPAIGN SCREENS

Implement:

* Campaign Center
* Campaign Detail
* Create Campaign Wizard
* Edit Campaign
* CSV Import
* Manual Campaign
* Webhook Setup
* User Assignment
* Routing Configuration

---

# 115. REQUIRED AI SCREENS

Implement:

* AI Copilot
* Priority Lead Recommendations
* AI Message Generator
* Lead Summary
* Campaign Recommendations

---

# 116. REQUIRED REPORT SCREENS

Implement:

* Executive Summary
* Sales
* Marketing
* User Performance
* Pipeline Health
* Custom Reports

---

# 117. REQUIRED USER SCREENS

Implement:

* Users
* Invite User
* Edit User
* User Profile
* Teams
* Add/Edit Team

---

# 118. REQUIRED AUTOMATION SCREENS

Implement:

* Automation List
* Create Automation
* Edit Automation
* Automation Logs

---

# 119. REQUIRED INTEGRATION SCREENS

Implement:

* Integration Cards
* Connect Integration
* Integration Configuration
* Webhook/API Configuration
* Integration Logs

---

# 120. REQUIRED SETTINGS SCREENS

Implement:

* Organization
* Profile
* Users & Teams
* Pipelines
* Custom Fields
* Tags
* Lead Routing
* Automations
* Integrations
* Duplicate Handling
* Roles / Permissions
* SLA
* Subscription placeholder

---

# 121. RESPONSIVE UI

Desktop:

match supplied screenshots closely.

Tablet:

* collapsible sidebar
* responsive cards
* scrollable tables

Mobile:

* Bootstrap offcanvas sidebar
* cards stack vertically
* responsive forms
* drawers fullscreen where appropriate
* horizontal table scroll

---

# 122. DO NOT BUILD STATIC MOCKUPS

This is important.

Do NOT create fake dashboards using only hardcoded numbers.

Every:

* KPI
* table
* chart
* count
* status
* report
* filter
* user
* campaign
* lead
* opportunity

must connect to actual database queries.

Seed data can populate local demo values.

---

# 123. ERROR HANDLING

Add:

* validation messages
* flash messages
* toast notifications
* API error logging
* integration error handling
* graceful AI failure

Never expose sensitive exception data in UI.

---

# 124. DEVELOPMENT ORDER

Implement sequentially.

## Phase 1

Authentication + Bootstrap + CampaignPilot layout.

## Phase 2

Organizations + Roles + Permissions + Teams.

## Phase 3

Pipelines + Stages + Tags + Custom Fields + Offerings.

## Phase 4

Leads Listing + Add/Edit Lead.

## Phase 5

Lead 360 + Activities + Notes + Tasks + Attachments.

## Phase 6

Opportunities.

## Phase 7

Campaign Center + Campaign Wizard.

## Phase 8

Dashboard.

## Phase 9

Users + Teams UI.

## Phase 10

Duplicate Management + SLA.

## Phase 11

Automations.

## Phase 12

Integrations + Webhooks + CSV.

## Phase 13

AI Architecture + AI Copilot.

## Phase 14

Reports.

## Phase 15

Settings.

## Phase 16

Testing + Seeders + Responsive Cleanup + Optimization + Documentation.

Do NOT stop after writing a plan.

Actually implement each phase.

---

# 125. AFTER EACH MAJOR PHASE

Run:

```bash
php artisan optimize:clear
php artisan test
php artisan route:list
```

When migrations are ready:

```bash
php artisan migrate
```

Never use:

```bash
php artisan migrate:fresh
```

unless I explicitly authorize it.

Do NOT run:

```text
npm install
npm run build
npm run dev
```

---

# 126. IMPORTANT DO NOT RULES

Do NOT:

* create another Laravel project
* change Laravel version
* use Tailwind
* use Vue
* use React
* require NPM
* require Vite
* make CRM real-estate-only
* hardcode organization ID
* hardcode API keys
* expose credentials
* create static fake pages
* place all logic inside controllers
* load all leads at once
* run migrate:fresh
* delete working code unnecessarily
* fake external API success
* fake WhatsApp success
* fake AI if AI is disabled

---

# 127. ENVIRONMENT VARIABLES

Update `.env.example` with placeholders such as:

```env
APP_NAME="CampaignPilot CRM"

AI_ENABLED=false
AI_PROVIDER=
AI_API_KEY=
AI_MODEL=

QUEUE_CONNECTION=database

MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="CampaignPilot CRM"
```

Add provider variables only when needed.

Never place real secrets into `.env.example`.

---

# 128. FINAL DOCUMENTATION

When implementation is finished create:

```text
CAMPAIGNPILOT_IMPLEMENTATION.md
```

Document:

* Application Architecture
* Installation
* Authentication
* Database
* Organizations
* Roles
* Permissions
* Leads
* Campaigns
* AI
* Reports
* Automations
* Integrations
* Webhooks
* CSV Import
* Queue
* Scheduler
* Email
* File Storage
* Seed Credentials
* Environment Variables
* API Endpoints
* Deployment Commands
* Testing
* Security
* Pending API Credentials
* Production Checklist

Also update:

```text
README.md
.env.example
```

---

# 129. FINAL EXPECTATION

The final application must be a **working CRM**, not simply screenshots converted to HTML.

The user should be able to:

1. Register a business.
2. Login.
3. Create users and teams.
4. Configure roles.
5. Create leads.
6. Import leads.
7. Receive webhook leads.
8. Search/filter leads.
9. Assign leads.
10. Manage lead stages.
11. Track activities.
12. Create tasks.
13. Manage opportunities.
14. Create campaigns.
15. Configure routing.
16. Analyze campaigns.
17. Run automations.
18. Configure integrations.
19. Use AI features when configured.
20. Generate reports.
21. Export data.
22. Customize fields.
23. Customize pipelines.
24. Manage duplicates.
25. Monitor SLA.
26. View dashboards.

The system must remain **generic for any business industry**.

Industry-specific requirements must be handled through:

* Custom Fields
* Pipelines
* Tags
* Offerings
* Terminology
* Templates

Do not hardcode industry-specific business logic.

---

# START IMPLEMENTATION NOW

Start by:

1. Inspecting the Laravel 10 project.
2. Checking existing files and `.env`.
3. Creating Bootstrap-only authentication without NPM.
4. Creating CampaignPilot CRM auth design.
5. Creating the reusable CRM layout/sidebar.
6. Creating organization architecture.
7. Creating roles, permissions and teams.
8. Creating migrations/models.
9. Continuing through all implementation phases above.

Do not merely tell me what files you intend to create.

Actually create and modify the Laravel files necessary to build the complete application.

Use my supplied CampaignPilot CRM screenshots continuously as the UI reference.

The final result must be a **production-structured, multi-business Campaign + Lead CRM using Laravel 10, Blade and Bootstrap 5 without NPM**.
