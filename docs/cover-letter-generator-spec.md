# Cover Letter Generator — Technical Specification

## 1. Overview

A low-friction, AI-powered cover letter generator that synthesizes a candidate's existing profile with a target job description to produce a tailored, professional cover letter. The system is designed to minimize user input while maximizing output relevance.

## 2. System Architecture

```
Frontend (Vue SPA)
  └─ Job Detail View / Candidate Profile View
       └─ "Generate Cover Letter" action
            └─ POST /api/ai/cover-letter
                 └─ AiController::generateCoverLetter()
                      ├─ Fetch candidate profile (authenticated user)
                      ├─ Fetch job description (target job)
                      ├─ Build LLM prompt with data mapping
                      └─ Call Groq API (llama-3.3-70b-versatile)
                           └─ Return structured JSON
                                └─ Render letter in modal / textarea
```

## 3. Data Mapping Requirements

### 3.1 Source A: Candidate Profile (existing)

| Field | Source Location | Usage |
|-------|-----------------|-------|
| `name` | `users.name` | Salutation, sign-off |
| `email` | `users.email` | Contact info block |
| `phone` | `candidates.phone` | Contact info block |
| `linkedin_url` | `candidates.linkedin_url` | Contact info block |
| `bio` | `candidates.bio` | Core narrative, values, career focus |
| `skills` | `candidates.skills` (JSON array) | Match against job requirements |
| `resume_path` | `candidates.resume_path` | Flag if present; do NOT embed full text |

### 3.2 Source B: Job Description (target)

| Field | Source Location | Usage |
|-------|-----------------|-------|
| `title` | `job_listings.title` | Role reference |
| `company_name` | `users.company_name` via `employer` relation | Company personalization |
| `description` | `job_listings.description` | Company context, mission |
| `responsibilities` | `job_listings.responsibilities` | Candidate pitch alignment |
| `benefits` | `job_listings.benefits` | Optional: show enthusiasm for perks |
| `skills_required` | `job_listings.skills_required` | Skill matching, gap highlighting |
| `work_type` | `job_listings.work_type` | Work style mention |
| `location` | `job_listings.location` | Location mention |

### 3.3 Derived / Computed Fields

| Derived Field | Logic | Purpose |
|---------------|-------|---------|
| `matched_skills` | Intersection of candidate skills and job skills | Highlight relevant strengths |
| `missing_skills` | Job skills not in candidate profile | Address with transferable skills or learning mindset |
| `seniority_alignment` | Compare candidate implied level vs `experience_level` | Tone calibration |
| `company_context` | First 2 sentences of job description | Show company research |

## 4. API Design

### 4.1 Endpoint

```
POST /api/ai/cover-letter
Auth: Sanctum (candidate)
```

### 4.2 Request Body

```json
{
  "job_id": 42
}
```

### 4.3 Response Body

```json
{
  "success": true,
  "message": "Cover letter generated.",
  "data": {
    "cover_letter": "Dear Hiring Manager,\n\nI am writing to express my strong interest in the Senior Laravel Developer position at TechCorp...",
    "tone": "professional",
    "sections": {
      "opening": "I am writing to express my strong interest...",
      "body": "With 3+ years of experience in Laravel...",
      "closing": "I would welcome the opportunity..."
    },
    "word_count": 245,
    "readability_score": "good"
  }
}
```

## 5. Prompt Engineering Strategy

### 5.1 Core Prompt Template

```
You are an expert career coach and professional copywriter. Generate a tailored cover letter for a candidate applying to a specific job. The letter must be professional, concise (200-300 words), and directly reference the candidate's real experience and the job's actual requirements.

## CANDIDATE PROFILE
- Name: {name}
- Current Role/Background: {bio}
- Skills: {matched_skills}
- Missing Skills (candidate does not list these, but should acknowledge): {missing_skills}
- Contact: {email} | {phone} | {linkedin_url}

## TARGET JOB
- Title: {job_title}
- Company: {company_name}
- Job Description: {company_context}
- Key Responsibilities: {responsabilities}
- Required Skills: {job_skills}
- Work Type: {work_type}
- Location: {location}

## GENERATION RULES
1. Opening paragraph: State the role, express genuine enthusiasm for the company's mission (reference something specific from the job description), and include 1-2 relevant credentials.
2. Body paragraph 1: Connect 2-3 of the candidate's actual skills/experiences to specific job requirements. Use concrete language.
3. Body paragraph 2 (if needed): Address a gap or missing skill with a learning mindset or transferable skill. Do NOT lie or exaggerate.
4. Closing: Reiterate interest, mention availability, and provide a clear call to action.
5. Tone: Professional but warm. Avoid generic phrases like "I am writing to apply for..." — start with impact.
6. Do NOT mention salary, benefits, or work type explicitly.
7. Return ONLY the cover letter text, no markdown, no code fences, no JSON.
```

### 5.2 Tone Variants

Offer 3 tone options via query param or simple UI toggle:

