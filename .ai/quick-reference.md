# Lantern Quick Reference

## File Structure
```
app/Features/
├── AppFeatures.php              # Top-level feature
├── Todos/
│   ├── TodosFeature.php         # Feature grouping
│   ├── CreateTodoAction.php     # Action
│   ├── UpdateTodoAction.php     # Action
│   └── DeleteTodoAction.php     # Action
└── Users/
    └── UsersFeature.php
```

## Minimal Feature
```php
<?php
namespace App\Features\Todos;

use Lantern\Features\Feature;

class TodosFeature extends Feature
{
    const ACTIONS = [
        CreateTodoAction::class,
    ];
}
```

## Minimal Action
```php
<?php
namespace App\Features\Todos;

use Lantern\Features\Action;
use Lantern\Features\ActionResponse;

class CreateTodoAction extends Action
{
    public function perform(string $title): ActionResponse
    {
        // Your logic here
        return $this->success(['title' => $title]);
    }
}
```

## Action with Everything
```php
<?php
namespace App\Features\Todos;

use App\Models\Todo;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;
use Lantern\Features\ConstraintsBuilder;

class UpdateTodoAction extends Action
{
    const ID = 'todos-update';
    const GUEST_USERS = false;
    
    public function __construct(
        private TodoRepository $repo,
        private Todo $todo
    ) {}
    
    public function perform(string $title): ActionResponse
    {
        $updated = $this->repo->update($this->todo, compact('title'));
        return $this->success($updated);
    }
    
    public function prepare(): ActionResponse
    {
        return $this->success(['todo' => $this->todo]);
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('update', $this->todo);
        $builder->assertEqual(
            $builder->user()->id,
            $this->todo->user_id,
            'Not your todo'
        );
    }
    
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->classExists(Todo::class);
    }
}
```

## Calling Actions
```php
// Simple call
$response = CreateTodoAction::make($repo)->perform('Buy milk');

// With prepare
$data = UpdateTodoAction::make($repo, $todo)->prepare();

// Check availability first
$action = DeleteTodoAction::make($repo, $todo);
if ($action->available()) {
    $response = $action->perform();
}

// Handle response
if ($response->successful()) {
    $data = $response->data();
    $title = $response->data('title');
} else {
    $errors = $response->errors();
}
```

## AvailabilityBuilder Methods
```php
// Laravel Gates
$builder->userCan('create', Todo::class);
$builder->userCannot('delete', $todo);

// Assertions
$builder->assertTrue($condition, 'Message');
$builder->assertFalse($condition, 'Message');
$builder->assertNull($value, 'Message');
$builder->assertNotNull($value, 'Message');
$builder->assertEqual($a, $b, 'Message');
$builder->assertNotEqual($a, $b, 'Message');
$builder->assertEmpty($value, 'Message');
$builder->assertNotEmpty($value, 'Message');

// Access helpers
$user = $builder->user();
$action = $builder->action();
```

## ConstraintsBuilder Methods
```php
// Check class exists
$constraints->classExists(\ZipArchive::class);

// Check PHP extension
$constraints->extensionIsLoaded('gd');
$constraints->extensionIsLoaded('imagick');

// Check executable
$constraints->executableIsInstalled('convert');
$constraints->executableIsInstalled('pdftk');
```

## Registration (AppServiceProvider)
```php
use Lantern\Lantern;

public function boot()
{
    Lantern::register(AppFeatures::class);
    
    // Optional: Configure paths for executable checks
    Lantern::pathDirs(['/usr/local/bin/']);
}
```

## Artisan Commands
```bash
# Create Feature
php artisan lantern:make-feature Todos/TodosFeature
php artisan lantern:make-feature Admin/AdminFeature --stack=admin

# Create Action  
php artisan lantern:make-action Todos/CreateTodoAction
php artisan lantern:make-action Todos/UpdateTodoAction
```

## Common Patterns

### Controller Usage
```php
class TodoController extends Controller
{
    public function store(Request $request, TodoRepository $repo)
    {
        $response = CreateTodoAction::make($repo)
            ->perform($request->title, $request->description);
            
        if ($response->successful()) {
            return redirect()->route('todos.index')
                ->with('success', 'Todo created!');
        }
        
        return back()->withErrors($response->errors());
    }
    
    public function edit(Todo $todo, TodoRepository $repo)
    {
        $response = UpdateTodoAction::make($repo, $todo)->prepare();
        return view('todos.edit', $response->data());
    }
}
```

### Form Request Integration
```php
class TodoController extends Controller
{
    public function store(CreateTodoRequest $request, TodoRepository $repo)
    {
        $response = CreateTodoAction::make($repo)
            ->perform(...$request->validated());
            
        return $response->successful()
            ? redirect()->route('todos.index')
            : back()->withErrors($response->errors());
    }
}
```

### API Response
```php
class TodoApiController extends Controller
{
    public function store(Request $request, TodoRepository $repo)
    {
        $response = CreateTodoAction::make($repo)
            ->perform($request->title);
            
        return $response->successful()
            ? response()->json($response->data(), 201)
            : response()->json(['errors' => $response->errors()], 422);
    }
}
```

### Nested Features
```php
class AppFeatures extends Feature
{
    const FEATURES = [
        TodosFeature::class,
        UsersFeature::class,
    ];
}

class TodosFeature extends Feature
{
    const FEATURES = [
        TodoCategoriesFeature::class,
    ];
    
    const ACTIONS = [
        CreateTodoAction::class,
        UpdateTodoAction::class,
    ];
}
```

### Multi-Stack Setup
```php
// In AppServiceProvider
Lantern::register(
    AppFeatures::class,      // Main app features
    VendorFeatures::class    // Vendor package features
);

// VendorFeatures.php
class VendorFeatures extends Feature
{
    const STACK = 'vendor-name';
    const FEATURES = [/* ... */];
}
```

## Key Differences

### Constraints vs Availability
| Aspect | Constraints | Availability |
|--------|-------------|--------------|
| Purpose | System requirements | User authorization |
| Cached | Yes | No |
| Scope | Features & Actions | Actions only |
| Example | PHP extension loaded | User can edit todo |

### Feature vs Action
| Aspect | Feature | Action |
|--------|---------|--------|
| Purpose | Group/organize | Execute logic |
| Contains | Actions/Features | Business logic |
| Executable | No | Yes (via perform) |
| Availability | No | Yes |
| Constraints | Yes | Yes |

## Error Messages

**"Feature does not extend the base Feature class"**
- Your Feature must extend `Lantern\Features\Feature`

**"Feature does not contain any other Feature or Action"**
- Add at least one Action or Feature to ACTIONS or FEATURES constant

**"Action not available"**
- Check constraints are met
- Check availability conditions
- Verify user is authenticated (unless GUEST_USERS = true)

**"Action method missing"**
- Implement `perform()` method in your Action
- Or `prepare()` if calling `->prepare()`

**"Action ID is invalid"**
- Action IDs cannot contain periods (.)
- Use kebab-case: 'todos-create' not 'todos.create'

## Tips for AI Agents

1. **Namespace**: Always use `App\Features` (user preference)
2. **Commands**: Use `lantern:make-action` and `lantern:make-feature`
3. **Dependencies**: Inject via constructor, Laravel resolves them
4. **Responses**: Always return `ActionResponse` from `perform()`
5. **Availability**: Use for user checks, not system checks
6. **Constraints**: Use for system checks, not user checks
7. **IDs**: Auto-generated from class names (usually don't need custom)
8. **Registration**: Features must be registered in parent Feature's ACTIONS or FEATURES array

