# CampaignPilot CRM — Client Study Guide

**Document type:** Product briefing for client review  
**Audience:** Business owners, managers, and stakeholders  
**Purpose:** Help you understand what CampaignPilot does, how your team will use it, and what is already working today.

You do not need technical knowledge to read this. Use it as a study pack before a demo, UAT session, or handover meeting.

---

## 1. One-page summary

CampaignPilot is a **private sales and marketing workspace** for one company.

It brings together:

- **Leads** — people who enquired
- **Campaigns** — the ads or marketing activity that produced those enquiries
- **Pipeline** — the sales stages from new enquiry to won or lost
- **Users & teams** — who owns the follow-up
- **Reports** — whether the work is paying for itself
- **AI Copilot (Beta)** — ranked suggestions based on your data (see section 8)

Each company gets its **own workspace**. Another company cannot see your leads, campaigns, or staff.

It is not limited to real estate. The same process works for education, automotive, insurance, solar, agencies, healthcare, home services, and any lead-driven business.

---

## 2. The business flow (memorise this)

```
Advert / form / WhatsApp / website
        ↓
     Lead is created
        ↓
  Assigned to an agent (or a team)
        ↓
  Follow-up: call, note, task, stage change
        ↓
  Qualified → Opportunity → Proposal → Won or Lost
        ↓
  Dashboard + Reports show what is working
```

**Campaign** = the marketing bucket (example: “August Meta Housing Ads”).  
**Lead** = the person who responded.  
**Agent** = the person who must follow up.

If you remember only one thing: **every enquiry should land in one place, with an owner, a stage, and a next action.**

---

## 3. Who can do what

| Role | What they are for |
|---|---|
| **Administrator** | Full control: company setup, users, integrations, settings |
| **Manager** | Campaigns, leads, teams, reports |
| **Team lead** | Supervises a group of agents |
| **Agent** | Works assigned leads: call, note, move stage |
| **Viewer** | Can look, cannot change important data |

If a button is missing on someone’s screen, their role is not allowed to use it. That is by design.

---

## 4. Screen-by-screen study notes

### Dashboard
The morning control room.

Study these questions:

- How many leads arrived in this period?
- Which source is strongest?
- Where are leads stuck in the funnel?
- Which campaign is recommended, and why?

You can change the date range (7 days, 14 days, 30 days, quarter).

### Leads
The working list for agents.

They can:

- add a lead by hand
- filter by source, campaign, stage, agent, city, score
- open a record and see history, notes, tasks, and follow-up
- assign, tag, export, or merge duplicates

**Study tip:** Open one lead and walk the full card: contact → stage → note → task → message draft.

### Campaigns
Where marketing activity is planned and tracked.

A campaign has a name, channel (Meta, Google, website, and so on), budget, dates, assigned agents, and status (Draft, Active, Paused, Completed).

Leads can also be imported from a CSV file against a campaign.

### AI Copilot (Beta)
A helper screen that ranks:

- which leads to focus on today
- suggested follow-up tasks
- message drafts
- city hotspots
- conversion bands (high / medium / low)

See **section 8** for how these suggestions are produced. They are based on your CRM data and scoring rules. They are not a black-box “magic” model unless OpenAI is switched on for summaries.

### Reports & Insights
Management view. Tabs include:

| Tab | What to study |
|---|---|
| **Executive Summary** | Revenue, qualified rate, funnel, sources, forecast |
| **Sales Performance** | Won deals, win rate, deal mix, closers |
| **Marketing Performance** | Spend, CPL, ROAS, campaign table |
| **Agent Performance** | Assigned vs won, conversion, response time |
| **Pipeline Health** | Healthy / at-risk / stalled deals |
| **Custom Report Builder** | Group results by source, campaign, agent, city, stage, or day |

Export is available from the top of the page.

### Users & Teams
Administrators invite people, choose a role, and put them in teams.

On the **Teams** tab you can:

- create a team (name, description, manager, members)
- click **Manage members** to add or remove people and change the manager
- invite a new user with a team already selected

### Automations
Rules that run without someone clicking, for example:

- new lead → assign owner + create a follow-up task
- lead sits too long → notify the team
- stage changes → start the next action

### Integrations
Connections to outside tools (Meta, Google, website forms, WhatsApp, and similar).

**Current reality for Meta:** Connect saves the login so CampaignPilot is authorised. Creating a campaign inside Meta Ads Manager does **not yet automatically copy** that campaign into CampaignPilot. The team should still create or match the campaign here.

### Settings
Company name, industry, currency, timezone, pipelines (stage names), tags, custom fields, duplicate rules, lead routing, and permissions.

---

## 5. How a lead gets into the system

| Method | When to use it |
|---|---|
| **Add Lead** | Walk-in, phone call, or a lead that did not come through ads |
| **CSV import** | A spreadsheet from another tool or an old list |
| **Website / webhook** | Forms or another system send enquiries automatically |
| **Connected channel** | After an integration is connected and fully set up |

When a lead arrives, the system can:

1. flag possible **duplicates** (same phone or email)
2. **assign** it using your routing rule (for example round-robin)
3. create a **follow-up task**
4. give it a **score** (see section 8)

---

## 6. The sales pipeline (default)

**New Lead → Contacted → Qualified → Opportunity → Proposal → Negotiation → Closed Won / Closed Lost**

You can rename or add stages in Settings.

A healthy working day:

1. New leads appear on Dashboard and in Leads.
2. Agents contact them and add a note.
3. Serious buyers move to Qualified or Opportunity.
4. A sale is Closed Won. A no is Closed Lost.
5. Managers use the funnel to see where people get stuck.

---

## 7. Suggested walkthrough for the client session

Use this as a 30–40 minute study path.

