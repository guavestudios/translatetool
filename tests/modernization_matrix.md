# PHP 8.3 modernization regression matrix

## Critical flows
- Login/logout flow (`/login`, `/logout`)
- Overview and folder navigation (`/overview`, `/key/:id`)
- Folder CRUD (`/add/folder`, `/edit/folder`, `/del/folder/:id`)
- Key CRUD and multi-delete (`/key/:id`, `/delete/multikeys`, `/delete/:keyId/:active`)
- Import validation + confirm import (`/import`, `POST /import/confirm`)
- Export and download (`/export`, `/download`)
- Dump and key conversion (`/dump`, `/convert`)
- Missing translation report (`/nottranslated`)
- Widget update endpoint (`/widget/update`)

## Automated checks in repository
- `php tests/php83_smoke.php` for:
  - DB shape invariants (`translations`, `log` columns)
  - converter adapter wiring (`yaml`, `csv`)
  - validator and tree generation baseline behavior
- `php tests/test.php` for legacy validator scenarios (best effort, informational)

## Manual parity checks (post-refactor)
- Confirm all routes above return expected status/redirect behavior.
- Confirm export writes to configured files under document root.
- Confirm import rejects invalid CSV and accepts valid CSV.
- Confirm DB schema remains unchanged (no DDL migration, same table/trigger usage).
