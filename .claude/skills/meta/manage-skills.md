---
name: manage-skills
description: "Create or update Claude Code skills for TanaVitrine project. Use when discovering new patterns, workflows, or conventions that should be documented as reusable skills."
---

# Manage Skills

## Instructions

### When to Create a New Skill
Create a skill when you identify:
- A repetitive task or workflow
- Project-specific conventions or patterns
- Complex multi-step processes
- Domain-specific operations (store management, subscriptions, etc.)
- Integration patterns (Stripe, file uploads, etc.)

### Skill File Structure
All skills must follow this format:

```markdown
---
name: skill-name-here
description: "Brief description (max 1024 chars). Include when to use this skill and key trigger words."
---

# Skill Title

## Instructions

1. Clear step-by-step instructions
2. Include bash commands with proper paths
3. Reference project conventions
4. Show file locations and structure

## Key Conventions
- List project-specific patterns
- Highlight important rules
- Reference related files/components

## Examples

### Example Title
\`\`\`language
code example here
\`\`\`
```

### Naming Conventions
- **name**: lowercase, hyphens only, max 64 chars
- **description**: Be specific, include trigger terms
- Examples: `laravel-create-model`, `vue-create-page`, `manage-store-photos`

### Skill Categories (Directory Structure)
```
.claude/skills/
├── laravel/          # Laravel backend (models, controllers, migrations)
├── vue/              # Vue/Inertia frontend (pages, components)
├── store/            # Store/vitrine specific features
├── subscription/     # Stripe/payment features
├── deploy/           # Build, test, deployment
└── meta/             # Skills about skills (this file)
```

## Creating a New Skill

1. **Identify the pattern**: What repetitive task needs documentation?

2. **Choose category**: Which directory? Create new if needed.

3. **Write the skill file**:
```bash
touch .claude/skills/[category]/[skill-name].md
```

4. **Fill frontmatter**:
   - Clear, descriptive name (lowercase-with-hyphens)
   - Detailed description with trigger words
   - Explain WHEN to use this skill

5. **Write Instructions**:
   - Step-by-step, actionable
   - Include bash commands
   - Reference specific files
   - Show exact patterns to follow

6. **Add Examples**:
   - Real code from the project
   - Show complete, working examples
   - Multiple examples for different scenarios

## Updating Existing Skills

1. **When to update**:
   - Convention changes in the project
   - New patterns discovered
   - Better examples found
   - Missing important steps

2. **How to update**:
   - Read existing skill first
   - Preserve frontmatter structure
   - Update instructions/examples
   - Keep description accurate

## Examples

### Good Skill Name & Description
```yaml
---
name: laravel-create-api-endpoint
description: "Create RESTful API endpoints with Laravel Sanctum authentication. Use when building API routes, implementing authentication, or creating JSON responses for mobile/external clients."
---
```

### Bad Skill Name & Description
```yaml
---
name: api
description: "API stuff"
---
```

### Complete Skill Example
```markdown
---
name: stripe-subscription-webhook
description: "Handle Stripe webhook events for TanaVitrine subscriptions. Use when implementing subscription lifecycle events, payment failures, or plan changes via Stripe webhooks."
---

# Stripe Subscription Webhook

## Instructions

1. **Register webhook route** in `routes/web.php`:
\`\`\`php
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook')
    ->withoutMiddleware([VerifyCsrfToken::class]);
\`\`\`

2. **Handle webhook in controller**:
\`\`\`php
public function handleWebhook(Request $request)
{
    $payload = $request->getContent();
    $signature = $request->header('Stripe-Signature');

    $event = Webhook::constructEvent(
        $payload,
        $signature,
        config('cashier.webhook.secret')
    );

    return $this->handleSubscriptionUpdated($event);
}
\`\`\`

3. **Configure in Stripe Dashboard**:
   - Add webhook endpoint URL
   - Select events to listen
   - Copy webhook secret to `.env`

## Key Conventions
- Always verify webhook signature
- Use Laravel Cashier's built-in handlers when possible
- Log all webhook events for debugging
- Return 200 response to acknowledge

## Examples

### Handle Subscription Cancelled
\`\`\`php
protected function handleSubscriptionCancelled($event)
{
    $subscription = Subscription::findOrFail($event->data->object->id);
    $subscription->markAsCancelled();

    // Downgrade team to free plan
    $team = $subscription->user->currentTeam;
    $team->update(['plan_id' => Plan::free()->id]);

    return response()->json(['status' => 'success']);
}
\`\`\`
```

## TanaVitrine Project Context

When creating skills for this project, remember:
- **Laravel 11** with Jetstream (team-based)
- **Vue 3** + Inertia.js with shadcn/ui components
- **Docker/Sail** for local development
- **Stripe/Cashier** for subscriptions
- **Team model** represents stores/vitrines
- **Slug-based routing** for public store pages
- **Plan limits** via `HasPlanLimits` trait
- **Formatters** in `@/utils/formatters.js`
- **Composables** in `resources/js/Composables/`

Include these in relevant skills!
