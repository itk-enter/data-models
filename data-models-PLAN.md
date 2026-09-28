# Plan: `itk-enter/data-models` — a home for our own data models

A repository that holds our NGSI-LD data models, one folder per model, laid
out like the Smart Data Models (SDM) program, with PHP tooling that
validates each model and generates the documentation files SDM would
otherwise generate on acceptance (examples in all formats, `model.yaml`,
`swagger.yaml`, `schema.sql`, `doc/spec.md`, the subject `context.jsonld`),
plus a documentation site on GitHub Pages.

The first model is `PublicToilet`, today in `enter/PublicToilet_SDM/`.

Goals:

- A model can live here indefinitely, whether or not it is ever submitted
  to SDM.
- A Source in `enter` can pin a model version by URL.
- People can read every model, and every version of it, on a documentation
  site: spec, Swagger UI, schema and examples.
- A model that is later submitted to SDM needs no restructuring: its folder
  is already what `smart-data-models/incubated` takes.
- The tooling uses the same stack and conventions as `enter`, so the team
  maintains one toolchain.

Two public faces, from one repo:

| Who | Where | What |
| --- | --- | --- |
| Broker, JSON-LD processors | `https://raw.githubusercontent.com/itk-enter/data-models/<Model>/v<version>/…` | Raw files at a pinned tag, immutable |
| People, and anyone dereferencing an `$id` or term IRI | `https://itk-enter.github.io/data-models/…` | Docs site on GitHub Pages: rendered spec, Swagger UI, downloads, term pages |

## Findings this plan rests on

Checked against the SDM repositories on 2026-09-28.

- **SDM's generators are not reusable as-is.** The scripts in
  `smart-data-models/data-models/utils/` (`10_model.yaml_v13.py`,
  `20_create_spec_v11.0.py`, `25_create_subject_context_V7.py`,
  `50_swagger_generator_v3.0.py`, …) push their output to SDM's GitHub
  org and read credentials from `/home/fiware/credentials.json`. The repo
  states no licence for them. Use them as a **format reference only**.
- **`pysmartdatamodels`** (PyPI, MIT) is the only SDM code that is
  reusable, and this plan doesn't use it:
  - Its `validate_data_model_schema` checks SDM conventions; phase 3's own
    convention checks cover what we rely on.
  - Its `generate_sql_schema` maps `model.yaml` types to PostgreSQL; phase 4
    ports that mapping to PHP, with attribution (MIT).
- **Published formats**, taken from `dataModel.PointOfInterest/Museum`:
  - `model.yaml`:
    `{ModelName: {description, properties: {attr: {description, type, x-ngsi: {model, type, units?}}}, required, type: object}}`,
    with `$ref`s resolved and nested objects expanded.
  - `swagger.yaml`: OpenAPI 3.0.0. `components.schemas.<Model>` `$ref`s the
    `model.yaml`, and one path `GET /ngsi-ld/v1/entities?type=<Model>`
    `$ref`s the key-values and normalized examples.
  - `context.jsonld` is **per subject**, not per model. Each term maps to an
    IRI. Common terms from `common-schema.json` (`address`, `name`,
    `location`, …) map to `https://smartdatamodels.org/<term>`, and
    subject terms to `https://smartdatamodels.org/<subject>/<term>`.
  - `doc/spec.md`: a header, description and version, a property list
    (``- `name[type]`: description. Model: [url](url)``), the required
    properties, then the `notes.yaml` text.
  - `examples/`: `example.json` (key-values), `example-normalized.json`
    (NGSI v2 normalized), `example.jsonld` (NGSI-LD key-values),
    `example-normalized.jsonld` (NGSI-LD normalized).
- SDM's own output has quirks, such as
  `Model: https://schema.org/https://schema.org/postalCode` in Museum.
  Match the structure, not the bugs.
- **SDM's hosting pattern**: schema `$id`s point to GitHub Pages
  (`https://smart-data-models.github.io/<subject>/<Model>/schema.json`), and
  contexts are pinned via `raw.githubusercontent.com`. We copy that split.
- **Swagger UI is static files**, so it runs on GitHub Pages. Its
  "Try it out" has nothing to call there: the swagger describes a broker's
  `GET /ngsi-ld/v1/entities`, and there's no API on Pages.
