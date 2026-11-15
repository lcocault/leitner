-- Migration 003 : catégorie des documents (ex. « CAP Esthétique - Pole 1 »)
-- Ajoute la colonne, puis classe les documents déjà importés selon leur pôle. Idempotente : ne modifie jamais
-- un document qui a déjà une catégorie. Les documents absents de la liste restent sans catégorie.
BEGIN;
ALTER TABLE documents ADD COLUMN IF NOT EXISTS categorie TEXT;
CREATE INDEX IF NOT EXISTS idx_documents_categorie ON documents(categorie);

-- Pole 1 : 40 documents
UPDATE documents SET categorie = 'CAP Esthétique - Pole 1' WHERE categorie IS NULL AND titre IN (
    'BP- P1 - COSMETOLOGIE - LES PRODUITS FINIS SEMI PERMANENT',
    'Book de modelage visage signature terrade',
    'CAP - POLE 1 - Les instruments de maquillage',
    'CAP - POLE 1 - Les produits de maquillage',
    'CAP - Pole 1 - Colorimetrie',
    'CAP - Pole 1 - Les produits de soin',
    'CAP - Pole 1 - Maquillage soir',
    'CAP - Pole 1 Constituants de la matiere vivante',
    'CAP - Pole 1 Les appareils de sterilisation.docx',
    'CAP - Pole 1 Les principes actifs.docx',
    'CAP POLE 1 Le systeme tegumentaire',
    'CAP POLE 1 Les instruments de soin visage.docx',
    'CAP POLE 1- Les produits nettoyants.docx',
    'CAP POLE 1-COSMETOLOGIE-Les produits finis gommant.docx',
    'CAP POLE 1-Les formes galeniques.docx',
    'CAP- POLE 1 Fiche synthese biocontamination',
    'CAP- POLE 1 La biocontamination et prevention.docx',
    'CAP- POLE 1-COSMETOLOGIE -Composition d''un produit cosmetique',
    'CAP- Pole 1 - La cellule',
    'CAP- Pole 1 - Les Masques.docx',
    'CAP- Pole 1 - Peau seborrheique',
    'CAP- Pole 1 Anatomie des os et des muscles.docx',
    'CAP- PÔLE 1 - Les produits d''hygiène',
    'CAP- PÔLE 1- L''historique du parfum',
    'CAP- PÔLE 1- Les déodorants',
    'CAP- PÔLE 1- Les parfumerie alcoolique',
    'CAP- PÔLE 1- Les produits capillaires',
    'CAP- PÔLE 1- Les produits de rasage',
    'CAP-P1-TECHNOLOGIE-HAUTE FREQUENCE',
    'CAP-P1-TECHNOLOGIE-LA DESINCRUSTATION',
    'CAP-POLE 1 - La peau seche alipidique',
    'CAP-POLE 1 - Le demaquillage',
    'CAP-POLE 1 - Le maquillage flash',
    'CAP-POLE 1 - Le maquillage jour',
    'CAP-POLE 1 L''appareil a ultrasons',
    'CAP-POLE 1- BIOLOGIE- LES TYPES ET ETATS DE PEAUX.docx',
    'CAP-POLE 1- La peau normale',
    'CAP-Pole 1 - Ionophorese',
    'Fiche synthese - Eau constituante de la matiere',
    'Les annexes cutanees'
);

-- Pole 2 : 18 documents
UPDATE documents SET categorie = 'CAP Esthétique - Pole 2' WHERE categorie IS NULL AND titre IN (
    'BTS- Vernis semi permanent',
    'CAP - POLE 2 - L''épilation des sourcils',
    'CAP - POLE 2 L''appareil unguéal',
    'CAP - POLE 2 Les affections de l''appareil unguéal',
    'CAP - POLE 2 Les produits de maquillage de l''ongle',
    'CAP - POLE 2 Les produits de soin des ongles',
    'CAP - Pôle 2 Epilation maillot',
    'CAP-POLE 2- Epilation des aisselles',
    'CAP-POLE 2- Epilation des demi jambes',
    'CAP-POLE 2- L''appareil pilo sébacée',
    'CAP-POLE 2- Les affections du système',
    'CAP-POLE2- Les Appareils à cires',
    'CAP-PÔLE 2 Les instruments utilisés en épilation',
    'CAP-PÔLE 2 Les produits d''épilation',
    'CAP-PÔLE 2- La beauté des mains',
    'CAP-PÔLE 2- La beauté des pieds',
    'CAP-PÔLE 2- La pose de vernis classique.French.Fantaisie',
    'CAP-PÔLE 2- Les instruments de manucure'
);

-- Pole 3 : 25 documents
UPDATE documents SET categorie = 'CAP Esthétique - Pole 3' WHERE categorie IS NULL AND titre IN (
    'CAP - Fichier client',
    'CAP - POLE 3 - CONCURRENT',
    'CAP - POLE 3 - La fidélisation client',
    'CAP - POLE 3 - Les outils d''aide à la vente',
    'CAP - Pôle 3 Aménagement des espaces et des postes de travail',
    'CAP - Pôle 3 L''organigramme',
    'CAP - Pôle 3 Le système olfactif',
    'CAP - Pôle 3 Les formes juridiques et commerciales',
    'CAP POLE 3 L''image du professionnel (1)',
    'CAP POLE 3 Les principes de base de la communication',
    'CAP PÔLE 3 La typologie comportementale des clients',
    'CAP PÔLE 3 Le comportement professionnel',
    'CAP PÔLE 3 Les PLV et ILV',
    'CAP PÔLE 3 Les différentes étapes de la vente',
    'CAP PÔLE 3 Les techniques de marchandisage',
    'CAP PÔLE 3 Les techniques de réalisation des vitrines',
    'CAP- PÔLE 3 - CADRE ORGANISATIONNEL- La déontologie',
    'CAP- PÔLE 3 - CADRE ORGANISATIONNEL- Les démarches d_installation',
    'CAP-POLE 3 Guide d''élboration dossier EP3',
    'CAP-POLE 3 Les outils de prise de rendez-vous',
    'CAP-POLE 3- La tenue de caisse',
    'CAP-PÔLE 3- CADRE ORGANISATIONNEL- Les organismes de contrôle',
    'CAP_POLE 3_Produits cosmétiques VS Médicament',
    'GUIDE DE RÉDACTION DU DOSSIER DE VENTE',
    'Vente - Prestations'
);

COMMIT;
