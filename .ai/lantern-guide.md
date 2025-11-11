# Lantern Framework - AI Agent Guide

## Overview
Lantern is a Laravel package that provides a declarative, feature-based architecture for organizing domain logic. It helps structure applications around **Features** and **Actions** with built-in authorization and system constraints.

**Key Concepts:**
- **Features**: Containers that group related Actions and/or sub-Features
- **Actions**: Executable units of business logic with authorization and validation
- **Constraints**: System-level requirements (e.g., PHP extensions, executables, classes)
- **Availability**: User/context-specific authorization checks
- **ActionProxy**: Wrapper that enforces availability checks before executing Actions

## Core Architecture

### 1. Features (`Lantern\Features\Feature`)

Features organize your domain model into logical groups. They MUST contain either Actions or other Features.

```php
<?php
namespace App\Features\Todos;

use Lantern\Features\Feature;
use Lantern\Features\ConstraintsBuilder;

class TodosFeature extends Feature
{
    // Optional: Custom ID (defaults to snake-case class name)
    const ID = 'todos';
    
    // Optional: Stack name for multi-tenant/modular apps
    const STACK = null;
    
    // Optional: Human-readable description
    const DESCRIPTION = 'Manage todo items';
    
    // Actions provided by this feature
    const ACTIONS = [
        CreateTodoAction::class,
        UpdateTodoAction::class,
        DeleteTodoAction::class,
    ];
    
    // Sub-features (optional)
    const FEATURES = [
        TodoCategoriesFeature::class,
    ];
    
    // Optional: System-level constraints
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->classExists(\ZipArchive::class);
        $constraints->extensionIsLoaded('gd');
        $constraints->executableIsInstalled('convert');
    }
}
```

**Important Rules:**
- Features MUST have at least one Action or Feature in their constants
- Features CANNOT have availability checks (only constraints)
- Feature constraints are checked when determining Action availability
- Features must have a no-argument constructor

### 2. Actions (`Lantern\Features\Action`)

Actions are the executable units of business logic. They MUST implement a `perform()` method.

```php
<?php
namespace App\Features\Todos;

use App\Models\Todo;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;
use Lantern\Features\ConstraintsBuilder;

class CreateTodoAction extends Action
{
    // Optional: Custom ID (defaults to kebab-case class name without "Action" suffix)
    const ID = 'todos-create';
    
    // Set to true if action is for guest users (default: false)
    const GUEST_USERS = false;
    
    // Constructor: Inject dependencies via Laravel's container
    public function __construct(
        private TodoRepository $repository
    ) {}
    
    // Required: Main business logic
    public function perform(string $title, string $description): ActionResponse
    {
        $todo = $this->repository->create([
            'title' => $title,
            'description' => $description,
        ]);
        
        return $this->success($todo);
    }
    
    // Optional: Prepare data (e.g., for forms)
    public function prepare(): ActionResponse
    {
        $categories = Category::all();
        return $this->success(compact('categories'));
    }
    
    // Optional: User/context authorization
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('create', Todo::class);
        $builder->assertTrue(
            $builder->user()->todos()->count() < 100,
            'Maximum todo limit reached'
        );
    }
    
    // Optional: System-level constraints
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->classExists(Todo::class);
    }
}
```

**Action Response Methods:**
- `$this->success($data = null)` - Return successful response
- `$this->failure($errors = null, array $data = [])` - Return failure response

### 3. Calling Actions

Actions are called through the `make()` static method, which returns an `ActionProxy`:

```php
// Basic usage
$response = CreateTodoAction::make($repository)->perform('Title', 'Description');

// With prepare() method
$prepareResponse = CreateTodoAction::make($repository)->prepare();

// Check availability manually
$proxy = CreateTodoAction::make($repository);
if ($proxy->available()) {
    $response = $proxy->perform('Title', 'Description');
}

// Access response data
if ($response->successful()) {
    $todo = $response->data(); // Get all data
    $title = $response->data('title'); // Get specific key with dot notation
} else {
    $errors = $response->errors();
}
```

**Important:** The `ActionProxy` automatically checks:
1. Feature constraints (system-level)
2. Action constraints (system-level)
3. Action availability (user/context-level)

If any check fails, a `LanternException` is thrown before `perform()` executes.

### 4. Availability Checks (`AvailabilityBuilder`)

Availability checks are user/context-specific and run on every request (not cached).

