<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeAdminCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin-crud 
                            {name : The model name in PascalCase (e.g. Product, Customer, Order)}
                            {--icon=Folder : Lucide icon component name}
                            {--group=Master Data : Admin menu group title}
                            {--force : Overwrite existing files if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a complete production-grade admin CRUD module (Model, Migration, Request, Controller, and Vue DataTable)';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rawName = $this->argument('name');
        $modelName = Str::studly($rawName);
        $modelNamePlural = Str::pluralStudly($modelName);
        $humanName = Str::headline($modelName);
        $humanNamePlural = Str::plural($humanName);
        $humanNameLowerCase = Str::lower($humanName);
        $variableNameSingular = Str::camel($modelName);
        $variableNamePlural = Str::camel($modelNamePlural);
        $tableName = Str::snake($modelNamePlural);
        $routePrefix = Str::kebab($modelNamePlural);
        $iconName = Str::studly($this->option('icon') ?: 'Folder');
        $groupName = $this->option('group') ?: 'Master Data';
        $codeExample = strtoupper(substr($modelName, 0, 3)).'-001';

        $this->info("🚀 Generating Admin CRUD Module for: [{$modelName}]");

        $replacements = [
            '{{modelName}}' => $modelName,
            '{{modelNamePlural}}' => $modelNamePlural,
            '{{humanName}}' => $humanName,
            '{{humanNamePlural}}' => $humanNamePlural,
            '{{humanNameLowerCase}}' => $humanNameLowerCase,
            '{{variableNameSingular}}' => $variableNameSingular,
            '{{variableNamePlural}}' => $variableNamePlural,
            '{{tableName}}' => $tableName,
            '{{routePrefix}}' => $routePrefix,
            '{{iconName}}' => $iconName,
            '{{codeExample}}' => $codeExample,
        ];

        // 1. Generate Migration
        $this->generateMigration($tableName);

        // 2. Generate Model
        $this->generateFromStub(
            resource_path('stubs/crud/model.stub'),
            app_path("Models/{$modelName}.php"),
            $replacements,
            "Model [app/Models/{$modelName}.php]"
        );

        // 3. Generate FormRequest
        $this->generateFromStub(
            resource_path('stubs/crud/request.stub'),
            app_path("Http/Requests/Admin/{$modelName}Request.php"),
            $replacements,
            "FormRequest [app/Http/Requests/Admin/{$modelName}Request.php]"
        );

        // 4. Generate Controller
        $this->generateFromStub(
            resource_path('stubs/crud/controller.stub'),
            app_path("Http/Controllers/Admin/{$modelName}Controller.php"),
            $replacements,
            "Controller [app/Http/Controllers/Admin/{$modelName}Controller.php]"
        );

        // 5. Generate Vue Page
        $this->generateFromStub(
            resource_path('stubs/crud/vue-index.stub'),
            resource_path("js/Pages/Admin/{$modelNamePlural}/Index.vue"),
            $replacements,
            "Vue Component [resources/js/Pages/Admin/{$modelNamePlural}/Index.vue]"
        );

        // 6. Register Route in routes/web.php
        $this->registerRoute($modelName, $routePrefix);

        // 7. Register Menu in config/admin_menu.php
        $this->registerMenu($humanName, $routePrefix, $iconName, $groupName);

        $this->newLine();
        $this->components->info("✨ Modul CRUD [{$modelName}] berhasil dibuat sepenuhnya!");
        $this->line('👉 Langkah berikutnya:');
        $this->line('   1. Jalankan migrasi database: <fg=yellow>php artisan migrate</>');
        $this->line('   2. Kompilasi frontend: <fg=yellow>npm run build</>');
        $this->line("   3. Akses halaman di browser: <fg=cyan>http://localhost:8000/admin/{$routePrefix}</>");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Generate database migration.
     */
    protected function generateMigration(string $tableName): void
    {
        $existingMigrations = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (! empty($existingMigrations) && ! $this->option('force')) {
            $this->components->warn("Migration for [{$tableName}] already exists. Skipping.");

            return;
        }

        $timestamp = date('Y_m_d_His');
        $fileName = "{$timestamp}_create_{$tableName}_table.php";
        $filePath = database_path("migrations/{$fileName}");

        $migrationContent = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();
            \$table->string('code')->unique();
            \$table->string('name');
            \$table->string('status')->default('active');
            \$table->text('description')->nullable();
            \$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;

        $this->files->put($filePath, $migrationContent);
        $this->components->twoColumnDetail('Migration', "database/migrations/{$fileName}");
    }

    /**
     * Generate file from stub template.
     */
    protected function generateFromStub(string $stubPath, string $targetPath, array $replacements, string $label): void
    {
        if ($this->files->exists($targetPath) && ! $this->option('force')) {
            $this->components->warn("{$label} already exists. Skipping.");

            return;
        }

        if (! $this->files->exists($stubPath)) {
            $this->components->error("Stub file [{$stubPath}] not found.");

            return;
        }

        $content = $this->files->get($stubPath);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        $directory = dirname($targetPath);
        if (! $this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        $this->files->put($targetPath, $content);
        $this->components->twoColumnDetail('Created', $label);
    }

    /**
     * Register resource route in routes/web.php.
     */
    protected function registerRoute(string $modelName, string $routePrefix): void
    {
        $routesPath = base_path('routes/web.php');
        $routesContent = $this->files->get($routesPath);

        $useStatement = "use App\Http\Controllers\Admin\\{$modelName}Controller;";
        if (! str_contains($routesContent, $useStatement)) {
            $routesContent = preg_replace(
                '/use App\\\\Http\\\\Controllers\\\\Admin\\\\.*?;\n/',
                "$0{$useStatement}\n",
                $routesContent,
                1
            );
        }

        $routeRegistration = "    // {$modelName} Management\n".
            "    Route::post('{$routePrefix}/bulk-destroy', [{$modelName}Controller::class, 'bulkDestroy'])->name('{$routePrefix}.bulk-destroy');\n".
            "    Route::resource('{$routePrefix}', {$modelName}Controller::class)->only(['index', 'store', 'update', 'destroy']);\n";

        if (! str_contains($routesContent, "'{$routePrefix}'")) {
            $routesContent = str_replace(
                '    // System Settings',
                "{$routeRegistration}\n    // System Settings",
                $routesContent
            );
            $this->files->put($routesPath, $routesContent);
            $this->components->twoColumnDetail('Route Registered', "routes/web.php [admin.{$routePrefix}.*]");
        }
    }

    /**
     * Register navigation menu in config/admin_menu.php.
     */
    protected function registerMenu(string $humanName, string $routePrefix, string $iconName, string $groupName): void
    {
        $menuPath = config_path('admin_menu.php');
        $menuContent = $this->files->get($menuPath);

        if (str_contains($menuContent, "'admin.{$routePrefix}.index'")) {
            return;
        }

        $newItem = <<<PHP
        [
            'title' => '{$humanName}',
            'icon' => '{$iconName}',
            'route' => 'admin.{$routePrefix}.index',
            'active' => 'admin.{$routePrefix}.*',
        ],
PHP;

        // Check if group already exists
        if (str_contains($menuContent, "'title' => '{$groupName}'")) {
            // Append as child inside existing group
            $pattern = "/('title' => '{$groupName}'.*?'children' => \[)(.*?)(\n        \],)/s";
            $menuContent = preg_replace(
                $pattern,
                "$1$2\n            [\n                'title' => '{$humanName}',\n                'route' => 'admin.{$routePrefix}.index',\n                'active' => 'admin.{$routePrefix}.*',\n            ],",
                $menuContent
            );
        } else {
            // Insert before Settings menu
            $menuContent = str_replace(
                "        [\n            'title' => 'Settings',",
                "{$newItem}\n        [\n            'title' => 'Settings',",
                $menuContent
            );
        }

        $this->files->put($menuPath, $menuContent);
        $this->components->twoColumnDetail('Menu Registered', 'config/admin_menu.php');
    }
}
