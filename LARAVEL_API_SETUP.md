# MCC-FERMS LARAVEL API SETUP GUIDE

## ✅ What Has Been Created

### 1. API Routes (`routes/api.php`)
- All REST API endpoints for mobile app
- Authentication routes (login, logout, register)
- Student routes (facilities, equipment, reservations, requests)
- Admin routes (dashboard, approvals, management)
- Protected with Laravel Sanctum authentication

### 2. API Controllers (`app/Http/Controllers/Api/`)
- **AuthController**: Login, logout, register, user info
- **FacilityController**: List facilities, check availability
- **EquipmentController**: List equipment, get details
- **ReservationController**: CRUD for facility reservations
- **RequestController**: Equipment borrow requests
- **TransactionController**: View transactions
- **NotificationController**: User notifications
- **ProfileController**: User profile management
- **AdminController**: Complete admin functionality

### 3. Middleware
- RoleMiddleware already exists in your project
- Configured for API authentication
- Role-based access control

### 4. Configuration
- API routes registered in `bootstrap/app.php`
- Middleware aliases configured

---

## 🔧 REQUIRED SETUP STEPS

### Step 1: Install Laravel Sanctum

```bash
cd C:/Users/kenn/AppData/Local/hermes/cache/scratch/mcc-ferms-laravel/mcc-ferms
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Step 2: Configure CORS for Mobile App

Edit `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
'allowed_origins' => ['*'], // Or specify your mobile app domain
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

### Step 3: Update User Model

The User model needs HasApiTokens trait. Edit `app/Models/User.php`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens; // Add this trait
    
    // ... rest of the code
}
```

### Step 4: Add Missing Migration Fields

Check if these fields exist in equipment tables. If not, create migration:

```bash
php artisan make:migration add_borrow_return_times_to_equipment_tables
```

Then add in the migration:

```php
public function up()
{
    Schema::table('equipment_request_items', function (Blueprint $table) {
        if (!Schema::hasColumn('equipment_request_items', 'borrow_time')) {
            $table->time('borrow_time')->default('08:00')->after('borrow_date');
        }
        if (!Schema::hasColumn('equipment_request_items', 'expected_return_time')) {
            $table->time('expected_return_time')->default('17:00')->after('expected_return');
        }
    });
    
    Schema::table('equipment_transactions', function (Blueprint $table) {
        if (!Schema::hasColumn('equipment_transactions', 'borrow_time')) {
            $table->time('borrow_time')->default('08:00')->after('borrow_date');
        }
        if (!Schema::hasColumn('equipment_transactions', 'expected_return_time')) {
            $table->time('expected_return_time')->default('17:00')->after('expected_return');
        }
    });
}
```

Then run: `php artisan migrate`

### Step 5: Test the API

Start Laravel server:
```bash
php artisan serve
```

Test login endpoint:
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

---

## 📱 FLUTTER CONFIGURATION

Update the Flutter app's API URL in:
`C:/Users/kenn/mcc_ferms_mobile/lib/core/constants/app_constants.dart`

```dart
static const String apiBaseUrl = 'http://localhost:8000/api';
```

For physical Android device testing, use your computer's IP address:
```dart
static const String apiBaseUrl = 'http://192.168.1.XXX:8000/api';
```

---

## 🔑 API AUTHENTICATION FLOW

1. **Login**: POST `/api/login` → Returns user + token
2. **Store token**: Flutter saves token in secure storage
3. **Authenticated requests**: Include header `Authorization: Bearer {token}`
4. **Logout**: POST `/api/logout` → Revokes token

---

## 📋 AVAILABLE API ENDPOINTS

### Public Endpoints
- `POST /api/login` - User login
- `POST /api/register` - User registration

### Student Endpoints (Authenticated)
- `GET /api/user` - Get current user
- `GET /api/facilities` - List facilities
- `GET /api/equipment` - List equipment
- `POST /api/reservations` - Create reservation
- `GET /api/reservations` - User's reservations
- `POST /api/borrow` - Create borrow request
- `GET /api/requests` - User's requests
- `GET /api/transactions` - User's transactions
- `GET /api/notifications` - User's notifications
- `GET /api/profile` - Get profile
- `PUT /api/profile` - Update profile

### Admin Endpoints (Staff/Admin/SuperAdmin)
- `GET /api/admin/dashboard` - Admin statistics
- `GET /api/admin/requests` - All requests
- `POST /api/admin/requests/{id}/approve` - Approve request
- `POST /api/admin/requests/{id}/reject` - Reject request
- `POST /api/admin/requests/{id}/release` - Release equipment
- `GET /api/admin/transactions` - All transactions
- `POST /api/admin/transactions/{id}/return` - Mark returned
- `GET /api/admin/facilities` - Manage facilities
- `POST /api/admin/facilities` - Create facility
- `PUT /api/admin/facilities/{id}` - Update facility
- `GET /api/admin/equipment` - Manage equipment
- `POST /api/admin/equipment` - Create equipment
- `PUT /api/admin/equipment/{id}` - Update equipment

---

## ✅ WHAT'S PRESERVED

- ✅ Existing web application (routes/web.php) untouched
- ✅ All existing controllers preserved
- ✅ Database structure unchanged
- ✅ Business logic reused from existing controllers
- ✅ Validation rules maintained
- ✅ Authorization rules enforced

---

## 🧪 TESTING CHECKLIST

After setup, test these workflows:

### Student Tests
- [ ] Login with student account
- [ ] View facilities list
- [ ] Create facility reservation
- [ ] View equipment list
- [ ] Create equipment borrow request
- [ ] View own requests
- [ ] View notifications
- [ ] Update profile

### Admin Tests
- [ ] Login with admin account
- [ ] View admin dashboard
- [ ] Approve facility reservation
- [ ] Reject equipment request
- [ ] Release approved equipment
- [ ] Mark equipment as returned
- [ ] Create new facility
- [ ] Create new equipment

---

## 🚀 NEXT STEPS

1. Complete Laravel Sanctum installation (Step 1-4 above)
2. Test API endpoints with Postman or curl
3. Continue Flutter implementation (screens, services, UI)
4. Connect Flutter to Laravel API
5. Full end-to-end testing

---

## 📞 TROUBLESHOOTING

### "CORS Error"
- Check `config/cors.php` configuration
- Ensure API routes are prefixed with `/api`

### "Unauthenticated" Error
- Check token is being sent in Authorization header
- Verify token hasn't expired
- Check Sanctum is installed and configured

### "419 Token Mismatch"
- This is for web routes only, API routes don't need CSRF
- Ensure you're hitting `/api/*` routes, not web routes

### "Route Not Found"
- Verify `api` routes are registered in `bootstrap/app.php`
- Check route name matches exactly

---

**Status**: API infrastructure complete. Ready for Sanctum installation and testing.