**Available Methods:**
```php
protected function availability(AvailabilityBuilder $builder)
{
    // Laravel Gate integration
    $builder->userCan('create', Todo::class);
    $builder->userCannot('delete', $this->todo);
    
    // Assertions
    $builder->assertTrue($condition, 'Error message');
    $builder->assertFalse($condition, 'Error message');
    $builder->assertNull($value, 'Error message');
    $builder->assertNotNull($value, 'Error message');
    $builder->assertEqual($value1, $value2, 'Error message');
    $builder->assertNotEqual($value1, $value2, 'Error message');
    $builder->assertEmpty($value, 'Error message');
    $builder->assertNotEmpty($value, 'Error message');
    
    // Access current user
    $user = $builder->user();
    
    // Access the action instance
    $action = $builder->action();
}
```

### 5. Constraints (`ConstraintsBuilder`)

Constraints are system-level requirements that are cached after first check.

**Available Methods:**
```php
protected function constraints(ConstraintsBuilder $constraints)
{
    // Check if class exists (uses autoloading)
    $constraints->classExists(\ZipArchive::class);
    
    // Check if PHP extension is loaded
    $constraints->extensionIsLoaded('gd');
    $constraints->extensionIsLoaded('imagick');
    
    // Check if executable is installed on system
    $constraints->executableIsInstalled('convert');
    $constraints->executableIsInstalled('pdftk');
}
```

**Key Differences:**
- **Constraints**: System-level, cached, checked for both Features and Actions
- **Availability**: User/context-level, not cached, only for Actions

## Registration & Setup

Register your top-level Feature(s) in `AppServiceProvider`:

```php
use Lantern\Lantern;

public function boot()
{
    // Single feature stack
    Lantern::register(AppFeatures::class);
    
    // Multiple feature stacks (e.g., app + vendor packages)
    Lantern::register(AppFeatures::class, VendorFeatures::class);
    
    // Configure executable search paths (optional)
    Lantern::pathDirs([
        '/usr/local/bin/',
        '/opt/homebrew/bin/',
    ]);
}
```

## Artisan Commands

```bash
# Create a new Feature
php artisan lantern:make-feature Todos/TodosFeature
php artisan lantern:make-feature Todos/TodosFeature --stack=admin

# Create a new Action
php artisan lantern:make-action Todos/CreateTodoAction
php artisan lantern:make-action Todos/UpdateTodoAction
```

**Default Namespace:** `App\Features` (configurable in `config/lantern.php`)

## Configuration

Publish and customize config:

```bash
php artisan vendor:publish --tag=lantern-config
```

**config/lantern.php:**
```php
return [
    'features_path' => app_path('Features'), // Where Features/Actions are created
];
```

## Common Patterns

### Pattern 1: Hierarchical Features
```php
class AppFeatures extends Feature
{
    const FEATURES = [
        TodosFeature::class,
        UsersFeature::class,
        ReportsFeature::class,
    ];
}
```

### Pattern 2: Action with Dependencies
```php
class UpdateTodoAction extends Action
{
    public function __construct(
        private TodoRepository $repository,
        private Todo $todo  // Injected from route model binding
    ) {}
    
    public function perform(string $title): ActionResponse
    {
        $this->repository->update($this->todo, ['title' => $title]);
        return $this->success($this->todo->fresh());
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        // Ensure user owns the todo
        $builder->assertEqual(
            $builder->user()->id,
            $this->todo->user_id,
            'You do not own this todo'
        );
    }
}

// Usage in controller
UpdateTodoAction::make($repository, $todo)->perform($request->title);
```

### Pattern 3: Guest Actions
```php
class RegisterUserAction extends Action
{
    const GUEST_USERS = true; // Allow for non-authenticated users
    
    public function perform(array $data): ActionResponse
    {
        $user = User::create($data);
        return $this->success($user);
    }
}
```

## Best Practices for AI Agents

1. **Always use `App\Features` namespace** (not `src/`) per user preference
2. **Use Artisan commands** to create Features/Actions (ensures proper structure)
3. **Check constraints vs availability**: Use constraints for system requirements, availability for user authorization
4. **Return ActionResponse**: Always use `$this->success()` or `$this->failure()` in `perform()`
5. **Inject dependencies**: Use constructor injection, resolved by Laravel's container
6. **Feature hierarchy**: Organize related Actions under Features, Features under parent Features
7. **ID naming**: Let Lantern auto-generate IDs unless you need custom ones
8. **Error messages**: Provide clear messages in availability checks for better UX

## Troubleshooting

**Action not available:**
- Check Feature constraints are met
- Check Action constraints are met
- Check availability() conditions
- Verify user is authenticated (unless GUEST_USERS = true)

**LanternException thrown:**
- Action/Feature not registered in parent Feature
- Constraints failed
- Availability checks failed
- Missing `perform()` method

**Feature registration errors:**
- Feature must extend `Lantern\Features\Feature`
- Feature must have ACTIONS or FEATURES (cannot be empty)
- Feature IDs must be unique within a stack