| Tone | Temperature | Prompt Adjustment |
|------|-------------|-------------------|
| `professional` | 0.3 | Strict formal business tone |
| `balanced` | 0.5 | Professional with personality |
| `creative` | 0.7 | More expressive, for design/marketing roles |

### 5.3 LLM Configuration

```php
'model' => 'llama-3.3-70b-versatile',
'temperature' => 0.4, // base; overridden by tone
'max_tokens' => 512,
'response_format' => ['type' => 'text'], // plain text, not JSON
```

## 6. Backend Implementation

### 6.1 Controller Method

```php
public function generateCoverLetter(Request $request)
{
    $request->validate([
        'job_id' => 'required|integer|exists:job_listings,id',
        'tone' => 'nullable|string|in:professional,balanced,creative',
    ]);

    $user = $request->user()->load('candidateProfile');
    $profile = $user->candidateProfile;

    if (!$profile) {
        return response()->json([
            'success' => false,
            'message' => 'Please complete your candidate profile before generating a cover letter.',
            'data' => null,
        ], 422);
    }

    $job = JobListing::approved()
        ->with('employer')
        ->findOrFail($request->job_id);

    $tone = $request->input('tone', 'balanced');
    $temperature = match($tone) {
        'professional' => 0.3,
        'creative' => 0.7,
        default => 0.5,
    };

    $prompt = $this->buildCoverLetterPrompt($user, $profile, $job);

    $response = Http::withHeaders([
        'Authorization' => 'Bearer ' . config('services.groq.key'),
        'Content-Type' => 'application/json',
    ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => $temperature,
        'max_tokens' => 512,
    ]);

    if ($response->failed()) {
        return response()->json([
            'success' => false,
            'message' => 'AI generation failed. Please try again.',
            'data' => null,
        ], 502);
    }

    $letter = trim($response->json()['choices'][0]['message']['content'] ?? '');

    if (!$letter) {
        return response()->json([
            'success' => false,
            'message' => 'AI returned an empty response.',
            'data' => null,
        ], 502);
    }

    return response()->json([
        'success' => true,
        'message' => 'Cover letter generated.',
        'data' => [
            'cover_letter' => $letter,
            'tone' => $tone,
            'word_count' => str_word_count($letter),
            'job_title' => $job->title,
            'company_name' => $job->employer?->company_name ?? 'the company',
        ],
    ]);
}
```

### 6.2 Route Registration

```php
// routes/api.php
Route::middleware('candidate')->group(function () {
    Route::post('/ai/cover-letter', [AiController::class, 'generateCoverLetter']);
});
```

## 7. Frontend Implementation

### 7.1 UI/UX Workflow

**Entry Points:**
1. Job Detail page — "Generate Cover Letter" button (candidate-only)
2. Candidate Profile page — "Generate Cover Letter" button + job selector dropdown

**Flow:**
```
[Job Detail Page]
  └─ User clicks "Generate Cover Letter"
       ├─ If no candidate profile: redirect to profile with toast
       ├─ If profile incomplete: modal warning + link to edit
       └─ Modal opens with:
            ├─ Tone selector (Professional / Balanced / Creative)
            ├─ "Generate" button
            └─ Loading state (2-3s)
                 └─ Success:
                      ├─ Render letter in editable textarea
                      ├─ Word count badge
                      ├─ Copy to clipboard button
                      ├─ Download as .txt button
                      └─ "Regenerate" button (keeps tone selection)
                 └─ Error:
                      ├─ Error message
                      └─ Retry button
```

### 7.2 Components

**New Files:**
- `src/components/profile/CoverLetterModal.vue` — modal wrapper
- `src/components/profile/CoverLetterEditor.vue` — textarea + actions

**Modified Files:**
- `src/views/candidate/JobDetailView.vue` — add "Generate Cover Letter" button
- `src/api/ai.js` — add `generateCoverLetter(params)` function
- `src/router/index.js` — no changes needed

### 7.3 Component Spec

