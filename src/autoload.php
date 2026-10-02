<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $f = __DIR__ . '/' . $class . '.php';
    if (is_file($f)) {
        require $f;
    }
});

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Security::token()) . '">';
}
