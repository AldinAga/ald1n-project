# Ald1n x ECC — opt-in engineering integration (2026-10-18)

## Exact upstream authority

- Official project: https://github.com/affaan-m/ECC
- Examined upstream commit: `76aaa77df6adc5b889e689007bf99b9a4f74af34` (2026-10-15).
- License: MIT, preserved in `docs/third-party/ECC-MIT-LICENSE.txt`.
- ECC's root distribution includes hundreds of skills, dozens of agent definitions, optional hooks and provider integrations. **This PR intentionally imports a vetted subset.** The full ECC Codex plugin remains an optional developer-side installation.

## Included: usable repo-local skills and agents

| Path | Purpose | Origin |
|---|---|---|
| `.agents/skills/ecc-laravel-verification/SKILL.md` | Laravel 13 / PHP 8.4 QA | ECC `skills/laravel-verification/SKILL.md` |
| `.agents/skills/ecc-react-native-patterns/SKILL.md` | Expo SDK 57 / RN 0.86 guidance | ECC `skills/react-native-patterns/SKILL.md` |
| `.agents/skills/ecc-browser-qa/SKILL.md` | Chromium UI, responsive + role coverage | ECC `skills/browser-qa/SKILL.md` |
| `.agents/skills/ald1n-ecc-engineering/SKILL.md` | Coordinate tasks with project safety gates | Adapted from ECC workflow ideas |
| `.codex/agents/explorer.toml` | Read-only source explorer | ECC `explorer` |
| `.codex/agents/reviewer.toml` | Read-only code/security reviewer | ECC `reviewer` |
| `.codex/agents/docs-researcher.toml` | Read-only documentation checker | ECC `docs-researcher` |
| `.codex/config.toml` | Project-local multi-agent role mapping | Adapted from ECC Codex reference |

The three vendored ECC skill texts are pinned to the upstream commit, with a clearly marked Ald1n safety preface. **No automatic hook, third-party installer, MCP server, secret access, package manager or CLI mutation was imported.** Agents request read-only sandboxes. This integration does not guarantee that any specific chat client loads them automatically; clients must support local skill and agent discovery.

## Optional: full ECC on a developer workstation running compatible Codex CLI

Per upstream `.codex-plugin/README.md` for Codex 0.146.0+:

```bash
codex plugin marketplace add affaan-m/ECC
codex plugin add ecc@ecc
codex plugin list --json
```

Run these only on a trusted **developer workstation**, never on `s53` CloudLinux production. They require a real Codex CLI, local network access, and explicit operator action. Start a fresh Codex session, inspect plugin registration via `/plugins`, and review `/hooks` manually before trusting any ECC hook. Do not run ECC's global `install.sh`, `sync-ecc-to-codex.sh`, or `.mcp.json` bootstrap on production. Do not stack the native plugin and legacy sync unnecessarily.

For ChatGPT skill-library users, the standalone `ald1n-ecc-engineering` skill can be installed by user action from a validated `skill.zip`; putting `.agents/skills` in GitHub does not itself modify the user's ChatGPT skill inventory.

## Highest-priority Ald1n contracts

1. **At every new batch**, reconstruct current `main`, `AGENTS.md`, `docs/operations/000-LATEST.md` and relevant numbered operations reports. `AGENTS.md` wins every conflict with ECC.
2. Preserve Laravel route/role/archive/Direct Sale behavior, Expo Router navigation, exact app identity, EAS runtime and actual production state. No silent refactoring.
3. Host execution is forbidden without owner-approved gates: operator scripts only in `/home/icaffeco/ald1n-project/incoming/`, reports only under `docs/operations/`, SHA-256 prechecks, no `git pull/reset/clean` on stale/dirty host, no process substitution, no reliance on host `python3`.
4. CI/browser PASS is source- and run-specific. Android native module acceptance needs native Gradle proof, not an unrelated database run.
5. No automatic main merge, production deploy, migration, OTA, Play upload, secrets modification, or overwrite of .env/storage/vendor/.htaccess.

## What this PR deliberately does NOT do

- Change the running CMS, Android APK, PHP routes, migrations or webroot.
- Install 311 ECC skills globally or claim the full ECC native plugin was installed.
- Enable unreviewed third-party hooks or MCP agents on the server.
- Imply that the outstanding Liquid Glass 2.0 preflight checksum problem is resolved on the CloudLinux host; it must be verified using the exact operator script bytes.

## ECC update policy

Any later ECC update needs: reviewed upstream SHA, allowlist of changed local files, license check, readable diff, exact-source CI, and owner-approved PR. No auto-sync into production.
