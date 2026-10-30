# B1001 - Jedinstveni read-only audit za naredno bezbedno ciscenje (2026-10-30)

**Status:** B1000 host Report974: 9/9 starih generisanih cache/module direktorijuma je proslo preflight, **340.784 potencijalnih inode-ova**. Nista jos nije obrisano. Authenticated Report974 SHA-256 `cc6546269c345d164da6736d327719075fca5ec960e1559eae86c7708959de9e` je sacuvan i na GitHub i Google Drive.

## Sta korisnik sada pokrece

**Samo jednu skriptu:** `B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh` (ZIP paket ili istovetan sirovi .sh fajl).

Ciljna putanja: `/home/icaffeco/ald1n-project/incoming/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh`.

Komanda:

```bash
bash /home/icaffeco/ald1n-project/incoming/B1001-ALD1N-CONSOLIDATED-CLEANUP-READONLY-AUDIT.sh --run
```

Skripta automatski proverava svoj SHA256 i sintaksu. Nije potreban dodatni rucni `sha256sum`. Ocekuje se sledeci **novi numerisani** izvestaj (975 ako nema novijih) `docs/operations/*-B1001-CONSOLIDATED-CLEANUP-READONLY-AUDIT-*.md`. Posaljite tacan originalni `.md` fajl, ne samo terminal screenshot.

## Objedinjene kontrole

1. Precizan SHA-256 izvornog Report974 i dev:inode:uid / inode-count svakog od devet kandidata, uz detekciju promena.
2. Kanonski izvori `ald1n-project/apps/cms/current` i `apps/mobile/current`; prijavljuje lokalni Git HEAD, ali **GitHub main ostaje autoritet**.
3. Realni CMS i Android API anonimni HTTP odgovori (`cms.ald1n.com/`, `/api/v1/bootstrap`) na pocetku i na kraju. Legacy `mobile-build.ald1n.com` **nije** health gate.
4. Procesi, sopstveni cron, sopstveni PID cwd/exe/fd, symlink root i predaci, mountinfo, owner, filesystem, 45-minutna izmena i inode drift. Ne podrazumeva da ogranicen procesni pregled obuhvata tudje procese.
5. Jedan read-only pokusaj za cPanel DomainInfo. Bez pristupa/kompletnog bindinga status ostaje **NEPROVERENO**, bez opasnog nagadjanja.
6. Svi prepoznati vrhunski `ald1n-*`, `.ald1n-*`, stari build/Node direktorijumi i `tmp/ald1n`, plus prva dubina kanonskog `ald1n-project/tmp` sa najvecim potrosacima. Ostali domeni nisu ni kandidati.
7. Status izdvojenog B995 sifrovanog scripts/reports arhiva i kontinuitet izvestaja, uz eksplicitan **HOLD** za aktuelni SQL/private backup restore koji nije nezavisno potvrdjen.
8. Stara izdanja B984/B990, aktivni Node22 toolchain, CMS vendor/storage, current mobile, globalni npm, ostali domeni, cPanel/mail i svi backupi su unapred zasticeni.

**Ova skripta nikada ne brise nijedan fajl.** Jedino sto trajno kreira jeste jedan numerisani izvestaj u `docs/operations`. Ne pokrece EAS, OTA, Google Play, migracije, naredne batch-eve niti resetuje git. Rezultati o prikrivenim procesima/cPanel mapiranju i produkcionom DB restore-u ne mogu se automatski pretvoriti u PASS. Lista kandidat znaci sledecu pojedinacnu proveru, ne dozvolu za brisanje.

## Naredni korak tek nakon izvestaja

Na osnovu autenticnog host Report975 (ili stvarnog sledeceg broja), priprema se odvojena skripta za **sve pojedinacno dokazano nepotrebne tacne putanje**, sa ponovnom trenutnom kontrolom, bez symlink/mount granica, eksplicitnom typed owner potvrdom, per-target pre/post statusom, checkpointom i fail-closed pri nepoznatim referencama. Niti jedan `HOLD`/`UNKNOWN`/backup/active release/other website nije dozvoljen za brisanje. Prethodno pripremljeni, ali neizvrseni B1001 devet-cache destructive draft je **SUPERSEDED-DO-NOT-RUN** i nije ova naredna skripta.

## Evidencija kvaliteta

Izvorna skripta imala je 26/26 izolovanih lokalnih QA kontrola PASS: pravilni 9/9 sintetički scan, tacan brojac 975, odbijanje tampera i nekanonskog poziva, HTTP503 HOLD, symlink target HOLD uz sacuvan unrelated-site sentinel, aktivni cron HOLD, nepotpuna B995 arhiva HOLD. **To nisu stvarni CloudLinux rezultati**. Ne prijavljujte oslobodjene inode dok host ne vrati tacan post-delete izvestaj.
