# EXT:seal

SEAL Search - Flexible integration of the Search Engine
Abstraction ([SEAL](https://php-cmsig.github.io/search/index.html)) Layer project into TYPO3. Different index
configuration and multiple possibilities. Use it with TYPO3 or for example with Meilisearch, Solr, Loupe, Typesense -
without changing the integration. Have fun.

## Installation

1. Install and configure the [EXT:index](https://github.com/lochmueller/index) extension.
2. Run `composer require lochmueller/seal`
3. *Optional: Install the right adapter, if you want to use a specific engine*
4. Configure the search adapter via site configuration
5. Load the SiteSet of the extension
6. Configure individual search options

## Extension structure

- Adapter - The seal TYPO3 Adapter for local database
- Command - Collection of CLI tools for indexing and schema building
- Controller - All frontend plugins
- Engine - Factory for engine create process
- Event - All events of EXT:seal
- EventListener - Usage of external and internal events - also connection to the EXT:index extension.
- Exception - Internal exceptions
- Middleware - Functions based on the PSR Middleware stack like autocomplete
- Pagination - Fluid Pagination based on Seal Generator
- Schema - Management of the Schema structure for the current instance

# Credits

Thanks [SEAL](https://php-cmsig.github.io/search/index.html) for the nice idea and TYPO3 Association & TYPO3 Community
for the sponsoring of the extension.