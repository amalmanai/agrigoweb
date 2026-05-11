<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Détecte la présence de noms d'animaux courants (liste non exhaustive) dans un texte.
 * Comparaison par mots entiers pour limiter les faux positifs.
 */
final class CommentAnimalModerationService
{
    /**
     * Noms et appellations courantes en français (minuscules).
     */
    private const ANIMAL_NAMES = [
        'abeille', 'aigle', 'alpaga', 'anaconda', 'anguille', 'antilope', 'araignée', 'araignee',
        'autruche', 'baleine', 'bison', 'blaireau', 'boa', 'bouc', 'boeuf', 'buffle', 'buse',
        'cachalot', 'canard', 'canari', 'carpe', 'castor', 'cerf', 'chameau', 'chamois', 'chat',
        'chaton', 'chatte', 'chats', 'chacal', 'cheval', 'chevaux', 'chevreuil', 'chèvre', 'chevre',
        'chien', 'chiots', 'chiot', 'chiens', 'chimpanzé', 'chimpanze', 'cigogne', 'cobra', 'cochon',
        'coq', 'corbeau', 'cougar', 'couleuvre', 'coyote', 'crabe', 'crocodile', 'cygne', 'coccinelle',
        'daim', 'dauphin', 'dindon', 'dinosaure', 'dromadaire', 'écureuil', 'ecureuil', 'éléphant',
        'elephant', 'élan', 'elan', 'escargot', 'faucon', 'faon', 'flamant', 'fourmi', 'fouine',
        'furet', 'gazelle', 'gecko', 'girafe', 'gorille', 'grenouille', 'grue', 'guépard', 'guepard',
        'hamster', 'hérisson', 'herisson', 'hippopotame', 'hirondelle', 'homard', 'huitre', 'huître',
        'hyène', 'hyene', 'iguane', 'jaguar', 'kangourou', 'koala', 'labrador', 'lama', 'lapin',
        'léopard', 'leopard', 'lézard', 'lezard', 'lièvre', 'lievre', 'lion', 'lionne', 'loup',
        'loutre', 'lynx', 'macaque', 'marmotte', 'méduse', 'meduse', 'mille-pattes', 'morse',
        'mouche', 'mouflon', 'mouton', 'mulot', 'mygale', 'oie', 'okapi', 'opossum', 'orang-outan',
        'orque', 'otarie', 'ours', 'panda', 'panthère', 'panthere', 'paon', 'papillon', 'perroquet',
        'phoque', 'pieuvre', 'pigeon', 'pinson', 'piranha', 'poisson', 'porc', 'poney', 'poule',
        'poulet', 'poussin', 'poulpe', 'python', 'ragondin', 'rat', 'raton', 'renard', 'requin',
        'rhinocéros', 'rhinoceros', 'rossignol', 'sanglier', 'sardine', 'scarabée', 'scarabee',
        'serpent', 'singe', 'souris', 'sphinx', 'taureau', 'tigre', 'thon', 'tortue', 'truite',
        'truie', 'vache', 'vipère', 'vipere', 'wallaby', 'wombat', 'yack', 'yak', 'zèbre', 'zebre',
        'zébu', 'zebu', 'âne', 'ane', 'biquet', 'bouquetin', 'caille', 'calmar', 'caribou', 'cétoine',
        'chouette', 'cormoran', 'étourneau', 'faisan', 'hanneton', 'lamantin', 'limace', 'loche',
        'lombric', 'mante', 'merle', 'moineau', 'moustique', 'narval', 'ocelot', 'oiseau', 'oiseaux',
        'orignal', 'pelican', 'pélican', 'pingouin', 'pintade', 'poulain', 'sauterelle', 'taupe',
        'termite', 'tétard', 'tetard', 'vautour', 'vespa', 'wapiti',
    ];

    public function containsAnimalName(string $text): bool
    {
        $text = trim($text);
        if ($text === '') {
            return false;
        }

        $normalized = mb_strtolower($text, 'UTF-8');
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) {
            return false;
        }

        $forbidden = array_flip(array_unique(self::ANIMAL_NAMES));
        foreach ($tokens as $token) {
            if (isset($forbidden[$token])) {
                return true;
            }
        }

        return false;
    }
}
