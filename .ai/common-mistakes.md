# Lantern Common Mistakes & How to Avoid Them

## Mistake 1: Using `src/` Instead of `app/Features`

❌ **Wrong:**
```php
namespace Src\Features\Todos;
```

✅ **Correct:**
```php
namespace App\Features\Todos;
```

**Why:** User preference is to use `App\Features` namespace. The default config also points to `app_path('Features')`.

---

## Mistake 2: Not Returning ActionResponse from perform()

❌ **Wrong:**
```php
public function perform(string $title): void
{
    Todo::create(['title' => $title]);
}
```

✅ **Correct:**
```php
public function perform(string $title): ActionResponse
{
    $todo = Todo::create(['title' => $title]);
    return $this->success($todo);
}
```

**Why:** Actions MUST return `ActionResponse`. Use `$this->success()` or `$this->failure()`.

---

## Mistake 3: Forgetting to Register Actions in Feature

❌ **Wrong:**
```php
class TodosFeature extends Feature
{
    // Empty - no actions declared!
}
```

✅ **Correct:**
```php
class TodosFeature extends Feature
{
    const ACTIONS = [
        CreateTodoAction::class,
        UpdateTodoAction::class,
    ];
}
```

**Why:** Features must contain at least one Action or Feature. Actions must be registered to be available.

---

## Mistake 4: Using Availability for System Checks

❌ **Wrong:**
```php
protected function availability(AvailabilityBuilder $builder)
{
    // This should be a constraint!
    $builder->assertTrue(
        extension_loaded('gd'),
        'GD extension required'
    );
}
```

✅ **Correct:**
```php
protected function constraints(ConstraintsBuilder $constraints)
{
    $constraints->extensionIsLoaded('gd');
}
```

**Why:** Use `constraints()` for system-level checks (cached), `availability()` for user/context checks (not cached).

---

## Mistake 5: Using Constraints for User Authorization

❌ **Wrong:**
```php
protected function constraints(ConstraintsBuilder $constraints)
{
    // This should be availability!
    $constraints->assertTrue(auth()->user()->isAdmin());
}
```

✅ **Correct:**
```php
protected function availability(AvailabilityBuilder $builder)
{
    $builder->assertTrue(
        $builder->user()->isAdmin(),
        'Admin access required'
    );
}
```

**Why:** Constraints are for system requirements, not user authorization.

---

## Mistake 6: Calling Action Directly Instead of Through make()

❌ **Wrong:**
```php
$action = new CreateTodoAction($repository);
$response = $action->perform('Title');
```

✅ **Correct:**
```php
$response = CreateTodoAction::make($repository)->perform('Title');
```

**Why:** `make()` returns an `ActionProxy` that enforces availability and constraint checks. Direct instantiation bypasses these checks.

---

## Mistake 7: Not Setting GUEST_USERS for Guest Actions

❌ **Wrong:**
```php
class RegisterUserAction extends Action
{
    // Missing GUEST_USERS = true
    public function perform(string $email): ActionResponse
    {
        // ...
    }
}
```

✅ **Correct:**
```php
class RegisterUserAction extends Action
{
    const GUEST_USERS = true;
    
    public function perform(string $email): ActionResponse
    {
        // ...
    }
}
```

**Why:** By default, actions require authenticated users. Set `GUEST_USERS = true` for registration, login, etc.

---

## Mistake 8: Using Periods in Action IDs

❌ **Wrong:**
```php
class CreateTodoAction extends Action
{
    const ID = 'todos.create'; // Periods not allowed!
}
```

✅ **Correct:**
```php
class CreateTodoAction extends Action
{
    const ID = 'todos-create'; // Use kebab-case
}
```

**Why:** Lantern validates that IDs don't contain periods. Use kebab-case or let Lantern auto-generate.

---

## Mistake 9: Not Handling ActionResponse Properly

❌ **Wrong:**
```php
$response = CreateTodoAction::make($repo)->perform('Title');
// Assuming it always succeeds
$todo = $response->data();
```

✅ **Correct:**
```php
$response = CreateTodoAction::make($repo)->perform('Title');

if ($response->successful()) {
    $todo = $response->data();
    // Handle success
} else {
    $errors = $response->errors();
    // Handle failure
}
```

**Why:** Actions can fail. Always check `successful()` or `unsuccessful()` before accessing data.

---

## Mistake 10: Forgetting to Register Feature in Parent

❌ **Wrong:**
```php
// Created TodosFeature but never registered it
class AppFeatures extends Feature
{
    const FEATURES = [
        // TodosFeature missing!
    ];
}
```

✅ **Correct:**
```php
class AppFeatures extends Feature
{
    const FEATURES = [
        TodosFeature::class,
    ];
}
```

**Why:** Features must be registered in a parent Feature's `FEATURES` array to be discovered.

---

## Mistake 11: Not Registering Top-Level Feature

❌ **Wrong:**
```php
// AppServiceProvider - no Lantern registration!
public function boot()
{
    // Missing Lantern::register()
}
```

✅ **Correct:**
```php
use Lantern\Lantern;

public function boot()
{
    Lantern::register(AppFeatures::class);
}
```

**Why:** Top-level Features must be registered in `AppServiceProvider` for Lantern to discover them.

---

## Mistake 12: Trying to Add Availability to Features

❌ **Wrong:**
```php
class TodosFeature extends Feature
{
    // Features don't support availability!
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('view-todos');
    }
}
```

