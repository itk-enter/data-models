# Data models

How a model is laid out in this repo, and how to add, change and release
one. For what each `task` command does, see
[Project structure and commands](project-structure.md).

## Why a model folder looks like this

Every model follows the Smart Data Models (SDM) layout:

```text
dataModel.PointOfInterest/          ← subject: a group of related models
├── context.jsonld                  ← JSON-LD @context for every model in the subject
└── PublicToilet/                   ← one model
    ├── schema.json                 ← the model itself
    ├── notes.yaml
    ├── ADOPTERS.yaml
    ├── LICENSE.md
    ├── examples/example.json
    └── … generated files
```

We copy SDM's layout exactly so that:

- anyone who knows SDM models can read ours without learning anything new,
- our models reuse SDM's shared definitions (address, location, contact
  point, opening hours, …) instead of redefining them,
- a model can be submitted to SDM later by copying its files across, with
  no rewrite (see the main [README](../README.md#submitting-a-model-to-sdm)).

Subject folders must be named `dataModel.<Something>`, as in SDM. The
tooling only looks for models in folders named that way.

### The files you write

| File | What it is |
| --- | --- |
| `schema.json` | The model: a JSON Schema listing every property, its type and its description. This is the one that matters. |
| `examples/example.json` | One example entity, written as plain key-values. It must validate against `schema.json`. |
| `notes.yaml` | Free-text notes shown on the spec page (`notesHeader`, `notesMiddle`, `notesFooter`), plus an optional `status` (defaults to "own model"). |
| `ADOPTERS.yaml` | Who uses the model. |
| `LICENSE.md` | The model's licence (CC BY 4.0 for PublicToilet). |

### The files the tooling writes

Never edit these by hand; `task generate` rewrites them from the files
above:

| File | What it is |
| --- | --- |
| `model.yaml` | The schema in SDM's simplified YAML format. |
| `swagger.yaml` | An OpenAPI description, shown with Swagger UI on the docs site. |
| `schema.sql` | A PostgreSQL table matching the model. |
| `doc/spec.md` | The human-readable specification. |
| `README.md` | Status, version, the `enter` pin line and links. |
| `examples/example-normalized.json`, `example.jsonld`, `example-normalized.jsonld` | The example in the other NGSI formats (NGSI v2 normalized, NGSI-LD key-values, NGSI-LD normalized). |
| `../context.jsonld` | The subject's JSON-LD `@context`: maps each property name to its IRI. Shared by every model in the subject. |

CI regenerates everything and fails if the result differs from what's
committed, so a forgotten `task generate` is caught before merge.

### Rules `schema.json` must follow

`task validate` checks all of these:

- It's valid JSON Schema (2020-12), and every `$ref` to SDM's shared
  definitions resolves.
- `examples/example.json` validates against it.
- Every top-level property's `description` follows SDM's convention: it
  starts with `Property.`, `Relationship.` or `GeoProperty.`, and can then
  carry `Model:'…'`, `Enum:'…'` and `Units:'…'` markers, e.g.
  `"Property. Model:'https://schema.org/Text'. Kind of installation. Enum:'fixed, automatic, portable'"`.
  The generators read the type and markers from here.
- `x-version` and `$schemaVersion` are the same, and a semver version
  (`1.2.3`).
- `$id`, `x-model-schema` and `x-license-url` point at this model's own
  location. The expected URLs are built from `config.yaml`, and the error
  message tells you what they should be.

## Creating a new model

There's no scaffolding command yet (`task model:new` isn't implemented), so
start from an existing model:

1. Create the folder, e.g. `dataModel.PointOfInterest/NewModel/`, or a new
   subject folder `dataModel.<Subject>/NewModel/`.
2. Copy PublicToilet's source files into it: `schema.json`, `notes.yaml`,
   `ADOPTERS.yaml`, `LICENSE.md` and `examples/example.json`.
3. Edit `schema.json`: its `title`, `description`, the `type` enum and the
   properties. Set `x-version` and `$schemaVersion` to `0.0.1`, and update
   `$id`, `x-model-schema` and `x-license-url` to the new folder (or run
   `task validate -- NewModel` and copy the expected URLs from the errors).
4. Write a matching `examples/example.json`, and update the notes and
   adopters.
5. Run `task validate -- NewModel` until it passes, then
   `task generate -- NewModel`.
6. Commit the source files and the generated ones, and open a PR.

A new model shows up on the docs site once it's released. Until then, the
site's front page lists it as unreleased, with a link to its folder on
GitHub.

## Changing a model

1. Edit the source files (usually `schema.json` and `examples/example.json`).
2. Bump the version in `schema.json`: set both `x-version` and
   `$schemaVersion`. Released versions never change, so any change to a
   released model needs a new version:
   - **patch** (`0.0.1` → `0.0.2`): wording and description fixes,
   - **minor** (`0.1.0`): new optional properties,
   - **major** (`1.0.0`): anything that can break existing data, such as
     removing or renaming a property or making one required.
3. Run `task validate -- <Model>` and `task generate -- <Model>`.
4. Add an entry to [`CHANGELOG.md`](../CHANGELOG.md).
5. Commit everything and open a PR.

Merging the PR doesn't release anything: `enter` keeps using the version
it's pinned to until you release the new one and update the pin.

## Releasing a model

A release is a git tag named `<Model>/v<version>`, e.g.
`PublicToilet/v0.0.1`. The tag is what gets published, not `main`: raw file
URLs and the versioned pages on the docs site are all built from tags.

1. Get the change merged to `main`, then update your local copy:

   ```shell
   git checkout main
   git pull
   ```

2. Run the release, from the host (not inside a container):

   ```shell
   task release -- PublicToilet
   ```

   This stops with an error if the working tree has uncommitted changes, if
   the model doesn't validate, if the generated files aren't up to date, or
   if the tag already exists. Otherwise it creates the tag
   `<Model>/v<x-version>` and pushes it to GitHub.

3. GitHub Actions takes over:
   - **Release** checks the tag's version matches the schema's `x-version`,
     and runs the full check again.
   - **Deploy docs site** rebuilds the site with the new version added. The
     model's page shows the latest version, and every older version keeps
     its own page.

4. Point `enter` at the new version by changing the tag in its
   `contextUrl`:

   ```php
   contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.2/dataModel.PointOfInterest/context.jsonld',
   ```

To check a release, open its raw `context.jsonld` URL; it should load. The
model's page on <https://itk-enter.github.io/data-models/> should list the
new version.
