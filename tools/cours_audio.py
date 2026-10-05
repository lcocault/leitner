#!/usr/bin/env python3
"""Génère un MP3 par cours Markdown (tous les chapitres à la suite), avec la synthèse vocale locale Piper.

Préparation (une fois) :
    python3 -m venv tts && tts/bin/pip install piper-tts lameenc
    tts/bin/python -m piper.download_voices fr_FR-siwis-medium --data-dir voix

Utilisation :
    tts/bin/python tools/cours_audio.py --voix voix/fr_FR-siwis-medium.onnx [--cours docs/cours] [--sortie docs/audio] [fichier.md ...]

Un MP3 déjà à jour (plus récent que son .md) n'est pas régénéré, sauf avec --force.
"""
import argparse
import glob
import os
import re
import sys
import time

import lameenc
from piper import PiperVoice

DEBIT_KBPS = 64
PAUSE_BLOC = 0.35      # secondes de silence entre deux paragraphes
PAUSE_CHAPITRE = 1.2   # secondes de silence entre deux chapitres

# Sigles que la voix lirait comme un mot : on les épelle phonétiquement.
SIGLES = {
    'SA': 'esse a', 'EI': 'eu i', 'PAO': 'pé a o', 'APE': 'a pé eu', 'AHA': 'a hache a', 'VIH': 'vé i hache',
    'CI': 'cé i', 'UV': 'u vé', 'UVA': 'u vé a', 'UVB': 'u vé bé', 'UVC': 'u vé cé', 'BEP': 'bé eu pé',
    'NB': 'nota béné', 'pH': 'pé hache', 'OUI': 'oui', 'QUOI': 'quoi', 'QUI': 'qui', 'QUAND': 'quand',
    'POURQUOI': 'pourquoi', 'ON': 'on',
}
REMPLACEMENTS = [
    (r'\bE/H/E\b', 'eau dans huile dans eau'), (r'\bH/E/H\b', 'huile dans eau dans huile'),
    (r'\bH/E\b', 'huile dans eau'), (r'\bE/H\b', 'eau dans huile'),
    (r'\bType IV\b', 'Type quatre'), (r'\bType III\b', 'Type trois'), (r'\bType II\b', 'Type deux'), (r'\bType I\b', 'Type un'),
    (r'(?<=\d)[   ](?=\d{3}(?!\d))', ''),          # 37 000 -> 37000
    (r'\s*°C\b', ' degrés'), (r'(\d)\s*°', r'\1 degrés'),
    (r'\s*m²', ' mètres carrés'), (r'\s*€', ' euros'), (r'\s*%', ' pour cent'),
    (r'(\d)\s*ml\b', r'\1 millilitres'), (r'(\d)\s*cm\b', r'\1 centimètres'), (r'(\d)\s*g\b', r'\1 grammes'),
    (r'(\d)\s*m\b', r'\1 mètres'), (r'(\d)\s*min\b', r'\1 minutes'), (r'(\d)\s*h\s*(\d)', r'\1 heures \2'),
    (r'\bn°\s*', 'numéro '), (r'\bKbis\b', 'K bis'),
    (r'[«»"`]', ''), (r'…', '.'), (r'\s\+\s', ' plus '), (r'\s/\s', ', '), (r'/', ' '),
    (r'\s+–\s+', '. '), (r'\s+([,.])', r'\1'), (r'\s{2,}', ' '),
]


def parler(texte):
    """Transforme un fragment Markdown en texte lisible à voix haute."""
    texte = re.sub(r'\*\*|\*', '', texte)
    texte = re.sub(r'\b(' + '|'.join(SIGLES) + r')\b', lambda m: SIGLES[m.group(1)], texte)
    for motif, par in REMPLACEMENTS:
        texte = re.sub(motif, par, texte)
    return texte.strip()


def finir(phrase):
    phrase = phrase.strip()
    return phrase if re.search(r'[.!?:;]$', phrase) else phrase + '.'


