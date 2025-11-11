# Lantern Glossary & Terminology

## Core Concepts

### Action
**Definition:** An executable unit of business logic that performs a specific task.

**Characteristics:**
- Extends `Lantern\Features\Action`
- Must implement `perform()` method
- Returns `ActionResponse`
- Can have `prepare()` method (optional)
- Can have `availability()` checks
- Can have `constraints()`
- Called via `Action::make()->perform()`

**Example:**
```php
class CreateTodoAction extends Action
{
    public function perform(string $title): ActionResponse
    {
        // Business logic here
        return $this->success($todo);
    }
}
```

**Synonyms:** Task, Command, Operation
**Related:** Feature, ActionProxy, ActionResponse

---

### ActionProxy
**Definition:** A wrapper around an Action that enforces availability and constraint checks before execution.

**Characteristics:**
- Returned by `Action::make()`
- Forwards method calls to underlying Action
- Checks constraints and availability before `perform()`
- Throws `LanternException` if checks fail
- Transparent to the developer (acts like the Action)

**Example:**
```php
$proxy = CreateTodoAction::make($repo); // Returns ActionProxy
$response = $proxy->perform('Title');   // Proxy checks, then calls Action
```

**Related:** Action, LanternException

---

### ActionResponse
**Definition:** The return value from an Action's `perform()` or `prepare()` method, indicating success or failure.

**Characteristics:**
- Created via `$this->success()` or `$this->failure()`
- Has `successful()` and `unsuccessful()` methods
- Contains `data()` (on success) or `errors()` (on failure)
- Supports dot notation for nested data access

**Example:**
```php
// In Action
return $this->success(['todo' => $todo]);

// In Controller
if ($response->successful()) {
    $todo = $response->data('todo');
}
```

**Related:** Action

---

### Availability
**Definition:** User/context-specific authorization checks that determine if an Action can be performed.

**Characteristics:**
- Defined in `availability()` method
- Only for Actions (not Features)
- NOT cached (checked every time)
- Uses `AvailabilityBuilder`
- Runs after constraints
- Examples: user permissions, ownership, quotas

**Example:**
```php
protected function availability(AvailabilityBuilder $builder)
{
    $builder->userCan('create', Todo::class);
    $builder->assertTrue(
        $builder->user()->todos()->count() < 100,
        'Maximum todos reached'
    );
}
```

**Synonyms:** Authorization, Permission Check
**Related:** Constraints, AvailabilityBuilder

---

### AvailabilityBuilder
**Definition:** A fluent builder for defining availability checks in Actions.

**Characteristics:**
- Passed to `availability()` method
- Provides assertion methods
- Integrates with Laravel Gates
- Access to current user via `user()`
- Access to action via `action()`

**Methods:**
- `userCan()`, `userCannot()`
- `assertTrue()`, `assertFalse()`
- `assertEqual()`, `assertNotEqual()`
- `assertNull()`, `assertNotNull()`
- `assertEmpty()`, `assertNotEmpty()`

**Example:**
```php
$builder->userCan('update', $this->todo);
$builder->assertEqual($builder->user()->id, $this->todo->user_id);
```

**Related:** Availability

---

### Constraints
**Definition:** System-level requirements that must be met for a Feature or Action to be available.

**Characteristics:**
- Defined in `constraints()` method
- For Features AND Actions
- Cached (checked once per request)
- Uses `ConstraintsBuilder`
- Runs before availability
- Examples: PHP extensions, executables, classes

**Example:**
```php
protected function constraints(ConstraintsBuilder $constraints)
{
    $constraints->extensionIsLoaded('gd');
    $constraints->executableIsInstalled('convert');
    $constraints->classExists(\ZipArchive::class);
}
```

**Synonyms:** System Requirements, Prerequisites
**Related:** Availability, ConstraintsBuilder

---

### ConstraintsBuilder
**Definition:** A fluent builder for defining system-level constraints.

**Characteristics:**
- Passed to `constraints()` method
- Checks are cached
- Three main methods

**Methods:**
- `classExists($className)` - Check if class exists/loadable
- `extensionIsLoaded($extensionName)` - Check if PHP extension loaded
- `executableIsInstalled($executableName)` - Check if binary available

**Example:**
```php
$constraints->extensionIsLoaded('imagick');
$constraints->executableIsInstalled('pdftk');
```

**Related:** Constraints

---

### Feature
**Definition:** A container that groups related Actions and/or sub-Features.

