# Multi-Brand Developer Practical Assessment

**Candidate:** Ilyas Abdul Basheer  
**Role:** Senior / Full-Stack Developer  
**Repository:** [FullStackDeveloperTechnicalAssessment-IlyasAbdulBasheer](https://github.com/Ilyasabdulbasheer/FullStackDeveloperTechnicalAssessment-IlyasAbdulBasheer)  
**Time-Box Budget:** 6 Hours (Actual Time Spent: ~4 Hours 50 Minutes)  
**Submission Package:** Repository, Technical SEO Audit, WordPress Mini-Plugin, Safe AI Feature, Incident Response Note, Time Log, and AI Usage Declaration.

---

## 1. Executive Summary & Assessment Scope

This repository contains the complete deliverables for the Senior Developer Practical Assessment across five business brands in the UAE / MENA region:
1. **IP Studios** (`ipstudios.com`) — Creative media and live production
2. **DrVet Animal Hospital** (`drvet.ae`) — Veterinary medical clinic & 24/7 emergency care
3. **Space42 Arena** (`space42arena.com`) — Multi-purpose entertainment and event arena
4. **Jazzbar** (`jazzbar.ae`) — Live music lounge & private hospitality
5. **MENA Lead** (`menalead.com`) — Project management, venue operations, and corporate consultancy

---

## 2. Repository Layout & Deliverables

```
├── Task1/
│   └── SEO_Audit.md                 # Cross-Brand Technical SEO Audit & Prioritized Top-5 Matrix
│
├── Task2/
│   ├── ARCHITECTURE_AND_JUSTIFICATION.md # Data model justification (CPT vs Custom Table) & Threat Model
│   ├── group-enquiry-desk/          # Complete standalone WordPress mini-plugin
│   │   ├── group-enquiry-desk.php   # Main plugin header & bootstrap lifecycle
│   │   ├── includes/
│   │   │   ├── class-enquiry-post-type.php      # CPT 'group_enquiry', statuses, 5-brand type mappings
│   │   │   ├── class-rate-limiter.php           # Transient-based IP rate limiter (zero external deps)
│   │   │   ├── class-enquiry-form.php           # Shortcode [group_enquiry_form brand="..."], honeypot trap
│   │   │   ├── class-enquiry-handler.php        # CSRF nonces, sanitization, validation, safe insertion
│   │   │   ├── class-enquiry-admin.php          # Admin inbox, brand filtering, status updates
│   │   │   └── class-enquiry-rest-controller.php# Authenticated REST API (GET /wp-json/group-enquiry/v1/enquiries)
│   │   └── assets/
│   │       ├── css/ (frontend.css, admin.css)
│   │       └── js/  (frontend.js - AJAX with no-JS fallback)
│   └── tests/
│       ├── README_TESTS.md          # 4 repeatable security & functionality test specifications
│       └── test-suite.php           # Automated CLI test runner (runnable via: php test-suite.php)
│
├── Task3/
│   ├── AI_SAFETY_AND_APPROVAL.md    # 5-line core mechanism & deep-dive into privacy/hallucination defense
│   ├── sample-input-output.json     # Concrete fixtures for Space42 Arena and DrVet Animal Hospital
│   ├── demo-runner.php              # Standalone demonstration CLI script (runnable via: php demo-runner.php)
│   └── ai-seo-helper/               # Modular WordPress admin feature
│       ├── class-ai-provider-interface.php # Extensible provider abstraction
│       ├── class-mock-llm-provider.php     # Default mock provider (deterministic, zero-cost, zero-secret)
│       ├── class-real-llm-adapter.php      # Production-ready OpenAI/Gemini connector structure
│       ├── class-ai-seo-service.php        # PII scrubbing, schema validation, staging state
│       └── class-ai-seo-admin-ui.php       # Admin review panel with mandatory human approval button
│
├── Task4/
│   └── Incident_Response.md         # Space42 breach triage (first 30m vs 24h) & E-commerce idempotency
│
└── Docs/
    ├── AI_USAGE_DECLARATION.md      # Mandatory AI disclosure: tools, prompt log & human security checks
    └── TIME_LOG.md                  # Audit trail showing adherence to the strict 6-hour time-box
```

---

## 3. Architecture Choices & Key Decisions

### 3.1 Data Model: Custom Post Type (`group_enquiry`) vs. Custom SQL Table
* **Decision:** Implemented as a **Custom Post Type (`group_enquiry`)** with custom workflow post statuses (`enquiry_new`, `enquiry_in_progress`, `enquiry_closed`) and structured postmeta.
* **Justification:**
  1. **Strict Role-Based Access Control (RBAC):** Customer contact details are private. By leveraging WordPress Core CPT capabilities (`edit_others_posts`) and registering the post type with `'public' => false` and `'publicly_queryable' => false`, public visitors and subscribers are fundamentally blocked from querying submissions.
  2. **Auditability & Lifecycle:** Built-in post status transitions allow future event-driven hooks (`transition_post_status`) without database schema modifications.
  3. **Zero Migration Risk:** Avoids `dbDelta()` table migrations, prefix mismatches, and raw SQL injection attack surfaces.
  *(See [`Task2/ARCHITECTURE_AND_JUSTIFICATION.md`](file:///c:/Projects/Take%20Home/FullStackDeveloperTechnicalAssessment-IlyasAbdulBasheer/Task2/ARCHITECTURE_AND_JUSTIFICATION.md) for full analysis).*

### 3.2 Security Hardening & Zero-Trust Inputs
* **Brand Immutability:** The target brand is strictly locked by the shortcode attribute (e.g. `[group_enquiry_form brand="space42arena"]`) and cryptographically bound to the submission nonce. Form inputs cannot override or manipulate the brand context.
* **CSRF Nonce Defense:** Dynamic cryptographic nonces (`wp_verify_nonce`) protect all submissions and admin status transitions.
* **Spam & DDoS Protection:**
  - **Honeypot Trap:** Invisible offscreen input (`ged_website_hp`) silently catches automated bots without disrupting legitimate humans.
  - **Transient Rate Limiter:** Limits IP submissions to max 5 per 10-minute sliding window using the WordPress Transients API (zero external plugin dependencies).
* **Sanitization & Escaping:** Strict input cleaning (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`) and strict contextual output escaping (`esc_html`, `esc_attr`, `esc_url`).

### 3.3 Safe AI Integration with Human-in-the-Loop
* **Zero Cost / Zero Secret Default:** Implements `Mock_LLM_Provider` by default. No paid API subscriptions or keys are required to run and test.
* **Provider Abstraction:** Structured via `AI_SEO_Provider_Interface` with an extensible `Real_LLM_Adapter` showing how an external API (OpenAI/Gemini) connects using server-side secrets (`AI_API_KEY`) without client exposure.
* **Privacy First:** Input descriptions are scrubbed of PII (emails and phone numbers) via regex before dispatch.
* **Mandatory Human Approval Gate:** Outputs are marked `pending_human_approval` and rendered in an admin staging preview. Content is **never automatically published** until an authorized human editor clicks "Approve & Apply".

---

## 4. Environment Requirements & Installation Steps

### Prerequisites
* **PHP:** Version 7.4 or 8.0+
* **WordPress:** Version 5.8+ (Tested up to WordPress 6.x)
* **Web Server:** Nginx or Apache with `mod_rewrite` enabled
* **Database:** MySQL 5.7+ or MariaDB 10.3+

### Installation Steps
1. Clone or download this repository.
2. Copy the plugin directory into your WordPress plugins folder:
   ```bash
   cp -r Task2/group-enquiry-desk /path-to-your-wordpress/wp-content/plugins/
   ```
3. Log into your WordPress Admin Dashboard (`/wp-admin`).
4. Navigate to **Plugins ➔ Installed Plugins** and click **Activate** under **Group Enquiry Desk**.
5. Create a new test page and insert the shortcode:
   ```text
   [group_enquiry_form brand="space42arena"]
   ```
   *(Supported brand attributes: `ipstudios`, `drvet`, `space42arena`, `jazzbar`, `menalead`)*.
6. Access the submissions management inbox under **WP Admin ➔ Group Enquiries**.

---

## 5. Basic Test Instructions

### Method A: Automated CLI Test Suite (Standalone PHP)
You can execute the 4 mandatory test scenarios directly from your terminal:
```bash
php Task2/tests/test-suite.php
```
*Tests verified:*
1. Normal Valid Submission (CPT persistence and postmeta storage)
2. Invalid Input Validation (rejection of malformed email and short names)
3. Unauthorized REST Access (HTTP 401/403 barrier for public/subscribers)
4. Malicious Input Handling (XSS `<script>` and SQL injection neutralization)

### Method B: Testing the AI SEO Helper CLI Demo
```bash
php Task3/demo-runner.php
```
*Demonstrates PII scrubbing, structured JSON generation for Space42 Arena and DrVet, and the human approval staging gate.*

### Method C: REST API Verification
Query the authenticated endpoint:
```bash
# Public request (Expected: HTTP 401 Unauthorized)
curl -I http://your-local-site.test/wp-json/group-enquiry/v1/enquiries

# Authenticated request (Using Admin Cookie or Application Password)
curl -X GET http://your-local-site.test/wp-json/group-enquiry/v1/enquiries \
  --user "admin:your_application_password"
```

---

## 6. Completed vs. Incomplete Items & Known Limitations

### Completed Items
* [x] **Task 1:** Comprehensive 5-brand SEO audit, Space42 Arena & DrVet deep dives, Top-5 prioritized matrix, 2 before/after code implementations (`SEO_Audit.md`).
* [x] **Task 2:** Production-ready `group-enquiry-desk` plugin with responsive form, brand-locking, CPT data model, admin inbox, filter dropdowns, quick status updates, REST API, honeypot, transient rate limiter, and 4 automated tests.
* [x] **Task 3:** Safe AI SEO helper with provider interface, default mock LLM, real LLM connector structure, schema validation, PII redaction, human-in-the-loop admin staging UI, and sample input/output fixture.
* [x] **Task 4:** 1-page technical incident response writeup covering Space42 Arena overnight breach forensics (first 30m vs 24h) and e-commerce payment webhook / idempotency diagnosis.
* [x] **Documentation:** README, Architecture Justification, AI Usage Declaration (`Docs/`), and Time Log (`Docs/`).

### Known Limitations (Assessment Prototype Scope)
* **Email Dispatch:** Outgoing notification emails (`wp_mail`) were intentionally omitted per assessment instructions (*"no outgoing emails are required"*). In a full production deployment, email alerts would be routed through a transactional SMTP provider (SendGrid/Postmark).
* **Gutenberg Custom Block UI:** The form is implemented via shortcode with block editor compatibility. A React-based `@wordpress/blocks` wrapper with sidebar inspector controls would be the next iteration.

---

## 7. Staging, Backup, and Rollback Approach

1. **Staging Environment Isolation:**
   - All schema, plugin, and core updates are deployed first to an isolated staging environment running an exact mirror of production PHP and MySQL versions.
   - Database migrations and webhook handlers are validated using synthetic transaction replays.
2. **Automated Backup Strategy:**
   - Automated nightly full backups (database + `wp-content/uploads/`) stored in encrypted, off-site S3 storage with 30-day retention.
   - Immediate pre-deployment database snapshots triggered before any code release.
3. **Safe Rollback Protocol:**
   - Atomic deployments utilizing symlinked releases (e.g. Deployer / GitHub Actions):
     ```text
     /var/www/site/current -> /var/www/site/releases/20261105_140000
     ```
   - In the event of fatal PHP errors or anomalous health checks, the symlink is instantaneously reverted to the previous release timestamp within seconds, without altering database data.

---

## 8. What I Would Improve With One More Day

If allocated an additional 24 hours of development time:
1. **React / Gutenberg Native Block:** Build a dedicated Gutenberg Block using `@wordpress/scripts` allowing content editors to select the brand via a visual dropdown in the block inspector, with live visual preview in the editor.
2. **Transactional Webhook Dispatch:** Add an outgoing webhook system (`wp_remote_post`) in the plugin that notifies Slack/Teams channels whenever a high-priority enquiry arrives for IP Studios or Space42 Arena.
3. **Automated E2E Playwright / Cypress Suite:** Implement end-to-end browser tests to simulate multi-device form submissions, keyboard accessibility navigation (WCAG 2.1 AA), and screen reader announcements.
4. **Export to CSV / CRM Connector:** Add an admin export button allowing authorized managers to export filtered enquiries to CSV or sync directly with HubSpot / Zoho CRM.
