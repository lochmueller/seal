# EXT:seal

SEAL Search - Flexible integration of the Search Engine Abstraction Layer ([SEAL](https://php-cmsig.github.io/search/index.html)) project into TYPO3. Different index configurations and multiple possibilities. Use it with TYPO3 or for example with Meilisearch, Solr, Loupe, Typesense - without changing the integration. Have fun.

This extension was funded by the [TYPO3 Association](https://typo3.org): [community ideas I](https://typo3.org/article/members-have-selected-five-ideas-to-be-funded-in-quarter-3-2025) / [community idea II](https://talk.typo3.org/t/ext-seal-ecosystem-expansion-advanced-search-ai-vector-integration-tim-lochmuller/6594) & [first blogpost](https://typo3.org/article/typo3-meets-seal-a-breath-of-fresh-air-for-search) / [second blogpost](https://news.typo3.com/article/extseal-takes-the-next-step-advanced-search-geo-features-ai-vector-integration)

## Requirements

- PHP 8.3+
- TYPO3 v13.4 or v14.0
- [EXT:index](https://github.com/lochmueller/index)

## Installation

1. Install and configure the [EXT:index](https://github.com/lochmueller/index) extension
2. Run `composer require lochmueller/seal` (note: This extension only works in composer mode)
3. *Optional: Install the right adapter if you want to use a specific engine (see below)*
4. Load the SiteSet `lochmueller/seal` in your site configuration
5. Configure the search adapter via site configuration (DSN)
6. Configure individual search options

## Configuration

The extension is configured via the TYPO3 site configuration. The following options are available:

| Option                          | Default    | Description                         |
|---------------------------------|------------|-------------------------------------|
| `sealSearchDsn`                 | `typo3://` | DSN for the search adapter          |
| `sealAutocompleteMinCharacters` | `3`        | Minimum characters for autocomplete |
| `sealItemsPerPage`              | `10`       | Items per page in search results    |
| `sealHighlighting`              | `1`        | Highlight the search words in the result list |
| `sealHighlightingFields`        | `title,content` | Comma separated index fields that should be highlighted |

### Keyword Highlighting

If `sealHighlighting` is enabled, the configured fields are requested with the SEAL highlighting API
and rendered with the `<seal:highlight>` ViewHelper:

```html
<seal:highlight item="{item}" field="title"/>
<seal:highlight item="{item}" field="content" crop="300"/>
```

The ViewHelper escapes the indexed value and renders the matches as `<mark>` elements, so no
unescaped HTML from the index ends up in the frontend. With `crop` the text is shortened around the
first match. Every field without a match falls back to the raw value of the document.

### Accessibility (WCAG 2.2)

The shipped Fluid templates are built to pass a WCAG 2.2 AA audit out of the box:

- **Search landmark** – the form is rendered with `role="search"` and an `aria-label`.
- **Grouped filters** – tag and geo filters use `<fieldset>`/`<legend>` instead of an
  unassociated `<label>` (1.3.1).
- **Unique DOM ids** – every id is prefixed with a `sealId` derived from the content element
  uid, so multiple search plugins on one page keep their `for` and `aria-describedby`
  references intact (1.3.1 / 4.1.2).
- **Result semantics** – results are an ordered list of `<article>` elements, numbered
  continuously across pages, with a machine readable `<time datetime="…">` for the index date.
- **No redundant links** – the preview image link is removed from the accessibility tree
  (technique H2) and the "open" button carries the result title for assistive technology (2.4.4).
- **Pagination** – `aria-current="page"`, `rel="prev"`/`rel="next"`, previous/next links,
  page numbers with a hidden "Page" prefix and an ellipsis for windowed paginations.
- **Status messages** – the result count and the geolocation status are live regions (4.1.3).
- **Redundant entry** – search word, radius and determined coordinates are restored after
  every submit (WCAG 2.2 – 3.3.7).
- **Focus handling** – submitting the form and following a pagination link jumps to the
  focusable result headline (2.4.3), which has `scroll-margin-top` so it is not obscured by
  sticky headers (WCAG 2.2 – 2.4.11).
- **Target size** – `Resources/Public/Css/Seal.css` guarantees the 24x24 px minimum for
  pagination and filter controls (WCAG 2.2 – 2.5.8) and ships the `.seal-visually-hidden`
  helper, so the templates stay accessible without Bootstrap.

All labels are translatable via `Resources/Private/Language/locallang.xlf`; there is no
hard coded English left in the templates.

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

| Adapter          | Package                                                   |
|------------------|-----------------------------------------------------------|
| Memory (Testing) | `composer require cmsig/seal-memory-adapter`              |
| Loupe            | `composer require cmsig/seal-loupe-adapter` (Recommended) |
| Meilisearch      | `composer require cmsig/seal-meilisearch-adapter`         |
| Elasticsearch    | `composer require cmsig/seal-elasticsearch-adapter`       |
| Typesense        | `composer require cmsig/seal-typesense-adapter`           |
| Algolia          | `composer require cmsig/seal-algolia-adapter`             |
| Apache Solr      | `composer require cmsig/seal-solr-adapter`                |
| OpenSearch       | `composer require cmsig/seal-opensearch-adapter`          |
| RediSearch       | `composer require cmsig/seal-redisearch-adapter`          |
| Read/Write Split | `composer require cmsig/seal-read-write-adapter`          |
| Multi Adapter    | `composer require cmsig/seal-multi-adapter`               |

## CLI Commands

```bash
# Update the schema in all configured SEAL adapters
vendor/bin/typo3 seal:schema
```

## Extension Structure

| Directory        | Description                                 |
|------------------|---------------------------------------------|
| `Adapter/`       | The SEAL TYPO3 Adapter for local database   |
| `Command/`       | CLI tools for schema management             |
| `Controller/`    | Frontend plugins                            |
| `Engine/`        | Factory for engine creation                 |
| `Event/`         | PSR-14 events of EXT:seal                   |
| `EventListener/` | Event listeners and connection to EXT:index |
| `Exception/`     | Custom exceptions                           |
| `Filter/`        | Search filter implementations               |
| `Handler/`       | Request handlers (e.g., autocomplete)       |
| `Highlight/`     | Rendering of the keyword highlighting       |
| `Middleware/`    | PSR-15 middleware stack                     |
| `Pagination/`    | Fluid pagination based on SEAL Generator    |
| `Schema/`        | Schema structure management                 |
| `ViewHelpers/`   | Fluid ViewHelpers of EXT:seal               |

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