```vue
<!-- CoverLetterModal.vue -->
<script setup>
defineProps({ isOpen: Boolean, job: Object })
const emit = defineEmits(['close'])
const tone = ref('balanced')
const loading = ref(false)
const letter = ref('')
const error = ref('')

async function generate() {
  loading.value = true
  error.value = ''
  try {
    const res = await generateCoverLetter({ job_id: job.value.id, tone: tone.value })
    letter.value = res.data.data.cover_letter
  } catch (e) {
    error.value = e?.response?.data?.message || 'Failed to generate cover letter.'
  } finally {
    loading.value = false
  }
}

function reset() {
  letter.value = ''
  error.value = ''
}
</script>

<template>
  <BaseModal :is-open="isOpen" @close="emit('close')">
    <template #header>
      <h2>Generate Cover Letter</h2>
    </template>
    <template #body>
      <div v-if="!letter" class="space-y-4">
        <p class="text-sm text-surface-600">
          We'll use your profile and this job description to write a tailored cover letter.
        </p>
        <div>
          <label class="text-sm font-medium text-surface-700">Tone</label>
          <select v-model="tone" class="mt-1 w-full rounded-lg border border-surface-300 px-3 py-2 text-sm">
            <option value="professional">Professional</option>
            <option value="balanced">Balanced</option>
            <option value="creative">Creative</option>
          </select>
        </div>
        <BaseButton class="w-full" :loading="loading" @click="generate">
          Generate Cover Letter
        </BaseButton>
      </div>

      <div v-else class="space-y-4">
        <textarea
          v-model="letter"
          rows="12"
          class="w-full rounded-lg border border-surface-300 p-4 text-sm leading-relaxed"
        />
        <div class="flex items-center justify-between">
          <span class="text-xs text-surface-500">{{ letter.split(' ').length }} words</span>
          <div class="flex gap-2">
            <BaseButton variant="outline" size="sm" @click="reset">Regenerate</BaseButton>
            <BaseButton size="sm" @click="copyToClipboard">Copy</BaseButton>
          </div>
        </div>
      </div>

      <p v-if="error" class="mt-4 text-sm text-red">{{ error }}</p>
    </template>
  </BaseModal>
</template>
```

## 8. Edge Cases & Validation

| Scenario | Behavior |
|----------|----------|
| Candidate has no profile | Return 422 with message to complete profile |
| Candidate profile has empty bio | Proceed; prompt will note "(empty)" and instruct LLM to infer from skills |
| Candidate has no skills listed | Proceed; prompt will instruct LLM to focus on transferable skills from bio |
| Job has no description/responsibilities | Use title + category as fallback context |
| Job is not approved | Block access; return 404 or 403 |
| LLM returns empty/malformed text | Fallback: return a generic template with user/job data interpolated |
| LLM times out (>20s) | Return 504 with retry suggestion |
| Rate limiting | Implement per-user throttle: max 10 generations/hour |

## 9. Prompt Fallback Strategy

If the LLM fails or returns invalid output, serve a deterministic template:

```
Dear Hiring Manager,

I am writing to express my interest in the {job_title} position at {company_name}. With a background in {top_3_skills}, I am excited about the opportunity to contribute to your team.

[If bio exists: "{bio_snippet}"]

I am particularly drawn to this role because {company_context_snippet}.

I look forward to discussing how my skills align with your needs. You can reach me at {email} or {phone}.

Sincerely,
{name}
```

## 10. Testing Strategy

### Backend Tests

```php
public function test_candidate_can_generate_cover_letter()
{
    $candidate = User::create(['role' => 'candidate', ...]);
    $candidate->candidateProfile()->create(['bio' => 'Passionate dev', 'skills' => ['PHP'], ...]);
    $job = JobListing::create(['status' => 'approved', 'title' => 'Dev', ...]);

    Sanctum::actingAs($candidate);
    $response = $this->postJson('/api/ai/cover-letter', ['job_id' => $job->id]);

    $response->assertStatus(200)
        ->assertJsonStructure(['success', 'message', 'data' => [
            'cover_letter', 'tone', 'word_count', 'job_title', 'company_name'
        ]]);
}

public function test_cover_letter_requires_profile()
{
    $candidate = User::create(['role' => 'candidate', ...]); // no profile
    $job = JobListing::create(['status' => 'approved', ...]);

    Sanctum::actingAs($candidate);
    $this->postJson('/api/ai/cover-letter', ['job_id' => $job->id])
         ->assertStatus(422);
}

public function test_non_candidate_cannot_generate_cover_letter()
{
    $employer = User::create(['role' => 'employer', ...]);
    Sanctum::actingAs($employer);
    $this->postJson('/api/ai/cover-letter', ['job_id' => 1])
         ->assertStatus(403);
}
```

### Frontend Tests

- Modal opens/closes correctly
- Tone selection persists
- Loading state displays during API call
- Error state displays on failure
- Copy to clipboard functionality
- Word count updates reactively

## 11. Rollout Plan

| Phase | Scope | Duration |
|-------|-------|----------|
| Phase 1 | Backend endpoint + basic frontend modal (professional tone only) | 1 day |
| Phase 2 | Add tone selector + regenerate + copy/download | 1 day |
| Phase 3 | Add cover letter to employer dashboard (send to candidate) | 1 day |
| Phase 4 | Analytics: track generation rate, copy rate, application rate | 0.5 day |

## 12. Open Questions / Decisions Needed

1. **Resume parsing**: If resume text is extracted later, should it be injected into the prompt? → Yes, but only if `resume_path` exists and we have a parsing pipeline.
2. **Cover letter storage**: Should generated letters be saved to DB for history? → Recommend no for v1; add if users request it.
3. **Employer-initiated**: Should employers be able to request a cover letter from a candidate? → Future consideration; requires notification + permission flow.
4. **A/B testing**: Should we test different prompt versions? → Yes, after 100+ generations, compare application rates.
