# Project structure and commands

This repo is a lot of machinery for what is, in the end, a few JSON files
per model. This page explains why, what the parts are, and what each
`task` command does.

## Why there's so much here

For a model in Smart Data Models (SDM), SDM does four jobs: it checks the
model, generates the extra files (spec, `model.yaml`, `swagger.yaml`,
examples in every format, the JSON-LD context), publishes them at fixed
versions, and hosts a documentation site. Our models aren't in SDM, so this
repo does all four itself:

| Job | Done by | Command |
| --- | --- | --- |
| Check a model | `src/Validation/` | `task validate` |
| Generate the extra files | `src/Generator/`, `templates/` | `task generate` |
| Publish fixed versions | git tags | `task release` |
| Host a docs site | `src/Site/`, `site/`, GitHub Pages | `task site:build` |

The rest is ordinary project upkeep: tests, code style checks and CI.

## What's where

| Path | What it is |
| --- | --- |
| `dataModel.*/` | The data models, one folder per subject (see [Data models](data-models.md)). |
| `config.yaml` | Every public URL, our IRI namespace, and the pinned versions of the files in `vendor-assets/`. Change a URL here and everything follows. |
| `CHANGELOG.md` | What changed in each model release. |
| `bin/datamodels` | The command-line tool. The `task` commands run it for you. |
| `src/Model/` | Reads model folders and resolves their references to SDM's shared definitions. |
| `src/Validation/` | The checks `task validate` runs, one class per check. |
| `src/Generator/` | Writes the generated files, one class per file type. |
| `src/Site/` | Builds the docs site from released tags. |
| `src/Command/` | The commands `bin/datamodels` offers. |
| `templates/` | Templates for the generated Markdown: model README, spec and docs site pages. |
| `tests/` | PHPUnit tests. `tests/fixtures/sdm/Museum/` is a real SDM model, used to check our generated files match SDM's format. |
| `vendor-assets/` | Pinned copies of SDM's `common-schema.json` (the shared definitions) and Swagger UI, so checks and the site build work offline and give the same result every time. |
| `site/mkdocs.yml` | Settings for MkDocs, which turns the generated Markdown into the docs site. |
| `Taskfile.yml` | The `task` commands below. |
| `docker-compose.yml` | The containers the commands run in: PHP, MkDocs, and the style checkers. |
| `.github/workflows/` | CI. `ci.yml`, `release.yml` and `pages.yml` are ours; the rest are ITK Dev's standard code-style workflows, copied unchanged. |
| `build/` | Output of the site build. Not committed. |
| `CLAUDE.md` | Notes for AI coding agents working in the repo. |

Everything runs in Docker, so you only need [Docker](https://www.docker.com/)
and [Task](https://taskfile.dev/). Commands that take a model name get it
after `--`, e.g. `task validate -- PublicToilet`. `task --list-all` lists
them all.

## `task validate`

Checks a model, or every model if you don't name one, and lists anything
wrong. It changes no files. It checks that:

- `schema.json` is valid JSON Schema and its references to SDM's shared
  definitions resolve,
- `examples/example.json` matches the schema,
- property descriptions follow SDM's convention (`Property.`,
  `Relationship.`, …), which the generated files depend on,
- the version is set consistently,
- the schema's own URLs point at the model's own location.

## `task generate`

Rewrites a model's generated files (`model.yaml`, `swagger.yaml`,
`schema.sql`, `doc/spec.md`, `README.md`, the extra examples) and the
subject's `context.jsonld` from the source files. Run it after changing a
model, and commit what it writes. Running it twice in a row changes
nothing.

## `task check`

Runs `validate`, then `generate`, then fails if `generate` changed any
file. This is what CI runs on every PR: it catches a model that doesn't
validate, or a change committed without its regenerated files.

## `task release`

Publishes a model version, e.g. `task release -- PublicToilet`. It
refuses to run if there are uncommitted changes, the model doesn't
validate, the generated files are out of date, or the version is already
released. Otherwise it creates the git tag `<Model>/v<version>` and pushes
it to GitHub, which runs the release checks and redeploys the docs site.
Run it from your own terminal: the tag push uses your git credentials. The
full flow is in [Data models](data-models.md#changing-and-releasing-a-model).

## `task site:build`

Builds the docs site into `build/site/`. The site is built from released
tags, not from your working copy: each model gets a page for its latest
release and one per older version, plus a page for each term the model
defines, which is where its IRIs point. Models with no release yet appear
on the front page as unreleased. The build fails on broken links, and on
any schema URL or term IRI that doesn't have a page.

GitHub runs the same build and publishes it to
<https://itk-enter.github.io/data-models/> on every push to `main` and
every release tag.

## `task site:serve`

Builds the site and starts MkDocs' preview server on port 8000. The
container doesn't publish that port yet, so the preview can't be reached
from a browser outside Docker; use `task site:build` to check the site
instead.

## `task test`

Runs the PHPUnit tests for the tool itself: the checks, the generators, the
release command and the site build. Only needed when you change the PHP
code, not when you change a model.

## `task code-analysis`

Runs PHPStan, which finds type errors and other mistakes in the PHP code
without running it.

## `task coding-standards:check` and `task coding-standards:apply`

Check the formatting of Composer, Markdown, PHP, Twig and YAML files, the
same way ITK Dev's standard CI workflows do. `apply` fixes what it can
automatically; `check` applies the fixes, then fails if anything is still
wrong. Each language has its own command too, e.g.
`task coding-standards:markdown:check`.

## `task vendor:update`

Downloads the pinned versions of SDM's `common-schema.json` and Swagger UI
into `vendor-assets/`, and records a SHA-256 checksum for each Swagger UI
file in `vendor-assets/swagger-ui/checksums.sha256`. Since `vendor-assets/`
is committed, `git diff` afterwards shows exactly what changed. To move to
a newer version, change the pin in `config.yaml`, run this, then run
`task check`, since newer shared definitions can change what validates.

## `task model:new`

Meant to create a new model folder from a template, but not implemented
yet. Until it is, copy an existing model, as described in
[Data models](data-models.md#creating-a-new-model).
