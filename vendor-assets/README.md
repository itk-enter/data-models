# vendor-assets

Pinned copies of third-party files this repo's tooling depends on, fetched
and checksummed by `task vendor:update`:

- `common-schema.json`: Smart Data Models' common schema, at the source and
  ref pinned in `config.yaml`. Used by the validator (phase 3) to resolve
  `$ref`s offline.
- `swagger-ui/`: `swagger-ui-dist`'s bundle JS and CSS, at the version
  pinned in `config.yaml`, plus a `VERSION` file and a checksum. Used by the
  docs site (phase 6) to embed Swagger UI.

Named `vendor-assets/` rather than `vendor/` so it doesn't clash with
Composer's `vendor/`.

`task vendor:update` is not implemented yet; this folder is scaffolding for
it.
