# CampaignPilot CRM — How This Project Works

This guide is for business owners, managers, and team members.  
It explains **what CampaignPilot is**, **how people use it day to day**, and **how work moves from an advert to a closed deal**. You do not need technical knowledge to read it.

---

## 1. What is CampaignPilot?

CampaignPilot CRM is a **lead and campaign management system**.

It helps a business:

- collect enquiries (leads) from ads, forms, WhatsApp, email, and other channels
- assign those enquiries to the right people
- follow them through a sales pipeline
- run and track marketing campaigns
- see results on a dashboard and in reports

It is **not only for real estate**. The same system can be used for education, automotive, insurance, solar, agencies, healthcare, home services, and any other lead-driven business.

Each company that signs up gets **its own private workspace**. One company cannot see another company’s leads, campaigns, or users.

---

## 2. The simple idea

Think of CampaignPilot as a **control room** for sales and marketing.

1. A person sees an ad or fills a form.
2. Their details become a **lead** in CampaignPilot.
3. The lead is given to an **agent**.
4. The agent moves the lead through **stages** (New → Contacted → Qualified → Proposal → Won or Lost).
5. Managers watch the **dashboard** and **reports** to see what is working.

Campaigns are the marketing activity (for example “Ramadan Facebook Ads”). Leads are the people who respond.

---

## 3. How someone starts using it

1. Open the CampaignPilot website.
2. **Register** a company (organization name, industry, currency, timezone) **or** **log in** if an account already exists.
3. After login, the left menu is the main navigation.

If a manager invites a teammate, that person receives an invitation, sets a password, and then logs in. They only see what their **role** allows.

---

## 4. Who can do what (roles)

| Role | In plain language |
|---|---|
| **Administrator** | Full access. Company setup, users, integrations, everything. |
| **Manager** | Runs campaigns, leads, teams, and reports. Limited settings. |
| **Team lead** | Leads a group of agents and follows their work. |
| **Agent** | Works assigned leads: call, follow up, update stage. |
| **Viewer** | Can look, but cannot change important data. |

If a button is missing, it is usually because that person’s role is not allowed to use it.

---

## 5. The menu — what each page is for

### Dashboard
The home screen. It shows today’s picture: how many leads came in, where they came from, how the pipeline looks, and quick actions such as creating a campaign.

Use the date range and source/team filters at the top to look at a specific period.

### Leads (Lead Intelligence)
The list of all people who enquired.

From here the team can:

- add a lead by hand
- open a lead and see full history
- filter by source, campaign, stage, agent, city, score
- view **My Leads**, **Hot Leads**, **High Intent**, or **Duplicates**
- assign, tag, export, or do bulk actions
- merge duplicate records

Opening a lead shows contact details, campaign, stage, notes, tasks, activities, WhatsApp/email style follow-up, and related opportunities.

### Campaigns (Campaign Center)
Where marketing activity is planned and tracked.

A campaign typically has:

- a name and goal (for example “get leads”)
- a channel (Meta, Google, website, WhatsApp, and so on)
- a budget
- assigned agents
- status: Draft, Active, Paused, or Completed

Staff can create a campaign in CampaignPilot, attach agents, import leads from a CSV file, and see how many leads that campaign produced.

### AI Copilot (Beta)
An assistant that can help with questions and suggestions about leads and campaigns. It only works when the company has turned AI on in setup. If it is off, the screen will say so.

### Reports
Charts and summaries: lead volume, sources, campaign performance, agent productivity. Data can be exported.

### Users
Company people and **teams**. Administrators invite users, choose a role, and put people into teams so leads can be shared fairly.

### Automations
Rules that run by themselves, for example:

- when a new lead arrives → assign an owner and create a follow-up task
- when a lead sits too long → notify the team
- when a stage changes → start the next action

This reduces manual chasing.

### Integrations
Connections to outside platforms:

- Meta Ads, Instagram, TikTok, Google, LinkedIn
- Website forms, Webhook/API
- WhatsApp, Email
- listing sites such as Bayut and Dubizzle (optional)

Most connections are saved with an API key. **Meta Ads** uses a **Connect** button so the user can sign in with Facebook/Meta and approve access.

Incoming webhook events (leads sent from another system) appear in **Recent webhook logs**.

### Settings
Company profile: name, industry, currency, country, language, working hours style settings, pipelines (sales stages), custom fields, tags, duplicate rules, lead routing, and permissions.

---

## 6. How a lead usually arrives

A lead can enter CampaignPilot in several ways:

| Way | What happens |
|---|---|
| **Manual** | An agent clicks **Add Lead** and types the details. |
| **CSV import** | A spreadsheet of leads is uploaded against a campaign. |
| **Website form / webhook** | The website or another tool sends the enquiry automatically. |
| **Connected channel** | After an integration is connected, leads can arrive from that channel (when that channel is fully set up). |

When a lead arrives, the system can:

