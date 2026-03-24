<?php

namespace LaravelUtils\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CmiInstallCommand extends Command
{
    protected $signature = 'utils:cmi-install
                            {--force : Overwrite files that already exist}';

    protected $description = 'Install the CMI payment integration files into your Laravel application.';

    public function __construct(private Filesystem $filesystem)
    {
        parent::__construct();
    }

    /**
     * Map of stub path (relative to the stubs/ directory) => destination resolver.
     *
     * Each entry is [stub_relative_path, destination_absolute_path].
     */
    private function fileMap(): array
    {
        return [
            // Enum
            'app/Enums/CardBrandEnum.php' => app_path('Enums/CardBrandEnum.php'),

            // Service
            'app/Services/CmiService.php' => app_path('Services/CmiService.php'),

            // Controller
            'app/Http/Controllers/API/CmiController.php' => app_path('Http/Controllers/API/CmiController.php'),

            // Config
            'config/cmi.php' => config_path('cmi.php'),

            // Event
            'app/Events/CmiCallbackReceived.php' => app_path('Events/CmiCallbackReceived.php'),

            // Listener
            'app/Listeners/ProcessCmiPayment.php' => app_path('Listeners/ProcessCmiPayment.php'),

            // Value objects
            'app/ValueObjects/CmiCallbackData.php' => app_path('ValueObjects/CmiCallbackData.php'),

            'app/ValueObjects/CmiOrderData.php' => app_path('ValueObjects/CmiOrderData.php'),

            // Views
            'resources/views/cmi/layout.blade.php' => resource_path('views/cmi/layout.blade.php'),
            'resources/views/cmi/ok.blade.php' => resource_path('views/cmi/ok.blade.php'),
            'resources/views/cmi/fail.blade.php' => resource_path('views/cmi/fail.blade.php'),
        ];
    }

    public function handle(): int
    {
        $stubsBase = __DIR__.'/../../../stubs';
        $force = (bool) $this->option('force');
        $copied = 0;
        $skipped = 0;

        $this->components->info('Installing CMI payment integration…');

        foreach ($this->fileMap() as $stub => $destination) {
            $stubPath = $stubsBase.'/'.$stub;

            if (! $this->filesystem->exists($stubPath)) {
                $this->components->warn("Stub not found, skipping: {$stub}");
                $skipped++;

                continue;
            }

            if ($this->filesystem->exists($destination) && ! $force) {
                $this->components->warn("[SKIP] {$destination} already exists. Use --force to overwrite.");

                $skipped++;

                continue;
            }

            $this->filesystem->ensureDirectoryExists(dirname((string) $destination));
            $this->filesystem->copy($stubPath, $destination);

            $this->components->twoColumnDetail(
                "<fg=green>Copied</>  {$stub}",
                basename((string) $destination)
            );
            $copied++;
        }

        $this->appendEnvVariablesToEnvExample();

        $this->call('vendor:publish', ['--tag' => 'cmi-migrations']);

        $this->newLine();
        $this->components->info("Done. {$copied} file(s) copied, {$skipped} skipped.");

        if ($copied > 0) {
            $this->printNextSteps();
        }

        return self::SUCCESS;
    }

    private function appendEnvVariablesToEnvExample(): void
    {
        $envExamplePath = base_path('.env.example');
        $variables = [
            'CMI_CLIENT_ID=',
            'CMI_STORE_KEY=',
            'CMI_GATEWAY_URL=',
            'CMI_OK_URL=',
            'CMI_FAIL_URL=',
            'CMI_CALLBACK_URL=',
            'CMI_SHOP_URL=',
            'CMI_LANG=',
        ];

        $variablesContent = "\n# CMI Payment Integration\n".implode("\n", $variables)."\n";

        if ($this->filesystem->exists($envExamplePath)) {
            $this->filesystem->append($envExamplePath, $variablesContent);
        } else {
            $this->filesystem->put($envExamplePath, $variablesContent);
        }
    }

    private function printNextSteps(): void
    {
        $this->newLine();
        $this->components->info('Next steps:');

        $this->line('  <fg=yellow>1.</> Copy the CMI-related environment variables from <fg=cyan>.env.example</> to your <fg=cyan>.env</> file and fill in the appropriate values.');
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
