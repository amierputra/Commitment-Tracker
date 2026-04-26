# API Response Standardization Guide

## Table of Contents
- [Overview](#overview)
- [Core Concepts](#core-concepts)
- [Implementation Approaches](#implementation-approaches)
- [Recommended Approach](#recommended-approach)
- [Usage Examples](#usage-examples)
- [Handling Pagination](#handling-pagination)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

---

## Overview

**API Response Standardization** is the practice of creating a consistent, predictable JSON structure for all API responses. This ensures that clients (frontend applications, mobile apps, third-party integrations) always know what to expect.

### Why Standardize?

- ✅ **Consistency** - All endpoints return the same structure
- ✅ **Error Handling** - Unified way to communicate success/failure
- ✅ **Debugging** - Easier to trace issues
- ✅ **Documentation** - Simpler to document
- ✅ **Client-side Parsing** - Frontend can handle responses uniformly

### Related Concepts

1. **Response DTO (Data Transfer Object)** - Pattern for structuring data between systems
2. **API Contract Design** - Defining consistent interfaces
3. **RESTful API Best Practices** - HTTP-based API conventions

---

## Core Concepts

### Standard Response Structure

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "status_code": 200,
  "data": {
    // Your actual response data
  },
  "meta": {
    "timestamp": "2026-01-01T00:32:27+08:00"
  }
}
```

### Key Components

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Indicates if the operation succeeded |
| `message` | string | Human-readable message about the operation |
| `status_code` | integer | HTTP status code (200, 404, 422, etc.) |
| `data` | mixed | The actual response payload (object, array, null) |
| `errors` | object/null | Validation errors or error details (only on failure) |
| `meta` | object | Additional metadata (timestamp, version, etc.) |

---

## Implementation Approaches

### Approach 1: Helper Function

**Pros:**
- ✅ Simple and quick
- ✅ Global availability

**Cons:**
- ❌ Pollutes global namespace
- ❌ No enforcement
- ❌ Harder to test

```php
// app/Helpers/ResponseHelper.php
if (!function_exists('apiResponse')) {
    function apiResponse($data = null, $message = 'Success', $statusCode = 200) {
        return response()->json([
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'status_code' => $statusCode,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ]
        ], $statusCode);
    }
}

// Usage
return apiResponse($users, 'Users retrieved', 200);
```

---

### Approach 2: Trait (Reusable in Controllers)

**Pros:**
- ✅ Reusable across controllers
- ✅ No global pollution
- ✅ Easy to implement
- ✅ Flexible

**Cons:**
- ⚠️ Requires manual discipline
- ⚠️ Can be bypassed

```php
// app/Traits/ApiResponseTrait.php
namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function successResponse(
        $data = null, 
        string $message = 'Operation successful', 
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'status_code' => $statusCode,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $statusCode);
    }

    protected function errorResponse(
        string $message, 
        int $statusCode = 400, 
        $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status_code' => $statusCode,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $statusCode);
    }
}

// Usage in Controller
class UserController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $users = User::all();
        return $this->successResponse($users, 'Users retrieved');
    }
}
```

---

### Approach 3: Dedicated Response Class

**Pros:**
- ✅ Centralized logic
- ✅ Type-safe
- ✅ Easy to test

**Cons:**
- ⚠️ Requires manual discipline
- ⚠️ Can be bypassed

```php
// app/Http/Responses/ApiResponse.php
namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, $message = 'Success', $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'status_code' => $statusCode,
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $statusCode);
    }

    public static function error($message, $statusCode = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'status_code' => $statusCode,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], $statusCode);
    }
}

// Usage
use App\Http\Responses\ApiResponse;

return ApiResponse::success($data, 'Operation successful');
```

---

### Approach 4: Laravel API Resources (Most Laravel-Idiomatic)

**Pros:**
- ✅ **Architectural enforcement** - Cannot be bypassed
- ✅ Laravel-native solution
- ✅ Type-safe
- ✅ Best for complex transformations
- ✅ Built-in pagination support

**Cons:**
- ⚠️ More boilerplate (need Resource classes)
- ⚠️ Steeper learning curve

```php
// app/Http/Resources/BaseJsonResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

