<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Livewire\Attributes\Layout;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            return $this->redirectBasedOnRole(Auth::user());
        }

        $this->addError('email', 'Las credenciales proporcionadas no son válidas.');
    }

    public function quickLogin(string $email)
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            Auth::login($user);
            session()->regenerate();
            return $this->redirectBasedOnRole($user);
        }

        $this->addError('email', "El usuario con correo {$email} no fue encontrado.");
    }

    protected function redirectBasedOnRole(User $user)
    {
        if ($user->hasRole('Cocinero')) {
            return $this->redirectRoute('kitchen.kds', navigate: true);
        }

        if ($user->hasRole('Cajero')) {
            return $this->redirectRoute('cashier.index', navigate: true);
        }

        return $this->redirectRoute('tables.index', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
