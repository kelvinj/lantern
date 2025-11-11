# Lantern Decision Trees

## Decision Tree 1: Should I Create a Feature or an Action?

```
START: I need to add functionality to the app
│
├─ Does this involve executing business logic?
│  │
│  ├─ YES → Does it need to be called/executed directly?
│  │  │
│  │  ├─ YES → Create an ACTION
│  │  │         Examples: CreateTodo, SendEmail, GenerateReport
│  │  │
│  │  └─ NO → Is it just for organizing other actions?
│  │           │
│  │           ├─ YES → Create a FEATURE
│  │           │         Examples: TodosFeature, ReportsFeature
│  │           │
│  │           └─ NO → Reconsider if you need Lantern for this
│  │
│  └─ NO → Are you grouping related functionality?
│     │
│     ├─ YES → Create a FEATURE
│     │         Examples: AuthFeature, AdminFeature
│     │
│     └─ NO → You probably don't need Lantern for this
```

**Quick Rules:**
- **Action** = Executable business logic (has `perform()` method)
- **Feature** = Container/organizer (has `ACTIONS` or `FEATURES` arrays)
- **Both** can have constraints, only **Actions** can have availability

---

## Decision Tree 2: Constraints vs Availability

```
START: I need to add a check before an action runs
│
├─ Is this check about the SYSTEM/ENVIRONMENT?
│  │
│  ├─ YES → Examples:
│  │         - PHP extension installed?
│  │         - Binary/executable available?
│  │         - Class exists?
│  │         - Specific OS?
│  │         │
│  │         └─ Use CONSTRAINTS
│  │            - Add to constraints() method
│  │            - Will be cached
│  │            - Can be on Feature or Action
│  │
│  └─ NO → Is this check about the USER/CONTEXT?
│     │
│     ├─ YES → Examples:
│     │         - User has permission?
│     │         - User owns resource?
│     │         - User's quota not exceeded?
│     │         - Resource in correct state?
│     │         │
│     │         └─ Use AVAILABILITY
│     │            - Add to availability() method
│     │            - NOT cached (checked every time)
│     │            - Only on Actions (not Features)
│     │
│     └─ NO → Is this validation of input data?
│              │
│              ├─ YES → Use Laravel Form Request validation
│              │         (Not Lantern's responsibility)
│              │
│              └─ NO → Reconsider if this check is necessary
```

**Quick Rules:**
- **Constraints** = System requirements (cached, Features + Actions)
- **Availability** = User authorization (not cached, Actions only)
- **Validation** = Input data (use Form Requests, not Lantern)

---

## Decision Tree 3: Where Should This Action Go?

```
START: I created an Action, where should I put it?
│
├─ Is there an existing Feature for this domain?
│  │
│  ├─ YES → Does this Action fit that Feature's purpose?
│  │  │
│  │  ├─ YES → Add to that Feature's ACTIONS array
│  │  │         Example: CreateTodoAction → TodosFeature
│  │  │
│  │  └─ NO → Is this a sub-domain of that Feature?
│  │     │
│  │     ├─ YES → Create a sub-Feature
│  │     │         Example: TodoCategoriesFeature under TodosFeature
│  │     │
│  │     └─ NO → Create a new top-level Feature
│  │
│  └─ NO → Create a new Feature for this domain
│           Example: Create ReportsFeature for GenerateReportAction
```

**Example Structure:**
```
AppFeatures
├── TodosFeature
│   ├── CreateTodoAction
│   ├── UpdateTodoAction
│   └── Categories (sub-feature)
│       ├── CreateCategoryAction
│       └── AssignCategoryAction
└── ReportsFeature
    ├── GeneratePdfReportAction
    └── GenerateExcelReportAction
```

---

## Decision Tree 4: How Should I Structure My Features?

