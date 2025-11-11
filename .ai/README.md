# Lantern AI Agent Documentation

This directory contains comprehensive documentation to help AI agents understand and work with the Lantern framework.

## 📚 Documentation Files

### 1. **lantern-guide.md** - Complete Framework Guide
The comprehensive guide covering all aspects of Lantern:
- Core architecture (Features, Actions, ActionProxy)
- Availability checks and constraints
- Registration and setup
- Best practices
- Troubleshooting

**Use this when:** You need a complete understanding of Lantern or are working with it for the first time.

### 2. **quick-reference.md** - Quick Reference
Fast lookup for common patterns and syntax:
- Minimal code examples
- Method signatures
- Common patterns (controllers, APIs, etc.)
- Key differences (constraints vs availability, etc.)
- Error messages

**Use this when:** You know what you want to do and just need the syntax or a quick example.

### 3. **examples.md** - Real-World Examples
Complete, working examples of common use cases:
- Todo CRUD operations
- PDF generation with constraints
- Multi-step wizards
- Guest user actions
- API controllers
- Complex availability checks

**Use this when:** You need to see how Lantern is used in realistic scenarios.

### 4. **common-mistakes.md** - Common Mistakes & How to Avoid Them
A comprehensive list of mistakes AI agents commonly make:
- Wrong namespace usage
- Incorrect ActionResponse handling
- Misusing constraints vs availability
- Registration issues
- And 20+ more common pitfalls

**Use this when:** Debugging issues or before generating code to avoid common errors.

### 5. **decision-tree.md** - Decision Tree Guide
Step-by-step decision trees for:
- When to create a Feature vs Action
- Constraints vs Availability
- How to structure your features
- Troubleshooting availability issues

**Use this when:** You're unsure about architectural decisions or need help choosing the right approach.

## 🎯 Quick Start for AI Agents

### First Time Working with Lantern?
1. Read **lantern-guide.md** (sections: Overview, Core Architecture)
2. Review **examples.md** (Example 1: Simple Todo CRUD)
3. Check **common-mistakes.md** (top 5 mistakes)

### Creating a New Feature?
1. Use **decision-tree.md** (Feature Creation Decision Tree)
2. Reference **quick-reference.md** (Minimal Feature example)
3. Run: `php artisan lantern:make-feature YourFeature`

### Creating a New Action?
1. Use **decision-tree.md** (Action Creation Decision Tree)
2. Reference **examples.md** (find similar example)
3. Run: `php artisan lantern:make-action YourAction`
4. Check **common-mistakes.md** (avoid pitfalls)

### Debugging an Issue?
1. Check **common-mistakes.md** (find your error)
2. Use **decision-tree.md** (Troubleshooting section)
3. Reference **quick-reference.md** (Error Messages section)

## 🔑 Key Concepts Summary

### Features
- **Purpose:** Organize and group related Actions/Features
- **Contains:** Actions and/or sub-Features
- **Supports:** Constraints only (no availability)
- **Example:** `TodosFeature` groups all todo-related actions

### Actions
- **Purpose:** Execute business logic
- **Contains:** `perform()` method (required), `prepare()` method (optional)
- **Supports:** Both constraints and availability
- **Called via:** `ActionClass::make(...deps)->perform(...params)`
- **Returns:** `ActionResponse` (always)

### Constraints
- **Purpose:** System-level requirements
- **Cached:** Yes (checked once per request)
- **Scope:** Features and Actions
- **Examples:** PHP extensions, executables, classes
- **Methods:** `classExists()`, `extensionIsLoaded()`, `executableIsInstalled()`

### Availability
- **Purpose:** User/context authorization
- **Cached:** No (checked every time)
- **Scope:** Actions only
- **Examples:** User permissions, ownership checks
- **Methods:** `userCan()`, `assertTrue()`, `assertEqual()`, etc.

### ActionProxy
- **Purpose:** Wrapper that enforces checks before execution
- **Returned by:** `Action::make()`
- **Checks:** Feature constraints → Action constraints → Action availability
- **Throws:** `LanternException` if any check fails

## 📋 Checklist for AI Agents

Before generating Lantern code:

**Namespace & Structure:**
- [ ] Using `App\Features` namespace (not `src/`)
- [ ] Using Artisan commands to create files
- [ ] Following proper directory structure

