<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdminLoginRequest;
use App\Support\AdminSession;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class AdminAuthController
{
    public function __construct(
        private readonly AdminSession $session,
        private readonly ViewFactory $view,
    ) {}

    public function login(AdminLoginRequest $request): View|RedirectResponse
    {
        if ($request->isMethod('get')) {
            return $this->view->make('admin.login');
        }

        $admin = DB::table('admins')->where('username', $request->username())->first();

        // Same generic error either way — confirming a username exists is a
        // free enumeration oracle otherwise. Hash::check runs regardless of
        // whether $admin was found, against a static hash when it wasn't —
        // skipping it there would make the "no such user" branch answer
        // measurably faster than "wrong password" and leak the same thing
        // through timing instead.
        $hash = $admin->password ?? '$2y$10$usxeXybBla3Q02qYh0izL.KLnAY.d5NHqzqnkFrHDoBUOKY7hcU/O';

        if ($admin === null || ! Hash::check($request->password(), $hash)) {
            return $this->view->make('admin.login')->withErrors(['username' => __('app.admin.login_failed')]);
        }

        // A new session id on every successful login — the one before it
        // belonged to an anonymous visitor, session fixation otherwise.
        $request->session()->regenerate();

        $this->session->login((int) $admin->id);

        return redirect()->route('admin.tariffs');
    }

    public function logout(): RedirectResponse
    {
        $this->session->logout();

        return redirect()->route('admin.login');
    }
}
