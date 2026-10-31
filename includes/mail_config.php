<?php
function mail_settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $file = __DIR__ . '/mail.env';
    $values = is_file($file) ? (parse_ini_file($file) ?: []) : [];
    $read = function (string $key, string $default = '') use ($values): string {
        $env = getenv($key);
        if ($env !== false && $env !== '') {
            return (string) $env;
        }
        return (string) ($values[$key] ?? $default);
    };
    $settings = [
        'host' => $read('MAIL_HOST', 'sandbox.smtp.mailtrap.io'),
        'port' => (int) $read('MAIL_PORT', '2525'),
        'username' => $read('MAIL_USERNAME'),
        'password' => $read('MAIL_PASSWORD'),
        'from_address' => $read('MAIL_FROM_ADDRESS', 'orders@cconsole.test'),
        'from_name' => $read('MAIL_FROM_NAME', SHOP_NAME),
    ];
    return $settings;
}

function mail_is_configured(): bool
{
    $settings = mail_settings();
    return $settings['username'] !== '' && $settings['password'] !== '';
}
