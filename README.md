# EXT:seal

SEAL Search - Flexible integration of the Search Engine Abstraction Layer project.

## Installation

1. Run `composer require lochmueller/seal`
2. Install optional adapters from packagist
3. Configure the search adapter via site settings
4. Load the SiteSet of the extension
5. 


## Extension workflow

@todo Workflow beschreiben



## Extension structure

- Adapter - The seal TYPO3 Adapter for local database
- Command - Collection of CLI tools
- Controller - All frontend plugins
- Event - All events of EXT:seal
- EventListener - Usage of external and internal events
- Exception - Internal exceptions
- Indexing - all related to different index processes
- Indexing/Cache - Index process based on cache (like EXT:indexed_search)
- Indexing/Database - Index process based on database (like EXT:ke_search)
- Indexing/Web - Index process based on web requests (like EXT:solr)
- Schema - Management of the Schema structure for the current instance

# Ideas

- Log Adapter wrapper

# Credits

Thanks https://php-cmsig.github.io/search/index.html for the nice idea!
