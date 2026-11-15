<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $f = __DIR__ . '/' . $class . '.php';
    if (is_file($f)) {
        require $f;
    }
});

/** Regroupe des lignes (documents…) par catégorie, dans l'ordre reçu ; les lignes sans catégorie sont rangées sous « Sans catégorie », à la fin. */
function par_categorie(array $rows): array
{
    $groupes = [];
    foreach ($rows as $r) {
        $groupes[$r['categorie'] ?? ''][] = $r;
    }
    if (isset($groupes[''])) {
        $sans = $groupes[''];
        unset($groupes['']);
        $groupes['Sans catégorie'] = $sans;
    }
    return $groupes;
}

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(Security::token()) . '">';
}