- **SDM schemas mix drafts**: they declare JSON Schema 2020-12, but the
  common-schema they `$ref` uses `#/definitions/...` pointers from older
  drafts. The PHP validator must handle that (verified first, in phase 3).

## Decisions

Settle these before phase 1. Each has a recommendation.

| # | Decision | Recommendation |
| --- | --- | --- |
| D1 | Repo name and visibility | `itk-enter/data-models`, **public**. The broker dereferences `contextUrl`, so the context must be publicly reachable. "Not ready to share" then means not submitted to SDM, not secret. A model that must stay secret doesn't belong here. |
| D2 | IRI namespace for our own terms | Our own namespace, `https://itk-enter.github.io/data-models/<subject>/<term>`. Don't mint IRIs in `smartdatamodels.org`, which we don't control. Common SDM terms keep their SDM IRIs, so `address`, `location` etc. stay interoperable. Put the namespace in one config value, so a model moving to SDM is a single change. The docs site (phase 6) gives each term IRI a page, so the IRIs resolve. |
| D3 | Subject folder for PublicToilet | `dataModel.PointOfInterest/`. It matches SDM's likely home and the schema's current `$id`. |
| D4 | Language and runtime | **PHP 8.4**, the same as `enter`: `itkdev/php8.4-fpm` via `docker compose`, a `Taskfile.yml`, and Symfony 8.1 components. The tool is a small `symfony/console` application, not a full Symfony app. |
| D5 | Licences | Models under **CC-BY-4.0**, SDM-compatible (`LICENSE.md` per model). Tooling under **MIT** (root `LICENSE`). |
| D6 | Versioning | Per-model tags `<Model>/v<version>`, e.g. `PublicToilet/v0.0.1`. The version comes from the schema's `$schemaVersion`, which must equal `x-version`. |
| D7 | Generated files: committed or build-only | **Committed.** Pinned raw URLs must serve them, and reviewers see the effect of a schema change in the diff. CI enforces that they're current. |
| D8 | Which URL the broker pins | The **raw tag URL**. A tag is immutable in git, while a Pages URL only exists as long as the last deploy produced it. The Pages copy of a context is for people and for tools that want `application/ld+json`. |
| D9 | Swagger UI "Try it out" | **Off** (`supportedSubmitMethods: []`). Later, it can point `servers` at a real broker, which then must allow CORS from `itk-enter.github.io`. |
| D10 | Docs site tooling | **MkDocs Material through its official Docker image** (`squidfunk/mkdocs-material`, pinned tag), used as a CLI like any tool image, with no Python code of ours. The PHP tool writes the Markdown pages; MkDocs renders them. Swagger UI is embedded with a few lines of HTML and a pinned, committed copy of `swagger-ui-dist`, so no plugin is needed. |

## Target layout

```text
data-models/
├── README.md                 # what this is, model index (generated table), how to use/pin
├── CLAUDE.md                 # conventions for agents: edit sources, never generated files
├── LICENSE                   # MIT, tooling
├── Taskfile.yml
├── docker-compose.yml        # phpfpm (itkdev/php8.4-fpm), mkdocs, markdownlint, prettier
├── composer.json / composer.lock
├── phpstan.dist.neon, .php-cs-fixer.dist.php, phpunit.dist.xml
├── config.yaml               # namespace (D2), base URLs, pinned common-schema ref
├── bin/datamodels            # console entry point
├── src/                      # namespace ItkEnter\DataModels: Model/, Validation/, Generator/, Site/, Command/
├── templates/                # Twig: spec.md, README.md, site pages
├── tests/                    # PHPUnit, incl. a vendored SDM model as format fixture
├── vendor-assets/
│   ├── common-schema.json    # pinned copy of SDM's common-schema (task updates it)
│   └── swagger-ui/           # pinned swagger-ui-dist: bundle JS + CSS, VERSION, checksum
├── site/
│   └── mkdocs.yml            # docs site config (built output goes to build/site/, not committed)
├── .github/workflows/
│   ├── ci.yml
│   └── pages.yml             # builds and deploys the docs site
└── dataModel.PointOfInterest/
    ├── context.jsonld        # GENERATED, per subject
    └── PublicToilet/
        ├── schema.json       # SOURCE
        ├── notes.yaml        # SOURCE
        ├── ADOPTERS.yaml     # SOURCE
        ├── LICENSE.md        # SOURCE (CC-BY-4.0)
        ├── examples/
        │   ├── example.json               # SOURCE (key-values)
        │   ├── example-normalized.json    # GENERATED
        │   ├── example.jsonld             # GENERATED
        │   └── example-normalized.jsonld  # GENERATED
        ├── model.yaml        # GENERATED
        ├── swagger.yaml      # GENERATED
        ├── schema.sql        # GENERATED
        ├── README.md         # GENERATED: status, versions, pin URLs, links
        └── doc/spec.md       # GENERATED
```