abstract class BaseJsonResource extends JsonResource
{
    protected $message;
    protected $statusCode;

    public function __construct($resource, string $message = 'Operation successful', int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    /**
     * This method is ALWAYS called by Laravel
     * Developers cannot bypass this
     */
    public function with($request)
    {
        return [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
            'message' => $this->message,
            'status_code' => $this->statusCode,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->statusCode);
    }

    /**
     * Child classes MUST implement this
     */
    abstract public function toArray($request);
}

// app/Http/Resources/UserResource.php
namespace App\Http\Resources;

class UserResource extends BaseJsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles->pluck('name'), // Using Spatie roles
        ];
    }
}

// Usage in Controller
public function show($id)
{
    $user = User::with('company')->findOrFail($id);
    return new UserResource($user, 'User retrieved successfully');
}
```

---

## Recommended Approach

### For This Project: **API Resources (Approach 4)**

**Why?**
1. ✅ **Enforcement** - Architecturally enforced, cannot be bypassed
2. ✅ **Consistency** - Guaranteed consistent structure
3. ✅ **Scalability** - Easy to add field transformations later
4. ✅ **Type Safety** - IDE autocomplete and type hints
5. ✅ **Laravel-native** - Uses framework conventions

### Implementation Structure

```
app/
├── Http/
│   ├── Resources/
│   │   ├── BaseJsonResource.php          # Base for single resources
│   │   ├── BaseResourceCollection.php    # Base for collections/pagination
│   │   ├── UserResource.php              # User-specific resource
│   │   ├── ProductResource.php           # Product-specific resource (example)
│   │   └── ...
```

---

## Usage Examples

### Single Resource

```php
// Controller
public function show($id)
{
    $user = User::with('roles')->findOrFail($id);
    return new UserResource($user, 'User retrieved successfully');
}

// Response
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "roles": ["admin", "user"]
  },
  "success": true,
  "message": "User retrieved successfully",
  "status_code": 200,
  "meta": {
    "timestamp": "2026-01-01T00:32:27+08:00"
  }
}
```

### Collection (Non-Paginated)

```php
// Controller
public function index()
{
    $users = User::all();
    return UserResource::collection($users)
        ->additional([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'status_code' => 200,
        ]);
}

// Response
{
  "data": [
    {"id": 1, "name": "John Doe", "email": "john@example.com"},
    {"id": 2, "name": "Jane Smith", "email": "jane@example.com"}
  ],
  "success": true,
  "message": "Users retrieved successfully",
  "status_code": 200,
  "meta": {
    "timestamp": "2026-01-01T00:32:27+08:00"
  }
}
```

---

## Handling Pagination

### Base Collection for Pagination

```php
// app/Http/Resources/BaseResourceCollection.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseResourceCollection extends ResourceCollection
{
    protected $message;
    protected $statusCode;

    public function __construct($resource, string $message = 'Data retrieved successfully', int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->statusCode = $statusCode;
    }

    public function with($request)
    {
        return [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
            'message' => $this->message,
            'status_code' => $this->statusCode,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->statusCode);
    }
}
```

### Specific Collection

```php
// app/Http/Resources/UserCollection.php
namespace App\Http\Resources;

class UserCollection extends BaseResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection,
        ];
    }
}
```

### Usage

```php
// Controller
public function index()
{
    $users = User::with('roles')->paginate(15);
    return new UserCollection($users, 'Users retrieved successfully');
}

// Response
{
  "data": [
    {"id": 1, "name": "John Doe", "email": "john@example.com"},
    {"id": 2, "name": "Jane Smith", "email": "jane@example.com"}
  ],
  "links": {
    "first": "http://example.com/api/users?page=1",
    "last": "http://example.com/api/users?page=5",
    "prev": null,
    "next": "http://example.com/api/users?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "http://example.com/api/users",
    "per_page": 15,
    "to": 15,
    "total": 75,
    "timestamp": "2026-01-01T00:32:27+08:00"
  },
  "success": true,
  "message": "Users retrieved successfully",
  "status_code": 200
}
```

---

## Error Handling

### Validation Errors

```php
// Controller
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'status_code' => 422,
            'errors' => $validator->errors(),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 422);
    }

    // Create user...
}

