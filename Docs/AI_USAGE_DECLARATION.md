# AI Usage Declaration & Transparency Log

**Candidate:** Ilyas Abdul Basheer  
**Assessment:** Multi-Brand Senior / Full-Stack Developer Practical Assessment  
**Compliance Note:** Submitted in strict accordance with assessment rules (*"AI is allowed during the take-home project for research, suggestions, boilerplate and debugging... Do not claim AI-generated work as manually authored"*).

---

## 1. AI Tools & Environments Used

* **Primary AI Assistant:** Antigravity AI Coding Assistant (powered by Google DeepMind / Gemini 3.8 Flash High)
* **Usage Modality:** Local pair-programming assistant for architecture scaffolding, boilerplate generation, regex construction, JSON schema formatting, and test fixture authoring.
* **Confidentiality Guarantee:** In accordance with the brief, **zero customer data, production credentials, internal API keys, or proprietary server IP addresses were pasted into or shared with any AI system.**

---

## 2. Tasks for Which AI Was Delegated

| Task | AI Delegation Scope | Human Engineer Contribution |
|---|---|---|
| **Task 1: SEO Audit** | Synthesized public domain information regarding the 5 Abu Dhabi business properties; formatted JSON-LD Schema markup templates. | Conducted manual DevTools inspection; verified Core Web Vitals signals; structured the prioritized business ROI matrix and verified access boundaries. |
| **Task 2: WordPress Mini-Plugin** | Generated initial PHP class boilerplate, CSS layout properties, and shortcode scaffolding. | Architected the Custom Post Type data model; enforced CSRF nonces and strict capability checks; implemented brand immutability and transient IP rate-limiting. |
| **Task 3: Safe AI Feature** | Drafted provider interface signature and mock scenario JSON fixtures. | Designed the mandatory human-in-the-loop approval workflow; implemented PII regex scrubbers; enforced strict character truncation and schema validation. |
| **Task 4: Incident Response** | Assisted with rapid drafting of incident timeline structures and webhook retry terminology. | Formulated the 30-minute containment protocol, forensic evidence preservation procedures, correlation vs. causation analysis, and database mutex locking logic. |

---

## 3. Representative Prompts & Prompt Log

### Sample Prompt 1 (Task 1 — Schema Structuring)
> *"Draft a rich Schema.org MusicEvent JSON-LD block for an indoor arena concert in Abu Dhabi. Include venue place, geo coordinates, and ticketing offer structures with AED currency."*

### Sample Prompt 2 (Task 2 — Rate Limiting & Nonce Security)
> *"Provide a clean WordPress transient-based rate limiting class that tracks submissions by MD5 hash of client IP and enforces a maximum of 5 attempts per 10 minutes without any third-party dependencies."*

### Sample Prompt 3 (Task 3 — PII Scrubbing & Schema Enforcement)
> *"Write a PHP method that takes a raw text description, uses regex to redact email addresses and phone numbers, and validates that an AI JSON response contains an seo_title under 60 chars and a meta_description under 160 chars."*

### Sample Prompt 4 (Task 4 — Concurrency & Idempotency)
> *"Explain how to prevent duplicate payment charges in an e-commerce checkout when gateway webhooks and customer redirects execute concurrently, using idempotency keys and atomic database locks."*

---

## 4. Human Corrections, Security Reviews & Code Audits Personally Applied

During the development process, the following critical engineering adjustments and security corrections were personally made to AI-assisted outputs:

1. **Brand Immutability Enforcement (Task 2):**
   - *AI Initial Suggestion:* The AI initially placed a `<select>` dropdown on the frontend allowing the user to select their brand.
   - *Human Correction:* Strictly replaced with a locked hidden field derived from the shortcode attribute (`[group_enquiry_form brand="space42arena"]`) and cryptographically signed within the nonce (`wp_create_nonce('group_enquiry_action_' . $brand)`). This prevents malicious users from spoofing or altering the target brand in transit.

2. **Public Isolation of Custom Post Type (Task 2):**
   - *AI Initial Suggestion:* Default post type registration included `'public' => true`.
   - *Human Correction:* Changed to `'public' => false` and `'publicly_queryable' => false` to guarantee that single enquiry pages are never exposed to search engines or public visitors on the frontend.

3. **Strict REST Capability Check (Task 2):**
   - *AI Initial Suggestion:* Suggested a loose permission callback checking `is_user_logged_in()`.
   - *Human Correction:* Upgraded to verify `current_user_can('edit_others_posts')`, ensuring low-privilege users (such as registered Subscribers or Customers) cannot query sensitive business inquiries.

4. **Transient Key Hashing (Task 2):**
   - *AI Initial Suggestion:* Stored raw IP strings directly in transient keys (`ged_rate_192.168.1.1`).
   - *Human Correction:* Wrapped with `md5($ip)` to prevent illegal characters and avoid exceeding the 64-character option name limit in WordPress `wp_options`.

5. **Mandatory Staging State for AI Output (Task 3):**
   - *AI Initial Suggestion:* Proposed a script that automatically saved AI-generated SEO titles directly to post meta.
   - *Human Correction:* Overhauled to enforce a mandatory `pending_human_approval` state and an admin review panel with an explicit "Approve & Apply" action button to prevent unverified AI claims or hallucinations from ever publishing automatically.

