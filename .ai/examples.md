# Lantern Examples

## Example 1: Simple Todo CRUD

### Feature Definition
```php
<?php
namespace App\Features\Todos;

use Lantern\Features\Feature;

class TodosFeature extends Feature
{
    const ID = 'todos';
    const DESCRIPTION = 'Manage todo items';
    
    const ACTIONS = [
        ListTodosAction::class,
        CreateTodoAction::class,
        UpdateTodoAction::class,
        DeleteTodoAction::class,
        ToggleTodoAction::class,
    ];
}
```

### Create Action
```php
<?php
namespace App\Features\Todos;

use App\Models\Todo;
use App\Repositories\TodoRepository;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;

class CreateTodoAction extends Action
{
    const ID = 'todos-create';
    
    public function __construct(
        private TodoRepository $repository
    ) {}
    
    public function perform(string $title, ?string $description = null): ActionResponse
    {
        $todo = $this->repository->create([
            'user_id' => auth()->id(),
            'title' => $title,
            'description' => $description,
            'completed' => false,
        ]);
        
        return $this->success($todo);
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('create', Todo::class);
        
        // Limit: max 100 todos per user
        $builder->assertTrue(
            $builder->user()->todos()->count() < 100,
            'You have reached the maximum number of todos (100)'
        );
    }
}
```

### Update Action
```php
<?php
namespace App\Features\Todos;

use App\Models\Todo;
use App\Repositories\TodoRepository;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;

class UpdateTodoAction extends Action
{
    const ID = 'todos-update';
    
    public function __construct(
        private TodoRepository $repository,
        private Todo $todo
    ) {}
    
    public function prepare(): ActionResponse
    {
        return $this->success([
            'todo' => $this->todo,
        ]);
    }
    
    public function perform(string $title, ?string $description = null): ActionResponse
    {
        $updated = $this->repository->update($this->todo, [
            'title' => $title,
            'description' => $description,
        ]);
        
        return $this->success($updated);
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('update', $this->todo);
        
        // User must own the todo
        $builder->assertEqual(
            $builder->user()->id,
            $this->todo->user_id,
            'You can only update your own todos'
        );
        
        // Cannot update completed todos
        $builder->assertFalse(
            $this->todo->completed,
            'Cannot update completed todos'
        );
    }
}
```

### Controller Integration
```php
<?php
namespace App\Http\Controllers;

use App\Features\Todos\CreateTodoAction;
use App\Features\Todos\UpdateTodoAction;
use App\Features\Todos\DeleteTodoAction;
use App\Http\Requests\CreateTodoRequest;
use App\Http\Requests\UpdateTodoRequest;
use App\Models\Todo;
use App\Repositories\TodoRepository;

class TodoController extends Controller
{
    public function __construct(
        private TodoRepository $repository
    ) {}
    
    public function store(CreateTodoRequest $request)
    {
        $response = CreateTodoAction::make($this->repository)
            ->perform(
                $request->validated('title'),
                $request->validated('description')
            );
        
        if ($response->successful()) {
            return redirect()
                ->route('todos.index')
                ->with('success', 'Todo created successfully!');
        }
        
        return back()
            ->withErrors($response->errors())
            ->withInput();
    }
    
    public function edit(Todo $todo)
    {
        $response = UpdateTodoAction::make($this->repository, $todo)
            ->prepare();
        
        return view('todos.edit', $response->data());
    }
    
    public function update(UpdateTodoRequest $request, Todo $todo)
    {
        $response = UpdateTodoAction::make($this->repository, $todo)
            ->perform(
                $request->validated('title'),
                $request->validated('description')
            );
        
        if ($response->successful()) {
            return redirect()
                ->route('todos.show', $todo)
                ->with('success', 'Todo updated successfully!');
        }
        
        return back()
            ->withErrors($response->errors())
            ->withInput();
    }
    
    public function destroy(Todo $todo)
    {
        $response = DeleteTodoAction::make($this->repository, $todo)
            ->perform();
        
        if ($response->successful()) {
            return redirect()
                ->route('todos.index')
                ->with('success', 'Todo deleted successfully!');
        }
        
        return back()
            ->withErrors($response->errors());
    }
}
```

