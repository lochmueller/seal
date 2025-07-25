# EXT:seal

SEAL Search - Flexible integration of the Search Engine Abstraction ([SEAL](https://php-cmsig.github.io/search/index.html)) Layer project into TYPO3. Different index configuration and multiple possibilities. Use it with TYPO3 or for example with Meilisearch, Solr, Loupe, Typesense - without changing the integration. Have fun.


## Installation

1. Run `composer require lochmueller/seal`
2. *Optional: Install the right adapter, if you want to use a specific engine*
3. Configure the search adapter via site configuration
4. Load the SiteSet of the extension
5. *Optional: Create a scheduler tasks for the index process*
6. Configure individuell search options


## Configuration

Base in the index type you have to create scheduler tasks. "Cache indexing" do not need any configuration. The "Database indexing" need the "seal:index" scheduler task with the right site seletced. The indx process is running in the excution of the task. The "Web indexing" need als the "seal:index" task, but this task only fill ob the message bus for the web selection. So you need "seal:index" and also "message:consume" of the core.

### Database & Web index configuration

```yaml
test:
  hallo: 
```


## Extension structure

- Adapter - The seal TYPO3 Adapter for local database
- Command - Collection of CLI tools for indexing and schema building
- Controller - All frontend plugins
- Dto - Data transfer options for the application
- Engine - Factory for engine create process
- Event - All events of EXT:seal
- EventListener - Usage of external and internal events
- Exception - Internal exceptions
- Indexing - all related to different index processes
  - Cache - Index process based on cache (like EXT:indexed_search)
  - Database - Index process based on database (like EXT:ke_search)
  - Web - Index process based on web requests (like EXT:solr)
- Queue - Message and handler for the async handling message queue in Web indexing process
- Middleware - Functions based on the PSR Middleware stack like autocomplete
- Pagination - Fluid Pagination based on Seal Generator
- Schema - Management of the Schema structure for the current instance


# Credits

Thanks [SEAL](https://php-cmsig.github.io/search/index.html) for the nice idea and TYPO3 Association for the sponsoring of the extension.
