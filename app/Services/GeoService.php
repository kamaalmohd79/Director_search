<?php
namespace App\Services;

use Symfony\Component\Process\Process;

class GeoService
{
    public function distanceMatrix(array $addressRows): array
    {
        $python = env('PYTHON_BIN', 'python');
        $script = base_path('python/geo.py');

        $payload = json_encode($addressRows, JSON_UNESCAPED_UNICODE);
        $process = new Process([$python, $script]);
        $process->setInput($payload);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            return ['points' => [], 'distance_matrix' => [], 'error' => $process->getErrorOutput()];
        }
        return json_decode($process->getOutput(), true) ?? ['points'=>[], 'distance_matrix'=>[]];
    }
}