// Response
{
  "success": false,
  "message": "Validation failed",
  "status_code": 422,
  "errors": {
    "email": ["The email has already been taken."],
    "name": ["The name field is required."]
  },
  "meta": {
    "timestamp": "2026-01-01T00:32:27+08:00"
  }
}
```

### Not Found

```php
// Controller
public function show($id)
{
    $user = User::find($id);
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found',
            'status_code' => 404,
            'errors' => null,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 404);
    }
    
    return new UserResource($user, 'User retrieved successfully');
}
```

### Exception Handler (Global Error Handling)

```php
// app/Exceptions/Handler.php
namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        // Only apply to API routes
        if ($request->is('api/*')) {
            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'status_code' => 404,
                    'errors' => null,
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }

            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Endpoint not found',
                    'status_code' => 404,
                    'errors' => null,
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }

            // Generic server error
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'status_code' => 500,
                'errors' => config('app.debug') ? $exception->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
```

---

## Best Practices

### 1. Consistent Status Codes

Use standard HTTP status codes:

| Code | Meaning | When to Use |
|------|---------|-------------|
| 200 | OK | Successful GET, PUT, PATCH, DELETE |
| 201 | Created | Successful POST (resource created) |
| 204 | No Content | Successful DELETE (no response body) |
| 400 | Bad Request | Invalid request format |
| 401 | Unauthorized | Authentication required |
| 403 | Forbidden | Authenticated but not authorized |
| 404 | Not Found | Resource doesn't exist |
| 422 | Unprocessable Entity | Validation failed |
| 500 | Internal Server Error | Server error |

### 2. Meaningful Messages

```php
// ❌ Bad
return new UserResource($user, 'Success');

// ✅ Good
return new UserResource($user, 'User retrieved successfully');
```

### 3. Null vs Empty Array

```php
// For single resources that don't exist
"data": null

// For empty collections
"data": []
```

### 4. Conditional Fields

```php
// UserResource.php
public function toArray($request)
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'email' => $this->email,
        
        // Only show sensitive data to admins
        'phone' => $this->when($request->user()->isAdmin(), $this->phone),
        
        // Only include if relationship is loaded
        'roles' => $this->whenLoaded('roles', function() {
            return $this->roles->pluck('name');
        }),
    ];
}
```

### 5. Versioning

Include API version in meta or headers:

```php
public function with($request)
{
    return [
        'success' => true,
        'message' => $this->message,
        'status_code' => $this->statusCode,
        'meta' => [
            'timestamp' => now()->toIso8601String(),
            'version' => 'v1', // API version
        ],
    ];
}
```

---

## Comparison Table

| Feature | Trait | API Resources |
|---------|-------|---------------|
| **Enforcement** | ⚠️ Manual | ✅ Architectural |
| **Ease of Setup** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Pagination** | ⚠️ Manual | ✅ Built-in |
| **Field Control** | ❌ Limited | ✅ Excellent |
| **Type Safety** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Laravel-Idiomatic** | ⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Best For** | Simple APIs | Production APIs |

---

## Quick Reference

### Creating a New Resource

```bash
# Create resource
php artisan make:resource UserResource

# Create collection
php artisan make:resource UserCollection
```

### Common Patterns

```php
// Single resource
return new UserResource($user, 'User retrieved');

// Collection (paginated)
return new UserCollection($users, 'Users retrieved');

// Collection (all)
return UserResource::collection($users);

// Error response
return response()->json([
    'success' => false,
    'message' => 'Error message',
    'status_code' => 400,
    'errors' => $errors,
], 400);
```

---

## Conclusion

For the Commitment Tracker project, **use API Resources (Approach 4)** because:

1. ✅ **Architectural enforcement** - Team cannot bypass it
2. ✅ **Scalable** - Easy to add complexity later
3. ✅ **Consistent** - Guaranteed structure across all endpoints
4. ✅ **Laravel-native** - Follows framework best practices

This ensures long-term maintainability and consistency across your API.
