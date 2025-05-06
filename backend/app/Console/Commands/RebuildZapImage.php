<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RebuildDockerImages extends Command
{
    protected $signature = 'scanner:rebuild-images';
    protected $description = 'Rebuilds selected Docker images for the BRANACA scanner';

    public function handle()
    {
        if (env('REBUILD_DOCKERS') !== 'true') {
            $this->info('REBUILD_DOCKERS is not set to true. Skipping rebuild.');
            return Command::SUCCESS;
        }

        $basePath = base_path();

        $this->info('Rebuilding Docker image: zap-scanner...');
        $zapResult = $this->runBuild("$basePath/zap-scanner/Dockerfile", 'zap-scanner');
        $this->line($zapResult);

        $this->info('Rebuilding Docker image: bracana-nikto...');
        $niktoResult = $this->runBuild("$basePath/nikto_scanner/Dockerfile", 'bracana-nikto');
        $this->line($niktoResult);

        // Uncomment below when ready
        // $this->info('Rebuilding Docker image: bracana-semgrep...');
        // $semgrepResult = $this->runBuild("$basePath/semgrep_scanner/Dockerfile", 'bracana-semgrep');
        // $this->line($semgrepResult);

        // $this->info('Rebuilding Docker image: bracana-codeql...');
        // $codeqlResult = $this->runBuild("$basePath/codeql_scanner/Dockerfile", 'bracana-codeql');
        // $this->line($codeqlResult);

        return Command::SUCCESS;
    }

    protected function runBuild(string $dockerfilePath, string $tag): string
    {
        if (!File::exists($dockerfilePath)) {
            return " Dockerfile not found: $dockerfilePath";
        }

        $context = dirname($dockerfilePath);
        $cmd = "docker build -t $tag -f " . escapeshellarg($dockerfilePath) . " " . escapeshellarg($context);
        exec($cmd, $output, $status);

        return $status === 0
            ? " $tag built successfully."
            : " Failed to build $tag.\n" . implode("\n", $output);
    }
}
