<?php

/*
 * Chargeur d'EntityManager pour phpstan-doctrine (voir phpstan.dist.neon).
 * Aucune connexion à la base n'est ouverte : seules les métadonnées sont lues.
 */

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
