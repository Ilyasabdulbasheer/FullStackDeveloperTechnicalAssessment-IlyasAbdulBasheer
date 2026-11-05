# Group Enquiry Desk — Security & Functionality Test Suite

**Task:** Task 2 — Repeatable Security & Functionality Tests  
**Target:** Group Enquiry Desk (`group-enquiry-desk`)  
**Evaluation:** Assessment requirement for 4 repeatable tests covering normal submission, invalid input, unauthorized access, and malicious input.

---

## Overview of the 4 Repeatable Test Scenarios

| Test Case | Objective | Input Payload | Expected Result | Security Defense Verified |
|---|---|---|---|---|
| **Test 1: Normal Valid Submission** | Confirm proper creation of enquiry and CPT persistence for a configured brand. | Valid name, valid email, configured brand (`space42arena`), matching enquiry type (`venue_hire`), message, and valid CSRF nonce. | **HTTP 201 Created / Success JSON.** New post created with status `enquiry_new`. | End-to-end form lifecycle & custom post type metadata storage. |
| **Test 2: Invalid Input Validation** | Verify strict rejection of malformed data without throwing uncaught exceptions. | Malformed email (`invalid-email-format`), missing name, invalid enquiry type for the target brand. | **HTTP 400 Bad Request.** Specific validation error message returned (`invalid_email`). No DB write occurs. | Server-side validation integrity (`is_email`, whitelist checks). |
| **Test 3: Unauthorized REST Access** | Confirm that anonymous visitors cannot read customer submissions. | `GET /wp-json/group-enquiry/v1/enquiries` without authentication cookies or application passwords. | **HTTP 401 Unauthorized / HTTP 403 Forbidden.** Access denied. Submissions remain private. | Principle of Least Privilege & REST `permission_callback`. |
| **Test 4: Potentially Malicious Input (XSS / SQLi)** | Ensure injected malicious payloads are sanitized and cannot execute or corrupt SQL. | Name: `<script>alert('xss')</script>Jane`<br>Message: `UNION SELECT 1, user_pass FROM wp_users--` | **HTTP 201 Created.** Payload is sanitized via `sanitize_text_field()` to `Jane`; message stored harmlessly without executing or triggering SQL injection. | Nonce defense, `sanitize_text_field`, prepared statements, and contextual escaping. |

---

## How to Execute the Tests

### Method A: Automated CLI Test Runner (Standalone PHP)
You can run the included test suite script from your terminal:

```bash
php Task2/tests/test-suite.php
```

### Method B: Manual cURL Verification

#### 1. Valid Normal Submission Test
```bash
curl -X POST https://your-local-wp.test/wp-admin/admin-ajax.php \
  -F "action=group_enquiry_submit" \
  -F "ged_brand=space42arena" \
  -F "ged_nonce=VALID_NONCE_HERE" \
  -F "ged_full_name=Sarah Jenkins" \
  -F "ged_email=sarah.jenkins@example.com" \
  -F "ged_enquiry_type=venue_hire" \
  -F "ged_preferred_date=2026-12-15" \
  -F "ged_message=Inquiry for corporate summit hall rental."
```
*Expected Response:*
```json
{"success":true,"data":{"message":"Thank you! Your enquiry has been received securely.","enquiry_id":101}}
```

#### 2. Invalid Input Test (Malformed Email)
```bash
curl -X POST https://your-local-wp.test/wp-admin/admin-ajax.php \
  -F "action=group_enquiry_submit" \
  -F "ged_brand=drvet" \
  -F "ged_nonce=VALID_NONCE_HERE" \
  -F "ged_full_name=Bob" \
  -F "ged_email=not-an-email" \
  -F "ged_enquiry_type=general_service" \
  -F "ged_message=Need an appointment."
```
*Expected Response:*
```json
{"success":false,"data":{"message":"Please provide a valid email address.","code":"invalid_email"}}
```

#### 3. Unauthorized REST Access Test (Public Visitor Blocked)
```bash
curl -I https://your-local-wp.test/wp-json/group-enquiry/v1/enquiries
```
*Expected Response:*
```http
HTTP/1.1 401 Unauthorized
Content-Type: application/json; charset=UTF-8

{
  "code": "rest_not_logged_in",
  "message": "Authentication required to access customer enquiries.",
  "data": { "status": 401 }
}
```

#### 4. Malicious Input & XSS Injection Test
```bash
curl -X POST https://your-local-wp.test/wp-admin/admin-ajax.php \
  -F "action=group_enquiry_submit" \
  -F "ged_brand=ipstudios" \
  -F "ged_nonce=VALID_NONCE_HERE" \
  -F "ged_full_name=<script>alert('pwned')</script>Alex Smith" \
  -F "ged_email=alex@example.com" \
  -F "ged_enquiry_type=content_production" \
  -F "ged_message=<img src=x onerror=alert(1)>Let's talk about production."
```
*Expected Result:*
- The raw script tags are completely stripped upon entry via `sanitize_text_field()` and `sanitize_textarea_field()`.
- Customer name is safely stored as `Alex Smith`.
- In the admin table, output is rendered via `esc_html()`, guaranteeing zero XSS vulnerability.

