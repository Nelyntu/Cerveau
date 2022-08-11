<?php

namespace Twitch\CommandHandler\Hangman;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twitch\Command;
use Twitch\CommandHandler\CommandHandlerInterface;

class HangmanCommandHandler implements CommandHandlerInterface
{
    private const COMMAND_NAME = 'hg';

    public function __construct(private readonly FilesystemAdapter $cache, private readonly TranslatorInterface $translator)
    {
    }

    public function supports(string $name): bool
    {
        return $name === self::COMMAND_NAME;
    }

    public function handle(Command $command): ?string
    {
        /** @var HangmanSession $session */
        /** @var CacheItem $wordToFindItemCache */
        [$wordToFindItemCache, $session] = $this->retrieveWord();

        $suggestedLetter = $command->arguments->firstArgument;
        if ($suggestedLetter === null) {
            return $this->translator->trans(
                'commands.hg.situation',
                ['%wordFoundByUsers%' => $session->getWordFoundByUsers()],
                'commands');
        }

        if (!is_string($suggestedLetter) || strlen($suggestedLetter) > 1) {
            return $this->translator->trans( 'commands.hg.invalid_letter', [], 'commands');
        }

        $suggestedLetter = mb_strtoupper($suggestedLetter);

        // cooldown detection
        $coolDownItemCache = $this->cache->getItem('cerveau:command:hg:cooldown:' . $command->user);
        if ($coolDownItemCache->isHit()) {
            $remainingCoolDownTime = (int)($coolDownItemCache->get() - microtime(true));

            return $this->translator->trans('commands.hg.triggered_cooldown', ['%remainingCoolDownTime%' => $remainingCoolDownTime], 'commands');
        }

        $coolDownItemCache->expiresAfter(1);
        $coolDownItemCache->set(1 + microtime(true));
        $this->cache->save($coolDownItemCache);

        if ($session->isLetterAlreadySuggested($suggestedLetter)) {
            return $this->translator->trans( 'commands.hg.already_suggested_letter', ['%suggestedLetter%' => $suggestedLetter], 'commands');
        }

        if ($session->suggestLetter($suggestedLetter)) {
            $responseToProposition = $this->translator->trans( 'commands.hg.succeed_suggested_letter', ['%suggestedLetter%' => $suggestedLetter,], 'commands');

        } else {
            $fails = $session->getFails();
            if ($fails === 9) {
                $this->cache->deleteItem('cerveau:command:hg:v3:data');

                return $this->translator->trans( 'commands.hg.game_over', ['%wordToFind%' => $session->getWordToFind()], 'commands');
            }
            $responseToProposition = $this->translator->trans( 'commands.hg.failed_suggested_letter', ['%suggestedLetter%' => $suggestedLetter, '%tries%' => (9 - $fails)], 'commands');
        }

        $wordToFindItemCache->set($session);
        $this->cache->save($wordToFindItemCache);

        if ($session->isWordFound()) {
            $this->cache->deleteItem('cerveau:command:hg:v3:data');

            return $this->translator->trans( 'commands.hg.win', ['%wordToFind%' => $session->getWordToFind()], 'commands');
        }

        return $responseToProposition . $this->translator->trans( 'commands.hg.reminder', ['%wordFoundByUsers%' => $session->getWordFoundByUsers()], 'commands');
    }

    public function isAuthorized(string $username): bool
    {
        return true;
    }

    public function getName(): array
    {
        return [self::COMMAND_NAME];
    }

