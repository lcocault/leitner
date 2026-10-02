# Boîte de Leitner

Application web PHP 8.2+ / PostgreSQL pour réviser (CAP esthétique) avec le système de Leitner. Pas de dépendance Composer.

## Structure
- `public/` : point d'entrée (`index.php`) et assets — racine web
- `src/` : classes (contrôleurs, `Repository` PDO, `Leitner` logique métier, `Markdown`, `Importer`, `Security`)
- `views/` : templates PHP
- `sql/schema.sql` : création des tables
- `config.php` / `.env.example` : configuration par variables d'environnement

## Installation
1. `psql -f sql/schema.sql` sur la base PostgreSQL.
2. Définir les variables (voir `.env.example`) : `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_PASSWORD_HASH`
   (hash : `php -r 'echo password_hash("motdepasse", PASSWORD_DEFAULT);'`).
3. Pointer le site (AlwaysData) sur le dossier `public/`.

Test local : `php -S 127.0.0.1:8000 -t public`.