## Example 2: PDF Report Generation with Constraints

### Feature with Constraints
```php
<?php
namespace App\Features\Reports;

use Lantern\Features\Feature;
use Lantern\Features\ConstraintsBuilder;

class ReportsFeature extends Feature
{
    const ID = 'reports';
    const DESCRIPTION = 'Generate and manage reports';
    
    const ACTIONS = [
        GeneratePdfReportAction::class,
        GenerateExcelReportAction::class,
    ];
    
    protected function constraints(ConstraintsBuilder $constraints)
    {
        // Require imagick extension for PDF manipulation
        $constraints->extensionIsLoaded('imagick');
        
        // Require wkhtmltopdf for PDF generation
        $constraints->executableIsInstalled('wkhtmltopdf');
    }
}
```

### Action with Constraints
```php
<?php
namespace App\Features\Reports;

use App\Services\PdfGenerator;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;
use Lantern\Features\ConstraintsBuilder;

class GeneratePdfReportAction extends Action
{
    const ID = 'reports-generate-pdf';
    
    public function __construct(
        private PdfGenerator $generator
    ) {}
    
    public function perform(string $reportType, array $filters = []): ActionResponse
    {
        try {
            $pdf = $this->generator->generate($reportType, $filters);
            
            return $this->success([
                'pdf_path' => $pdf->getPath(),
                'pdf_url' => $pdf->getUrl(),
            ]);
        } catch (\Exception $e) {
            return $this->failure([
                'generation' => 'Failed to generate PDF: ' . $e->getMessage()
            ]);
        }
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        $builder->userCan('generate-reports');
        
        // Premium feature
        $builder->assertTrue(
            $builder->user()->isPremium(),
            'PDF reports are only available for premium users'
        );
    }
    
    protected function constraints(ConstraintsBuilder $constraints)
    {
        $constraints->classExists(PdfGenerator::class);
        $constraints->executableIsInstalled('pdftk');
    }
}
```

## Example 3: Multi-Step Wizard Action

```php
<?php
namespace App\Features\Onboarding;

use App\Models\User;
use App\Services\OnboardingService;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;

class CompleteOnboardingAction extends Action
{
    const ID = 'onboarding-complete';
    
    public function __construct(
        private OnboardingService $service
    ) {}
    
    public function prepare(): ActionResponse
    {
        $user = auth()->user();
        
        return $this->success([
            'user' => $user,
            'completed_steps' => $user->onboarding_steps ?? [],
            'available_roles' => ['developer', 'designer', 'manager'],
        ]);
    }
    
    public function perform(
        string $role,
        array $interests,
        bool $newsletter = false
    ): ActionResponse {
        $user = auth()->user();
        
        $this->service->complete($user, [
            'role' => $role,
            'interests' => $interests,
            'newsletter' => $newsletter,
        ]);
        
        return $this->success([
            'user' => $user->fresh(),
            'redirect_url' => route('dashboard'),
        ]);
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        $user = $builder->user();
        
        // Must not have completed onboarding already
        $builder->assertFalse(
            $user->onboarding_completed,
            'You have already completed onboarding'
        );
        
        // Must have verified email
        $builder->assertNotNull(
            $user->email_verified_at,
            'Please verify your email before completing onboarding'
        );
    }
}
```

## Example 4: Guest User Action (Registration)

```php
<?php
namespace App\Features\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;

class RegisterUserAction extends Action
{
    const ID = 'auth-register';
    const GUEST_USERS = true; // Important: Allow guest users
    
    public function perform(
        string $name,
        string $email,
        string $password
    ): ActionResponse {
        // Check if email already exists
        if (User::where('email', $email)->exists()) {
            return $this->failure([
                'email' => 'This email is already registered'
            ]);
        }
        
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        
        // Log the user in
        auth()->login($user);
        
        return $this->success([
            'user' => $user,
            'redirect_url' => route('dashboard'),
        ]);
    }
}
```

