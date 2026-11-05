# Group Enquiry Desk — Architecture & Data Model Justification

**Component:** WordPress Mini-Plugin (`group-enquiry-desk`)  
**Candidate:** Saud Pardesi  
**Task:** Task 2 — Secure WordPress Mini-Plugin  

---

## 1. Data Model Decision: Custom Post Type (CPT) vs. Custom SQL Table

The assessment requires choosing and justifying a well-structured WordPress data model to store enquiries with attributes: Created Date, Brand, Enquiry Type, Customer Name, Email, Message, Optional Preferred Date, and Status (`new`, `in progress`, `closed`).

### Architectural Comparison

| Dimension | Custom Post Type (CPT) (`group_enquiry`) | Custom Database Table (`wp_group_enquiries`) |
|---|---|---|
| **Access Control & RBAC** | **Native WordPress Capabilities:** Seamlessly maps to `edit_posts`, `edit_others_posts`, and `delete_posts`. Leverages `map_meta_cap` out of the box. | **Manual Permission Checks:** Requires writing custom authorization checks on every raw SQL query and data retrieval point. |
| **Status Management** | **Native Post Statuses:** Built-in support for `register_post_status()` (`enquiry_new`, `enquiry_in_progress`, `enquiry_closed`), trash retention, and status transitions. | **Manual Column & Lifecycle:** Requires custom column handling, state validation, and custom soft-delete logic. |
| **Security & SQL Injection** | **Zero Raw SQL Exposure:** Data persists via `wp_insert_post()` and `update_post_meta()`, which inherently use parameterized statements and WordPress core sanitization. | **Higher Risk Profile:** Requires manual `wpdb->prepare()` for every query. A single omitted prepare call creates high-severity SQLi vulnerabilities. |
| **REST API Integration** | **Native Core Integration:** Integrates seamlessly with `register_rest_route()` or core WP REST controllers. | **Full Custom Controller:** Requires writing custom pagination, schema validation, and serialization logic from scratch. |
| **Database Migrations** | **Zero Schema Migrations:** No `dbDelta()` or versioned SQL schema upgrades needed during plugin activation/updates. Clean uninstall lifecycle. | **Schema Maintenance Burden:** Requires `dbDelta()` table creation, index management, character collation handling, and schema upgrade routines. |
| **Performance at Scale** | Ideal for moderate to high volume inquiries (up to tens of thousands per year). Caching handled by WordPress object cache (`WP_Query`). | Superior performance only at ultra-high volumes (hundreds of thousands of rows per month) where normalized single-table storage reduces joins. |

### Technical Justification & Verdict

**Choice: Custom Post Type (`group_enquiry`) with Registered Post Statuses & Post Meta.**

**Justification:**
1. **Security & Principle of Least Privilege:** A customer enquiry desk is a sensitive internal workflow tool containing personal contact information. Utilizing WordPress core's native CPT permissions guarantees that anonymous visitors and unauthorized roles (e.g. subscribers) can **never** query or view enquiry records.
2. **Workflow Lifecycle:** Post statuses in WordPress provide an audited, event-driven state machine (`transition_post_status` hook) that allows future enhancements (e.g. email notifications to brand managers when status moves to `in progress`) without refactoring the database schema.
3. **Enterprise Maintainability:** Avoids custom DDL migrations (`CREATE TABLE`), character set mismatches, and multi-site table prefix errors across different hosting environments.

---

## 2. Security Architecture & Threat Modeling

### 2.1 CSRF (Cross-Site Request Forgery) Defense
* Every frontend form submission includes a cryptographically secure WordPress nonce (`wp_create_nonce('group_enquiry_submit_action')`).
* The submission handler strictly verifies this nonce via `wp_verify_nonce()`. Any request failing verification immediately halts with HTTP 403 Forbidden.
* Admin status updates similarly enforce admin-specific action nonces.

### 2.2 Brand Immutability (Client-Side Tampering Prevention)
* The brief specifies: *"Brand should be selected by the site/page configuration, not trusted from user input."*
* The frontend form shortcode/block defines the brand (e.g. `[group_enquiry_form brand="space42"]`).
* The brand identifier is validated against a strict server-side whitelist (`ipstudios`, `drvet`, `space42arena`, `jazzbar`, `menalead`).
* Even if an attacker modifies the hidden HTML input or intercepts the HTTP POST request to inject an arbitrary brand string, the server rejects unauthorized values and forces the whitelisted context.

### 2.3 Input Sanitization & Output Escaping
* **Customer Name:** Sanitized using `sanitize_text_field()` and stripped of all HTML/script tags.
* **Email Address:** Sanitized using `sanitize_email()` and validated via `is_email()`. Invalid formats are rejected before hitting the database.
* **Enquiry Type:** Checked against the brand's allowed enquiry type whitelist.
* **Message Body:** Sanitized via `sanitize_textarea_field()` (strips executable tags while preserving line breaks).
* **Date:** Validated against ISO `Y-m-d` regex.
* **Output Escaping:** Every dynamic value rendered in the Admin Dashboard is strictly escaped using `esc_html()`, `esc_attr()`, or `esc_url()` to neutralize Stored Cross-Site Scripting (XSS).

### 2.4 Anti-Spam & Rate Limiting (Zero Third-Party Dependency)
1. **Honeypot Trap:** A hidden field (`enquiry_website_hp`) is rendered with CSS `display:none;` and `tabindex="-1"`. Automated spam bots automatically fill in every input field, triggering silent rejection.
2. **Transient IP Rate Limiting:** A sliding window rate limiter stores client IP hashes in the WordPress Transients API (`_transient_enquiry_rate_{md5_ip}`). If more than 5 submissions originate from the same IP within a 10-minute window, subsequent submissions are throttled with HTTP 429 Too Many Requests.

### 2.5 REST API Access Control
* The REST endpoint (`GET /wp-json/group-enquiry/v1/enquiries`) is secured via a mandatory `permission_callback`.
* The callback verifies `current_user_can('edit_others_posts')`. Unauthenticated requests or requests from regular site visitors immediately return HTTP 401 Unauthorized / HTTP 403 Forbidden.

