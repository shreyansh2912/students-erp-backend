# Response Helper Usage

## Overview

The `ResponseHelper` provides consistent JSON responses across all API endpoints.

## Functions

### `successJson($data, $message, $statusCode)`

Returns a success response:

```php
return successJson($user, 'User created successfully', 201);
```

**Output:**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": { ... }
}
```

### `errorJson($message, $errors, $statusCode)`

Returns an error response:

```php
return errorJson('Validation failed', $validator->errors(), 422);
```

**Output:**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": { ... }
}
```

## Usage Examples

### Simple Success
```php
return successJson($students);
// Default message: "Success", status: 200
```

### Success with Custom Message
```php
return successJson($exam, 'Exam published successfully');
```

### Success with Custom Status Code
```php
return successJson($student, 'Student created', 201);
```

### Simple Error
```php
return errorJson('Student not found', null, 404);
```

### Error with Validation Errors
```php
return errorJson('Invalid input', [
    'email' => ['Email is required'],
    'name' => ['Name must be at least 3 characters']
], 422);
```

### Error from Exception
```php
try {
    // ... code
} catch (\Exception $e) {
    return errorJson($e->getMessage(), null, 400);
}
```

## Refactored Controller Example

**Before:**
```php
return response()->json([
    'success' => true,
    'message' => 'Student created successfully',
    'data' => $student,
], 201);
```

**After:**
```php
return successJson($student, 'Student created successfully', 201);
```

## Benefits

✅ **Consistency** - All responses follow the same structure  
✅ **Less Code** - Shorter, more readable controller methods  
✅ **Maintainability** - Change format in one place  
✅ **Type Safety** - Parameters clearly defined  
✅ **Global Access** - Available everywhere without imports