```
START: Planning feature hierarchy
│
├─ Do I have multiple distinct domains?
│  │
│  ├─ YES → Create a top-level Feature for each domain
│  │         │
│  │         ├─ AppFeatures (top-level)
│  │         │   ├── TodosFeature
│  │         │   ├── UsersFeature
│  │         │   └── ReportsFeature
│  │         │
│  │         └─ Register AppFeatures in AppServiceProvider
│  │
│  └─ NO → Do I have sub-domains within a domain?
│     │
│     ├─ YES → Create nested Features
│     │         │
│     │         ├─ TodosFeature
│     │         │   ├── ACTIONS: [CreateTodo, UpdateTodo]
│     │         │   └── FEATURES: [CategoriesFeature, TagsFeature]
│     │         │
│     │         └─ Each sub-feature has its own Actions
│     │
│     └─ NO → Single Feature with Actions
│               │
│               └─ TodosFeature
│                   └── ACTIONS: [CreateTodo, UpdateTodo, DeleteTodo]
```

**Depth Guidelines:**
- **Flat** (1-2 levels): Small apps, simple domains
- **Nested** (3-4 levels): Large apps, complex domains
- **Avoid** (5+ levels): Too complex, reconsider structure

---

## Decision Tree 5: Should This Be a Guest Action?

```
START: Creating an Action
│
├─ Can this Action be performed without authentication?
│  │
│  ├─ YES → Examples:
│  │         - User registration
│  │         - Login
│  │         - Password reset request
│  │         - Public content viewing
│  │         │
│  │         └─ Set GUEST_USERS = true
│  │
│  └─ NO → Requires authenticated user?
│     │
│     ├─ YES → Leave GUEST_USERS = false (default)
│     │         Action will require auth()->user()
│     │
│     └─ UNSURE → Ask yourself:
│               - Would a logged-out user need this?
│               - Is this a public feature?
│               │
│               ├─ YES → GUEST_USERS = true
│               └─ NO → GUEST_USERS = false
```

**Important:** If `GUEST_USERS = false` (default) and no user is authenticated, the action will be denied.

---

## Decision Tree 6: What Should perform() Return?

```
START: Writing perform() method
│
├─ Did the operation succeed?
│  │
│  ├─ YES → Do you have data to return?
│  │  │
│  │  ├─ YES → return $this->success($data);
│  │  │         Example: return $this->success($todo);
│  │  │
│  │  └─ NO → return $this->success();
│  │           Example: return $this->success();
│  │
│  └─ NO → Do you have specific errors?
│     │
│     ├─ YES → return $this->failure($errors, $data);
│     │         Example: return $this->failure(['email' => 'Already exists']);
│     │
│     └─ NO → return $this->failure();
│               Example: return $this->failure();
```

**Never:**
- Return `void`
- Return `null`
- Return raw data
- Throw exceptions for business logic failures

**Always:**
- Return `ActionResponse`
- Use `$this->success()` or `$this->failure()`

---

## Decision Tree 7: Troubleshooting "Action Not Available"

```
START: Action is not available / LanternException thrown
│
├─ Check: Is the user authenticated?
│  │
│  ├─ NO → Is GUEST_USERS = true?
│  │  │
│  │  ├─ NO → Set GUEST_USERS = true OR require authentication
│  │  └─ YES → Continue to next check
│  │
│  └─ YES → Continue to next check
│
├─ Check: Are Feature constraints met?
│  │
│  ├─ NO → Fix system requirements:
│  │         - Install missing PHP extension
│  │         - Install missing executable
│  │         - Add missing class
│  │
│  └─ YES → Continue to next check
│
├─ Check: Are Action constraints met?
│  │
│  ├─ NO → Fix system requirements (same as above)
│  │
│  └─ YES → Continue to next check
│
├─ Check: Are availability conditions met?
│  │
│  ├─ NO → Check availability() method:
│  │         - Does user have required permission?
│  │         - Does user own the resource?
│  │         - Are custom conditions satisfied?
│  │         │
│  │         └─ Fix the failing condition OR
│  │            Adjust availability logic
│  │
│  └─ YES → Continue to next check
│
└─ Check: Is Action registered?
   │
   ├─ NO → Add Action to Feature's ACTIONS array
   │
   └─ YES → Check if Feature is registered
      │
      ├─ NO → Add Feature to parent's FEATURES array
      │         OR register in AppServiceProvider
      │
      └─ YES → Check Lantern::register() in AppServiceProvider
```

