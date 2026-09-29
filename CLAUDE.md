# Conventions for agents

This repo holds `itk-enter`'s own NGSI-LD data models plus the tooling that
validates and documents them. See `README.md` for an overview,
`docs/data-models.md` for how models are created, changed and released, and
`docs/project-structure.md` for what each file and `task` command does.

## Source vs. generated files

Inside each model folder (`<Subject>/<Model>/`):

- **Source, edit these:** `schema.json`, `notes.yaml`, `ADOPTERS.yaml`,
  `LICENSE.md`, `examples/example.json`.
- **Generated, never edit these by hand:** `model.yaml`, `swagger.yaml`,
  `schema.sql`, `README.md`, `doc/spec.md`, `examples/example-normalized.json`,
  `examples/example.jsonld`, `examples/example-normalized.jsonld`, and the
  subject's `context.jsonld`. Run `task generate` to update them after
  editing a source file. YAML, Markdown and SQL generated files carry a
  "generated — do not edit" header; JSON generated files can't hold a
  comment, so this list is the source of truth for those.

`vendor-assets/` holds pinned third-party files (SDM's common-schema,
`swagger-ui-dist`), refreshed by `task vendor:update`, not edited by hand.

## Working in this repo

- `task check` (validate + generate + `git diff --exit-code`) is what CI
  runs; a PR that edits `schema.json` without regenerating fails it.
- Everything generated is deterministic (sorted keys, stable ordering) so a
  second `task generate` run changes nothing.
- The IRI namespace, base URLs and vendor pins live in one place,
  `config.yaml` — don't hardcode `itk-enter.github.io` or
  `raw.githubusercontent.com` URLs elsewhere.
- A model can live here indefinitely, submitted to SDM or not. Submitting a
  model later is a separate, manual step (see "Submitting a model to SDM" in
  `README.md`) — no command in this repo does it.