✅ **Correct:**
```php
class TodosFeature extends Feature
{
    // Use constraints only
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->classExists(Todo::class);
    }
}

// Put availability in Actions instead
class CreateTodoAction extends Action
{
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('create', Todo::class);
    }
}
```

**Why:** Features only support `constraints()`, not `availability()`. Put user checks in Actions.

---

## Mistake 13: Not Providing Error Messages in Availability

❌ **Wrong:**
```php
protected function availability(AvailabilityBuilder $builder)
{
    $builder->assertTrue($builder->user()->isPremium());
    // No error message - user won't know why it failed
}
```

✅ **Correct:**
```php
protected function availability(AvailabilityBuilder $builder)
{
    $builder->assertTrue(
        $builder->user()->isPremium(),
        'This feature is only available for premium users'
    );
}
```

**Why:** Error messages help users understand why an action is unavailable.

---

## Mistake 14: Manually Editing Package Files Instead of Using Artisan

❌ **Wrong:**
```bash
# Manually creating files
touch app/Features/Todos/CreateTodoAction.php
```

✅ **Correct:**
```bash
# Use Artisan commands
php artisan lantern:make-action Todos/CreateTodoAction
php artisan lantern:make-feature Todos/TodosFeature
```

**Why:** Artisan commands ensure proper structure, namespacing, and boilerplate code.

---

## Mistake 15: Not Understanding ActionProxy

❌ **Wrong:**
```php
// Trying to access Action methods directly
$action = CreateTodoAction::make($repo);
$action->someCustomMethod(); // May not work as expected
```

✅ **Correct:**
```php
// ActionProxy forwards method calls to the underlying Action
$action = CreateTodoAction::make($repo);
$action->perform('Title'); // Works - proxied to Action

// Or access properties
$action->someProperty; // Works - proxied to Action
```

**Why:** `make()` returns an `ActionProxy`, not the Action itself. The proxy forwards calls to the Action while enforcing checks.

---

## Mistake 16: Empty Feature

❌ **Wrong:**
```php
class TodosFeature extends Feature
{
    // No ACTIONS or FEATURES - will throw exception
}
```

✅ **Correct:**
```php
class TodosFeature extends Feature
{
    const ACTIONS = [
        CreateTodoAction::class,
    ];
    
    // OR
    
    const FEATURES = [
        SubFeature::class,
    ];
}
```

**Why:** Features must contain at least one Action or Feature.

---

## Mistake 17: Not Handling LanternException

❌ **Wrong:**
```php
// No try-catch - exception will bubble up
$response = CreateTodoAction::make($repo)->perform('Title');
```

✅ **Correct:**
```php
use Lantern\LanternException;

try {
    $response = CreateTodoAction::make($repo)->perform('Title');
} catch (LanternException $e) {
    // Handle action not available, constraints failed, etc.
    return back()->withErrors(['action' => $e->getMessage()]);
}
```

**Why:** If availability or constraints fail, `LanternException` is thrown. Handle it gracefully.

---

## Mistake 18: Incorrect Constructor Signature in Features

❌ **Wrong:**
```php
class TodosFeature extends Feature
{
    public function __construct(private SomeService $service)
    {
        // Features can't have constructor dependencies!
    }
}
```

✅ **Correct:**
```php
class TodosFeature extends Feature
{
    // No constructor needed - or use the default no-arg constructor
}
```

**Why:** Features must be instantiable without arguments for constraint checking. Put dependencies in Actions instead.

---

## Mistake 19: Not Using Dot Notation for Response Data

❌ **Wrong:**
```php
$response = CreateTodoAction::make($repo)->perform('Title');
$data = $response->data();
$title = $data['todo']['title']; // Manual array access
```

✅ **Correct:**
```php
$response = CreateTodoAction::make($repo)->perform('Title');
$title = $response->data('todo.title'); // Dot notation
$todo = $response->data('todo'); // Get nested data
```

**Why:** `data()` supports Laravel's dot notation for cleaner access to nested data.

---

## Mistake 20: Mixing Up Feature Stacks

❌ **Wrong:**
```php
// Two features with same stack name but different purposes
class AppFeatures extends Feature
{
    const STACK = 'main';
}

class VendorFeatures extends Feature
{
    const STACK = 'main'; // Conflict!
}
```

✅ **Correct:**
```php
class AppFeatures extends Feature
{
    const STACK = null; // Default stack
}

class VendorFeatures extends Feature
{
    const STACK = 'vendor-name'; // Unique stack
}
```

**Why:** Each top-level Feature should have a unique stack name to avoid conflicts.

---

## Quick Checklist for AI Agents

Before generating Lantern code, verify:

- [ ] Using `App\Features` namespace (not `src/`)
- [ ] Actions return `ActionResponse` from `perform()`
- [ ] Actions are registered in Feature's `ACTIONS` array
- [ ] Features are registered in parent Feature's `FEATURES` array
- [ ] Top-level Feature is registered in `AppServiceProvider`
- [ ] Using `constraints()` for system checks
- [ ] Using `availability()` for user checks
- [ ] Setting `GUEST_USERS = true` for guest actions
- [ ] Calling actions via `make()`, not direct instantiation
- [ ] Handling `ActionResponse` with `successful()` check
- [ ] Providing error messages in availability checks
- [ ] Using Artisan commands to create Features/Actions
- [ ] Features have no-arg constructors
- [ ] Action IDs use kebab-case (no periods)

