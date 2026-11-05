# Task 3 | Safe AI Integration & Human-in-the-Loop Approval Mechanism

**Candidate:** Saud Pardesi  
**Component:** Admin-Only AI SEO Helper Prototype (`ai-seo-helper`)  
**Target Pages:** Space42 Arena Event Page & DrVet Animal Hospital Service Page  

---

## 1. Five-Line Core Mechanism Summary (Brief Requirement)

1. **Provider Abstraction:** The system decouples AI generation through a strict `AI_Provider_Interface`, running a deterministic `Mock_LLM_Provider` by default with zero API key dependencies.
2. **Server-Side Secret Isolation:** If external LLM APIs (OpenAI/Anthropic/Gemini) are enabled, keys are stored strictly in server environment variables (`AI_API_KEY`), never exposed to frontend code or client browsers.
3. **Strict Structural Validation:** Output is coerced and validated against a rigid JSON schema (`seo_title` ≤ 60 chars, `meta_description` ≤ 160 chars, 2–3 FAQs); non-conforming responses trigger graceful fallbacks.
4. **Mandatory Human-in-the-Loop Approval:** Generated metadata remains in a staging/draft state with an admin review panel; content is **never published** until an authorized human editor reviews and clicks "Approve & Apply".
5. **Zero-Leak Privacy & Hallucination Defense:** All input text is scrubbed of PII (names, emails, phone numbers) before LLM ingestion, and prompts enforce strict negative constraints prohibiting hallucinated medical guarantees (DrVet) or fabricated ticket prices (Space42).

---

## 2. In-Depth Architectural & Safety Decisions

### 2.1 Prevention of Customer Data & PII Leakage
* **The Problem:** Editors frequently paste raw text into CMS fields that may contain client emails, phone numbers, or confidential internal notes.
* **Our Defense:** Before any prompt is dispatched to an LLM provider:
  1. A sanitization pipeline executes regex-based PII redaction (`[REDACTED_EMAIL]`, `[REDACTED_PHONE]`).
  2. The AI feature operates strictly on public marketing context (Page Title and Event/Service description), never on the customer enquiry database.

### 2.2 Hallucination Prevention & Fact Grounding
* **For DrVet Animal Hospital (Healthcare Boundary):**
  - Prompt system instructions strictly enforce: *"Do not invent medical treatments, pharmaceutical dosages, or guarantee surgical outcomes. Only summarize provided veterinary capabilities."*
* **For Space42 Arena (Event & Pricing Boundary):**
  - Prompt system instructions enforce: *"Do not invent ticket prices or backstage access rules not explicitly stated in the source text."*
* **Server-side length clipping:** Titles and descriptions are clamped to SEO character limits so search engine snippets do not get truncated with ellipsis.

### 2.3 The Human Approval Workflow (Step-by-Step)
```
[Admin Editor Enters Title & Description]
                   │
                   ▼
  [Click: "Generate SEO Recommendations"]
                   │
                   ▼
   [Server Scans & Scrubs PII / Secrets]
                   │
                   ▼
 [LLM Provider Generates Structured JSON]
                   │
                   ▼
[Server Validates JSON Schema & Character Limits]
                   │
                   ▼
 [Suggestions Displayed in Staging Review Modal]
                   │
                   ▼
  ┌─────────────────────────────────┐
  │ Human Editor Reads & Edits:     │
  │ • Check SEO Title               │
  │ • Check Meta Description        │
  │ • Review FAQ 1, 2, 3            │
  └─────────────────────────────────┘
                   │
                   ▼
     [Click: "Approve & Insert"]
                   │
                   ▼
 [Only Now Persisted to WordPress Post Meta]
```

