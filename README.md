# itk-enter/data-models

This repo holds the NGSI-LD data models that `itk-enter` publishes itself,
when no existing [Smart Data Models](https://smartdatamodels.org/) (SDM) model fits
the data we publish.

Browse the models on the docs site: <https://itk-enter.github.io/data-models/>

## How this relates to Smart Data Models

SDM is the shared catalogue of NGSI-LD data models used across FIWARE
projects. A model in SDM is a folder with a `schema.json`, a few metadata
files and an example; once SDM accepts a model, its own tooling generates
everything else (a spec page, `model.yaml`, `swagger.yaml`, examples in
every NGSI format, a JSON-LD `@context`) and hosts it.

Our models aren't in SDM, so nothing generates or hosts those files for us.
This repo fills that gap:

- Each model uses SDM's folder layout and conventions, so it looks and
  behaves like an SDM model, reuses SDM's common definitions (address,
  location, contact point, …) and can be submitted to SDM later without
  being rewritten.
- The tooling here checks each model and generates the files SDM would
  have generated.
- Releases are git tags, and GitHub serves the files: raw files at a
  pinned tag for machines, and a docs site for people.

| Who | Where | What |
| --- | --- | --- |
| Broker, JSON-LD processors | `https://raw.githubusercontent.com/itk-enter/data-models/<Model>/v<version>/…` | Raw files at a pinned tag, never changed after release |
| People, anyone following a term's IRI | `https://itk-enter.github.io/data-models/…` | Docs site: spec, Swagger UI, downloads, a page per term |

## Using a model from `enter`

Pin a released model in the `enter` Source by its tagged `context.jsonld`:

```php
model: 'PublicToilet',
contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.1/dataModel.PointOfInterest/context.jsonld',
```

The URL contains the version tag, so `enter` keeps using exactly that
version until you change the URL. Each model's own `README.md` shows the
line to use for its latest release, and [`CHANGELOG.md`](CHANGELOG.md) lists
what changed in each release.

## Documentation

- [Data models](docs/data-models.md): why a model folder looks the way it
  does, how to add or change a model, and how to release one.
- [Project structure and commands](docs/project-structure.md): what the
  files in this repo are for, and what each `task` command does.

## Submitting a model to SDM

A model can live here indefinitely. When one is ready for the Smart Data
Models program:

1. Copy its source files (`schema.json`, `notes.yaml`, `ADOPTERS.yaml`,
   `examples/example.json`) into a fork of `smart-data-models/incubated`
   and open a PR. Generated files and `context.jsonld` stay out of the PR,
   since SDM generates its own.
2. Before submitting, decide whether to switch the model's terms to the SDM
   namespace (`namespace` in `config.yaml`). Switching changes the expanded
   IRIs of those attributes for data already published, so plan a
   re-import in `enter`.
