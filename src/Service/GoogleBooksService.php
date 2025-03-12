<?php

namespace App\Service;

use Google\Client;
use Google\Service\Books;

class GoogleBooksService
{
    private $booksService;

    public function __construct(string $apiKey) // Assure-toi que ce paramètre existe
    {
        $client = new Client();
        $client->setApplicationName('BiblioTech');
        $client->setDeveloperKey($apiKey);
        $this->booksService = new Books($client);
    }

    public function rechercherLivres($query)
    {
        $optParams = ['q' => $query];
        return $this->booksService->volumes->listVolumes($optParams);
    }
}
