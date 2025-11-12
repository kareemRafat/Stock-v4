<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public ?string $username = '';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getUsernameFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent()
    {
        return TextInput::make('username')
            ->label('اسم الدخول')
            ->required()
            ->autofocus()
            ->autocomplete()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'username' => $data['username'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        RateLimiter::hit($this->getRateLimitingThrottleKey());

        throw ValidationException::withMessages([
            'data.username' => 'اسم الدخول أو كلمة المرور غير صحيحة.',
        ]);
    }

    protected function getRateLimitingThrottleKey(): string
    {
        $username = $this->form->getState()['username'] ?? request()->input('username');

        return 'login|'.$username.'|'.request()->ip();
    }
}