The folder is `vendor-assets/` rather than `vendor/`, so it doesn't clash
with Composer's `vendor/`.

Every generated file carries a "generated — do not edit" header where the
format allows a comment (YAML, Markdown, SQL). JSON can't hold one, so
`CLAUDE.md` and the README list the generated JSON files.

## Phases

### Phase 0: create the repo (you)

1. Create `itk-enter/data-models` (public, D1) with a `main` branch and
   branch protection requiring CI.
2. Give the account the agent works as write access, or plan to work from
   a fork.
3. Under Settings → Pages, set the source to **GitHub Actions**. This needs
   repo admin rights.

Done when: the empty repo exists and the agent can push a branch to it.

### Phase 1: scaffold

Mirror `enter`'s setup; copy from it where possible.

1. `composer.json` (`"php": ">=8.4"`) with the dependencies:
   - `symfony/console`, `symfony/yaml`, `symfony/process` (git calls),
     `symfony/filesystem`, all `~8.1`
   - `twig/twig`
   - `opis/json-schema` (draft 2020-12)

   Dev dependencies: `phpunit/phpunit`, `phpstan/phpstan`,
   `friendsofphp/php-cs-fixer`. PSR-4 autoload `ItkEnter\DataModels\` →
   `src/`.
2. `docker-compose.yml` services:
   - `phpfpm`: `itkdev/php8.4-fpm`, repo mounted
   - `mkdocs`: `squidfunk/mkdocs-material:<pinned tag>`
   - `markdownlint` and `prettier`, as in `enter`
3. `bin/datamodels`: a `symfony/console` application. Commands are added
   per phase: `validate`, `generate`, `site:build`, `release`, `model:new`,
   `vendor:update`.
4. `Taskfile.yml`: `enter`'s `compose`, `composer`, `php`, `coding-standards:*`
   and `code-analysis` tasks, plus these, each running through `phpfpm`:
   - `validate`: all checks from phase 3, for all or a given model
   - `generate`: all generators from phase 4, for all or a given model
   - `check`: `validate` + `generate` + fail if the git tree changed (CI)
   - `test`: PHPUnit
   - `site:build` and `site:serve`: phase 6
   - `release -- <Model>`: phase 5
   - `vendor:update`: refresh `vendor-assets/` (common-schema, swagger-ui) at
     the pinned versions and verify the checksums
   - `model:new -- <Subject> <Model>`: scaffold a model folder from a template
5. `config.yaml`: namespace (D2), public base URLs (raw GitHub + Pages),
   the SDM common-schema source URL, the pinned swagger-ui version.
6. Root `README.md`, `CLAUDE.md`, `LICENSE`, `.gitignore` (`vendor/`, `build/`).

Done when: `task coding-standards:check`, `task code-analysis` and
`task test` pass on an empty test suite, and `bin/datamodels list` runs.

### Phase 2: import PublicToilet

1. Copy `enter/PublicToilet_SDM/` into `dataModel.PointOfInterest/PublicToilet/`.
2. Move `example.json` into `examples/`. **Drop the hand-written
   `example-normalized.json`**: phase 4 generates it. Keep a copy in
   `tests/fixtures/` to compare against the generator output.
3. Point the schema's self-references at this repo instead of SDM.
   Common-schema `$ref`s stay on SDM.
   - `$id` and `x-model-schema`: the Pages URL,
     `https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/schema.json`.
     It resolves to the latest version once phase 6 deploys.
   - `x-license-url`: the `LICENSE.md` on GitHub.
   - The `title` prefix: "Smart Data Models - …" claims SDM hosting.

   Derive all of these from `config.yaml`, so validation (phase 3) can
   check them rather than trusting hand edits.
4. Set `modelTags` / `x-model-tags` (both are empty today).
5. Add `LICENSE.md` (CC-BY-4.0) and `ADOPTERS.yaml`, listing Aarhus
   Kommune / the enter project.
6. Carry over the open points in `notes.yaml`: soap tag, LGA/NPTM
   licensing, `keyScheme` values, the PublicToiletObserved companion. They
   aren't resolved by this plan.

Done when: the folder holds only the source files and the schema claims
no SDM URLs.

### Phase 3: validation

Implement in `src/Validation/`, exposed as `bin/datamodels validate` /
`task validate`. Each check reports the model, the file and the problem.

**Do this first:** a spike test proving `opis/json-schema` can validate
`example.json` against the PublicToilet schema, with SDM's common-schema
URL mapped to `vendor-assets/common-schema.json` through its loader and the
`#/definitions/...` `$ref`s resolving. This is the plan's riskiest
assumption. If it fails, try `opis` with the draft set explicitly, then
`justinrainbow/json-schema`. Stop and report before building on a
validator that doesn't pass.

