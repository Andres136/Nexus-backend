<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeService extends Command
{
    protected $signature = 'make:service {name}';
    protected $description = 'Crear una clase Service';

    public function handle(): int
    {
        $name = $this->argument('name');

        $path = app_path('Services/' . str_replace('\\', '/', $name) . '.php');
        $namespace = 'App\\Services\\' . str_replace('/', '\\', dirname($name));
        $className = class_basename($name);

        // Crear carpeta si no existe
        if (!File::exists(dirname($path))) {
            File::makeDirectory(dirname($path), 0755, true);
        }

        if (File::exists($path)) {
            $this->error('❌ El Service ya existe');
            return Command::FAILURE;
        }

        $stub = <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    //
}
PHP;

        File::put($path, $stub);

        $this->info("✅ Service creado correctamente: {$path}");

        return Command::SUCCESS;
    }
}