---

## Decision Tree 8: How to Handle ActionResponse in Controller

```
START: Got ActionResponse from Action
│
├─ Is this a web request (returning view/redirect)?
│  │
│  ├─ YES → Check if successful
│  │  │
│  │  ├─ YES → Redirect with success message
│  │  │         return redirect()->route('...')
│  │  │             ->with('success', 'Done!');
│  │  │
│  │  └─ NO → Redirect back with errors
│  │           return back()
│  │               ->withErrors($response->errors())
│  │               ->withInput();
│  │
│  └─ NO → Is this an API request (returning JSON)?
│     │
│     ├─ YES → Check if successful
│     │  │
│     │  ├─ YES → Return JSON with data
│     │  │         return response()->json([
│     │  │             'data' => $response->data()
│     │  │         ], 200);
│     │  │
│     │  └─ NO → Return JSON with errors
│     │           return response()->json([
│     │               'errors' => $response->errors()
│     │           ], 422);
│     │
│     └─ NO → Custom handling based on your needs
```

---

## Decision Tree 9: Should I Use prepare() Method?

```
START: Considering adding prepare() to Action
│
├─ Do you need to fetch data BEFORE performing the action?
│  │
│  ├─ YES → Examples:
│  │         - Loading form data for edit page
│  │         - Getting dropdown options
│  │         - Fetching current state
│  │         │
│  │         └─ Add prepare() method
│  │            - Return data via $this->success($data)
│  │            - Call via Action::make()->prepare()
│  │
│  └─ NO → Do you only need to execute the action?
│     │
│     ├─ YES → Only implement perform()
│     │         No need for prepare()
│     │
│     └─ UNSURE → Ask yourself:
│               - Will I show a form before this action?
│               - Do I need to display current data?
│               │
│               ├─ YES → Add prepare()
│               └─ NO → Skip prepare()
```

**Example:**
```php
// Edit form - use prepare()
public function edit(Todo $todo)
{
    $data = UpdateTodoAction::make($repo, $todo)->prepare();
    return view('todos.edit', $data->data());
}

// Update - use perform()
public function update(Request $request, Todo $todo)
{
    $response = UpdateTodoAction::make($repo, $todo)
        ->perform($request->title);
    // ...
}
```

---

## Decision Tree 10: Custom ID or Auto-Generated?

```
START: Defining Action/Feature ID
│
├─ Is the auto-generated ID acceptable?
│  │
│  ├─ YES → Don't set ID constant
│  │         Lantern will auto-generate from class name
│  │         Example: CreateTodoAction → 'create-todo'
│  │
│  └─ NO → Do you need a specific format?
│     │
│     ├─ YES → Set custom ID
│     │         const ID = 'todos-create';
│     │         
│     │         Rules:
│     │         - Use kebab-case
│     │         - No periods (.)
│     │         - Must be unique
│     │
│     └─ NO → Use auto-generated ID
```

**Auto-Generation Rules:**
- `CreateTodoAction` → `create-todo`
- `UpdateTodoAction` → `update-todo`
- `TodosFeature` → `todos-feature`
- Namespace parts included: `Todos/CreateAction` → `todos-create`

---

## Quick Decision Matrix

| Question | Feature | Action |
|----------|---------|--------|
| Can be executed? | ❌ No | ✅ Yes |
| Has perform()? | ❌ No | ✅ Yes |
| Has prepare()? | ❌ No | ✅ Optional |
| Has constraints()? | ✅ Yes | ✅ Yes |
| Has availability()? | ❌ No | ✅ Yes |
| Contains Actions? | ✅ Yes | ❌ No |
| Contains Features? | ✅ Yes | ❌ No |
| Registered in parent? | ✅ Yes | ✅ Yes |
| Can have dependencies? | ❌ No | ✅ Yes |

