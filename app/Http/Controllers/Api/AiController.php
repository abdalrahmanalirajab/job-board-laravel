<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function generateJobDescription(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'skills' => 'nullable|array',
            'technologies' => 'nullable|array',
            'experience_level' => 'nullable|string|in:junior,mid,senior,any',
            'work_type' => 'nullable|string|in:remote,onsite,hybrid',
            'salary_min' => 'nullable|numeric',
            'salary_max' => 'nullable|numeric',
            'category' => 'nullable|string',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is not configured.',
                'data' => null,
            ], 503);
        }

        $skills = implode(', ', $validated['skills'] ?? []);
        $technologies = implode(', ', $validated['technologies'] ?? []);
        $experience = $validated['experience_level'] ?? 'any';
        $workType = $validated['work_type'] ?? 'remote';
        $category = $validated['category'] ?: 'General';
        $salaryLabel = '';
        if (! empty($validated['salary_min']) && ! empty($validated['salary_max'])) {
            $salaryLabel = '$'.number_format($validated['salary_min']).' - $'.number_format($validated['salary_max']).' per year';
        } else {
            $salaryLabel = 'Competitive';
        }

        $prompt = "You are an expert technical recruiter and copywriter. Generate a professional job posting for the following position.\n\n"
            ."**Job Title:** {$validated['title']}\n"
            ."**Category:** {$category}\n"
            ."**Skills Required:** {$skills}\n"
            ."**Technologies:** {$technologies}\n"
            ."**Experience Level:** {$experience}\n"
            ."**Work Type:** {$workType}\n"
            ."**Salary Range:** {$salaryLabel}\n\n"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            ."{\n"
            .'  "description": "A compelling 2-3 paragraph job description that highlights the role, company culture, and what makes this opportunity exciting. Be professional but engaging.",'."\n"
            .'  "responsibilities": "A bullet-point list (using \\n• prefix for each item) of 5-8 key responsibilities for this role. Be specific to the job title and skills.",'."\n"
            .'  "benefits": "A bullet-point list (using \\n• prefix for each item) of 4-6 attractive benefits and perks relevant to the role and work type."'."\n"
            ."}\n\n"
            ."Rules:\n"
            ."- Make the description engaging and professional, not generic\n"
            ."- Tailor responsibilities to the actual job title and required skills\n"
            ."- Include modern relevant benefits (flexible work, learning budget, etc.) based on work type\n"
            ."- Do NOT include salary details in the description text\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1024,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'AI generation failed. Please try again.',
                'data' => null,
            ], 502);
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an empty response.',
                'data' => null,
            ], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned invalid JSON.',
                'data' => null,
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job content generated successfully.',
            'data' => [
                'description' => $generated['description'] ?? '',
                'responsibilities' => $generated['responsibilities'] ?? '',
                'benefits' => $generated['benefits'] ?? '',
            ],
        ]);
    }

    public function interviewPrep(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'skills' => 'nullable|array',
            'technologies' => 'nullable|array',
            'experience_level' => 'nullable|string|in:junior,mid,senior,any',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'AI service is not configured.',
                'data' => null,
            ], 503);
        }

        $skills = implode(', ', $validated['skills'] ?? []);
        $technologies = implode(', ', $validated['technologies'] ?? []);
        $experience = $validated['experience_level'] ?? 'any';
        $description = $validated['description'] ?? '';

        $prompt = "You are an expert interview coach helping a candidate prepare for a job interview. Generate comprehensive interview preparation material for the following position.\n\n"
            ."**Job Title:** {$validated['title']}\n"
            ."**Description:** {$description}\n"
            ."**Skills Required:** {$skills}\n"
            ."**Technologies:** {$technologies}\n"
            ."**Experience Level:** {$experience}\n\n"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            ."{\n"
            .'  "technical_questions": ['."\n"
            .'    {"question": "...", "tip": "Brief answer guidance or what the interviewer looks for"},'."\n"
            ."    (5-6 questions, tailored to the skills and technologies listed)\n"
            ."  ],\n"
            .'  "behavioral_questions": ['."\n"
            .'    {"question": "...", "tip": "STAR method guidance (Situation, Task, Action, Result)"},'."\n"
            ."    (3-4 questions commonly asked for this experience level)\n"
            ."  ],\n"
            .'  "tips": ["specific actionable tip 1", "tip 2", ...],'."\n"
            ."  (4-5 specific tips for succeeding in this particular interview)\n"
            .'  "what_to_expect": "A 2-3 sentence overview of what the candidate should expect during the interview process for this type of role"\n'
            ."}\n\n"
            ."Rules:\n"
            ."- Make technical questions specific to the listed skills and technologies\n"
            ."- Questions should match the {$experience} experience level\n"
            ."- Tips should be actionable and specific, not generic\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1500,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'AI generation failed. Please try again.',
                'data' => null,
            ], 502);
        }

        $body = $response->json();
        $content = $body['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an empty response.',
                'data' => null,
            ], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned invalid JSON.',
                'data' => null,
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Interview prep material generated successfully.',
            'data' => [
                'technical_questions' => $generated['technical_questions'] ?? [],
                'behavioral_questions' => $generated['behavioral_questions'] ?? [],
                'tips' => $generated['tips'] ?? [],
                'what_to_expect' => $generated['what_to_expect'] ?? '',
            ],
        ]);
    }

    public function smartReplies(Request $request, $id)
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.role' => 'required|string|in:sender,receiver',
            'messages.*.body' => 'required|string',
            'job_title' => 'nullable|string|max:200',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service is not configured.', 'data' => null], 503);
        }

        $jobTitle = $validated['job_title'] ?? 'the position';
        $conversationText = '';
        foreach ($validated['messages'] as $msg) {
            $role = $msg['role'] === 'sender' ? 'You' : 'Them';
            $conversationText .= "{$role}: {$msg['body']}\n";
        }

        $prompt = "You are a hiring communication assistant. Based on this conversation about the {$jobTitle} position, suggest exactly 3 brief, natural reply options for the person receiving the latest message.\n\n"
            ."**Conversation:**\n{$conversationText}\n"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            .'{ "replies": ["reply option 1", "reply option 2", "reply option 3"] }'."\n\n"
            ."Rules:\n"
            ."- Each reply should be 1-2 sentences max\n"
            ."- Vary the tone: one professional/formal, one friendly/casual, one concise\n"
            ."- Make replies specific to the conversation context, not generic\n"
            ."- If scheduling is relevant, one option should suggest available times\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.6,
            'max_tokens' => 256,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'AI generation failed.', 'data' => null], 502);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? null;
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'AI returned an empty response.', 'data' => null], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => 'AI returned invalid JSON.', 'data' => null], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Smart replies generated.',
            'data' => ['replies' => $generated['replies'] ?? []],
        ]);
    }

    public function checkTone(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'context' => 'nullable|string|max:500',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service is not configured.', 'data' => null], 503);
        }

        $context = $validated['context'] ? "\n**Context:** {$validated['context']}" : '';

        $prompt = "You are a professional communication coach specializing in hiring conversations. Analyze this message for clarity, professionalism, and tone.\n\n"
            ."**Draft message:** {$validated['message']}\n{$context}\n"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            ."{\n"
            .'  "improved": "The polished, improved version of the message. If already good, return a slightly refined version.",'."\n"
            .'  "notes": "A brief 1-2 sentence explanation of what was changed and why.",'."\n"
            .'  "is_already_good": true_or_false'."\n"
            ."}\n\n"
            ."Rules:\n"
            ."- Keep the original meaning and intent intact\n"
            ."- Fix grammar, spelling, and punctuation\n"
            ."- Improve professionalism without making it sound robotic\n"
            ."- If the message is already excellent, note that and return a near-identical version\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.5,
            'max_tokens' => 300,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'AI generation failed.', 'data' => null], 502);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? null;
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'AI returned an empty response.', 'data' => null], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => 'AI returned invalid JSON.', 'data' => null], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tone analysis complete.',
            'data' => [
                'improved' => $generated['improved'] ?? $validated['message'],
                'notes' => $generated['notes'] ?? '',
                'is_already_good' => $generated['is_already_good'] ?? false,
            ],
        ]);
    }

    public function summarizeConversation(Request $request, $id)
    {
        $validated = $request->validate([
            'messages' => 'required|array|min:1',
            'messages.*.sender' => 'required|string',
            'messages.*.body' => 'required|string',
            'job_title' => 'nullable|string|max:200',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service is not configured.', 'data' => null], 503);
        }

        $jobTitle = $validated['job_title'] ?? 'the position';
        $conversationText = '';
        foreach ($validated['messages'] as $msg) {
            $conversationText .= "{$msg['sender']}: {$msg['body']}\n";
        }

        $prompt = "You are a professional hiring assistant. Summarize this conversation about the {$jobTitle} position.\n\n"
            ."**Conversation:**\n{$conversationText}\n"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            ."{\n"
            .'  "summary": "A 2-3 sentence overview of the conversation.",'."\n"
            .'  "key_points": ["key point 1", "key point 2", ...],'."\n"
            .'  "action_items": ["action item 1", "action item 2", ...],'."\n"
            .'  "next_steps": "A brief description of what should happen next"'."\n"
            ."}\n\n"
            ."Rules:\n"
            ."- Be concise and factual\n"
            ."- Extract 3-5 key discussion points\n"
            ."- Identify concrete action items (things to do, not just topics)\n"
            ."- Next steps should be actionable and specific\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(25)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.4,
            'max_tokens' => 800,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'AI generation failed.', 'data' => null], 502);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? null;
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'AI returned an empty response.', 'data' => null], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => 'AI returned invalid JSON.', 'data' => null], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Conversation summarized.',
            'data' => [
                'summary' => $generated['summary'] ?? '',
                'key_points' => $generated['key_points'] ?? [],
                'action_items' => $generated['action_items'] ?? [],
                'next_steps' => $generated['next_steps'] ?? '',
            ],
        ]);
    }

    public function suggestSchedule(Request $request, $id)
    {
        $validated = $request->validate([
            'messages' => 'nullable|array',
            'messages.*.sender' => 'required|string',
            'messages.*.body' => 'required|string',
            'job_title' => 'nullable|string|max:200',
            'work_type' => 'nullable|string|in:remote,onsite,hybrid',
        ]);

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service is not configured.', 'data' => null], 503);
        }

        $jobTitle = $validated['job_title'] ?? 'the position';
        $workType = $validated['work_type'] ?? 'remote';
        $conversationText = '';
        if (! empty($validated['messages'])) {
            foreach ($validated['messages'] as $msg) {
                $conversationText .= "{$msg['sender']}: {$msg['body']}\n";
            }
        }

        $contextBlock = $conversationText ? "\n**Previous conversation:**\n{$conversationText}\n" : '';

        $prompt = "You are a hiring assistant helping schedule an interview for a {$jobTitle} position ({$workType} role). Generate a professional scheduling message with suggested time slots.\n\n"
            ."{$contextBlock}"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            .'{ "message": "A professional message suggesting 2-3 interview time slots with dates and times, formatted clearly. Include the interview format (video call for remote, on-site visit for onsite, etc.). Keep it concise and professional." }'."\n\n"
            ."Rules:\n"
            ."- Suggest time slots for the next business days\n"
            ."- Use a professional but friendly tone\n"
            ."- For remote roles, mention it will be a video call\n"
            ."- For onsite, mention the office location context\n"
            ."- Include duration estimate (30-60 min)\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(20)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.6,
            'max_tokens' => 400,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json(['success' => false, 'message' => 'AI generation failed.', 'data' => null], 502);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? null;
        if (! $content) {
            return response()->json(['success' => false, 'message' => 'AI returned an empty response.', 'data' => null], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => 'AI returned invalid JSON.', 'data' => null], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule suggestion generated.',
            'data' => ['message' => $generated['message'] ?? ''],
        ]);
    }

    public function optimizeProfile(Request $request)
    {
        $user = $request->user()->load('candidateProfile');
        $profile = $user->candidateProfile;

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Candidate profile not found. Please complete your profile first.',
                'data' => null,
            ], 404);
        }

        $apiKey = config('services.groq.key');
        if (! $apiKey) {
            return response()->json(['success' => false, 'message' => 'AI service is not configured.', 'data' => null], 503);
        }

        $skills = $profile->skills ?? [];
        $bio = $profile->bio ?? '';
        $linkedin = $profile->linkedin_url ?? '';
        $phone = $profile->phone ?? '';
        $resume = $profile->resume_path ?? '';
        $name = $user->name ?? '';

        $totalJobs = JobListing::approved()->count();

        if ($totalJobs === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No active job listings available for market analysis yet. Please try again later.',
                'data' => null,
            ], 503);
        }

        $skillCounts = [];
        $techCounts = [];
        $experienceCounts = [];

        JobListing::approved()
            ->with('technologies')
            ->select('id', 'skills_required', 'experience_level')
            ->chunk(200, function ($jobs) use (&$skillCounts, &$techCounts, &$experienceCounts) {
                foreach ($jobs as $job) {
                    $jobSkills = $job->skills ?? [];
                    foreach ($jobSkills as $s) {
                        $s = trim(strtolower($s));
                        if ($s) {
                            $skillCounts[$s] = ($skillCounts[$s] ?? 0) + 1;
                        }
                    }
                    foreach ($job->technologies as $t) {
                        $name = strtolower($t->name ?? $t->technology ?? '');
                        if ($name) {
                            $techCounts[$name] = ($techCounts[$name] ?? 0) + 1;
                        }
                    }
                    $exp = $job->experience_level ?? 'any';
                    $experienceCounts[$exp] = ($experienceCounts[$exp] ?? 0) + 1;
                }
            });

        arsort($skillCounts);
        arsort($techCounts);
        $topSkills = array_slice($skillCounts, 0, 15, true);
        $topTechs = array_slice($techCounts, 0, 15, true);

        $marketData = "Total active jobs: {$totalJobs}\n"
            .'Top in-demand skills: '.implode(', ', array_map(fn ($s, $c) => "{$s} ({$c} jobs, ".round(($c / $totalJobs) * 100).'%)', array_keys($topSkills), $topSkills))."\n"
            .'Top in-demand technologies: '.implode(', ', array_map(fn ($s, $c) => "{$s} ({$c} jobs, ".round(($c / $totalJobs) * 100).'%)', array_keys($topTechs), $topTechs))."\n"
            .'Experience level distribution: '.json_encode($experienceCounts);

        $candidateSkills = implode(', ', $skills ?: ['none listed']);

        $prompt = "You are a career optimization expert analyzing a job candidate's profile against current market demand.\n\n"
            ."**Candidate Profile:**\n"
            ."- Name: {$name}\n"
            ."- Skills: {$candidateSkills}\n"
            .'- Bio: '.($bio ?: '(empty)')."\n"
            .'- LinkedIn: '.($linkedin ?: '(not provided)')."\n"
            .'- Phone: '.($phone ?: '(not provided)')."\n"
            .'- Resume uploaded: '.($resume ? 'yes' : 'no')."\n\n"
            ."**Current Market Demand:**\n{$marketData}\n\n"
            ."Generate exactly this JSON structure (no markdown, no code fences, just raw JSON):\n"
            ."{\n"
            .'  "completeness_score": 0_to_100,'."\n"
            .'  "strengths": ["specific strength 1", "strength 2", ...],'."\n"
            .'  "gaps": ["specific gap 1", "gap 2", ...],'."\n"
            .'  "skills_analysis": {'."\n"
            .'    "in_demand_you_have": [{"skill": "name", "demand": "X% of jobs"}],'."\n"
            .'    "in_demand_you_need": [{"skill": "name", "demand": "X% of jobs", "reason": "why this matters"}],'."\n"
            .'    "your_unique_skills": ["skill that sets you apart"]'."\n"
            ."  },\n"
            .'  "bio_feedback": "Specific feedback on bio quality and how to improve it",'."\n"
            .'  "suggestions": [{'."\n"
            .'    "priority": "high|medium|low",'."\n"
            .'    "category": "skills|profile|bio|resume",'."\n"
            .'    "text": "Specific actionable suggestion",'."\n"
            .'    "impact": "Estimated impact like +15% match rate"'."\n"
            ."  }, ...]\n"
            ."}\n\n"
            ."Rules:\n"
            ."- Completeness score: 0-100 based on profile filled fields, skills count, bio quality, resume presence\n"
            ."- Be specific and data-driven, reference actual job counts and percentages\n"
            ."- Suggestions should be prioritized (high first) with estimated impact\n"
            ."- Skills analysis must cross-reference candidate skills against actual market demand data\n"
            ."- Bio feedback should be constructive with specific improvement examples\n"
            .'- Return ONLY the JSON object, nothing else';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.4,
            'max_tokens' => 1000,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'AI generation failed. Please try again.',
                'data' => null,
            ], 502);
        }

        $content = $response->json()['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned an empty response.',
                'data' => null,
            ], 502);
        }

        $generated = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'AI returned invalid JSON.',
                'data' => null,
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Profile analysis complete.',
            'data' => [
                'completeness_score' => $generated['completeness_score'] ?? 0,
                'strengths' => $generated['strengths'] ?? [],
                'gaps' => $generated['gaps'] ?? [],
                'skills_analysis' => $generated['skills_analysis'] ?? [],
                'bio_feedback' => $generated['bio_feedback'] ?? '',
                'suggestions' => $generated['suggestions'] ?? [],
            ],
        ]);
    }
}
