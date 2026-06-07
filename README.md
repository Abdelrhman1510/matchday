# Matchday API

A Laravel 11 API-only application with Sanctum authentication, designed for mobile app access.

## Features

- ✅ Laravel 11 with Sanctum for API token authentication
- ✅ API-only configuration (minimal web routes)
- ✅ MySQL 8 database support
- ✅ Consistent API response format
- ✅ CORS configured for mobile access
- ✅ Rate limiting (60/min authenticated, 30/min guests)
- ✅ API versioning (v1 structure ready)
- ✅ Comprehensive exception handling with JSON responses
- ✅ Base FormRequest class with consistent error formatting
- ✅ Spatie Laravel Permission for role/permission management
- ✅ Intervention Image for image processing
- ✅ QR Code generation support
- ✅ Laravel Scout for full-text search

## Requirements

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js & NPM (optional, for asset compilation)

## Installation

1. **Clone or navigate to the project directory:**
   ```bash
   cd matchday-api
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   ```
   
   Update the following in your `.env` file:
   - `DB_DATABASE=matchday_api`
   - `DB_USERNAME=your_username`
   - `DB_PASSWORD=your_password`

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Run migrations:**
   ```bash
   php artisan migrate
   ```

6. **Start the development server:**
   ```bash
   php artisan serve
   ```

## API Structure

### Base URL
```
http://localhost:8000/api/v1
```

### Response Format
All API responses follow this structure:
```json
{
    "success": true|false,
    "message": "Response message",
    "data": {},
    "meta": {}
}
```

For errors with validation:
```json
{
    "success": false,
    "message": "Validation failed",
    "data": {},
    "meta": {},
    "errors": {
        "field": ["Error message"]
    }
}
```

### Endpoints

#### Health Check
```
GET /api/v1/health
```

#### Get Authenticated User
```
GET /api/v1/user
Authorization: Bearer {token}
```

## Authentication

This API uses Laravel Sanctum for token-based authentication.

### Token Generation Example
```php
$token = $user->createToken('device-name')->plainTextToken;
```

### Protected Routes
Add `auth:sanctum` middleware to routes that require authentication:
```php
Route::middleware('auth:sanctum')->group(function () {
    // Your protected routes
});
```

## Rate Limiting

- **Authenticated users:** 60 requests per minute
- **Guest users:** 30 requests per minute

Rate limiting is automatically applied based on authentication status.

## CORS Configuration

CORS is configured to allow all origins by default. Update `.env` for production:
```env
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
CORS_ALLOWED_METHODS=GET,POST,PUT,DELETE,PATCH
CORS_ALLOWED_HEADERS=Content-Type,Authorization,X-Requested-With
```

## API Versioning

Routes are organized by version in `routes/api/v1/`:
- `auth.php` - Authentication routes
- `routes.php` - Other resource routes

To create a new version, duplicate the v1 folder and update the route prefix in `bootstrap/app.php`.

## Using API Responses in Controllers

All controllers extend the base `Controller` class which includes the `ApiResponse` trait:

```php
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return $this->successResponse($users, 'Users retrieved');
    }

    public function store(Request $request)
    {
        // validation fails
        return $this->errorResponse('Invalid data', 422, $errors);
    }

    public function paginated()
    {
        $users = User::paginate(15);
        return $this->paginatedResponse($users, 'Users retrieved');
    }
}
```

## Form Requests

Extend the base `FormRequest` class for consistent validation error formatting:

```php
use App\Http\Requests\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ];
    }
}
```

## Installed Packages

- **Laravel Sanctum** - API token authentication
- **Spatie Laravel Permission** - Role and permission management
- **Intervention Image** - Image manipulation
- **SimpleSoftwareIO QR Code** - QR code generation
- **Laravel Scout** - Full-text search

## Development

### Running Tests
```bash
php artisan test
```

### Code Style
```bash
./vendor/bin/pint
```

### Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Production Deployment

1. Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
2. Configure proper database credentials
3. Set specific CORS allowed origins
4. Run `php artisan config:cache`
5. Run `php artisan route:cache`
6. Run `php artisan view:cache`
7. Set up proper queue workers if using queues

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
