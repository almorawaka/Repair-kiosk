<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Request;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('/staff/dashboard');
            return;
        }
        $this->view('staff/login', [], 'staff');
    }

    public function handleLogin(Request $request): void
    {
        $username = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        if ($username === '' || $password === '') {
            $this->flash('error', 'Enter both username and password.');
            $this->redirect('/staff/login');
            return;
        }

        if (!Auth::attempt($username, $password)) {
            $this->flash('error', 'Incorrect username or password.');
            $this->redirect('/staff/login');
            return;
        }

        $this->redirect('/staff/dashboard');
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        $this->redirect('/staff/login');
    }
}
