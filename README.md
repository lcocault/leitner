# Boîte de Leitner

Application web PHP 8.2+ / PostgreSQL pour réviser (CAP esthétique) avec le système de Leitner. Pas de dépendance Composer.

## Structure
- `public/` : point d'entrée (`index.php`) et assets — racine web
- `src/` : classes (contrôleurs, `Repository` PDO, `Leitner` logique métier, `Markdown`, `Importer`, `Security`)
- `tools/cours_audio.py` : génération des MP3 des cours (voir [Audio](#audio))
- `views/` : templates PHP
- `sql/schema.sql` : création des tables
- `config.php` / `.env.example` : configuration par variables d'environnement

## Installation
1. `psql -f sql/schema.sql` sur la base PostgreSQL (nouvelle installation).
   Base existante : appliquer dans l'ordre les scripts de `sql/migrations/` (`001_add_document_resume.sql`, puis `002_add_document_cours.sql`).
2. Définir les variables (voir `.env.example`) : `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_PASSWORD_HASH`
   (hash : `php -r 'echo password_hash("motdepasse", PASSWORD_DEFAULT);'`).
3. Pointer le site (AlwaysData) sur le dossier `public/`.
4. Facultatif : `AUDIO_BASE_URL` = racine HTTPS (ex. bucket S3 public) des versions audio des cours. Le lecteur de la page
   d'un document charge `<AUDIO_BASE_URL>/<titre du document>.mp3` (MP3 générés par `tools/cours_audio.py`).

Test local : `php -S 127.0.0.1:8000 -t public`.

## Import de contenus

Les contenus (fiches de révision, résumé et cours complet d'un document) sont chargés par la page **Import**
(`index.php?page=import`) à partir d'**un fichier JSON**. Un document de référence (par exemple un PDF de cours) peut recevoir :

| Contenu | Où il apparaît | Comment l'importer |
|---|---|---|
| **Fiches** question / réponse | Révision (boîtes de Leitner) | un objet par fiche |
| **Résumé** | page du document | champ `resume` d'un objet « document » |
| **Cours complet** | page du document, découpé en chapitres | champ `cours` d'un objet « document » |
| **Audio** du cours | lecteur de la page du document | MP3 déposé à part (voir [Audio](#audio)) |

### Procédure
1. Produire le fichier JSON (à la main, ou avec un LLM : voir le [prompt](#prompt-pour-un-llm) ci-dessous).
2. Ouvrir la page **Import**, choisir le fichier, cliquer sur **Importer**.
3. Lire le bandeau de rapport : `N fiche(s) importée(s), N résumé(s) et N cours enregistré(s), N erreur(s)`. Les erreurs sont listées
   avec le numéro de l'objet fautif (`Fiche #12 : …`, `Document #3 : …`) ; les objets valides sont importés malgré les autres erreurs.

### Format du fichier
Un **tableau JSON** (UTF-8) d'objets. Il y a deux sortes d'objets, qui peuvent être mélangés dans le même fichier.

**1. Fiche** : une question et sa réponse, rattachées à un document.

| Champ | Requis | Contenu |
|---|---|---|
| `document` | oui | titre du document de référence (255 caractères max). Le document est créé s'il n'existe pas |
| `question` | oui | texte brut, une seule notion par fiche |
| `reponse` | oui | Markdown (voir ci-dessous) |
| `niveau_maturite` | non | `M1` à `M5` (défaut `M1`). Utiliser `M1` pour un nouveau contenu |
| `date_presentation_min` | non | `AAAA-MM-JJ`, première date de présentation (défaut : date du jour) |
| `paragraphe_reference` | non | section du document source, affichée avec la fiche (ex. `B. Les anomalies`) |

**2. Contenu d'un document** : un objet **sans** `question` ni `reponse`, avec `document` et au moins l'un de `resume`, `cours`.
Il ne crée aucune fiche ; il enregistre (ou remplace) le résumé et/ou le cours du document.

| Champ | Requis | Contenu |
|---|---|---|
| `document` | oui | titre du document (255 caractères max) |
| `resume` | non | Markdown, texte court de 1 à 3 paragraphes |
| `cours` | non | Markdown, contenu complet du document |

Exemple :

```json
[
  {
    "document": "CAP - Pole 2 L'appareil unguéal",
    "resume": "**L'ongle**\n\nL'ongle est une plaque cornée … ",
    "cours": "## L'appareil unguéal : l'ongle\n\nL'ongle est …\n\n## A. Structure\n\n- La matrice …"
  },
  {
    "document": "CAP - Pole 2 L'appareil unguéal",
    "question": "Qu'est-ce que l'hyponychium ?",
    "reponse": "L'**épaississement de l'épiderme** à l'extrémité du lit de l'ongle.",
    "niveau_maturite": "M1",
    "date_presentation_min": "2026-10-21",
    "paragraphe_reference": "A. Structure – vocabulaire"
  }
]
```

### Règles et limites
- **Le titre `document` fait la jointure** : il doit être **identique, caractère pour caractère**, dans tous les objets d'un même
  document, et dans le nom du MP3 (`<titre>.mp3`). Convention : nom du PDF sans l'extension `.pdf`.
- **Réimport** : un objet « contenu » remplace le résumé / le cours existants ; en revanche chaque objet « fiche » **crée une nouvelle
  fiche** (pas de dédoublonnage). Ne pas réimporter deux fois le même fichier de fiches.
- Limites : 2000 objets maximum par fichier, 5 Mo maximum (et `upload_max_filesize` / `post_max_size` de PHP).
  Pour un gros volume, découper en plusieurs fichiers.
- Les champs doivent être des chaînes de caractères ; un champ invalide rejette l'objet concerné (pas le fichier entier).
  Un fichier qui n'est pas un tableau JSON valide est rejeté en entier.

### Markdown accepté
Dans `reponse`, `resume` et `cours` : titres `#` à `######`, listes à puces (`-`) et numérotées (`1.`), tableaux (ligne d'en-tête,
ligne `|---|---|`, lignes de données), citations (`>`), blocs de code, `**gras**`, `*italique*`, `` `code` `` et liens.
Dans le JSON, les retours à la ligne s'écrivent `\n` et les guillemets `\"`.

Pour `cours`, **chaque titre `##` ou `###` ouvre un chapitre** de lecture (et un chapitre de l'audio) : commencer le cours par un
titre `##`, utiliser `##` pour les grandes parties et `###` pour les sous-parties.

### Audio
Le MP3 d'un cours est généré à partir du Markdown du cours (un chapitre annoncé par titre `##` / `###`) avec la synthèse vocale locale Piper :

```bash
python3 -m venv tts && tts/bin/pip install piper-tts lameenc
tts/bin/python -m piper.download_voices fr_FR-siwis-medium --data-dir voix
tts/bin/python tools/cours_audio.py --voix voix/fr_FR-siwis-medium.onnx --cours docs/cours --sortie docs/audio
```

Le fichier produit porte le nom du `.md` (donc le titre du document) avec l'extension `.mp3` ; le déposer à la racine de
`AUDIO_BASE_URL`. Un MP3 à jour n'est pas régénéré (sauf `--force`). Pour une bonne lecture vocale, écrire les unités et symboles en
toutes lettres dans le cours (« 20 mètres carrés », « 5 pour cent ») ; les sigles courants sont épelés par `tools/cours_audio.py`
(dictionnaire `SIGLES` à compléter si besoin).

### Prompt pour un LLM
À coller dans un LLM en lui fournissant le PDF (ou son texte). Remplacer `<TITRE>` par le nom du PDF sans `.pdf` et `<DATE>` par la date
du jour (`AAAA-MM-JJ`). Relire le résultat avant import : le LLM peut omettre ou déformer un point.

````text
Tu es un assistant pédagogique. À partir du document fourni (PDF de cours, niveau CAP esthétique), produis UN fichier JSON
d'import pour une application de révision par fiches (système de Leitner). Réponds uniquement avec le JSON, sans texte autour,
sans bloc de code Markdown.

Le fichier est un tableau JSON (UTF-8) contenant, dans cet ordre :

1) UN objet « contenu du document » :
{
  "document": "<TITRE>",
  "resume": "<résumé en Markdown>",
  "cours": "<cours complet en Markdown>"
}

2) PUIS les objets « fiche », un par notion :
{
  "document": "<TITRE>",
  "question": "<question claire et précise, une seule notion>",
  "reponse": "<réponse en Markdown>",
  "niveau_maturite": "M1",
  "date_presentation_min": "<DATE>",
  "paragraphe_reference": "<section du document : titre ou lettre du paragraphe>"
}

RÈGLES GÉNÉRALES
- Le champ "document" vaut exactement "<TITRE>" dans tous les objets (même casse, mêmes accents, mêmes espaces).
- Tout le contenu doit venir du document : n'invente rien, ne complète pas avec tes connaissances. Si un passage est illisible
  ou absent, ne le comble pas. Le texte est en français.
- Dans le JSON, échappe correctement les guillemets (\") et écris les retours à la ligne \n. Aucune virgule finale.
- Markdown autorisé : titres, listes (- ou 1.), tableaux (| a | b | puis |---|---|), **gras**, *italique*.

RÈGLES POUR "resume"
- 1 à 4 paragraphes courts, une idée par phrase, mots-clés importants en **gras**.
- Il doit permettre de comprendre l'essentiel du document en 1 minute. Pas de titre de niveau 1.

RÈGLES POUR "cours" (reprend TOUT le contenu du document, sans résumer)
- Reformule proprement le document dans l'ordre d'origine, sans perdre aucune information (définitions, chiffres, listes,
  tableaux, protocoles, précautions, exemples). Transcris les tableaux en tableaux Markdown, y compris ceux qui sont des images.
- Commence par un titre "## <titre du cours>". Utilise "##" pour les grandes parties et "###" pour les sous-parties : chaque
  titre ouvre un chapitre. N'utilise pas de titre "#". Ne mets pas de titre vide.
- Le cours est aussi lu à voix haute : écris les unités et symboles en toutes lettres (« 37 degrés », « 5 pour cent »,
  « 2 mètres carrés », « 300 euros »), évite les abréviations ambiguës, n'utilise ni émojis ni ligne de séparation.
- Ne fais pas référence à des schémas ou photos (« voir figure ») ; décris leur contenu utile en une phrase si nécessaire.

RÈGLES POUR LES FICHES
- Couvre tout le contenu du cours : une fiche par notion distincte (définition, rôle, caractéristiques, étapes, contre-indications,
  chiffres clés, comparaisons…). Vise environ 10 fiches par page de document, entre 10 et 40 au total pour un document court ;
  plus pour un long document. Aucune question en double.
- "question" : autonome (compréhensible sans le document), précise, au singulier sur une seule notion. Évite « Quelles sont les
  informations du document ? ». Pour une liste, demande-la explicitement (« Quelles sont les 4 couches de l'épiderme ? »).
- "reponse" : courte et complète, 1 à 6 lignes. Utilise une liste à puces pour une énumération, un tableau Markdown pour une
  comparaison, **gras** pour les mots-clés à retenir. Pas de formule d'introduction (« La réponse est… »).
- "niveau_maturite" : toujours "M1". "date_presentation_min" : toujours "<DATE>".
- "paragraphe_reference" : section d'origine (ex. "B. Les anomalies", "Protocole de soin"), jamais vide.

AVANT DE RÉPONDRE, vérifie : JSON valide ; premier objet = contenu du document ; "document" identique partout ; chaque fiche a
question, reponse, niveau_maturite, date_presentation_min ; aucun point du document n'est oublié ni inventé.
````
