<?php

use App\Console\Commands\DetectAffairesWikipedia;
use Illuminate\Support\Str;

test('patterns haute confiance détectent les condamnations', function () {
    $patterns = [
        '/condamn[ée]e?\s.{0,80}(tribunal|cour|justice)/iu',
        '/peine\s+de\s+(prison|.*mois|.*an)/iu',
        '/inéligibilit[ée]/iu',
        '/condamn[ée]e?\s+[àa]\s+\d+/iu',
    ];

    $texts = [
        'Il a été condamné par le tribunal correctionnel de Paris.',
        'Elle a été condamnée par la cour d\'appel de Lyon.',
        'Condamné à une peine de prison de 2 ans.',
        'Peine de 18 mois d\'emprisonnement.',
        'Prononcée l\'inéligibilité pour 3 ans.',
        'Condamné à 50 000 euros d\'amende.',
    ];

    foreach ($texts as $text) {
        $matched = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $matched = true;
                break;
            }
        }
        expect($matched)->toBeTrue("Le texte '{$text}' devrait être détecté par un pattern haute confiance.");
    }
});

test('patterns moyenne confiance détectent les mises en examen', function () {
    $patterns = [
        '/mis[e]?\s+en\s+examen/iu',
        '/renvoy[ée]e?\s+devant\s+(le\s+)?tribunal/iu',
        '/garde\s+[àa]\s+vue/iu',
        '/poursuivi[e]?\s+(pour|en)/iu',
    ];

    $texts = [
        'Il a été mis en examen pour corruption.',
        'Mise en examen dans l\'affaire des emplois fictifs.',
        'Renvoyé devant le tribunal correctionnel.',
        'Placé en garde à vue par la PJ.',
        'Poursuivi pour abus de biens sociaux.',
        'Poursuivie en justice pour fraude.',
    ];

    foreach ($texts as $text) {
        $matched = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $matched = true;
                break;
            }
        }
        expect($matched)->toBeTrue("Le texte '{$text}' devrait être détecté par un pattern moyenne confiance.");
    }
});

test('les textes normaux ne déclenchent pas de faux positifs', function () {
    $patterns = array_merge(
        [
            '/condamn[ée]e?\s.{0,80}(tribunal|cour|justice)/iu',
            '/peine\s+de\s+(prison|.*mois|.*an)/iu',
            '/inéligibilit[ée]/iu',
            '/condamn[ée]e?\s+[àa]\s+\d+/iu',
        ],
        [
            '/mis[e]?\s+en\s+examen/iu',
            '/renvoy[ée]e?\s+devant\s+(le\s+)?tribunal/iu',
            '/garde\s+[àa]\s+vue/iu',
        ]
    );

    $safeTexts = [
        'Élu député de la 5e circonscription du Rhône en 2022.',
        'Membre de la commission des finances.',
        'Il a condamné les propos du président.',
        'Ministre de la Justice depuis 2024.',
        'Rapporteur du projet de loi de finances.',
    ];

    foreach ($safeTexts as $text) {
        foreach ($patterns as $pattern) {
            expect(preg_match($pattern, $text))->toBe(0, "Faux positif détecté : '{$text}' ne devrait pas matcher '{$pattern}'.");
        }
    }
});