**Features:**
- [ ] Extends `Lantern\Features\Feature`
- [ ] Has at least one Action or Feature
- [ ] Only uses `constraints()` (not `availability()`)
- [ ] Registered in parent Feature or AppServiceProvider

**Actions:**
- [ ] Extends `Lantern\Features\Action`
- [ ] Has `perform()` method returning `ActionResponse`
- [ ] Uses `$this->success()` or `$this->failure()`
- [ ] Registered in parent Feature's `ACTIONS` array
- [ ] Sets `GUEST_USERS = true` if for guests
- [ ] Uses `constraints()` for system checks
- [ ] Uses `availability()` for user checks

**Usage:**
- [ ] Called via `Action::make()` not `new Action()`
- [ ] Handles `ActionResponse` with `successful()` check
- [ ] Catches `LanternException` where appropriate

## 🎨 Code Generation Templates

### Minimal Feature
```php
<?php
namespace App\Features\YourDomain;

use Lantern\Features\Feature;

class YourFeature extends Feature
{
    const ACTIONS = [
        YourAction::class,
    ];
}
```

### Minimal Action
```php
<?php
namespace App\Features\YourDomain;

use Lantern\Features\Action;
use Lantern\Features\ActionResponse;

class YourAction extends Action
{
    public function perform(): ActionResponse
    {
        // Your logic here
        return $this->success();
    }
}
```

### Controller Usage
```php
$response = YourAction::make()->perform();

if ($response->successful()) {
    return redirect()->route('success')
        ->with('message', 'Success!');
}

return back()->withErrors($response->errors());
```

## 🔍 Finding Information

| I need to... | Check this file | Section |
|--------------|----------------|---------|
| Understand Lantern basics | lantern-guide.md | Overview, Core Architecture |
| See a working example | examples.md | Any example |
| Get quick syntax | quick-reference.md | Relevant section |
| Avoid a mistake | common-mistakes.md | All sections |
| Make a decision | decision-tree.md | Relevant tree |
| Debug an error | common-mistakes.md | Error Messages |
| Learn about constraints | lantern-guide.md | Section 5: Constraints |
| Learn about availability | lantern-guide.md | Section 4: Availability |
| Understand ActionProxy | lantern-guide.md | Section 3: Calling Actions |

## 🚀 Common Tasks

### Task: Create a CRUD Feature
1. `php artisan lantern:make-feature YourDomain/YourFeature`
2. `php artisan lantern:make-action YourDomain/CreateAction`
3. `php artisan lantern:make-action YourDomain/UpdateAction`
4. `php artisan lantern:make-action YourDomain/DeleteAction`
5. Register actions in Feature's `ACTIONS` array
6. Register Feature in parent Feature's `FEATURES` array

### Task: Add Authorization to Action
1. Add `availability()` method to Action
2. Use `$builder->userCan()` for Laravel Gate checks
3. Use `$builder->assertTrue()` for custom logic
4. Provide clear error messages

### Task: Add System Requirements
1. Add `constraints()` method to Feature or Action
2. Use `$constraints->extensionIsLoaded()` for PHP extensions
3. Use `$constraints->executableIsInstalled()` for binaries
4. Use `$constraints->classExists()` for classes

## 📖 Additional Resources

- **Official Docs:** https://lanternphp.github.io
- **Repository:** https://github.com/lanternphp/lantern
- **Package:** lanternphp/lantern

## 💡 Tips for AI Agents

1. **Always use Artisan commands** - Don't manually create files
2. **Check common-mistakes.md first** - Avoid known pitfalls
3. **Use examples as templates** - Adapt working code
4. **Namespace matters** - Always `App\Features`, never `src/`
5. **ActionResponse is required** - Every `perform()` must return it
6. **Registration is critical** - Actions in Features, Features in parents
7. **Constraints ≠ Availability** - System vs User checks
8. **make() not new** - Always use static `make()` method
9. **Handle responses properly** - Check `successful()` before using data
10. **Error messages help users** - Provide clear messages in availability

## 🆘 Getting Help

If you encounter an issue:
1. Check **common-mistakes.md** for your specific error
2. Use **decision-tree.md** troubleshooting section
3. Review **examples.md** for similar use cases
4. Consult **lantern-guide.md** for detailed explanations
5. Check the official documentation at https://lanternphp.github.io

