# Ald1n project - GitHub full source backup workflow

Canonical private repository: `AldinAga/ald1n-project`, branch `main`.

GitHub is the off-host source/history backup of the complete safe Ald1n monorepo: Laravel CMS source, Mobile source, shared packages, migrations, OpenAPI, application assets, scripts, tests, documentation and operation reports.

Production `.env` files, database dumps/data, credentials, private keys, keystores, `vendor`, `node_modules`, logs/cache/sessions, private Laravel storage payloads, runtime backups, release archives and `incoming` files are intentionally excluded. Runtime `.gitignore`/`.gitkeep` placeholder files are safe and may remain tracked.

Every future certified PASS batch should finish with exactly one backup command:

```bash
bash /home/icaffeco/ald1n-project/scripts/github-backup-all.sh "descriptive PASS checkpoint"
```

The helper stages the complete safe project, accepts only approved runtime placeholders, blocks sensitive/runtime payloads and high-risk credential signatures, refuses remote divergence, runs `git diff --check`, commits, pushes, and verifies that local and GitHub `main` end on the same SHA. It never force-pushes.
