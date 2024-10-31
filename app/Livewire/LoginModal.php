<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginModal extends Component
{
    public $email;
    public $password;
    public $remember = false;
    public $errorMessage;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
    ];

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->flash('message', 'Login successful!');
            return redirect()->intended(url()->previous());
        } else {
            $this->errorMessage = 'پاسورڊ يا ايميل ۾ غلطي';
        }
    }

    public function render()
    {
        return view('livewire.login-modal');
    }
}
