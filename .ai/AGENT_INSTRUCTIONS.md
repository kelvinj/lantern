# AI Agent Instructions for Lantern

## 🎯 Quick Start (Read This First!)

Lantern is a Laravel package for organizing domain logic using **Features** (containers) and **Actions** (executable business logic).

### The 3 Golden Rules

1. **Always use `App\Features` namespace** (never `src/`)
2. **Always use Artisan commands** to create Features/Actions
3. **Always return `ActionResponse`** from `perform()` methods

### The 5-Second Overview

```php
// 1. Create a Feature (container)
class TodosFeature extends Feature {
    const ACTIONS = [CreateTodoAction::class];
}

// 2. Create an Action (business logic)
class CreateTodoAction extends Action {
    public function perform(string $title): ActionResponse {
        $todo = Todo::create(['title' => $title]);
        return $this->success($todo);
    }
}

// 3. Use it
$response = CreateTodoAction::make($repo)->perform('Buy milk');
if ($response->successful()) {
    $todo = $response->data();
}
```

---

## 📚 Documentation Structure

This `.ai` directory contains 7 comprehensive guides:

1. **README.md** - Start here! Navigation guide for all docs
2. **lantern-guide.md** - Complete framework reference
3. **quick-reference.md** - Fast syntax lookup
4. **examples.md** - Real-world code examples
5. **common-mistakes.md** - 20+ mistakes to avoid
6. **decision-tree.md** - Step-by-step decision guides
7. **glossary.md** - Terminology and definitions

### When to Use Each Guide

| Situation | Use This File |
|-----------|---------------|
| First time with Lantern | README.md → lantern-guide.md |
| Need syntax quickly | quick-reference.md |
| Want to see examples | examples.md |
| Debugging an error | common-mistakes.md |
| Unsure what to do | decision-tree.md |
| Don't understand a term | glossary.md |

---

## ⚡ Essential Patterns (Memorize These)

### Pattern 1: Create Feature + Action
```bash
# Always use Artisan commands
php artisan lantern:make-feature Todos/TodosFeature
php artisan lantern:make-action Todos/CreateTodoAction
```

### Pattern 2: Minimal Action
```php
<?php
namespace App\Features\Todos;

use Lantern\Features\Action;
use Lantern\Features\ActionResponse;

class CreateTodoAction extends Action
{
    public function perform(string $title): ActionResponse
    {
        // Your logic
        return $this->success(['title' => $title]);
    }
}
```

### Pattern 3: Register in Feature
```php
class TodosFeature extends Feature
{
    const ACTIONS = [
        CreateTodoAction::class,  // ← Register here!
    ];
}
```

### Pattern 4: Use in Controller
```php
$response = CreateTodoAction::make($repo)->perform($title);

if ($response->successful()) {
    return redirect()->route('todos.index');
}

return back()->withErrors($response->errors());
```

---

## 🚨 Critical Mistakes to Avoid

### ❌ Mistake #1: Wrong Namespace
```php
namespace Src\Features\Todos;  // WRONG!
namespace App\Features\Todos;  // CORRECT!
```

### ❌ Mistake #2: Not Returning ActionResponse
```php
public function perform(): void { }  // WRONG!
public function perform(): ActionResponse {  // CORRECT!
    return $this->success();
}
```

### ❌ Mistake #3: Direct Instantiation
```php
$action = new CreateTodoAction($repo);  // WRONG!
$action = CreateTodoAction::make($repo);  // CORRECT!
```

### ❌ Mistake #4: Forgetting Registration
```php
// Created CreateTodoAction but forgot to add to Feature!
class TodosFeature extends Feature {
    const ACTIONS = [];  // WRONG - empty!
}

class TodosFeature extends Feature {
    const ACTIONS = [
        CreateTodoAction::class,  // CORRECT!
    ];
}
```

### ❌ Mistake #5: Constraints vs Availability
```php
// System check in availability - WRONG!
protected function availability(AvailabilityBuilder $builder) {
    $builder->assertTrue(extension_loaded('gd'));
}

// System check in constraints - CORRECT!
protected function constraints(ConstraintsBuilder $constraints) {
    $constraints->extensionIsLoaded('gd');
}
```

---

## 🎓 Key Concepts (Must Understand)

### Concept 1: Features vs Actions

| Feature | Action |
|---------|--------|
| Container | Executable |
| Has ACTIONS/FEATURES | Has perform() |
| Cannot be called | Can be called |
| Only constraints | Constraints + availability |

### Concept 2: Constraints vs Availability

| Constraints | Availability |
|-------------|--------------|
| System checks | User checks |
| Cached | Not cached |
| Features + Actions | Actions only |
| Example: PHP extension | Example: User permission |

### Concept 3: ActionProxy

```php
$proxy = CreateTodoAction::make($repo);  // Returns ActionProxy
// Proxy checks constraints → availability → then calls perform()
$response = $proxy->perform('Title');
```

The proxy is **transparent** - you use it like the Action itself.

---

## 📋 Pre-Flight Checklist

Before generating Lantern code, verify:

**Structure:**
- [ ] Using `App\Features` namespace
- [ ] Using Artisan commands to create files
- [ ] Features extend `Lantern\Features\Feature`
- [ ] Actions extend `Lantern\Features\Action`

**Implementation:**
- [ ] Actions have `perform()` returning `ActionResponse`
- [ ] Using `$this->success()` or `$this->failure()`
- [ ] Constraints for system checks
- [ ] Availability for user checks

