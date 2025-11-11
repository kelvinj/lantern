# Lantern Architecture & Flow Diagrams

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     Laravel Application                      │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              AppServiceProvider                        │ │
│  │                                                        │ │
│  │  Lantern::register(AppFeatures::class)                │ │
│  └────────────────────────────────────────────────────────┘ │
│                            │                                 │
│                            ▼                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              Feature Registry                          │ │
│  │  (Tracks all Features and Actions)                     │ │
│  └────────────────────────────────────────────────────────┘ │
│                            │                                 │
│                            ▼                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              Feature Hierarchy                         │ │
│  │                                                        │ │
│  │  AppFeatures                                           │ │
│  │  ├── TodosFeature                                      │ │
│  │  │   ├── CreateTodoAction                             │ │
│  │  │   ├── UpdateTodoAction                             │ │
│  │  │   └── CategoriesFeature                            │ │
│  │  │       └── CreateCategoryAction                     │ │
│  │  └── UsersFeature                                      │ │
│  │      └── UpdateProfileAction                          │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Action Execution Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Controller/Code                           │
│                                                              │
│  CreateTodoAction::make($repo)->perform('Title')            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  Action::make()                              │
│                                                              │
│  1. Instantiate Action with dependencies                    │
│  2. Wrap in ActionProxy                                     │
│  3. Return ActionProxy                                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  ActionProxy                                 │
│                                                              │
│  Intercepts perform() call                                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check Feature Constraints                       │
│                                                              │
│  For each parent Feature:                                   │
│  ├── Check extensionIsLoaded()                              │
│  ├── Check executableIsInstalled()                          │
│  └── Check classExists()                                    │
│                                                              │
│  ✓ All pass → Continue                                      │
│  ✗ Any fail → Throw LanternException                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check Action Constraints                        │
│                                                              │
│  ├── Check extensionIsLoaded()                              │
│  ├── Check executableIsInstalled()                          │
│  └── Check classExists()                                    │
│                                                              │
│  ✓ All pass → Continue                                      │
│  ✗ Any fail → Throw LanternException                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check Action Availability                       │
│                                                              │
│  1. Check user is authenticated (unless GUEST_USERS)        │
│  2. Run availability() method:                              │
│     ├── userCan() checks                                    │
│     ├── assertTrue() checks                                 │
│     ├── assertEqual() checks                                │
│     └── Other assertions                                    │
│                                                              │
│  ✓ All pass → Continue                                      │
│  ✗ Any fail → Throw LanternException                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Execute Action::perform()                       │
│                                                              │
│  Run business logic                                         │
│  Return ActionResponse                                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                  ActionResponse                              │
│                                                              │
│  ├── successful() → true/false                              │
│  ├── data() → returned data                                 │
│  └── errors() → error messages                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Controller/Code                                 │
│                                                              │
│  Handle response:                                           │
│  if ($response->successful()) { ... }                       │
└─────────────────────────────────────────────────────────────┘
```

## Feature Registration Flow

```
┌─────────────────────────────────────────────────────────────┐
│              AppServiceProvider::boot()                      │
│                                                              │
│  Lantern::register(AppFeatures::class)                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              FeatureRegistry::register()                     │
│                                                              │
│  1. Reset registry                                          │
│  2. Validate Feature class                                  │
│  3. Process Feature                                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Process Feature                                 │
│                                                              │
│  1. Check Feature extends base Feature class                │
│  2. Check Feature has ACTIONS or FEATURES                   │
│  3. Register Feature ID                                     │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Process ACTIONS Array                           │
│                                                              │
│  For each Action class:                                     │
│  ├── Validate Action extends base Action class             │
│  ├── Validate Action ID (no periods)                       │
│  ├── Register Action → Feature mapping                     │
│  └── Register Action in Laravel Gate                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Process FEATURES Array                          │
│                                                              │
│  For each sub-Feature class:                                │
│  ├── Recursively process sub-Feature                       │
│  └── Register parent → child relationship                  │
└─────────────────────────────────────────────────────────────┘
```

## Class Hierarchy

```
┌─────────────────────────────────────────────────────────────┐
│                    Lantern\Features                          │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │                    Feature                             │ │
│  │  (abstract class)                                      │ │
│  │                                                        │ │
│  │  + const ID                                            │ │
│  │  + const STACK                                         │ │
│  │  + const DESCRIPTION                                   │ │
│  │  + const ACTIONS = []                                  │ │
│  │  + const FEATURES = []                                 │ │
│  │  + constraints(ConstraintsBuilder)                     │ │
│  │  + constraintsMet(): bool                              │ │
│  └────────────────────────────────────────────────────────┘ │
│                            △                                 │
│                            │                                 │
│                            │ extends                         │
│                            │                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              Your Feature Classes                      │ │
│  │                                                        │ │
│  │  - AppFeatures                                         │ │
│  │  - TodosFeature                                        │ │
│  │  - UsersFeature                                        │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │                    Action                              │ │
│  │  (abstract class)                                      │ │
│  │                                                        │ │
│  │  + const ID                                            │ │
│  │  + const GUEST_USERS                                   │ │
│  │  + perform(): ActionResponse                           │ │
│  │  + prepare(): ActionResponse (optional)                │ │
│  │  + availability(AvailabilityBuilder)                   │ │
│  │  + constraints(ConstraintsBuilder)                     │ │
│  │  + success($data): ActionResponse                      │ │
│  │  + failure($errors): ActionResponse                    │ │
│  │  + static make(...$deps): ActionProxy                  │ │
│  └────────────────────────────────────────────────────────┘ │
│                            △                                 │
│                            │                                 │
│                            │ extends                         │
│                            │                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              Your Action Classes                       │ │
│  │                                                        │ │
│  │  - CreateTodoAction                                    │ │
│  │  - UpdateTodoAction                                    │ │
│  │  - DeleteTodoAction                                    │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Data Flow: Controller to Action

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTP Request                              │
│                                                              │
│  POST /todos                                                │
│  { "title": "Buy milk", "description": "..." }              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    Route                                     │
│                                                              │
│  Route::post('/todos', [TodoController::class, 'store'])    │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              TodoController::store()                         │
│                                                              │
│  1. Validate request (Form Request)                         │
│  2. Resolve dependencies (TodoRepository)                   │
│  3. Call Action                                             │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              CreateTodoAction::make($repo)                   │
│                                                              │
│  Laravel Container resolves $repo                           │
│  Returns ActionProxy                                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              ActionProxy::perform($title, $desc)             │
│                                                              │
│  1. Check constraints                                       │
│  2. Check availability                                      │
│  3. Call Action::perform()                                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              CreateTodoAction::perform()                     │
│                                                              │
│  1. Execute business logic                                  │
│  2. Create Todo in database                                 │
│  3. Return ActionResponse                                   │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              ActionResponse                                  │
│                                                              │
│  success: true                                              │
│  data: { todo: Todo {...} }                                 │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              TodoController::store()                         │
│                                                              │
│  if ($response->successful()) {                             │
│      return redirect()->route('todos.index');               │
│  }                                                           │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                    HTTP Response                             │
│                                                              │
│  302 Redirect to /todos                                     │
└─────────────────────────────────────────────────────────────┘
```

## Constraint Checking Flow

```
┌─────────────────────────────────────────────────────────────┐
│              Action Execution Starts                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Get Parent Features                             │
│                                                              │
│  FeatureRegistry::featuresForAction($action)                │
│  Returns: [TodosFeature, AppFeatures]                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              For Each Parent Feature                         │
│                                                              │
│  Feature::constraintsMet()                                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check Cache                                     │
│                                                              │
│  Has this Feature been checked before?                      │
│  ├── YES → Return cached result                             │
│  └── NO → Continue                                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Build Constraints                               │
│                                                              │
│  $constraints = new ConstraintsBuilder()                    │
│  $feature->constraints($constraints)                        │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check Each Constraint                           │
│                                                              │
│  For each constraint:                                       │
│  ├── ClassExists::isMet()                                   │
│  ├── ExtensionIsLoaded::isMet()                             │
│  └── ExecutableIsInstalled::isMet()                         │
│                                                              │
│  ✓ All pass → Cache result (true)                           │
│  ✗ Any fail → Cache result (false)                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Return Result                                   │
│                                                              │
│  ✓ true → Continue to next Feature                          │
│  ✗ false → Action not available                             │
└─────────────────────────────────────────────────────────────┘
```

## Availability Checking Flow

```
┌─────────────────────────────────────────────────────────────┐
│              Constraints Passed                              │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Check User Authentication                       │
│                                                              │
│  Is user authenticated?                                     │
│  ├── NO → Is GUEST_USERS = true?                            │
│  │   ├── YES → Continue                                     │
│  │   └── NO → Deny (not authenticated)                     │
│  └── YES → Continue                                         │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Build Availability                              │
│                                                              │
│  $builder = new AvailabilityBuilder($action, $user)         │
│  $action->availability($builder)                            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Run Availability Checks                         │
│                                                              │
│  For each check:                                            │
│  ├── userCan() → Gate::check()                              │
│  ├── assertTrue() → Evaluate condition                      │
│  ├── assertEqual() → Compare values                         │
│  └── Other assertions                                       │
│                                                              │
│  Collect all failures                                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Return Result                                   │
│                                                              │
│  ✓ All passed → Response::allow()                           │
│  ✗ Any failed → Response::deny($messages)                   │
└─────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
app/
└── Features/
    ├── AppFeatures.php              # Top-level feature
    │
    ├── Todos/
    │   ├── TodosFeature.php         # Domain feature
    │   ├── CreateTodoAction.php     # Action
    │   ├── UpdateTodoAction.php     # Action
    │   ├── DeleteTodoAction.php     # Action
    │   │
    │   └── Categories/              # Sub-domain
    │       ├── CategoriesFeature.php
    │       └── CreateCategoryAction.php
    │
    └── Users/
        ├── UsersFeature.php
        └── UpdateProfileAction.php
```

## Dependency Injection Flow

```
┌─────────────────────────────────────────────────────────────┐
│              CreateTodoAction::make($repo, $user)            │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Action::make() Static Method                    │
│                                                              │
│  1. Receive dependencies as arguments                       │
│  2. Instantiate Action: new static(...$dependencies)        │
│  3. Wrap in ActionProxy                                     │
│  4. Return ActionProxy                                      │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              Action Constructor                              │
│                                                              │
│  public function __construct(                               │
│      private TodoRepository $repo,                          │
│      private User $user                                     │
│  ) {}                                                        │
│                                                              │
│  Dependencies stored as private properties                  │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              ActionProxy Created                             │
│                                                              │
│  new ActionProxy($action)                                   │
│  Holds reference to Action instance                         │
└─────────────────────────────────────────────────────────────┘
```

## Summary

**Key Takeaways:**

1. **Registration Flow:** AppServiceProvider → Lantern::register() → FeatureRegistry
2. **Execution Flow:** make() → ActionProxy → Constraints → Availability → perform()
3. **Constraint Checking:** Cached, system-level, Features + Actions
4. **Availability Checking:** Not cached, user-level, Actions only
5. **Dependency Injection:** Via make() arguments → Constructor → Private properties

