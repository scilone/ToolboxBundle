<?php

namespace SciloneToolboxBundle\Elasticsearch;

use Elasticsearch\Client;
use Exception;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

readonly class FixtureManager
{
    public function __construct(
        private Client $client,
        private LoggerInterface $logger,
        private string $fixturesPath
    ) {}

    public function loadFixtures(bool $force = false): void
    {
        $filesystem = new Filesystem();
        if ($filesystem->exists($this->fixturesPath) === false) {
            throw new RuntimeException("Fixtures path does not exist: {$this->fixturesPath}");
        }

        $fixtureFiles = glob("{$this->fixturesPath}/*.{yaml,yml}", GLOB_BRACE);
        foreach ($fixtureFiles as $file) {
            $data = Yaml::parseFile($file);
            $data = $this->processData($data);
            $indexName = pathinfo($file, PATHINFO_FILENAME);

            if (isset($data['mapping'])) {
                if ($force) {
                    $this->deleteIndex($indexName);
                }

                $this->createIndex($indexName, $data['mapping'], $force);
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

        try {
            $this->client->indices()->create($params);
        } catch (Exception $e) {
            $this->logger->error("Failed to create index {$indexName}: " . $e->getMessage());
        }
    }

    private function deleteIndex(string $indexName): void
    {
        $params = [
            'index' => $indexName
        ];

        try {
            $this->client->indices()->delete($params);
        } catch (Exception $e) {
            $this->logger->error("Failed to delete index {$indexName}: " . $e->getMessage());
        }
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
            $doc = $document;
            unset($doc['_id']);
            $params['body'][] = $doc;
        }

        if (!empty($params['body'])) {
            $this->client->bulk($params);
        }
    }

    private function processData(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = $this->processValue($value);
            }
        });

        return $data;
    }

    private function processValue(string $value): mixed
    {
        if (preg_match('/^<\((.*)\)>$/', $value, $matches)) {
            try {
                return eval('return ' . $matches[1] . ';');
            } catch (Throwable $e) {
                $this->logger->error(
                    'Failed to process PHP expression in fixture data',
                    ['error' => $e->getMessage()]
                );

                return $value;
            }
        }

        return $value;
    }
}