## Example 5: Hierarchical Features

```php
<?php
namespace App\Features;

use Lantern\Features\Feature;

class AppFeatures extends Feature
{
    const ID = 'app';
    const DESCRIPTION = 'Main application features';
    
    const FEATURES = [
        Todos\TodosFeature::class,
        Users\UsersFeature::class,
        Reports\ReportsFeature::class,
        Auth\AuthFeature::class,
    ];
}
```

```php
<?php
namespace App\Features\Todos;

use Lantern\Features\Feature;

class TodosFeature extends Feature
{
    const ID = 'todos';
    
    const FEATURES = [
        Categories\CategoriesFeature::class,
        Tags\TagsFeature::class,
    ];
    
    const ACTIONS = [
        CreateTodoAction::class,
        UpdateTodoAction::class,
        DeleteTodoAction::class,
    ];
}
```

## Example 6: API Controller

```php
<?php
namespace App\Http\Controllers\Api;

use App\Features\Todos\CreateTodoAction;
use App\Features\Todos\UpdateTodoAction;
use App\Http\Requests\CreateTodoRequest;
use App\Models\Todo;
use App\Repositories\TodoRepository;
use Illuminate\Http\JsonResponse;

class TodoApiController extends Controller
{
    public function __construct(
        private TodoRepository $repository
    ) {}
    
    public function store(CreateTodoRequest $request): JsonResponse
    {
        $response = CreateTodoAction::make($this->repository)
            ->perform(
                $request->validated('title'),
                $request->validated('description')
            );
        
        if ($response->successful()) {
            return response()->json([
                'data' => $response->data(),
                'message' => 'Todo created successfully',
            ], 201);
        }
        
        return response()->json([
            'errors' => $response->errors(),
        ], 422);
    }
    
    public function update(UpdateTodoRequest $request, Todo $todo): JsonResponse
    {
        $response = UpdateTodoAction::make($this->repository, $todo)
            ->perform(
                $request->validated('title'),
                $request->validated('description')
            );
        
        if ($response->successful()) {
            return response()->json([
                'data' => $response->data(),
                'message' => 'Todo updated successfully',
            ]);
        }
        
        return response()->json([
            'errors' => $response->errors(),
        ], 422);
    }
}
```

## Example 7: Complex Availability Checks

```php
<?php
namespace App\Features\Projects;

use App\Models\Project;
use App\Repositories\ProjectRepository;
use Lantern\Features\Action;
use Lantern\Features\ActionResponse;
use Lantern\Features\AvailabilityBuilder;

class ArchiveProjectAction extends Action
{
    const ID = 'projects-archive';
    
    public function __construct(
        private ProjectRepository $repository,
        private Project $project
    ) {}
    
    public function perform(): ActionResponse
    {
        $this->repository->archive($this->project);
        
        return $this->success([
            'project' => $this->project->fresh(),
        ]);
    }
    
    protected function availability(AvailabilityBuilder $builder)
    {
        $user = $builder->user();
        
        // Must have permission
        $builder->userCan('archive', $this->project);
        
        // Must be project owner or admin
        $isOwner = $this->project->user_id === $user->id;
        $isAdmin = $user->hasRole('admin');
        $builder->assertTrue(
            $isOwner || $isAdmin,
            'Only project owners or admins can archive projects'
        );
        
        // Project must not already be archived
        $builder->assertFalse(
            $this->project->archived,
            'Project is already archived'
        );
        
        // Project must not have active tasks
        $builder->assertEmpty(
            $this->project->tasks()->active()->get(),
            'Cannot archive project with active tasks'
        );
        
        // Must not have pending invoices
        $builder->assertEqual(
            0,
            $this->project->invoices()->pending()->count(),
            'Cannot archive project with pending invoices'
        );
    }
}
```

