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
| `LICENSE.md` | The model's licence |

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

Once the PR is merged, release it as `0.0.1` by following
[2. Release it](#2-release-it) below. Until then, the docs site's front
page lists it as unreleased, with a link to its folder on GitHub.

## Changing and releasing a model

A release is a git tag named `<Model>/v<version>`, e.g.
`PublicToilet/v0.0.2`. **You never type the tag yourself.** `task release`
reads the version from `x-version` in the model's `schema.json` and builds
the tag from it. So the one thing that decides the version is the number
you write in `schema.json`.

The example below changes PublicToilet from `0.0.1` to `0.0.2`.

### 1. Make the change

1. Create a branch:

   ```shell
   git checkout main
   git pull
   git checkout -b publictoilet-0.0.2
   ```

2. Edit `dataModel.PointOfInterest/PublicToilet/schema.json` (and
   `examples/example.json` if the example should change too).

3. In the same `schema.json`, change **both** version fields to the new
   version:

   ```json
   "$schemaVersion": "0.0.2",
   "x-version": "0.0.2",
   ```

   Pick the new number like this:

   | Change | Bump | Example |
   | --- | --- | --- |
   | Fixes to wording or descriptions | last number | `0.0.1` → `0.0.2` |
   | New optional properties | middle number | `0.0.2` → `0.1.0` |
   | Removed, renamed or newly required properties | first number | `0.1.0` → `1.0.0` |

4. Run:

   ```shell
   task validate -- PublicToilet
   task generate -- PublicToilet
   ```

   `validate` must pass. `generate` rewrites the generated files, which
   you commit along with your change.

5. Add a section for the new version to [`CHANGELOG.md`](../CHANGELOG.md).

6. Commit, push and open a PR:

   ```shell
   git add -A
   git commit -m "PublicToilet 0.0.2: <what changed>"
   git push -u origin publictoilet-0.0.2
   ```

7. Merge the PR once CI is green. Nothing is released yet: `enter` still
   uses `0.0.1`.

### 2. Release it

1. Get the merged change and run the release, in your own terminal:

   ```shell
   git checkout main
   git pull
   task release -- PublicToilet
   ```

   This reads `0.0.2` from `schema.json`, creates the tag
   `PublicToilet/v0.0.2` and pushes it to GitHub. It stops without
   tagging if:
   - you have uncommitted changes: commit or stash them,
   - the model doesn't validate, or its generated files are out of date:
     fix it in a new PR,
   - the tag already exists: you forgot to bump `x-version` (step 1.3).

2. On GitHub, under **Actions**, wait for **Release** and **Deploy docs
   site** to go green. The model's page on
   <https://itk-enter.github.io/data-models/> then shows `0.0.2`, and `0.0.1`
   keeps its own page.

### 3. Use it in `enter`

Change the version in the Source's `contextUrl` from `v0.0.1` to `v0.0.2`:

```php
contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.2/dataModel.PointOfInterest/context.jsonld',
```

Open that URL in a browser first; if it loads, the release worked.
