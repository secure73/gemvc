# Traits

## Overview

GEMVC provides a set of reusable traits for common functionality in models and controllers.

## Model Traits

### 1. SafeDeleteModelTrait (`src/traits/model/SafeDeleteModelTrait.php`)
- Soft delete functionality
- Restore deleted records
- Check deletion status
- Filter deleted records

```php
class UserModel extends Model
{
    use SafeDeleteModelTrait;
    
    public function delete(): JsonResponse
    {
        return $this->safeDelete();
    }
    
    public function restore(): JsonResponse
    {
        return $this->safeRestore();
    }
}
```

### 2. CreateModelTrait (`src/traits/model/CreateModelTrait.php`)
- Create record with validation
- Handle creation errors
- Return appropriate response
- Log creation activity

```php
class UserModel extends Model
{
    use CreateModelTrait;
    
    public function create(array $data): JsonResponse
    {
        return $this->createRecord($data);
    }
}
```

### 3. ListTrait (`src/traits/model/ListTrait.php`)
- Paginated list functionality
- Sorting and filtering
- Search functionality
- Result formatting

```php
class UserModel extends Model
{
    use ListTrait;
    
    public function list(int $page = 1, int $limit = 10): JsonResponse
    {
        return $this->getList($page, $limit);
    }
}
```

### 4. UpdateModelTrait (`src/traits/model/UpdateModelTrait.php`)
- Update record with validation
- Handle update errors
- Return appropriate response
- Log update activity

```php
class UserModel extends Model
{
    use UpdateModelTrait;
    
    public function update(int $id, array $data): JsonResponse
    {
        return $this->updateRecord($id, $data);
    }
}
```

### 5. DeleteModelTrait (`src/traits/model/DeleteModelTrait.php`)
- Delete record with validation
- Handle deletion errors
- Return appropriate response
- Log deletion activity

```php
class UserModel extends Model
{
    use DeleteModelTrait;
    
    public function delete(int $id): JsonResponse
    {
        return $this->deleteRecord($id);
    }
}
```

## Controller Traits

### 1. RemoveTrait (`src/traits/controller/RemoveTrait.php`)
- Remove record functionality
- Handle removal errors
- Return appropriate response
- Log removal activity

```php
class UserController extends Controller
{
    use RemoveTrait;
    
    public function remove(int $id): JsonResponse
    {
        return $this->removeRecord($id);
    }
}
```

### 2. RestoreTrait (`src/traits/controller/RestoreTrait.php`)
- Restore record functionality
- Handle restoration errors
- Return appropriate response
- Log restoration activity

```php
class UserController extends Controller
{
    use RestoreTrait;
    
    public function restore(int $id): JsonResponse
    {
        return $this->restoreRecord($id);
    }
}
```

### 3. ActivateTrait (`src/traits/controller/ActivateTrait.php`)
- Activate/deactivate records
- Handle activation errors
- Return appropriate response
- Log activation activity

```php
class UserController extends Controller
{
    use ActivateTrait;
    
    public function activate(int $id): JsonResponse
    {
        return $this->activateRecord($id);
    }
    
    public function deactivate(int $id): JsonResponse
    {
        return $this->deactivateRecord($id);
    }
}
```

### 4. ListControllerTrait (`src/traits/controller/ListControllerTrait.php`)
- List records with pagination
- Handle list errors
- Return appropriate response
- Format list results

```php
class UserController extends Controller
{
    use ListControllerTrait;
    
    public function list(): JsonResponse
    {
        return $this->listRecords();
    }
}
```

### 5. CreateControllerTrait (`src/traits/controller/CreateControllerTrait.php`)
- Create record functionality
- Handle creation errors
- Return appropriate response
- Log creation activity

```php
class UserController extends Controller
{
    use CreateControllerTrait;
    
    public function create(): JsonResponse
    {
        return $this->createRecord();
    }
}
```

## Best Practices

### 1. Trait Usage
- Use traits for common functionality
- Keep traits focused and single-purpose
- Document trait requirements
- Handle errors consistently

### 2. Model Traits
- Use model traits for data operations
- Implement proper validation
- Handle errors gracefully
- Log important activities

### 3. Controller Traits
- Use controller traits for request handling
- Implement proper authorization
- Handle errors consistently
- Format responses properly

### 4. Error Handling
- Use consistent error handling
- Provide meaningful error messages
- Log errors appropriately
- Return appropriate status codes

### 5. Logging
- Log important activities
- Include relevant context
- Use appropriate log levels
- Handle logging errors

## Next Steps

- [Request Lifecycle](request-lifecycle.md)
- [Security Guide](../guides/security.md)
- [Performance Guide](../guides/performance.md) 