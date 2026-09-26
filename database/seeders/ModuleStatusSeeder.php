<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Nwidart\Modules\LaravelModulesServiceProvider;

class ModuleStatusSeeder extends Seeder
{
    public function run(): void
    {
        // Get all module directories
        $modulesPath = base_path('Modules');
        if (!File::exists($modulesPath)) {
            return;
        }

        $moduleDirs = File::directories($modulesPath);
        
        foreach ($moduleDirs as $dir) {
            $moduleName = basename($dir);
            
            // Check if module has manifest.php or module.json
            $manifestPath = $dir . '/manifest.php';
            $moduleJsonPath = $dir . '/module.json';
            
            $moduleData = null;
            if (File::exists($manifestPath)) {
                $moduleData = require $manifestPath;
            } elseif (File::exists($moduleJsonPath)) {
                $moduleData = json_decode(File::get($moduleJsonPath), true);
            }
            
            if (!$moduleData) {
                continue;
            }
            
            DB::table('modules')->updateOrInsert(
                ['name' => $moduleName],
                [
                    'name' => $moduleName,
                    'namespace' => $moduleData['namespace'] ?? "Modules\\{$moduleName}\\",
                    'enabled' => false,
                    'installed' => true,
                    'description' => $moduleData['description'] ?? null,
                    'priority' => $moduleData['priority'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
