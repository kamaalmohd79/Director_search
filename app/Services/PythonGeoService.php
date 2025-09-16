<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class PythonGeoService
{
    public function computeDistances(array $officers): array
    {
        $python = env('PYTHON_BIN', 'python'); // e.g., C:\Users\<you>\AppData\Local\Programs\Python\Python313\python.exe
        $script = base_path('python/geo.py');

        $payload = json_encode(['officers' => $officers], JSON_UNESCAPED_UNICODE);

        // Important on Windows to see output as it happens:
        $proc = new Process([$python, $script]);
        $proc->setEnv([
            'PYTHONUNBUFFERED' => '1',
        ]);
        $proc->setInput($payload);
        $proc->setTimeout(45); // give postcodes.io time if needed
        $proc->run();

        $stdout = $proc->getOutput();
        $stderr = $proc->getErrorOutput();

        // If Python failed or returned invalid JSON, DO NOT blow up the page.
        if (!$proc->isSuccessful()) {
            return [
                'officers'        => $officers,
                'distance_matrix' => [],
                'stats' => [
                    'count_with_pc'    => 0,
                    'count_without_pc' => 0,
                    'count_pairs'      => 0,
                    'min_km'           => null,
                    'avg_km'           => null,
                    'max_km'           => null,
                    'error'            => 'Python geocoding failed',
                    'stderr'           => trim($stderr),
                ],
            ];
        }

        $data = json_decode($stdout, true);
        if (!is_array($data)) {
            return [
                'officers'        => $officers,
                'distance_matrix' => [],
                'stats' => [
                    'count_with_pc'    => 0,
                    'count_without_pc' => 0,
                    'count_pairs'      => 0,
                    'min_km'           => null,
                    'avg_km'           => null,
                    'max_km'           => null,
                    'error'            => 'Invalid JSON from Python',
                    'stderr'           => trim($stderr),
                ],
            ];
        }

        // Make sure required keys exist so Blade never explodes.
        $out = [
            'officers'        => $data['officers'] ?? $officers,
            'distance_matrix' => $data['distance_matrix'] ?? [],
            'stats'           => $data['stats'] ?? [],
        ];

        $out['stats'] = array_merge([
            'count_with_pc'    => 0,
            'count_without_pc' => 0,
            'count_pairs'      => 0,
            'min_km'           => null,
            'avg_km'           => null,
            'max_km'           => null,
        ], $out['stats']);

        return $out;
    }
}