The checks:

1. **Schema is valid JSON Schema 2020-12** (meta-validation).
2. **`$ref`s resolve** against `vendor-assets/common-schema.json`. That keeps
   validation offline and reproducible.
3. **`examples/example.json` validates** against the schema.
4. **Description convention** for each top-level property. It starts with
   `Property.`, `Relationship.` or `GeoProperty.`. `Model:'…'` and
   `Units:'…'`, when present, parse. `Enum:'…'`, when present, matches the
   JSON Schema `enum`. The generators rely on this convention, so it's
   checked, not assumed. This also covers what `pysmartdatamodels`'
   validator would have checked for us.
5. **Version consistency**: `$schemaVersion == x-version`, a semver string.
6. **Self-references**: `$id`, `x-model-schema` and `x-license-url` equal
   the URLs derived from `config.yaml` for this model's folder.

Done when: `task validate` passes on PublicToilet, and each check has a
failing fixture test.

### Phase 4: generators

Implement in `src/Generator/`, one class per file type, exposed as
`bin/datamodels generate` / `task generate`. They share one loader,
`src/Model/`, which:

- reads `schema.json` and **fully dereferences** its `$ref`s: a small
  recursive resolver over the vendored common-schema, with a cycle guard.
  PHP has no direct counterpart to Python's `jsonref`, so this piece is
  our own, with its own tests.
- parses each property's description into
  `{ngsiType, model, units, enum, text}`.

All output is deterministic (sorted keys, stable ordering,
`JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`), so
`task check` can diff it. Text formats render through Twig templates in
`templates/`.

1. **Examples**, from `example.json` plus the parsed NGSI types:
   - `example-normalized.json` (NGSI v2):
     `{type: <Text|Number|Boolean|StructuredValue|geo:json|DateTime|Relationship>, value}`
   - `example.jsonld` (NGSI-LD key-values): `example.json` plus `@context` (the subject context URL)
   - `example-normalized.jsonld` (NGSI-LD normalized): `{type: Property|Relationship|GeoProperty, value|object}`, plus `@context`

   Test: the generated `example-normalized.json` equals the hand-written
   fixture from phase 2, apart from differences you judge to be errors in
   the hand-written one.
2. **`model.yaml`**, in the SDM format above, via `symfony/yaml`.
3. **`context.jsonld`**, per subject: the model type names and our own
   terms map to the D2 namespace, and common-schema terms map to
   `https://smartdatamodels.org/<term>`. Fail on a term defined with two
   different IRIs in one subject.
4. **`swagger.yaml`**: OpenAPI 3.0.0 in the SDM shape above. It `$ref`s our
   `model.yaml` and examples by **relative paths** (`./model.yaml#/PublicToilet`,
   `./examples/example.json`). That way the same file works in every
   versioned folder of the docs site, and Swagger UI resolves the refs
   same-origin. SDM uses absolute URLs here; this is a deliberate deviation.
   Omit `servers` (D9).
