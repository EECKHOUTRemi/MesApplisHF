<?php

namespace App\Handler;

/**
 * Normalise les libellés courts (noms d'ingrédients, d'ustensiles, de catégories) :
 * première lettre en majuscule et suppression de la ponctuation parasite.
 * À ne pas utiliser sur les textes longs (descriptions de recettes, etc.).
 */
class TextFormatter
{
    /**
     * @param string $text
     * @return string
     */
    public function format(string $text): string
    {
        // mb_ucfirst : gère les initiales accentuées (« éclair » → « Éclair »)
        return str_replace(['.', ',', '_', '/'], '', mb_ucfirst($text));
    }
}