def tableau(lignes):
    """Un tableau est lu ligne par ligne : « 1re cellule. En-tête : cellule. »"""
    cellules = [[c.strip() for c in l.strip().strip('|').split('|')] for l in lignes]
    entetes, corps = cellules[0], cellules[2:]
    blocs = []
    for ligne in corps:
        if len(ligne) == 2:
            if ligne[0] or ligne[1]:
                blocs.append(finir(f'{ligne[0]} : {ligne[1]}' if ligne[0] and ligne[1] else ligne[0] or ligne[1]))
            continue
        morceaux = [finir(c if i == 0 or i >= len(entetes) or not entetes[i] else f'{entetes[i]} : {c}')
                    for i, c in enumerate(ligne) if c]
        if morceaux:
            blocs.append(' '.join(morceaux))
    return blocs


def chapitres(markdown):
    """Même découpage que le site : un chapitre par titre de niveau 2 ou 3. Retourne [(titre, [blocs])]."""
    resultat, titre, parent, blocs = [], None, None, []
    lignes = markdown.replace('\r\n', '\n').split('\n')

    def clore():
        if blocs:
            resultat.append((titre or 'Introduction', list(blocs)))
        blocs.clear()

    i = 0
    while i < len(lignes):
        ligne = lignes[i]
        m = re.match(r'^(##|###)\s+(.*)$', ligne)
        if m:
            clore()
            if m.group(1) == '##':
                parent = titre = m.group(2).strip()
            else:
                titre = (parent + ' – ' if parent else '') + m.group(2).strip()
        elif ligne.startswith('|'):
            j = i
            while j < len(lignes) and lignes[j].startswith('|'):
                j += 1
            blocs.extend(tableau(lignes[i:j]))
            i = j
            continue
        elif re.match(r'^\s*(?:[-*+]|\d+\.)\s+', ligne):
            blocs.append(finir(re.sub(r'^\s*(?:[-*+]|\d+\.)\s+', '', ligne)))
        elif ligne.strip():
            blocs.append(finir(ligne))
        i += 1
    clore()
    return resultat


def synthese(voix, texte):
    return b''.join(morceau.audio_int16_bytes for morceau in voix.synthesize(texte))


def generer(voix, source, cible):
    frequence = voix.config.sample_rate
    silence = lambda secondes: b'\x00\x00' * int(frequence * secondes)
    encodeur = lameenc.Encoder()
    encodeur.set_bit_rate(DEBIT_KBPS)
    encodeur.set_in_sample_rate(frequence)
    encodeur.set_channels(1)
    encodeur.set_quality(2)
    encodeur.silence()

    plan = chapitres(open(source, encoding='utf-8').read())
    mp3, echantillons = bytearray(), 0
    for numero, (titre, blocs) in enumerate(plan, 1):
        textes = [finir(f'Chapitre {numero}. {parler(titre)}')] + [parler(b) for b in blocs]
        for texte in filter(None, textes):
            pcm = synthese(voix, texte) + silence(PAUSE_BLOC)
            echantillons += len(pcm) // 2
            mp3 += encodeur.encode(pcm)
        pcm = silence(PAUSE_CHAPITRE)
        echantillons += len(pcm) // 2
        mp3 += encodeur.encode(pcm)
    mp3 += encodeur.flush()
    with open(cible, 'wb') as f:
        f.write(mp3)
    return len(plan), echantillons / frequence


def main():
    p = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    p.add_argument('--voix', required=True, help='modèle Piper (.onnx)')
    p.add_argument('--cours', default='docs/cours', help='dossier des cours Markdown')
    p.add_argument('--sortie', default='docs/audio', help='dossier des MP3')
    p.add_argument('--force', action='store_true', help='régénère même les MP3 à jour')
    p.add_argument('fichiers', nargs='*', help='cours à traiter (par défaut : tous)')
    a = p.parse_args()

    os.makedirs(a.sortie, exist_ok=True)
    sources = a.fichiers or sorted(glob.glob(os.path.join(a.cours, '*.md')))
    voix = PiperVoice.load(a.voix)
    for source in sources:
        cible = os.path.join(a.sortie, os.path.basename(source)[:-3] + '.mp3')
        if not a.force and os.path.exists(cible) and os.path.getmtime(cible) >= os.path.getmtime(source):
            print(f'à jour   {os.path.basename(cible)}', flush=True)
            continue
        debut = time.time()
        nb, duree = generer(voix, source, cible)
        print(f'{nb:3d} chap. {duree / 60:5.1f} min  {os.path.getsize(cible) / 1e6:5.1f} Mo  '
              f'({time.time() - debut:.0f} s)  {os.path.basename(cible)}', flush=True)


if __name__ == '__main__':
    sys.exit(main())