5. **`schema.sql`**: a PHP port of `pysmartdatamodels.generate_sql_schema`'s
   type mapping from `model.yaml` to PostgreSQL. Credit it in a comment
   (MIT). Test it against the vendored Museum fixture's published
   `schema.sql`. Nothing in `enter` uses this file, so if the port stalls,
   drop it and note that in the README.
6. **`doc/spec.md`**: the SDM spec structure above, from `model.yaml` and
   `notes.yaml`. English only; SDM's translations are out of scope.
7. **Model `README.md`**: status ("own model" or "submitted to SDM as
   PR #…", from an optional `status:` key in `notes.yaml` or a small
   `model.meta.yaml`), the current version, a pinnable `contextUrl` for
   the latest tag, and links to spec, schema, examples and swagger.
8. **Root `README.md` model index**: a generated table of subject, model,
   version, status and links, between marker comments.

Format test: vendor one published SDM model (Museum: `schema.json`,
`model.yaml`, `swagger.yaml`, `schema.sql`, `examples/example.json`) into
`tests/fixtures/sdm/`. Assert that our `model.yaml` and `swagger.yaml`
for it match SDM's **structurally** (same keys and property set, same
`x-ngsi` types), not byte for byte.

Done when: `task generate` fills in every generated file for PublicToilet,
and a second run changes nothing.

### Phase 5: CI and releases

1. `.github/workflows/ci.yml` on pull requests and pushes to `main`,
   following ITK Dev's GitHub Actions templates as `enter` does:
   coding standards (PHP, Markdown, YAML, Twig), PHPStan, `task test`,
   `task check` (validate, regenerate, fail on diff), and `task site:build`
   without deploying (phase 6).
2. Release by tag:
   - `task release -- <Model>` reads the version from the schema, checks
     the tree is clean and generated files are current, then creates and
     pushes `<Model>/v<version>`.
   - A workflow on tag push re-runs `task check` and fails if the tag's
     version doesn't match the schema's.

Done when: a PR that edits `schema.json` without regenerating fails CI, and
`PublicToilet/v0.0.1` is tagged.

### Phase 6: documentation site on GitHub Pages

Two steps: `bin/datamodels site:prepare` (PHP, `src/Site/`) writes a MkDocs
source tree to `build/docs/`, then the `mkdocs` service runs
`mkdocs build -f site/mkdocs.yml` into `build/site/`. `task site:build`
runs both; `task site:serve` runs `mkdocs serve` on the prepared tree for a
local preview. The site root is `https://itk-enter.github.io/data-models/`.

**URL layout.** The paths are chosen so that `$id`s and D2 term IRIs
resolve without redirects we'd have to maintain:

```text
/                                          index: all models, status, latest version
/<Subject>/context.jsonld                  latest subject context
/<Subject>/<term>/                         term page          ← D2 term IRIs land here
/<Subject>/<Model>/                        model page, latest ← the model type IRI lands here
/<Subject>/<Model>/schema.json             latest schema      ← the schema $id
/<Subject>/<Model>/{model,swagger}.yaml    latest files, plus examples/, schema.sql, doc/
/<Subject>/<Model>/v<version>/             the same page and files, frozen at that tag
/<Subject>/<Model>/v<version>/context.jsonld  the subject context as it was at that tag
```

MkDocs with `use_directory_urls` writes `<Subject>/<Model>/index.md` to
`<Subject>/<Model>/index.html` and copies non-Markdown files through
unchanged. GitHub Pages redirects `/<Subject>/toiletType` to
`/<Subject>/toiletType/`, so the extensionless IRIs from the context
resolve. A model type IRI (`…/dataModel.PointOfInterest/PublicToilet`) is
the model page itself.

**`site:prepare` steps:**

1. **Collect versions.** For each model, list its `<Model>/v*` tags
   (`git tag --list` via `symfony/process`, which needs `fetch-depth: 0` in
   CI). Export the model folder and its subject `context.jsonld` at each tag
   with `git archive` into `build/docs/<Subject>/<Model>/v<version>/`. The
   newest tag also fills the unversioned "latest" path. Only released
   versions appear. Models with no tag yet are listed on the index as
   "unreleased", linking to their folder on GitHub.