**Characteristics:**
- Extends `Lantern\Features\Feature`
- Must contain Actions or Features (cannot be empty)
- Can have `constraints()` (but NOT `availability()`)
- Must have no-argument constructor
- Organized hierarchically

**Example:**
```php
class TodosFeature extends Feature
{
    const ACTIONS = [
        CreateTodoAction::class,
        UpdateTodoAction::class,
    ];
    
    const FEATURES = [
        CategoriesFeature::class,
    ];
}
```

**Synonyms:** Module, Domain, Feature Group
**Related:** Action, Stack

---

### Feature Registry
**Definition:** Internal Lantern component that tracks all registered Features and Actions.

**Characteristics:**
- Populated by `Lantern::register()`
- Maps Actions to their Features
- Validates Feature/Action structure
- Used by ActionProxy for constraint checking

**Usage:** Mostly internal, but can be accessed via `FeatureRegistry` class.

**Related:** Feature, Action

---

### GUEST_USERS
**Definition:** A constant on Actions that determines if the Action can be performed by unauthenticated users.

**Characteristics:**
- Default: `false` (requires authentication)
- Set to `true` for guest actions
- Examples: registration, login, public content

**Example:**
```php
class RegisterUserAction extends Action
{
    const GUEST_USERS = true;
    
    public function perform(string $email): ActionResponse
    {
        // Registration logic
    }
}
```

**Related:** Action, Availability

---

### ID (Action/Feature)
**Definition:** A unique identifier for an Action or Feature.

**Characteristics:**
- Auto-generated from class name (kebab-case)
- Can be customized via `const ID`
- Must be unique within a stack
- Cannot contain periods (.)
- Used in Laravel Gates

**Auto-Generation:**
- `CreateTodoAction` → `'create-todo'`
- `TodosFeature` → `'todos-feature'`

**Custom:**
```php
const ID = 'todos-create';
```

**Related:** Action, Feature, Stack

---

### LanternException
**Definition:** Exception thrown when an Action cannot be performed due to failed checks.

**Thrown When:**
- Feature constraints fail
- Action constraints fail
- Availability checks fail
- Action not registered
- Missing `perform()` method

**Example:**
```php
use Lantern\LanternException;

try {
    $response = CreateTodoAction::make($repo)->perform('Title');
} catch (LanternException $e) {
    // Handle unavailable action
}
```

**Related:** ActionProxy, Constraints, Availability

---

### perform()
**Definition:** Required method on Actions that contains the main business logic.

**Characteristics:**
- Must return `ActionResponse`
- Can accept parameters
- Called via ActionProxy
- Only runs if all checks pass

**Example:**
```php
public function perform(string $title, string $description): ActionResponse
{
    $todo = Todo::create(compact('title', 'description'));
    return $this->success($todo);
}
```

**Related:** Action, ActionResponse, prepare()

---

### prepare()
**Definition:** Optional method on Actions for fetching data before the action is performed.

**Characteristics:**
- Returns `ActionResponse`
- Typically used for form data
- Called via ActionProxy
- Subject to same checks as `perform()`

**Example:**
```php
public function prepare(): ActionResponse
{
    return $this->success([
        'todo' => $this->todo,
        'categories' => Category::all(),
    ]);
}

// Usage
$data = UpdateTodoAction::make($repo, $todo)->prepare();
```

**Related:** Action, ActionResponse, perform()

---

### Stack
**Definition:** A namespace for grouping top-level Features, useful for multi-tenant or modular applications.

**Characteristics:**
- Defined via `const STACK` on Features
- Default: `null` (main stack)
- Allows multiple feature hierarchies
- Each stack can have duplicate IDs (across stacks)

**Example:**
```php
class AppFeatures extends Feature
{
    const STACK = null; // Main stack
}

class VendorFeatures extends Feature
{
    const STACK = 'vendor-name'; // Separate stack
}

// Register both
Lantern::register(AppFeatures::class, VendorFeatures::class);
```

**Related:** Feature, ID

---

## Method Reference

### Action Methods

| Method | Required | Returns | Purpose |
|--------|----------|---------|---------|
| `perform()` | ✅ Yes | `ActionResponse` | Main business logic |
| `prepare()` | ❌ No | `ActionResponse` | Fetch data before action |
| `availability()` | ❌ No | `void` | Define user checks |
| `constraints()` | ❌ No | `void` | Define system checks |
| `success()` | N/A | `ActionResponse` | Return success response |
| `failure()` | N/A | `ActionResponse` | Return failure response |

### Feature Methods

