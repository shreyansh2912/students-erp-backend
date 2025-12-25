# API Response Helpers

## Overview

Standardized helper functions for consistent JSON API responses across all endpoints.

## Location

`app/Helpers/ApiResponse.php`

## Functions

### `apiSuccess($data = null, string $message = 'Operation successful', int $statusCode = 200)`

Returns a standardized success JSON response.

**Parameters:**
- `$data` (mixed): The data to return (optional)
- `$message` (string): Success message (default: "Operation successful")
- `$statusCode` (int): HTTP status code (default: 200)

**Response Structure:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Usage Examples:**

```php
// Simple success with data
return apiSuccess($user);

// Success with custom message
return apiSuccess($paper, 'Question paper created successfully');

// Success with data, message, and 201 status
return apiSuccess($question, 'Question created successfully', 201);

// Success with message only (no data)
return apiSuccess(null, 'Question deleted successfully');
```

---

### `apiError(string $message = 'An error occurred', int $statusCode = 400, $errors = null)`

Returns a standardized error JSON response.

**Parameters:**
- `$message` (string): Error message (default: "An error occurred")
- `$statusCode` (int): HTTP status code (default: 400)
- `$errors` (mixed): Additional error details like validation errors (optional)

**Response Structure:**
```json
{
  "success": false,
  "message": "An error occurred",
  "errors": { ... }
}
```

**Usage Examples:**

```php
// Simple error
return apiError('Question paper not found', 404);

// Error with default 400 status
return apiError($exception->getMessage());

// Error with validation errors
return apiError('Validation failed', 422, $validator->errors());
```

---

## Before vs After

### Before (Manual Response)
```php
return response()->json([
    'success' => true,
    'message' => 'Question paper created successfully',
    'data' => $paper,
], 201);
```

### After (Using Helper)
```php
return apiSuccess($paper, 'Question paper created successfully', 201);
```

---

## Benefits

✅ **Consistency**: All API endpoints return the same JSON structure  
✅ **Maintainability**: Single source of truth for response format  
✅ **Readability**: Cleaner controller code  
✅ **Type Safety**: Return type hints ensure JsonResponse  
✅ **Less Code**: Shorter, more concise responses

---

## Standard Response Format

All API responses follow this structure:

**Success Response:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data (optional)
  }
}
```

**Error Response:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    // Validation errors or additional error details (optional)
  }
}
```

---

## Usage in Controllers

### Example: QuestionPaperController

```php
public function store(QuestionPaperRequest $request): JsonResponse
{
    try {
        $paper = $this->paperService->createQuestionPaper($request->validated());
        
        return apiSuccess(
            $paper->load(['creator', 'organization']),
            'Question paper created successfully',
            201
        );
    } catch (\Exception $e) {
        return apiError($e->getMessage());
    }
}

public function index(Request $request): JsonResponse
{
    $papers = QuestionPaper::with(['creator', 'organization'])->get();
    
    return apiSuccess($papers);
}

public function destroy(QuestionPaper $paper): JsonResponse
{
    try {
        $this->paperService->deleteQuestionPaper($paper);
        
        return apiSuccess(null, 'Question paper deleted successfully');
    } catch (\Exception $e) {
        return apiError($e->getMessage());
    }
}
```

---

## Autoloading

The helper file is automatically loaded via `composer.json`:

```json
"autoload": {
    "files": [
        "app/Helpers/helpers.php",
        "app/Helpers/ApiResponse.php"
    ]
}
```

After modifying, run: `composer dump-autoload`
