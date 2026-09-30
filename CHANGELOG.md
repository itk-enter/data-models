# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Models are released independently, each tagged `<Model>/v<version>`.

## [Unreleased]

- Added `dataModel.PointOfInterest/Bench` (0.0.1, not yet released): a
  public bench, extending SDM's PointOfInterest with attributes from
  OpenStreetMap `amenity=bench` tagging.

- Removed the original implementation plan now that the repo is in use; its
  remaining guidance lives in `README.md`.

## PublicToilet 0.0.2 - 2026-09-29

### Fixed

- `context.jsonld` now maps `id` and `type` to the JSON-LD keywords `@id`
  and `@type`, as SDM's and the NGSI-LD core context do. Before, they were
  ordinary terms, so a processor using the context on its own (or before
  the core context) lost the entity's identifier and type.

  Pin the new context in `enter`:

  ```php
  contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.2/dataModel.PointOfInterest/context.jsonld',
  ```

## PublicToilet 0.0.1 - 2026-09-29

### Added

- First release of `dataModel.PointOfInterest/PublicToilet`. Docs:
  <https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/>

  To use it from `enter`, pin the tagged context in the Source:

  ```php
  model: 'PublicToilet',
  contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.1/dataModel.PointOfInterest/context.jsonld',
  ```
