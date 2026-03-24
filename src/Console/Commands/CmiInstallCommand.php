<?php

namespace LaravelCmi\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CmiInstallCommand extends Command
{
    protected $signature = 'utils:cmi-install
                            {--force : Overwrite files that already exist}';

    protected $description = 'Install the CMI payment integration files into your Laravel application.';

    /**
     * Map of stub path (relative to the stubs/ directory) => destination resolver.
     *
     * Each entry is [stub_relative_path, destination_absolute_path].
     */
    private function fileMap(): array
    {
        return [
            // Enum
            'app/CardBrandEnum.php' => app_path('CardBrandEnum.php'),

            // Service
            'app/Services/CmiService.php' => app_path('Services/CmiService.php'),

            // Controller
            'app/Http/Controllers/API/CmiController.php' => app_path('Http/Controllers/API/CmiController.php'),

            // Event
            'app/Events/CmiCallbackReceived.php' => app_path('Events/CmiCallbackReceived.php'),

            // Listener
            'app/Listeners/ProcessCmiPayment.php' => app_path('Listeners/ProcessCmiPayment.php'),

            // Value objects
            'app/ValueObject/CmiCallbackData.php' => app_path('ValueObject/CmiCallbackData.php'),
            'app/ValueObject/CmiOrderData.php' => app_path('ValueObject/CmiOrderData.php'),

            // Model (merge-sensitive — user is warned if it already exists)
            'app/Models/Payment.php' => app_path('Models/Payment.php'),

            // Migration
            'database/migrations/add_cmi_fields_to_payments_table.php' => database_path('migrations/'.date('Y_m_d_His').'_add_cmi_fields_to_payments_table.php'),

            // Views
            'resources/views/cmi/layout.blade.php' => resource_path('views/cmi/layout.blade.php'),
            'resources/views/cmi/ok.blade.php' => resource_path('views/cmi/ok.blade.php'),
            'resources/views/cmi/fail.blade.php' => resource_path('views/cmi/fail.blade.php'),
        ];
    }

    /** Files where overwriting is risky — the user gets an extra merge warning. */
    private const MERGE_SENSITIVE = [
        'app/Models/Payment.php',
    ];

    public function handle(Filesystem $filesystem): int
    {
        $stubsBase = __DIR__.'/../../stubs';
        $force = (bool) $this->option('force');
        $copied = 0;
        $skipped = 0;

        $this->components->info('Installing CMI payment integration…');

        foreach ($this->fileMap() as $stub => $destination) {
            $stubPath = $stubsBase.'/'.$stub;

            if (! $filesystem->exists($stubPath)) {
                $this->components->warn("Stub not found, skipping: {$stub}");
                $skipped++;

                continue;
            }

            if ($filesystem->exists($destination) && ! $force) {
                if (in_array($stub, self::MERGE_SENSITIVE, true)) {
                    $this->components->warn("[SKIP] {$destination} already exists. Review manually and merge CMI fields — run with --force to overwrite.");
                } else {
                    $this->components->warn("[SKIP] {$destination} already exists. Use --force to overwrite.");
                }
                $skipped++;

                continue;
            }

            $filesystem->ensureDirectoryExists(dirname((string) $destination));
            $filesystem->copy($stubPath, $destination);

            $this->components->twoColumnDetail(
                "<fg=green>Copied</>  {$stub}",
                basename((string) $destination)
            );
            $copied++;
        }

        $this->newLine();
        $this->components->info("Done. {$copied} file(s) copied, {$skipped} skipped.");

        if ($copied > 0) {
            $this->printNextSteps();
        }

        return self::SUCCESS;
    }

    private function printNextSteps(): void
    {
        $this->newLine();
        $this->components->info('Next steps:');

        $this->line('  <fg=yellow>1.</> Add the CMI section to <fg=cyan>config/services.php</>:');
        $this->line('');
        $this->line("     'cmi' => [");
        $this->line("         'store_key'    => env('CMI_STORE_KEY'),");
        $this->line("         'client_id'    => env('CMI_CLIENT_ID'),");
        $this->line("         'gateway_url'  => env('CMI_GATEWAY_URL'),");
        $this->line("         'ok_url'       => env('CMI_OK_URL'),");
        $this->line("         'fail_url'     => env('CMI_FAIL_URL'),");
        $this->line("         'callback_url' => env('CMI_CALLBACK_URL'),");
        $this->line("         'shop_url'     => env('CMI_SHOP_URL'),");
        $this->line("         'currency'     => 504,");
        $this->line("         'lang'         => env('CMI_LANG', 'fr'),");
        $this->line('     ],');
        $this->newLine();

        $this->line('  <fg=yellow>2.</> Add the required <fg=cyan>.env</> variables:');
        $this->line('     CMI_CLIENT_ID, CMI_STORE_KEY, CMI_GATEWAY_URL,');
        $this->line('     CMI_OK_URL, CMI_FAIL_URL, CMI_CALLBACK_URL, CMI_SHOP_URL, CMI_LANG');
        $this->newLine();

        $this->line('  <fg=yellow>3.</> Register the event listener in <fg=cyan>app/Providers/AppServiceProvider.php</>:');
        $this->line('     Event::listen(CmiCallbackReceived::class, ProcessCmiPayment::class);');
        $this->newLine();

        $this->line('  <fg=yellow>4.</> Add routes to <fg=cyan>routes/api.php</> / <fg=cyan>routes/web.php</>:');
        $this->line('     // api.php');
        $this->line("     Route::get('/reservations/{id}/pay', [CmiController::class, 'payReservation'])->middleware('auth:sanctum');");
        $this->line("     Route::post('/cmi/callback', [CmiController::class, 'handleCallback']);");
        $this->line('     // web.php');
        $this->line("     Route::get('/cmi/ok',   [CmiController::class, 'handleOk']);");
        $this->line("     Route::get('/cmi/fail', [CmiController::class, 'handleFail']);");
        $this->newLine();

        $this->line('  <fg=yellow>5.</> Run the migration:');
        $this->line('     php artisan migrate');
        $this->newLine();
    }
}
