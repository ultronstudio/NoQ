<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class ConfirmPassword extends Component
{
    public string $password = '';

    public function confirm()
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($this->password, auth()->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Zadané heslo není správné.',
            ]);
        }

        session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.confirm-password');
    }
}
