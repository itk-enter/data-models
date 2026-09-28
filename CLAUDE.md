# Conventions for agents

This repo holds `itk-enter`'s own NGSI-LD data models plus the tooling that
validates and documents them. Read `data-models-PLAN.md` first — it has the
full plan, its phases, and the decisions (`D1`-`D10`) behind this layout.

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

The root `README.md`'s model index table is also generated, between the
`<!-- model-index:start -->` / `<!-- model-index:end -->` markers.

`vendor-assets/` holds pinned third-party files (SDM's common-schema,
`swagger-ui-dist`), refreshed by `task vendor:update`, not edited by hand.

## Working in this repo

- `task check` (validate + generate + `git diff --exit-code`) is what CI
  runs; a PR that edits `schema.json` without regenerating fails it.
- Everything generated is deterministic (sorted keys, stable ordering) so a
  second `task generate` run changes nothing.
- The IRI namespace, base URLs and vendor pins live in one place,
  `config.yaml` (decision D2) — don't hardcode `itk-enter.github.io` or
  `raw.githubusercontent.com` URLs elsewhere.
- A model can live here indefinitely, submitted to SDM or not. Submitting a
  model later is a separate, manual step (see "Later: submitting a model to
  SDM" in the plan) — no command in this repo does it.