- detect possible **duplicates** (same phone or email)
- **assign** it using the company’s routing rule (for example round-robin among agents)
- create a **task** so someone follows up on time
- give it a **score** and tags (Hot Lead, High Intent, and so on)

---

## 7. How the sales pipeline works

Every lead sits in a stage. The default path looks like this:

**New Lead → Contacted → Qualified → Opportunity → Proposal → Negotiation → Closed Won / Closed Lost**

The company can rename or add stages in Settings.

A healthy day looks like this:

1. New leads appear on the Dashboard and in Leads.
2. Agents call or message them and add a note.
3. If the person is serious, the agent moves them to Qualified or Opportunity.
4. If they buy, the lead is Closed Won. If not, Closed Lost.

Managers use the funnel on the Dashboard to see where people get stuck.

---

## 8. How campaigns fit in

A **campaign** is the marketing bucket. Example: “Summer Google Ads” or “Facebook Lead Form — April”.

Typical flow:

1. Create the campaign in **Campaign Center** (or later, bring it from an ad platform).
2. Choose the channel, budget, dates, and agents.
3. Set it to **Active**.
4. Leads that come from that activity are linked to that campaign.
5. The campaign page shows leads, spend, and how well it is performing.

Agents who are assigned to a campaign are the people who should receive those leads.

---

## 9. Integrations in everyday language

Integrations mean: **“let another product talk to CampaignPilot.”**

### Meta Ads (Facebook / Instagram ads)

The Meta Ads card has a **Connect** button.

1. A manager with permission clicks **Connect**.
2. A Meta/Facebook login window opens.
3. They sign in and allow CampaignPilot to access ads information (as approved in the Meta app).
4. The card shows **Connected** with the Meta account name.
5. They can **Disconnect** or **Reconnect** later.

**Important — current reality**

- Connecting Meta **saves the login** so CampaignPilot is authorized.
- Creating a campaign inside Meta Ads Manager does **not yet automatically create** the same campaign inside CampaignPilot. That automatic copy is a next step.
- Until that is built, the team should still **create or match the campaign in CampaignPilot**, then work the leads here.

Other platforms (Google, TikTok, WhatsApp, and so on) currently use an API key / token and a Save button, unless a similar Connect flow is added later.

### Website and webhook

Other websites can send leads to CampaignPilot using the webhook address shown on the Integrations page. Successful and failed deliveries appear in the log table.

---

## 10. A typical working day

**Morning (manager)**  
Open Dashboard. Check new leads, overdue follow-ups, and which campaign brought the most enquiries.

**Morning (agent)**  
Open Leads → My Leads. Call the newest and hottest ones first. Add a note after each call. Move the stage. Complete tasks.

**During the day**  
New form or ad enquiries appear in the list. Automations may assign them and create tasks. Duplicates can be reviewed and merged.

**End of day / week (manager)**  
Open Reports. See conversion, source quality, and agent activity. Pause weak campaigns. Invite or reassign users if needed.

---

## 11. Settings a non-technical admin should know

These are the settings that change how the company works, without writing code:

- **Organization** — company name, industry, currency, timezone
- **Users & Teams** — who is in the company and who they report with
- **Lead routing** — how new leads are given out (for example one-by-one around the team)
- **Pipeline** — names of sales stages
- **Tags** — labels such as Hot Lead or Urgent (with a colour)
- **Custom fields** — extra questions unique to the business (for example “Plot size” or “Course interested in”)
- **Duplicates** — whether the system should flag or suggest merges
- **Permissions** — what each role may see or change
- **Integrations** — connect Meta and other channels

---

## 12. What success looks like

The project is working well when:

- every enquiry lands in **one place**
- no lead sits unassigned
- agents know **who to call next**
- managers can answer “which ad is paying for itself?”
- the same process works even if the business is not real estate

---

## 13. What this guide does not cover

This document does not explain servers, databases, or programming.

IT / technical staff handle:

- installing and hosting the website
- Meta Developer app, login configuration, and redirect URLs
- API keys, email server, and backups

If Connect with Meta fails, it is usually a Meta app permission or website address issue — not something an agent can fix from the Leads screen. Ask the administrator or technical partner.

---

## 14. Short glossary

| Word | Meaning |
|---|---|
| **Lead** | A person who showed interest. |
| **Campaign** | A marketing activity that is meant to produce leads. |
| **Pipeline / stage** | The step the lead is at in the sales journey. |
| **Agent** | The salesperson who owns the follow-up. |
| **Organization** | The company account inside CampaignPilot. |
| **Integration** | A connection to Facebook, Google, WhatsApp, a website, and similar. |
| **Webhook** | An automatic message from another system sending a new lead. |
| **Automation** | A rule that runs by itself when something happens. |
| **Duplicate** | Two records that are probably the same person. |

---

*CampaignPilot CRM — user guide for non-technical readers.*