**Registration:**
- [ ] Actions in Feature's `ACTIONS` array
- [ ] Features in parent's `FEATURES` array
- [ ] Top-level Feature in `AppServiceProvider`

**Usage:**
- [ ] Calling via `Action::make()` not `new Action()`
- [ ] Checking `$response->successful()`
- [ ] Handling errors with `$response->errors()`

---

## 🔧 Common Tasks

### Task: Create CRUD for Todos

```bash
# 1. Create Feature
php artisan lantern:make-feature Todos/TodosFeature

# 2. Create Actions
php artisan lantern:make-action Todos/CreateTodoAction
php artisan lantern:make-action Todos/UpdateTodoAction
php artisan lantern:make-action Todos/DeleteTodoAction

# 3. Register Actions in Feature
# Edit TodosFeature.php:
const ACTIONS = [
    CreateTodoAction::class,
    UpdateTodoAction::class,
    DeleteTodoAction::class,
];

# 4. Register Feature in AppFeatures
# Edit AppFeatures.php:
const FEATURES = [
    TodosFeature::class,
];
```

### Task: Add Authorization

```php
class UpdateTodoAction extends Action
{
    protected function availability(AvailabilityBuilder $builder)
    {
        // Laravel Gate check
        $builder->userCan('update', $this->todo);
        
        // Custom check
        $builder->assertEqual(
            $builder->user()->id,
            $this->todo->user_id,
            'You can only update your own todos'
        );
    }
}
```

### Task: Add System Requirements

```php
class GeneratePdfAction extends Action
{
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->extensionIsLoaded('imagick');
        $constraints->executableIsInstalled('wkhtmltopdf');
    }
}
```

---

## 🎯 Decision Flowcharts

### Should I create a Feature or Action?

```
Need to execute logic? 
├─ YES → Create Action
└─ NO → Need to organize? 
    ├─ YES → Create Feature
    └─ NO → Don't use Lantern
```

### Constraints or Availability?

```
Is this a system requirement?
├─ YES → Use constraints()
└─ NO → Is this user-specific?
    ├─ YES → Use availability()
    └─ NO → Use Laravel validation
```

### Where does this Action go?

```
Is there a Feature for this domain?
├─ YES → Add to that Feature's ACTIONS
└─ NO → Create new Feature first
```

---

## 🆘 Troubleshooting

### Error: "Action not available"

**Check:**
1. Is user authenticated? (or set `GUEST_USERS = true`)
2. Are Feature constraints met?
3. Are Action constraints met?
4. Are availability conditions met?
5. Is Action registered in Feature?

### Error: "Feature does not contain any Actions or Features"

**Fix:** Add at least one Action or Feature to the Feature's constants.

### Error: "Action ID is invalid"

**Fix:** Don't use periods in IDs. Use kebab-case: `'todos-create'` not `'todos.create'`

---

## 💡 Pro Tips

1. **Start with examples.md** - Copy and adapt working code
2. **Check common-mistakes.md** - Avoid known pitfalls
3. **Use decision-tree.md** - When unsure what to do
4. **Reference quick-reference.md** - For syntax
5. **Read lantern-guide.md** - For deep understanding

---

## 🔗 Quick Links

- **Official Docs:** https://lanternphp.github.io
- **Repository:** https://github.com/lanternphp/lantern
- **Package:** lanternphp/lantern

---

## 📖 Learning Path

### Level 1: Beginner (30 minutes)
1. Read this file (AGENT_INSTRUCTIONS.md)
2. Read README.md in this directory
3. Review examples.md (Example 1: Simple Todo CRUD)
4. Try creating a simple Feature + Action

### Level 2: Intermediate (1 hour)
1. Read lantern-guide.md (Core Architecture section)
2. Review all examples in examples.md
3. Study common-mistakes.md (top 10)
4. Practice with availability and constraints

### Level 3: Advanced (2 hours)
1. Read complete lantern-guide.md
2. Study decision-tree.md (all trees)
3. Review glossary.md
4. Build a complex feature with nested Features

---

## ✅ Success Criteria

You understand Lantern when you can:

- [ ] Explain the difference between Feature and Action
- [ ] Explain the difference between constraints and availability
- [ ] Create a Feature and Action using Artisan
- [ ] Register Actions in Features properly
- [ ] Use Actions in controllers correctly
- [ ] Handle ActionResponse appropriately
- [ ] Add authorization with availability()
- [ ] Add system requirements with constraints()
- [ ] Troubleshoot "Action not available" errors
- [ ] Structure Features hierarchically

---

## 🚀 Next Steps

1. **Read README.md** in this directory for navigation
2. **Review examples.md** to see working code
3. **Check common-mistakes.md** before coding
4. **Use quick-reference.md** as you code
5. **Consult decision-tree.md** when stuck

---

## 📝 Final Reminders

**Always:**
- Use `App\Features` namespace
- Use Artisan commands
- Return `ActionResponse`
- Call via `make()` not `new`
- Check `successful()` before using data
- Register Actions in Features
- Register Features in parents

**Never:**
- Use `src/` namespace
- Return void from `perform()`
- Instantiate Actions directly
- Forget to register Actions/Features
- Use periods in IDs
- Put availability in Features
- Put user checks in constraints

---

## 🎉 You're Ready!

You now have everything you need to work with Lantern effectively. Start with the examples, avoid the common mistakes, and reference the guides as needed.

**Happy coding!** 🚀

