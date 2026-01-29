# EXT:seal

SEAL Search - Flexible integration of the Search Engine Abstraction Layer ([SEAL](https://php-cmsig.github.io/search/index.html)) project into TYPO3. Different index configurations and multiple possibilities. Use it with TYPO3 or for example with Meilisearch, Solr, Loupe, Typesense - without changing the integration. Have fun.

## Requirements

- PHP 8.3+
- TYPO3 v13.4 or v14.0
- [EXT:index](https://github.com/lochmueller/index)

## Installation

1. Install and configure the [EXT:index](https://github.com/lochmueller/index) extension
2. Run `composer require lochmueller/seal`
3. *Optional: Install the right adapter if you want to use a specific engine (see below)*
4. Load the SiteSet `lochmueller/seal` in your site configuration
5. Configure the search adapter via site configuration (DSN)
6. Configure individual search options

## Configuration

The extension is configured via the TYPO3 site configuration. The following options are available:

| Option | Default | Description |
|--------|---------|-------------|
| `sealSearchDsn` | `typo3://` | DSN for the search adapter |
| `sealAutocompleteMinCharacters` | `3` | Minimum characters for autocomplete |
| `sealItemsPerPage` | `10` | Items per page in search results |

### DSN Examples

```
typo3://                           # Local TYPO3 database adapter
loupe://var/loupe                  # Loupe file-based search
meilisearch://127.0.0.1:7700       # Meilisearch server
elasticsearch://127.0.0.1:9200     # Elasticsearch server
typesense://127.0.0.1:8108         # Typesense server
algolia://APP_ID:API_KEY           # Algolia cloud service
solr://127.0.0.1:8983              # Apache Solr server
opensearch://127.0.0.1:9200        # OpenSearch server
redisearch://127.0.0.1:6379        # RediSearch
```

## Available Adapters

Install the adapter you need via composer:

| Adapter | Package |
|---------|---------|
| Memory (Testing) | `composer require cmsig/seal-memory-adapter` |
| Loupe | `composer require cmsig/seal-loupe-adapter` |
| Meilisearch | `composer require cmsig/seal-meilisearch-adapter` |
| Elasticsearch | `composer require cmsig/seal-elasticsearch-adapter` |
| Typesense | `composer require cmsig/seal-typesense-adapter` |
| Algolia | `composer require cmsig/seal-algolia-adapter` |
| Apache Solr | `composer require cmsig/seal-solr-adapter` |
| OpenSearch | `composer require cmsig/seal-opensearch-adapter` |
| RediSearch | `composer require cmsig/seal-redisearch-adapter` |
| Read/Write Split | `composer require cmsig/seal-read-write-adapter` |
| Multi Adapter | `composer require cmsig/seal-multi-adapter` |

## CLI Commands

```bash
# Update the schema in all configured SEAL adapters
vendor/bin/typo3 seal:schema
```

## Extension Structure

| Directory | Description |
|-----------|-------------|
| `Adapter/` | The SEAL TYPO3 Adapter for local database |
| `Command/` | CLI tools for schema management |
| `Controller/` | Frontend plugins |
| `Engine/` | Factory for engine creation |
| `Event/` | PSR-14 events of EXT:seal |
| `EventListener/` | Event listeners and connection to EXT:index |
| `Exception/` | Custom exceptions |
| `Filter/` | Search filter implementations |
| `Handler/` | Request handlers (e.g., autocomplete) |
| `Middleware/` | PSR-15 middleware stack |
| `Pagination/` | Fluid pagination based on SEAL Generator |
| `Schema/` | Schema structure management |

## Development

```bash
# Install dependencies
composer install

# Fix code style
composer code-fix

# Run static analysis (PHPStan level 8)
composer code-check

# Run unit tests
composer code-test
```

## Credits

Thanks to [SEAL](https://php-cmsig.github.io/search/index.html) for the nice idea and TYPO3 Association & TYPO3 Community for sponsoring the extension.
