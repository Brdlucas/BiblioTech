<?php

namespace App\Service;

use Google\Client;
use Google\Service\Books;

class GoogleBooksService
{
    private $booksService;

    public function __construct(string $apiKey)
    {
        // Initialisation du client Google
        $client = new Client();

        // Ajout d'un client HTTP personnalisé avec Guzzle et configuration SSL
        $client->setHttpClient(new \GuzzleHttp\Client([
            'verify' => __DIR__ . '/../../certificates/cacert.pem'

        ]));

        $client->setApplicationName('BiblioTech');
        $client->setDeveloperKey($apiKey);

        // Initialisation du service Google Books
        $this->booksService = new Books($client);
    }

    /**
     * Recherche des livres via l'API Google Books.
     *
     * @param string $query La requête de recherche.
     * @return array Les résultats de la recherche sous forme de tableau.
     */
    public function rechercherLivres(string $query): array
    {
        try {
            // Options pour la requête API
            $optParams = ['q' => $query];

            // Appel à l'API Google Books
            $response = $this->booksService->volumes->listVolumes($optParams);

            // Convertir la réponse en tableau (stdClass -> array)
            return json_decode(json_encode($response), true);
        } catch (\Exception $e) {
            // Gestion des erreurs : log ou exception personnalisée
            throw new \RuntimeException('Erreur lors de la recherche de livres : ' . $e->getMessage());
        }
    }
}
