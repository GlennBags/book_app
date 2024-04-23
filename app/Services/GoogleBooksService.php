<?php

namespace App\Services;

use App\DTO\GoogleBookVolume;
use App\Models\Listing;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Psr\Http\Message\ResponseInterface;

class GoogleBooksService
{
    public Client $client;
    public Collection $collection;

    public int $totalResults;
    public int $currentPage = 1;
    public int $totalPages;

    public string $apiKey;
    public string $url;
    public string $json;
    public string $baseUrl = 'https://www.googleapis.com/books/v1/volumes?q=';

    public ResponseInterface $response;

    public function __construct()
    {

    }

    /**
     * @throws GuzzleException
     */
    public static function getByAuthor(string $author)
    {
        return (new self())->execute(['author' => $author]);
    }

    /**
     * @throws GuzzleException
     */
    public function execute(array $params)
    {
        $this->getClient();
        $this->getApiKey();
        $this->buildUrl($params);
        $this->fetch();
        $this->saveResults();
    }

    /**
     * @throws \Exception
     */
    public function saveResults()
    {
        $decoded = json_decode($this->json);
        $this->totalResults = $decoded->totalItems;
        $this->totalPages = ceil($this->totalResults / 10);

        $this->collection = collect($decoded->items);

        foreach ($this->collection as $item) {
            $book = (new GoogleBookVolume($item))->toArray();
            Listing::create($book);
        }
    }

    public function buildUrl(array $params): string
    {
        $query = $params['query'] ?? '';
        $this->url = $this->baseUrl . $query;
        $author = $params['author'] ?? '';
        if ($author) {
            $this->url .= (($query ? '+' : '') . 'inauthor:' . str_replace(' ', '%20', $author));
        }

        return $this->url;
    }

    /**
     * @throws GuzzleException
     */
    public function fetch(): string
    {
        $this->response = $this->client->request('GET', $this->url);

        $this->json = $this->response->getBody()->getContents();

        return $this->json;
    }

    public function getApiKey(): string
    {
        if (isset($this->apiKey)) return $this->apiKey;

        return $this->apiKey = getenv('GOOGLE_API_KEY');
    }

    public function getClient(): Client
    {
        if (isset($this->client)) return $this->client;

        return $this->client = new Client();
    }

    public function addAuthor(array $options)
    {

    }
}