2. **Model pages** (`index.md` per version, from a Twig template). Include
   the version's `doc/spec.md`, then add:
   - Swagger UI: a `<div id="swagger-ui">` plus `vendor-assets/swagger-ui/`'s
     bundle JS and CSS (copied into `build/docs/assets/`), initialised with
     `SwaggerUIBundle({url: 'swagger.yaml', dom_id: '#swagger-ui', supportedSubmitMethods: []})` (D9).
     Enable the `md_in_html` extension in `mkdocs.yml`. Swagger UI shows
     every attribute, type, enum and description, plus the key-values and
     normalized examples.
   - A download list: schema, `model.yaml`, `swagger.yaml`, `schema.sql`,
     all four examples, the context.
   - The pin box: the raw tag URL for `contextUrl` (D8) and the `model:`
     value, ready to paste into an `enter` Source.
   - A version switcher linking every `v<version>/` of the model.
   - The status (own model, or submitted to SDM).
3. **Term pages** (`<Subject>/<term>/index.md`). For each term in a
   subject's latest context whose IRI is in our namespace: its description
   and type, and the models and versions that use it. Terms that map to
   `smartdatamodels.org` or schema.org get no page; their IRIs resolve
   elsewhere.
4. **Index page.** Generated from the same data as the root `README.md`
   model index.

**Build checks** (fail `task site:build`):

- `mkdocs build --strict`
- every `$id` in a latest schema maps to a built file
- every own-namespace IRI in every context has a page
- every relative `$ref` in every built `swagger.yaml` resolves

**Workflow** `.github/workflows/pages.yml`: on push to `main` and on
`*/v*` tag push, run `task site:build`, then `actions/upload-pages-artifact`
and `actions/deploy-pages`. Use a `concurrency` group so deploys never
overlap.

**After the first deploy**, check with `curl -I` that `.jsonld` is served
as `application/ld+json` and `.yaml` in a form Swagger UI loads. Note the
result in the README.

Done when: `https://itk-enter.github.io/data-models/dataModel.PointOfInterest/PublicToilet/`
shows the spec and Swagger UI for v0.0.1; the schema `$id`, the model type
IRI and a term IRI such as `…/dataModel.PointOfInterest/toiletType` all
resolve; and tagging v0.0.2 adds a `v0.0.2/` folder while `v0.0.1/` stays
unchanged.

### Phase 7: use it from `enter` (back in that project)

1. In the PublicToilet Source(s), set:

   ```php
   model: 'PublicToilet',
   contextUrl: 'https://raw.githubusercontent.com/itk-enter/data-models/PublicToilet/v0.0.1/dataModel.PointOfInterest/context.jsonld',
   ```

2. Check `src/Controller/TestController.php`, which maps SDM model URLs to
   paths, e.g. `'https://smartdatamodels.org/dataModel.Parking/OnStreetParking' => 'Parking/OnStreetParking'`,
   and add the equivalent entry for our model if the test view needs it.
3. Write an ADR in `enter` for the general decision: when no SDM model
   fits, we publish under our own model from `itk-enter/data-models`,
   pinned by tag. This is the reusable policy ADR 005 doesn't cover.
   Don't write a per-model ADR.
4. Remove `PublicToilet_SDM/` from the `enter` working tree.

## Later: submitting a model to SDM

Nothing in this plan blocks it. When a model is ready:

1. Copy its source files (`schema.json`, `notes.yaml`, `ADOPTERS.yaml`,
   `examples/example.json`) into a fork of `smart-data-models/incubated`
   and open a PR. Generated files and `context.jsonld` stay out of the PR,
   since SDM generates its own.
2. Before submitting, decide whether to switch the model's terms to the
   SDM namespace (D2). Switching changes the expanded IRIs of those
   attributes for data already published, so plan a re-import in `enter`.

## Out of scope

- SDM's translated specs, CSV exports, the `code/` folder and badges.
- A `PublicToiletObserved` companion model.
- Resolving the open points in PublicToilet's `notes.yaml`.
