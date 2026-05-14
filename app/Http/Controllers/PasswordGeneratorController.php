<?php

namespace App\Http\Controllers;

use App\Services\PasswordGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Password generator. The api endpoint backs the in-form "Generate" button
 * (see resources/views/components/password-field.blade.php) — full UI
 * comes in the Tools module.
 */
class PasswordGeneratorController extends Controller
{
    public function __construct(private readonly PasswordGeneratorService $generator) {}

    public function index(): View
    {
        return view('tools.generator');
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length' => ['nullable', 'integer', 'min:8', 'max:128'],
            'uppercase' => ['nullable', 'boolean'],
            'lowercase' => ['nullable', 'boolean'],
            'numbers' => ['nullable', 'boolean'],
            'symbols' => ['nullable', 'boolean'],
            'exclude_similar' => ['nullable', 'boolean'],
        ]);

        $password = $this->generator->generate($validated);
        $score = $this->generator->strengthScore($password);

        return response()->json([
            'password' => $password,
            'strength' => [
                'score' => $score,
                'label' => $this->generator->strengthLabel($score),
            ],
        ]);
    }
}
