-- Migration 001 : résumé des documents
-- Passe une base créée avec le schéma initial au schéma courant.
-- Sans effet si la colonne existe déjà ; aucune donnée existante n'est modifiée.
BEGIN;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS resume TEXT;
COMMIT;
