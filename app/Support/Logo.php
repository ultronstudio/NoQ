<?php

namespace App\Support;

use Illuminate\View\ComponentAttributeBag;
use InvalidArgumentException;

class Logo
{
    /**
     * Render a logo, with light/dark selection handled by the browser in auto mode.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function render(string $variant = 'auto', array $attributes = []): string
    {
        $files = [
            'light' => 'noq-logo-light.svg',
            'dark' => 'noq-logo-dark.svg',
            'mono-white' => 'noq-logo-mono-white.svg',
            'mono-black' => 'noq-logo-mono-black.svg',
            'symbol-light' => 'noq-symbol-light.svg',
            'symbol-dark' => 'noq-symbol-dark.svg',
        ];

        if ($variant !== 'auto' && ! isset($files[$variant])) {
            throw new InvalidArgumentException("Unknown logo variant [{$variant}].");
        }

        return view('components.logo', [
            'variant' => $variant,
            'sources' => $variant === 'auto'
                ? ['light' => $files['light'], 'dark' => $files['dark']]
                : [$variant => $files[$variant]],
            'attributes' => new ComponentAttributeBag($attributes),
        ])->render();
    }
}