| Step | What to click | What to notice |
|---|---|---|
| 1 | Log in | Each company has its own workspace |
| 2 | Dashboard | KPIs, funnel, sources, AI Copilot card |
| 3 | Leads → open one record | Score, stage, notes, tasks |
| 4 | Campaigns → one campaign | Budget, agents, linked leads |
| 5 | AI Copilot | Priority list, drafts, tasks |
| 6 | Reports → each tab | Content should change per tab |
| 7 | Users → Teams | Create / manage members |
| 8 | Integrations | Connect vs API key platforms |
| 9 | Settings | Pipeline names, routing, tags |

Ask after the walkthrough:

- Can my agents see only what they need?
- Will every enquiry have an owner?
- Can I tell which campaign is worth more spend?
- Is anything still manual that we expected to be automatic?

---

## 8. How AI suggestions are produced (important)

CampaignPilot labels some screens “AI”. For the client, the honest picture is:

**Most suggestions are calculated from your own CRM data using clear rules.**  
They are not a hidden neural network learning from every deal unless you switch on an external AI provider.

### 8.1 Lead score (the number everything else uses)

Each lead starts at **20**. Points are added when information is present:

| Information on the lead | Points added |
|---|---|
| Email | +10 |
| Phone | +10 |
| WhatsApp | +5 |
| Company | +5 |
| Budget | +15 |
| What they are interested in | +10 |
| City | +5 |
| Requirement / notes of need | +10 |
| High or urgent priority | +10 |

Maximum **99**.

A higher score means “this record looks more complete and more serious,” not “a robot predicted they will definitely buy.”

### 8.2 What the screens do with that score

| Screen | What you see | How it is computed |
|---|---|---|
| **Lead list / Copilot priority** | Hot / high-score people first | Sort by lead score |
| **Next action** | Call, meeting, WhatsApp, or follow-up | Simple rules: no reply yet → call; score 80+ → meeting; has interest → WhatsApp |
| **Message draft** | Ready WhatsApp / email text | A template filled with name, interest, and budget |
| **Conversion band** | High / medium / low | Score ≥ 70 high, 40–69 medium, below that low |
| **Dashboard Copilot card** | Best campaign, hottest source, unresponded leads | Campaign with most qualified leads; source with most leads; count of leads with no first reply |
| **Campaign suggestions** | Budget, posting window, audience | About 10% more budget; fixed evening window (weekdays 6–9 PM) unless real campaign stats exist |
| **Reports → AI Insights** | Takeaways and recommended actions | Standard management advice (not custom-written from every number on the page) |
| **Copilot chat** | Answer to a typed question | Keyword matching (e.g. “WhatsApp” → draft; “campaign” → best campaign; otherwise top 3 leads by score) |


### 8.3 Optional live AI (OpenAI)

If the company turns **AI** on in setup and provides an API key, CampaignPilot can send a lead summary to OpenAI (default model: a small chat model) and store the reply for a day.

If AI is **off** (the usual setting), the summary is a plain sentence built from name, interest, budget, location, and stage. The rest of Copilot still works using the rules above.

**What this means for the client:** Copilot is useful as a **prioritisation and drafting assistant**. It should not be treated as a guaranteed forecast of revenue. Always verify important details with the live lead and campaign numbers.

---

## 9. Teams — how members are managed

On **Users & Teams → Teams**:

1. Create a team: name, description, manager, tick members, **Add Team**.
2. On an existing team, click **Manage members**.
3. Change name, manager, or tick/untick people, then **Save team**.
4. When inviting a new user, you can assign a team on the invitation form.

The manager of a team is also kept as a member.

---

## 10. What “good” looks like after go-live

The system is doing its job when:

- every enquiry lands in **one list**
- no lead sits unassigned
- agents know **who to call next**
- managers can answer **which campaign is paying for itself**
- reports match what the sales team feels on the ground
- the same process still works if the product or industry changes

---

## 11. Honest limits (so expectations stay clear)

Please study these points before asking for extra scope:

1. **Meta Connect** authorises the account. It does not yet clone every Meta campaign into CampaignPilot automatically.
2. **AI Copilot** is mostly **rules + rankings** on your data. Live OpenAI text is optional and currently used for lead summaries.
3. **Report “Best follow-up time”** on the dashboard is a suggested window, not a full statistical model of every reply hour.
4. Integrations other than Meta generally use an API key / token until a similar Connect flow is added.
5. Agents cannot fix Meta login or server issues from the Leads screen. That is administrator / technical partner work.

---

## 12. Glossary

| Term | Meaning |
|---|---|
| **Lead** | A person who showed interest |
| **Campaign** | A marketing activity meant to produce leads |
| **Pipeline / stage** | The step the lead is at |
| **Opportunity** | A deal being worked, with value and close date |
| **Agent** | The salesperson who owns follow-up |
| **Organization** | Your company workspace |
| **Lead score** | Completeness / seriousness points (0–99) |
| **Integration** | Connection to Facebook, Google, WhatsApp, a website, etc. |
| **Webhook** | Automatic message from another system sending a lead |
| **Automation** | A rule that runs by itself |
| **Duplicate** | Two records that are probably the same person |
| **CPL** | Cost per lead |
| **ROAS** | Return on ad spend |

---

## 13. Questions to bring to the next meeting

Write answers (or doubts) here before we meet:

1. Who will be Administrator, Managers, and Agents in the first month?
2. What are your real sales stages? Do they match the default pipeline?
3. Which channels must be connected first (Meta, website form, WhatsApp, CSV only)?
4. How should new leads be assigned — round-robin, by city, by team?
5. Do you want live OpenAI summaries on, or keep Copilot on rules only?
6. What report will the owner look at every Monday?

---

*CampaignPilot CRM — client study guide. Share this file as-is, or export it to PDF/Word from any Markdown viewer.*