    public function getRandomWord(): string
    {
        $possibleWords = [
            'ANGLE',
            'ARMOIRE',
            'BANC',
            'BUREAU',
            'CABINET',
            'CARREAU',
            'CHAISE',
            'CLASSE',
            'CLEF',
            'COIN',
            'COULOIR',
            'DOSSIER',
            'EAU',
            'ECOLE',
            'ENTRER',
            'ESCALIER',
            'ETAGERE',
            'EXTERIEUR',
            'FENETRE',
            'INTERIEUR',
            'LAVABO',
            'LIT',
            'MARCHE',
            'MATELAS',
            'MATERNELLE',
            'MEUBLE',
            'MOUSSE',
            'MUR',
            'PELUCHE',
            'PLACARD',
            'PLAFOND',
            'PORTE',
            'POUBELLE',
            'RADIATEUR',
            'RAMPE',
            'RIDEAU',
            'ROBINET',
            'SALLE',
            'SALON',
            'SERRURE',
            'SERVIETTE',
            'SIEGE',
            'SIESTE',
            'SILENCE',
            'SOL',
            'SOMMEIL',
            'SONNETTE',
            'SORTIE',
            'TABLE',
            'TABLEAU',
            'TABOURET',
            'TAPIS',
            'TIROIR',
            'TOILETTE',
            'VITRE',
            'ALLER',
            'AMENER',
            'APPORTER',
            'APPUYER',
            'ATTENDRE',
            'BAILLER',
            'COUCHER',
            'DORMIR',
            'ECLAIRER',
            'EMMENER',
            'EMPORTER',
            'ENTRER',
            'FERMER',
            'FRAPPER',
            'INSTALLER',
            'LEVER',
            'OUVRIR',
            'PRESSER',
            'RECHAUFFER',
            'RESTER',
            'SONNER',
            'SORTIR',
            'VENIR',
            'ABSENT',
            'ASSIS',
            'BAS',
            'HAUT',
            'PRESENT',
            'GAUCHE',
            'DROITE',
            'DEBOUT',
            'DEDANS',
            'DEHORS',
            'FACE',
            'LOIN',
            'PRES',
            'TARD',
            'TOT',
            'APRES',
            'AVANT',
            'CONTRE',
            'DANS',
            'DE',
            'DERRIERE',
            'DEVANT',
            'DU',
            'SOUS',
            'SUR',
            'CRAYON',
            'STYLO',
            'FEUTRE',
            'MINE',
            'GOMME',
            'DESSIN',
            'COLORIAGE',
            'RAYURE',
            'PEINTURE',
            'PINCEAU',
            'COULEUR',
            'CRAIE',
            'PAPIER',
            'FEUILLE',
            'CAHIER',
            'CARNET',
            'CARTON',
            'CISEAUX',
            'DECOUPAGE',
            'PLIAGE',
            'PLI',
            'COLLE',
            'AFFAIRE',
            'BOITE',
            'CASIER',
            'CAISSE',
            'TROUSSE',
            'CARTABLE',
            'JEU',
            'JOUET',
            'PION',
            'DOMINO',
            'PUZZLE',
            'CUBE',
            'PERLE',
            'CHOSE',
            'FORME',
            'CARRE',
            'ROND',
            'PATE',
            'MODELER',
            'TAMPON',
            'LIVRE',
            'HISTOIRE',
            'BIBLIOTHEQUE',
            'IMAGE',
            'ALBUM',
            'TITRE',
            'CONTE',
            'DICTIONNAIRE',
            'MAGAZINE',
            'CATALOGUE',
            'PAGE',
            'LIGNE',
            'MOT',
            'ENVELOPPE',
            'ETIQUETTE',
            'CARTE',
            'APPEL',
            'AFFICHE',
            'ALPHABET',
            'APPAREIL',
            'CAMESCOPE',
            'CASSETTE',
            'CHAINE',
            'CHANSON',
            'CHIFFRE',
            'CONTRAIRE',
            'DIFFERENCE',
            'DOIGT',
            'ECRAN',
            'ECRITURE',
            'FILM',
            'FOIS',
            'FOI',
            'IDEE',
            'INSTRUMENT',
            'INTRUS',
            'LETTRE',
            'LISTE',
            'MAGNETOSCOPE',
            'MAIN',
            'MICRO',
            'MODELE',
            'MUSIQUE',
            'NOM',
            'NOMBRE',
            'ORCHESTRE',
            'ORDINATEUR',
            'PHOTO',
            'POINT',
            'POSTER',
            'POUCE',
            'PRENOM',
            'QUESTION',
            'RADIO',
            'SENS',
            'TAMBOUR',
            'TELECOMMANDE',
            'TELEPHONE',
            'TELEVISION',
            'TRAIT',
            'TROMPETTE',
            'VOIX',
            'XYLOPHONE',
            'ZERO',
            'CHANTER',
            'CHERCHER',
            'CHOISIR',
            'CHUCHOTER',
            'COLLER',
            'COLORIER',
            'COMMENCER',
            'COMPARER',
            'COMPTER',
            'CONSTRUIRE',
            'CONTINUER',
            'COPIER',
            'COUPER',
            'DECHIRER',
            'DECOLLER',
            'DECORER',
            'DECOUPER',
            'DEMOLIR',
            'DESSINER',
            'DIRE',
            'DISCUTER',
            'ECOUTER',
            'ECRIRE',
            'EFFACER',
            'ENTENDRE',
            'ENTOURER',
            'ENVOYER',
            'FAIRE',
            'FINIR',
            'FOUILLER',
            'GOUTER',
            'IMITER',
            'LAISSER',
            'LIRE',
            'METTRE',
            'MONTRER',
            'OUVRIR',
            'PARLER',
            'PEINDRE',
            'PLIER',
            'POSER',
            'PRENDRE',
            'PREPARER',
            'RANGER',
            'RECITER',
            'RECOMMENCER',
            'REGARDER',
            'REMETTRE',
            'REPETER',
            'REPONDRE',
            'SENTIR',
            'SOULIGNER',
            'TAILLER',
            'TENIR',
            'TERMINER',
            'TOUCHER',
            'TRAVAILLER',
            'TRIER',
            'AMI',
            'ATTENTION',
            'CAMARADE',
            'COLERE',
            'COPAIN',
            'COQUIN',
            'DAME',
            'DIRECTEUR',
            'DIRECTRICE',
            'DROIT',
            'EFFORT',
            'ELEVE',
            'ENFANT',
            'FATIGUE',
            'FAUTE',
            'FILLE',
            'GARCON',
            'GARDIEN',
            'MADAME',
            'MAITRE',
            'MAITRESSE',
            'MENSONGE',
            'ORDRE',
            'PERSONNE',
            'RETARD',
            'JOUEUR',
            'SOURIRE',
            'TRAVAIL',
            'AIDER',
            'DEFENDRE',
            'DESOBEIR',
            'DISTRIBUER',
            'ECHANGER',
            'EXPLIQUER',
            'GRONDER',
            'OBEIR',
            'OBLIGER',
            'PARTAGER',
            'PRETER',
            'PRIVER',
            'PROMETTRE',
            'PROGRES',
            'PROGRESSER',
            'PUNIR',
            'QUITTER',
            'RACONTER',
            'EXPLIQUER',
            'REFUSER',
            'SEPARER',
            'BLOND',
            'BRUN',
            'CALME',
            'CURIEUX',
            'DIFFERENT',
            'DOUX',
            'ENERVER',
            'GENTIL',
            'GRAND',
            'HANDICAPE',
            'INSEPARABLE',
            'JALOUX',
            'MOYEN',
            'MUET',
            'NOIR',
            'NOUVEAU',
            'PETIT',
            'POLI',
            'PROPRE',
            'ROUX',
            'SAGE',
            'SALE',
            'SERIEUX',
            'SOURD',
            'TRANQUILLE',
            'ARROSOIR',
            'ASSIETTE',
            'BALLE',
            'BATEAU',
            'BOITE',
            'BOUCHON',
            'BOUTEILLE',
            'BULLES',
            'CANARD',
            'CASSEROLE',
            'CUILLERE',
            'CUVETTE',
            'DOUCHE',
            'ENTONNOIR',
            'GOUTTES',
            'LITRE',
            'MOULIN',
            'PLUIE',
            'POISSON',
            'PONT',
            'POT',
            'ROUE',
            'SAC',
            'PLASTIQUE',
            'SALADIER',
            'SEAU',
            'TABLIER',
            'TASSE',
            'TROUS',
            'VERRE',
            'AGITER',
            'AMUSER',
            'ARROSER',
            'ATTRAPER',
            'AVANCER',
            'BAIGNER',
            'BARBOTER',
            'BOUCHER',
            'BOUGER',
            'DEBORDER',
            'DOUCHER',
            'ECLABOUSSER',
            'ESSUYER',
            'ENVOYER',
            'COULER',
            'PARTIR',
            'FLOTTER',
            'GONFLER',
            'INONDER',
            'JOUER',
            'LAVER',
            'MELANGER',
            'MOUILLER',
            'NAGER',
            'PLEUVOIR',
            'PLONGER',
            'POUSSER',
            'POUVOIR',
            'PRESSER',
            'RECEVOIR',
            'REMPLIR',
            'RENVERSER',
            'SECHER',
            'SERRER',
            'SOUFFLER',
            'TIRER',
            'TOURNER',
            'TREMPER',
            'VERSER',
            'VIDER',
            'VOULOIR',
            'AMUSANT',
            'CHAUD',
            'FROID',
            'HUMIDE',
            'INTERESSANT',
            'MOUILLE',
            'SEC',
            'TRANSPARENT',
            'MOITIE',
            'AUTANT',
            'BEAUCOUP',
            'ENCORE',
            'MOINS',
            'PEU',
            'PLUS',
            'TROP',
            'ANORAK',
            'ARC',
            'BAGAGE',
            'BAGUETTE',
            'BARBE',
            'BONNET',
            'BOTTE',
            'BOUTON',
            'BRETELLE',
            'CAGOULE',
            'CASQUE',
            'CASQUETTE',
            'CEINTURE',
            'CHAPEAU',
            'CHAUSSETTE',
            'CHAUSSON',
            'CHAUSSURE',
            'CHEMISE',
            'CIGARETTE',
            'COL',
            'COLLANT',
            'COURONNE',
            'CRAVATE',
            'CULOTTE',
            'ECHARPE',
            'EPEE',
            'FEE',
            'FLECHE',
            'FUSIL',
            'GANT',
            'HABIT',
            'JEAN',
            'JUPE',
            'LACET',
            'LAINE',
            'LINGE',
            'LUNETTES',
            'MAGICIEN',
            'MAGIE',
            'MAILLOT',
            'MANCHE',
            'MANTEAU',
            'MOUCHOIR',
            'MOUFLE',
            'NOEUD',
            'PAIRE',
            'PANTALON',
            'PIED',
            'POCHE',
            'PRINCE',
            'PYJAMA',
            'REINE',
            'ROBE',
            'ROI',
            'RUBAN',
            'SEMELLE',
            'SOLDAT',
            'SOCIERE',
            'TACHE',
            'TAILLE',
            'TALON',
            'TISSU',
            'TRICOT',
            'UNIFORME',
            'VALISE',
            'VESTE',
            'VETEMENT',
            'CHANGER',
            'CHAUSSER',
            'COUVRIR',
            'DEGUISER',
            'DESHABILLER',
            'ENLEVER',
            'HABILLER',
            'LACER',
            'PORTER',
            'RESSEMBLER',
            'CLAIR',
            'COURT',
            'ETROIT',
            'FONCE',
            'JOLI',
            'LARGE',
            'LONG',
            'MULTICOLORE',
            'NU',
            'USE',
            'BIEN',
            'MAL',
            'MIEUX',
            'PRESQUE',
            'AIGUILLE',
            'AMPOULE',
            'AVION',
            'BOIS',
            'BOUT',
            'BRICOLAGE',
            'BRUIT',
            'CABANE',
            'CARTON',
            'CLOU',
            'COLLE',
            'CROCHET',
            'ELASTIQUE',
            'FICELLE',
            'FIL',
            'MARIONNETTE',
            'MARTEAU',
            'METAL',
            'METRE',
            'MORCEAU',
            'MOTEUR',
            'OBJET',
            'OUTIL',
            'PEINTURE',
            'PINCEAU',
            'PLANCHE',
            'PLATRE',
            'SCIE',
            'TOURNEVIS',
            'VIS',
            'VOITURE',
            'ARRACHER',
            'ATTACHER',
            'CASSER',
            'COUDRE',
            'DETRUIRE',
            'ECORCHER',
            'ENFILER',
            'ENFONCER',
            'FABRIQUER',
            'MESURER',
            'PERCER',
            'PINCER',
            'REPARER',
            'REUSSIR',
            'SERVIR',
            'TAPER',
            'TROUER',
            'TROUVER',
            'ADROIT',
            'DIFFICILE',
            'DUR',
            'FACILE',
            'LISSE',
            'MALADROIT',
            'POINTU',
            'TORDU',
            'ACCIDENT',
            'AEROPORT',
            'CAMION',
            'ENGIN',
            'FEU',
            'FREIN',
            'FUSEE',
            'GARAGE',
            'GARE',
            'GRUE',
            'HELICOPTERE',
            'MOTO',
            'PANNE',
            'PARKING',
            'PILOTE',
            'PNEU',
            'QUAI',
            'TRAIN',
            'VIRAGE',
            'VITESSE',
            'VOYAGE',
            'WAGON',
            'ZIGZAG',
            'ARRETER',
            'ATTERRIR',
            'BOUDER',
            'CHARGER',
            'CONDUIRE',
            'DEMARRER',
            'DISPARAITRE',
            'DONNER',
            'ECRASER',
            'ENVOLER',
            'GARDER',
            'GARER',
            'MANQUER',
            'PARTIR',
            'POSER',
            'RECULER',
            'ROULER',
            'TENDRE',
            'TRANSPORTER',
            'VOLER',
            'ABIME',
            'ANCIEN',
            'BLANC',
            'BLEU',
            'CASSE',
            'CINQ',
            'DERNIER',
            'DEUX',
            'DEUXIEME',
            'DIX',
            'GRIS',
            'GROS',
            'HUIT',
            'JAUNE',
            'MEME',
            'NEUF',
            'PAREIL',
            'PREMIER',
            'QUATRE',
            'ROUGE',
            'SEPT',
            'SEUL',
            'SIX',
            'SOLIDE',
            'TROIS',
            'TROISIEME',
            'UN',
            'VERT',
            'DESSUS',
            'AUTOUR',
            'VITE',
            'VERS',
            'ACROBATE',
            'ARRET',
            'ARRIERE',
            'BARRE',
            'BARREAU',
            'BORD',
            'BRAS',
            'CERCEAU',
            'CHAISE',
            'CHEVILLE',
            'CHUTE',
            'COEUR',
            'CORDE',
            'CORPS',
            'COTE',
            'COU',
            'COUDE',
            'CUISSE',
            'DANGER',
            'DOIGTS',
            'DOS',
            'ECHASSES',
            'ECHELLE',
            'EPAULE',
            'EQUIPE',
            'ESCABEAU',
            'FESSE',
            'FILET',
            'FOND',
            'GENOU',
            'GYMNASTIQUE',
            'HANCHE',
            'JAMBE',
            'JEU',
            'MAINS',
            'MILIEU',
            'MONTAGNE',
            'MUR',
            'ESCALADE',
            'MUSCLE',
            'NUMERO',
            'ONGLE',
            'PARCOURS',
            'PAS',
            'PASSERELLE',
            'PENTE',
            'PEUR',
            'PIED',
            'PLONGEOIR',
            'POIGNET',
            'POING',
            'PONT',
            'SIGNE',
            'SINGE',
            'POUTRE',
            'EQUILIBRE',
            'PRISE',
            'RIVIERE',
            'CROCODILE',
            'ROULADE',
            'PIROUETTE',
            'SAUT',
            'SERPENT',
            'SPORT',
            'SUIVANT',
            'TETE',
            'TOBOGGAN',
            'TOUR',
            'TRAMPOLINE',
            'TUNNEL',
            'VENTRE',
            'ACCROCHER',
            'APPUYER',
            'ARRIVER',
            'BAISSER',
            'BALANCER',
            'BONDIR',
            'BOUSCULER',
            'COGNER',
            'COURIR',
            'DANSER',
            'DEPASSER',
            'DESCENDRE',
            'ECARTER',
            'ESCALADER',
            'GAGNER',
            'GENER',
            'GLISSER',
            'GRIMPER',
            'MARCHER',
            'PATTES',
            'DEBOUT',
            'MONTER',
            'MONTRER',
            'PENCHER',
            'PERCHER',
            'PERDRE',
            'RAMPER',
            'RATER',
            'REMPLACER',
            'RESPIRER',
            'RETOURNER',
            'REVENIR',
            'SAUTER',
            'SOULEVER',
            'SUIVRE',
            'TOMBER',
            'TRANSPIRER',
            'TRAVERSER',
            'DANGEUREUX',
            'EPAIS',
            'FORT',
            'GROUPE',
            'IMMOBILE',
            'ROND',
            'SERRE',
            'SOUPLE',
            'ENSEMBLE',
            'ICI',
            'JAMAIS',
            'TOUJOURS',
            'SOUVENT',
            'BAGARRE',
            'BALANCOIRE',
            'BALLON',
            'BANDE',
            'BICYCLETTE',
            'BILLE',
            'CAGE',
            'ECUREUIL',
            'CERF',
            'VOLANT',
            'CHATEAU',
            'COUP',
            'COUR',
            'COURSE',
            'ECHASSE',
            'FLAQUE',
            'EAU',
            'PAIX',
            'PARDON',
            'PARTIE',
            'PEDALE',
            'PELLE',
            'POMPE',
            'PREAU',
            'RAQUETTE',
            'RAYON',
            'RECREATION',
            'SABLE',
            'SIFFLET',
            'SIGNE',
            'TAS',
            'TRICYCLE',
            'TUYAU',
            'VELO',
            'FILE',
            'RANG',
            'BAGARRER',
            'BATTRE',
            'CACHER',
            'CRACHER',
            'CREUSER',
            'CRIER',
            'DEGONFLER',
            'DISPUTE',
            'EMPECHER',
            'GALOPER',
            'HURLER',
            'JONGLER',
            'LANCER',
            'PEDALER',
            'PLAINDRE',
            'PLEURER',
            'POURSUIVRE',
            'PROTEGER',
            'SAIGNER',
            'SALIR',
            'SIFFLER',
            'SURVEILLER',
            'TRAINER',
            'TROUVER',
            'FOU',
            'MECHANT',
        ];

        return mb_strtoupper($possibleWords[random_int(0, count($possibleWords) - 1)]);
    }

    /**
     * @return mixed[]
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function retrieveWord(): array
    {
        $wordToFindItemCache = $this->cache->getItem('cerveau:command:hg:v3:data');
        if (!$wordToFindItemCache->isHit()) {
            // new word !
            $wordToFind = $this->getRandomWord();
            $session = new HangmanSession($wordToFind);

            $wordToFindItemCache->expiresAfter(3600 * 5);
            $wordToFindItemCache->set($session);
            $this->cache->save($wordToFindItemCache);
        } else {
            /** @var HangmanSession $session */
            $session = $wordToFindItemCache->get();
        }

        return [$wordToFindItemCache, $session];
    }
}
