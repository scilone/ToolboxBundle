# SciloneToolboxBundle

## Elasticsearch Fixtures

Ce bundle fournit une fonctionnalité pour charger des fixtures dans Elasticsearch.

### Configuration

Ajoutez le bundle à votre application Symfony et configurez le client Elasticsearch.

Si l'autowiring du `FixtureManager` échoue, ajoutez cette configuration dans votre `services.yaml` :

```yaml
SciloneToolboxBundle\Elasticsearch\FixtureManager:
    arguments:
        $fixturesPath: '%kernel.project_dir%/elasticsearch-fixtures'
```

### Utilisation

1. Créez des fichiers YAML (`.yaml` ou `.yml`) dans le dossier `elasticsearch-fixtures/` de votre projet (pas dans le bundle).

2. Chaque fichier YAML définit un index :
   ```yaml
   # Nom du fichier = nom de l'index (ex: my_index.yaml)
   mapping:
     properties:
       id:
         type: keyword
       name:
         type: text
   data:
     - _id: "1"
       id: "doc1"
       name: "Document 1"
   ```

3. Exécutez la commande :
   ```bash
   php bin/console scilone:elasticsearch:load-fixtures
   ```

Seuls les fichiers `.yaml` et `.yml` sont traités.
