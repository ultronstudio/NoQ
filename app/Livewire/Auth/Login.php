<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): RedirectResponse
    {
        $this->email = mb_strtolower(trim($this->email));

        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Příliš mnoho pokusů. Zkuste to znovu za {$seconds} s.",
            ]);
        }

        if (! Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Zadané přihlašovací údaje nejsou správné.',
            ]);
        }

        RateLimiter::clear($key);

        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.auth.login');
    }
}