| Method | Required | Returns | Purpose |
|--------|----------|---------|---------|
| `constraints()` | ❌ No | `void` | Define system checks |

### Static Methods

| Method | Class | Returns | Purpose |
|--------|-------|---------|---------|
| `make()` | Action | `ActionProxy` | Create action instance |
| `id()` | Action/Feature | `string` | Get action/feature ID |
| `register()` | Lantern | `void` | Register features |

---

## Constants Reference

### Action Constants

| Constant | Type | Default | Purpose |
|----------|------|---------|---------|
| `ID` | `string\|null` | Auto-generated | Custom identifier |
| `GUEST_USERS` | `bool` | `false` | Allow guest users |

### Feature Constants

| Constant | Type | Default | Purpose |
|----------|------|---------|---------|
| `ID` | `string\|null` | Auto-generated | Custom identifier |
| `STACK` | `string\|null` | `null` | Stack namespace |
| `DESCRIPTION` | `string\|null` | `null` | Human description |
| `ACTIONS` | `array` | `[]` | Action classes |
| `FEATURES` | `array` | `[]` | Sub-feature classes |

---

## Comparison Table

### Constraints vs Availability

| Aspect | Constraints | Availability |
|--------|-------------|--------------|
| **Purpose** | System requirements | User authorization |
| **Scope** | Features + Actions | Actions only |
| **Cached** | Yes | No |
| **When Checked** | Before availability | After constraints |
| **Examples** | PHP extensions, executables | Permissions, ownership |
| **Builder** | `ConstraintsBuilder` | `AvailabilityBuilder` |

### Feature vs Action

| Aspect | Feature | Action |
|--------|---------|--------|
| **Purpose** | Organize/group | Execute logic |
| **Executable** | No | Yes |
| **Contains** | Actions/Features | Business logic |
| **Has perform()** | No | Yes |
| **Has prepare()** | No | Optional |
| **Has availability()** | No | Yes |
| **Has constraints()** | Yes | Yes |
| **Constructor** | No-arg only | Any dependencies |

### success() vs failure()

| Aspect | success() | failure() |
|--------|-----------|-----------|
| **Returns** | `ActionResponse` | `ActionResponse` |
| **Indicates** | Success | Failure |
| **Parameters** | `$data` (optional) | `$errors`, `$data` |
| **Usage** | `$this->success($todo)` | `$this->failure(['error'])` |
| **Check with** | `$response->successful()` | `$response->unsuccessful()` |

---

## Acronyms & Abbreviations

- **CRUD** - Create, Read, Update, Delete
- **DI** - Dependency Injection
- **IoC** - Inversion of Control (Laravel's container)

---

## Common Phrases

**"Action is not available"**
- Means: Constraints or availability checks failed
- Fix: Check constraints, availability, and authentication

**"Feature must contain Actions or Features"**
- Means: Empty Feature (no ACTIONS or FEATURES)
- Fix: Add at least one Action or Feature

**"Action not registered"**
- Means: Action not in any Feature's ACTIONS array
- Fix: Add Action to parent Feature

**"Constraints failed"**
- Means: System requirements not met
- Fix: Install missing extension/executable/class

**"User not logged in"**
- Means: Action requires auth but user is guest
- Fix: Set GUEST_USERS = true OR require authentication

---

## Naming Conventions

### Classes
- **Actions:** `{Verb}{Noun}Action` (e.g., `CreateTodoAction`)
- **Features:** `{Noun}Feature` or `{Noun}sFeature` (e.g., `TodosFeature`)

### IDs
- **Format:** kebab-case
- **Example:** `create-todo`, `todos-feature`
- **Avoid:** periods, camelCase, snake_case

### Namespaces
- **Base:** `App\Features`
- **Domain:** `App\Features\{Domain}`
- **Example:** `App\Features\Todos\CreateTodoAction`

---

## File Locations

| Item | Default Location |
|------|------------------|
| Features | `app/Features/` |
| Actions | `app/Features/{Domain}/` |
| Config | `config/lantern.php` |
| Commands | `php artisan lantern:make-*` |

---

## Related Laravel Concepts

| Lantern | Laravel Equivalent | Notes |
|---------|-------------------|-------|
| Action | Command/Job | But with authorization built-in |
| Availability | Gate/Policy | Integrated into Actions |
| Constraints | Service Provider checks | But cached and declarative |
| ActionResponse | JsonResponse | But framework-agnostic |
| Feature | Service Provider | But for organization, not registration |

