<?php

namespace SciloneToolboxBundle\Elasticsearch;

use Elasticsearch\Client;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;

class FixtureManager
{
    private Client $client;
    private string $fixturesPath;

    public function __construct(Client $client, string $fixturesPath)
    {
        $this->client = $client;
        $this->fixturesPath = $fixturesPath;
    }

    public function loadFixtures(): void
    {
        $filesystem = new Filesystem();
        if (!$filesystem->exists($this->fixturesPath)) {
            throw new \RuntimeException("Fixtures path does not exist: {$this->fixturesPath}");
        }

        $fixtureFiles = glob($this->fixturesPath . '/*.{yaml,yml}', GLOB_BRACE);
        foreach ($fixtureFiles as $file) {
            $data = Yaml::parseFile($file);
            $indexName = pathinfo($file, PATHINFO_FILENAME);

            if (isset($data['mapping'])) {
                $this->createIndex($indexName, $data['mapping']);
            }

            if (isset($data['data'])) {
                $this->insertData($indexName, $data['data']);
            }
        }
    }

    private function createIndex(string $indexName, array $mapping): void
    {
        $params = [
            'index' => $indexName,
            'body' => [
                'mappings' => $mapping
            ]
        ];

        $this->client->indices()->create($params);
    }

    private function insertData(string $indexName, array $data): void
    {
        $params = ['body' => []];

        foreach ($data as $document) {
            $params['body'][] = [
                'index' => [
                    '_index' => $indexName,
                    '_id' => $document['_id'] ?? null
                ]
            ];
            $params['body'][] = $document;
        }

        if (!empty($params['body'])) {
            $this->client->bulk($params);
        }
    }
}