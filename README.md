# Laravel Utils

Provides a single Artisan command that copies all utils into your application.

---

## Installation

### 1. Require the package

Run:

```bash
composer require lajouizakariae/laravel-utils
```

The service provider is auto-discovered via `composer.json`'s `extra.laravel.providers`.

### 2. Run the install command for CMI integration

```bash
php artisan utils:cmi-install
```

Use `--force` to overwrite files that already exist:

```bash
php artisan utils:cmi-install --force
```

---

## What gets copied

| Stub                                     | Destination                              |
| ---------------------------------------- | ---------------------------------------- |
| `app/Enums/CardBrandEnum.php`            | `app/Enums/CardBrandEnum.php`            |
| `app/Services/CmiService.php`            | `app/Services/CmiService.php`            |
| `app/Http/Controllers/CmiController.php` | `app/Http/Controllers/CmiController.php` |
| `config/cmi.php`                         | `config/cmi.php`                         |
| `app/Events/CmiCallbackReceived.php`     | `app/Events/CmiCallbackReceived.php`     |
| `app/Listeners/ProcessCmiPayment.php`    | `app/Listeners/ProcessCmiPayment.php`    |
| `app/ValueObject/CmiCallbackData.php`    | `app/ValueObject/CmiCallbackData.php`    |
| `app/ValueObject/CmiOrderData.php`       | `app/ValueObject/CmiOrderData.php`       |
| `resources/views/cmi/layout.blade.php`   | `resources/views/cmi/layout.blade.php`   |
| `resources/views/cmi/ok.blade.php`       | `resources/views/cmi/ok.blade.php`       |
| `resources/views/cmi/fail.blade.php`     | `resources/views/cmi/fail.blade.php`     |

The command also:

- Appends CMI environment variables to your `.env.example` automatically.
- Publishes the CMI migration via `php artisan vendor:publish --tag=cmi-migrations`.

---

## Post-install steps

### 1. Fill in `.env` variables

The install command appends the following block to your `.env.example`. Copy them to your `.env` and fill in the values:

```dotenv
CMI_CLIENT_ID=your_client_id
CMI_STORE_KEY=your_store_key
CMI_GATEWAY_URL=https://test-lanacash.cmi.co.ma/fim/est3dgate
CMI_OK_URL=https://your-domain.com/cmi/ok
CMI_FAIL_URL=https://your-domain.com/cmi/fail
CMI_CALLBACK_URL=https://your-domain.com/api/cmi/callback
CMI_SHOP_URL=https://your-domain.com
CMI_LANG=fr
```

### 2. Register the event listener in `AppServiceProvider`

```php
use App\Events\CmiCallbackReceived;
use App\Listeners\ProcessCmiPayment;
use Illuminate\Support\Facades\Event;

Event::listen(CmiCallbackReceived::class, ProcessCmiPayment::class);
```

### 3. Add routes

```php
// routes/api.php
Route::get('/cmi/pay', [CmiController::class, 'pay'])->middleware('auth:sanctum');
Route::post('/cmi/callback', [CmiController::class, 'handleCallback']);

// routes/web.php
Route::get('/cmi/ok',   [CmiController::class, 'handleOk']);
Route::get('/cmi/fail', [CmiController::class, 'handleFail']);
```

### 4. Run the migration

```bash
php artisan migrate
```
