# washroch/laravel-cmi

CMI (Centre Monétique Interbancaire) 3D-Secure payment integration scaffold for Laravel.

Provides a single Artisan command that copies all CMI integration files into your application.

---

## Installation

### 1. Require the package

If you are using it as a local path repository, add this to your application's `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/washroch/laravel-cmi"
    }
],
"require": {
    "washroch/laravel-cmi": "*"
}
```

Then run:

```bash
composer require washroch/laravel-cmi
```

The service provider is auto-discovered via `composer.json`'s `extra.laravel.providers`.

### 2. Run the install command

```bash
php artisan utils:cmi-install
```

Use `--force` to overwrite files that already exist:

```bash
php artisan utils:cmi-install --force
```

---

## What gets copied

| Stub                                                       | Destination                                                            |
| ---------------------------------------------------------- | ---------------------------------------------------------------------- |
| `app/CardBrandEnum.php`                                    | `app/CardBrandEnum.php`                                                |
| `app/Services/CmiService.php`                              | `app/Services/CmiService.php`                                          |
| `app/Http/Controllers/API/CmiController.php`               | `app/Http/Controllers/API/CmiController.php`                           |
| `app/Events/CmiCallbackReceived.php`                       | `app/Events/CmiCallbackReceived.php`                                   |
| `app/Listeners/ProcessCmiPayment.php`                      | `app/Listeners/ProcessCmiPayment.php`                                  |
| `app/ValueObject/CmiCallbackData.php`                      | `app/ValueObject/CmiCallbackData.php`                                  |
| `app/ValueObject/CmiOrderData.php`                         | `app/ValueObject/CmiOrderData.php`                                     |
| `app/Models/Payment.php`                                   | `app/Models/Payment.php` ⚠️ merge-sensitive                            |
| `database/migrations/add_cmi_fields_to_payments_table.php` | `database/migrations/{timestamp}_add_cmi_fields_to_payments_table.php` |
| `resources/views/cmi/layout.blade.php`                     | `resources/views/cmi/layout.blade.php`                                 |
| `resources/views/cmi/ok.blade.php`                         | `resources/views/cmi/ok.blade.php`                                     |
| `resources/views/cmi/fail.blade.php`                       | `resources/views/cmi/fail.blade.php`                                   |

> ⚠️ `Payment.php` may already exist in your project. If it does, the command will warn you and skip it (unless `--force` is used). Review the stub and merge the CMI fields manually.

---

## Post-install steps

The command prints these steps after a successful install:

### 1. Add CMI config to `config/services.php`

```php
'cmi' => [
    'store_key'    => env('CMI_STORE_KEY'),
    'client_id'    => env('CMI_CLIENT_ID'),
    'gateway_url'  => env('CMI_GATEWAY_URL'),
    'ok_url'       => env('CMI_OK_URL'),
    'fail_url'     => env('CMI_FAIL_URL'),
    'callback_url' => env('CMI_CALLBACK_URL'),
    'shop_url'     => env('CMI_SHOP_URL'),
    'currency'     => 504,
    'lang'         => env('CMI_LANG', 'fr'),
],
```

### 2. Add `.env` variables

```dotenv
CMI_CLIENT_ID=your_client_id
CMI_STORE_KEY=your_store_key
CMI_GATEWAY_URL=https://testpayment.cmi.co.ma/fim/est3Dgate
CMI_OK_URL=https://your-domain.com/cmi/ok
CMI_FAIL_URL=https://your-domain.com/cmi/fail
CMI_CALLBACK_URL=https://your-domain.com/api/cmi/callback
CMI_SHOP_URL=https://your-domain.com
CMI_LANG=fr
```

### 3. Register the event listener in `AppServiceProvider`

```php
use App\Events\CmiCallbackReceived;
use App\Listeners\ProcessCmiPayment;
use Illuminate\Support\Facades\Event;

Event::listen(CmiCallbackReceived::class, ProcessCmiPayment::class);
```

### 4. Add routes

```php
// routes/api.php
Route::get('/reservations/{id}/pay', [CmiController::class, 'payReservation'])->middleware('auth:sanctum');
Route::post('/cmi/callback', [CmiController::class, 'handleCallback']);

// routes/web.php
Route::get('/cmi/ok',   [CmiController::class, 'handleOk']);
Route::get('/cmi/fail', [CmiController::class, 'handleFail']);
```

### 5. Run the migration

```bash
php artisan migrate
```
