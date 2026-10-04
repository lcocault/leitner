-- Migration 002 : cours complet des documents (Markdown)
-- Sans effet si la colonne existe déjà ; aucune donnée existante n'est modifiée.
BEGIN;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS cours TEXT;
COMMIT;
