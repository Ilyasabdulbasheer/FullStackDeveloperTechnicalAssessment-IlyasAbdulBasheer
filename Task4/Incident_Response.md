# Production Incident Response & Deployment Judgment

**Candidate:** Ilyas Abdul Basheer (Senior Full-Stack Developer)  
**Task:** Task 4 — Technical Incident Response & Payment System Reliability  
**Scope:** Scenario A (Space42 Arena Overnight Breach) & Scenario B (E-Commerce Webhook & Idempotency Diagnosis)  
**Document Length:** 1 Page  

---

## Scenario A | Space42 Arena: Overnight Breach Triage & Forensic Analysis

### 1. First 30 Minutes: Containment, Evidence Preservation & Triage
* **Remain Calm & Objective:** Do not accept blame or speculate; treat the colleague's claim as an unverified hypothesis.
* **Preserve Volatile Forensic Evidence:** Before restarting services or overwriting code, capture memory, active socket connections (`netstat -tulpn` / `ss`), process trees (`ps aux --forest`), and export recent shell history.
* **Isolate & Contain (Stop the Bleed):** If malicious activity is active (webshell, SEO spam injection, or unauthorized redirection):
  - Place the site behind Cloudflare / CDN "Under Attack" mode or restrict `/wp-admin/` and `/wp-login.php` to authorized VPN/office IPs.
  - Do **not** wipe the server immediately—isolate the network boundary while preserving forensic disk state.
* **Freeze Logs:** Snapshot web server access/error logs (`nginx`/`apache`), PHP-FPM error logs, MySQL binary logs, and CDN edge request logs to read-only cold storage to prevent rotation or attacker tampering.
* **Communication:** Establish an internal Incident Channel. Post an initial stakeholder update: *"Anomaly detected; containment measures engaged; forensic investigation underway. Next operational update in 30 minutes."*

### 2. First 24 Hours: Correlation vs. Causation, Forensics & Safe Release
* **Distinguish Correlation from Causation:**
  - Audit the recent design deploy Git diff (`git diff HEAD~1..HEAD`). Did the changes touch only CSS/static templates, or did they modify PHP logic, introduce unescaped dynamic parameters, or add third-party dependencies?
  - Cross-reference file timestamps and earliest Indicators of Compromise (IoCs) against deployment logs. If malicious files (e.g. webshells in `wp-content/uploads/`) or unauthorized admin users were created *prior* to your deploy, the deployment timing is pure coincidence.
* **Log & Integrity Forensics:**
  - Verify core integrity via WP-CLI: `wp core verify-checksums` and `wp plugin verify-checksums --all`.
  - Inspect access logs for POST requests to anomalous PHP endpoints, brute-force attempts on `xmlrpc.php`, deserialization exploits, or directory traversal (`../`).
  - Audit administrative user tables (`wp_users`, `wp_usermeta`) for rogue super-admin accounts created overnight.
* **Credential Invalidation & Salt Shuffle:**
  - Invalidate all active sessions by rotating `AUTH_KEY` and salts in `wp-config.php`. Force password resets across all staff accounts. Rotate database, SFTP, and hosting API credentials.
* **Backup Verification & Clean Restoration:**
  - Identify the last verified clean automated backup taken prior to the earliest IoC timestamp. Test backup restore in an isolated staging sandbox.
* **Safe Release & Blameless Post-Mortem:**
  - Deploy verified clean build to a newly provisioned instance, apply WAF virtual patches, and run automated health checks.
  - Publish a blameless post-mortem detailing Timeline, Root Cause, Impact, Remediation Steps, and Preventative Infrastructure Guardrails.

---

## Scenario B | Future E-Commerce: Payment Gateway Failures & Duplicate Order Diagnosis

### 1. Root Cause Hypotheses
1. **Frontend Double-Submit:** Lack of UI submit disabling allows customers to click "Pay Now" twice on sluggish mobile connections, firing concurrent gateway checkouts.
2. **Webhook Race Condition & Retries:** The customer's browser redirect (`return_url`) and the gateway's asynchronous webhook hit the server concurrently, both trying to transition the order from `pending` to `processing` without database mutex locks.
3. **Missing Idempotency Handling:** If WooCommerce returns an HTTP 500 or times out while processing post-payment logic (e.g. inventory decrement or third-party CRM sync), the payment gateway assumes delivery failure and retries the webhook every 15 minutes, re-triggering fulfillment.

### 2. Diagnostic Investigation Steps
* **Log Correlation:** Match Payment Gateway Transaction IDs (`ch_xxx` / `pi_xxx`) against WooCommerce order transaction metadata and server access logs (`POST /wc-api/*` or `/wp-json/wc/v3/*`).
* **Webhook Health Inspection:** Review gateway developer dashboard (e.g. Stripe/Checkout.com events) for webhook delivery statuses, HTTP response codes, and round-trip latencies.
* **Database Deadlock Analysis:** Inspect MySQL slow query logs and `SHOW ENGINE INNODB STATUS` for lock waits on `wp_posts` / `wp_postmeta` during order state transitions.

### 3. Engineering Fixes & Prevention Architecture
* **Client-Side UI Locking:** Disable checkout submit button immediately on first click, replace text with an accessible loading indicator, and prevent multiple form dispatches:
  ```javascript
  form.addEventListener('submit', function() { submitBtn.disabled = true; spinner.show(); });
  ```
* **Gateway Idempotency Keys:** Send a deterministic `Idempotency-Key` (e.g., `hash(cart_id + customer_id + timestamp_hour)`) in the payment intent creation request. Even if duplicate HTTP requests hit the gateway, only one charge is executed.
* **Atomic State Machine & Distributed Locking:**
  - Implement a transient or Redis mutex lock on the order ID upon webhook receipt:
    ```php
    $lock_key = 'order_lock_' . $order_id;
    if ( ! wp_cache_add( $lock_key, 1, 'payment_locks', 30 ) ) {
        // Another worker is processing this order; return 200 OK to stop redundant gateway retries
        wp_send_json_success( array( 'status' => 'already_processing' ) );
    }
    ```
  - Enforce strict state transitions: only transition `pending` ➔ `processing`. If the order is already `processing` or `completed`, log the event, skip duplicate fulfillment, and return HTTP 200 OK immediately.
* **Decoupled Asynchronous Processing:** Acknowledge webhooks immediately with HTTP 200 OK upon signature verification, and offload stock reduction, customer receipts, and email notifications to background queues (Action Scheduler / Redis queues).

### 4. Staging Verification & PCI-DSS Compliance
* **Staging Replay Testing:** Use CLI tooling (e.g. Stripe CLI `stripe listen --forward-to`) to replay identical captured webhook payloads against local/staging environments, verifying that repeat calls execute idempotently without duplicate charges.
* **Secure Payment Data Handling (PCI-DSS):**
  - **Zero Cardholder Storage:** Never log, store, or serialize raw credit card numbers (PANs) or CVVs in server logs, databases, or post meta.
  - Utilize tokenized checkout (Stripe Elements / hosted tokenization). Store only non-sensitive tokens, card brand, and masked last 4 digits.
  - Ensure all webhook endpoints strictly verify HMAC cryptographic signatures before parsing payload data.

