-- Schéma PostgreSQL de la boîte de Leitner
CREATE TABLE IF NOT EXISTS documents (
    id          SERIAL PRIMARY KEY,
    titre       TEXT NOT NULL UNIQUE,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS fiches (
    id                      SERIAL PRIMARY KEY,
    document_id             INTEGER NOT NULL REFERENCES documents(id) ON DELETE RESTRICT,
    question                TEXT NOT NULL,
    reponse                 TEXT NOT NULL,
    niveau_maturite         TEXT NOT NULL DEFAULT 'M1' CHECK (niveau_maturite IN ('M1','M2','M3','M4','M5')),
    date_presentation_min   DATE NOT NULL DEFAULT CURRENT_DATE,
    paragraphe_reference    TEXT,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_fiches_document ON fiches(document_id);
CREATE INDEX IF NOT EXISTS idx_fiches_date ON fiches(date_presentation_min);

CREATE TABLE IF NOT EXISTS historique_revisions (
    id             SERIAL PRIMARY KEY,
    fiche_id       INTEGER NOT NULL REFERENCES fiches(id) ON DELETE CASCADE,
    date_revision  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resultat       TEXT NOT NULL CHECK (resultat IN ('correct','incorrect','incomplet')),
    niveau_avant   TEXT NOT NULL CHECK (niveau_avant IN ('M1','M2','M3','M4','M5')),
    niveau_apres   TEXT NOT NULL CHECK (niveau_apres IN ('M1','M2','M3','M4','M5'))
);
CREATE INDEX IF NOT EXISTS idx_histo_fiche ON historique_revisions(fiche_id);
