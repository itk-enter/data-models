# itk-enter/data-models

A home for `itk-enter`'s own NGSI-LD data models: one folder per model, laid
out like the [Smart Data Models](https://smartdatamodels.org/) (SDM) program,
with tooling that validates each model and generates the files SDM would
otherwise generate on acceptance (`model.yaml`, `swagger.yaml`, `schema.sql`,
`doc/spec.md`, examples in every format, the subject `context.jsonld`), plus
a documentation site on GitHub Pages.

See [`data-models-PLAN.md`](data-models-PLAN.md) for the full plan, its
phases and the decisions behind it.

Two public faces, from one repo:

| Who | Where | What |
| --- | --- | --- |
| Broker, JSON-LD processors | `https://raw.githubusercontent.com/itk-enter/data-models/<Model>/v<version>/…` | Raw files at a pinned tag, immutable |
| People, anyone dereferencing an IRI | `https://itk-enter.github.io/data-models/…` | Docs site: rendered spec, Swagger UI, downloads, term pages |

## Model index

<!-- model-index:start -->

| Subject | Model | Version | Status | Links |
| --- | --- | --- | --- | --- |
| dataModel.PointOfInterest | PublicToilet | 0.0.1 | own model | [Spec](https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/) |

<!-- model-index:end -->

## Using a model from `enter`

Once a model is tagged, pin it in a Source by its raw tag URL, e.g.:

```php
model: 'PublicToilet',
contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.1/dataModel.PointOfInterest/context.jsonld',
```

## Development

PHP 8.4 via `itkdev/php8.4-fpm`, run through [Task](https://taskfile.dev/) and
`docker compose`, the same as `itk-enter/enter`. See `task --list-all` for all
commands; the main ones:

```shell
task coding-standards:check    # Composer, Markdown, PHP, Twig and YAML checks
task code-analysis             # PHPStan
task test                      # PHPUnit
task validate                  # validate a model, or all models
task generate                  # regenerate a model's files, or all models
task check                     # validate + generate + fail if the tree changed
task site:build                # build the docs site into build/site/
task site:serve                # serve a local preview of the docs site
task release -- <Model>        # tag a release, e.g. `task release -- PublicToilet`
task vendor:update             # refresh vendor-assets/ at the pinned versions
```

`bin/datamodels` is the console entry point. Commands land one plan phase at
a time, and each phase adds the PHP dependencies and dev tooling (PHPUnit,
PHPStan, PHP CS Fixer, …) it actually needs, once there's real code under
`src/` for them to check. `model:new` is the one command still to land.

CI (`.github/workflows/ci.yml`) runs `code-analysis`, `test`, `check` and
`site:build` on every PR and push to `main`. `release.yml` re-checks a
`<Model>/v<version>` tag against the schema's own version. `pages.yml`
deploys the docs site to GitHub Pages on push to `main` and on release tags.
