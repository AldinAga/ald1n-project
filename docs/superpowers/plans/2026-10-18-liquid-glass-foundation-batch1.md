# Ald1n CMS Liquid Glass — Batch 1 Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Uvesti zaseban, diskretan Liquid Glass vizuelni sloj za postojeći autentifikovani Laravel CMS: shell, navigaciju, zajedničke površine, forme i tabele, bez promena poslovne logike.

**Architecture:** Novi ne-generisani CSS overlay učitava se **posle** postojećih app.css i generisanog ald1n-ui-v2.css u zajedničkom Blade layoutu. Menja samo prezentacione CSS varijable i postojeće selektore; trenutni JS kontroler light/dark/auto teme, rute, PHP i API ostaju netaknuti. Svaka promena dobija RED/GREEN ugovorni test, a konačna vizuelna potvrda dolazi iz izolovanog preview-a.

**Tech Stack:** PHP 8.4, Laravel 13, Blade, statički CSS bez bundlera i novih JS biblioteka, PHPUnit 12, GitHub CI.

**Spec:** docs/superpowers/specs/2026-10-18-liquid-glass-cms-design.md (izolovana grana design/liquid-glass-cms-20261018). Owner color override 2026-10-18 je uključen u specifikaciju.

**Repo/base:** AldinAga/ald1n-project, production/main ecfeb8e55a4af44467069bbcea578c1b95ccc528. Report745 završio pre-Liquid Glass higijenu: backup 158/157 verifikovani, ništa ponovo ne čistiti.

## Global Constraints

- **Samo Batch 1 od ukupno četiri.** Batch 2 uređuje arhivu porudžbina/Danger Zone, Batch 3 sticky actions editora, Batch 4 integraciju i završni release QA. Ne prisvajati njihov scope.
- Razvoj isključivo u izolovanom checkout/worktree-u od grane design/liquid-glass-cms-20261018. Produkcioni host git HEAD ostaje istorijski dirty/stale; bez git pull/reset/clean na hostingu, bez automatskog deploya.
- **Zabrana jarkih glow/mrlja.** Paleta je neutralna: graphite, smoky gray, niskozasićeni taupe/bronze; ne postoji obaveza da ostane stara narandžasta Ald1n UI paleta. Zvaničan logo/identitet se ne menja. Numanović ljubičasta boja nije CMS boja.
- Sačuvati postojeća HTML data-theme=dark/light i data-theme-mode=auto ponašanja; ne dirati localStorage i postojeći data-theme-toggle JS. Ne dodavati font, JS paket, Composer/npm zavisnost ni Vite pipeline.
- **Ne menjati generisane** apps/cms/current/public/assets/css/ald1n-ui-v2.css niti packages/design-tokens/ald1n-operator.json ili postojeći app.css. Novi overlay mora biti učitan treći i verzionisan pomoću filemtime uz fallback.
- Čuvati sve korisničke i bezbednosne kontrole, uključujući kurs (data-exchange-rate-sync), mobile hamburger (data-mobile-menu-toggle), globalnu pretragu (data-header-product-search-toggle), navigacione role/permission provere, oznake statusa i tekstove upozorenja.
- Bez DDL/migracija, SQL restore-a, pravih user/order CRUD testova, OTA, Android/iOS builda, Google Play akcija i izmene .env/storage/vendor/.htaccess/backupa.
- Repo QA u izolovanom PHP test okruženju (phpunit.xml: DB_CONNECTION=sqlite, DB_DATABASE=:memory:). Ne instalirati composer zavisnosti na produkcionom hostingu; ako ih nema, koristiti prethodno verifikovan izolovani CI runner ili označiti HOLD.
- Pre svakog commit-a zahtevani diff manifest i git diff --check; staging isključivo eksplicitnih putanja. Izveštaj sa hostinga, ako kasnije bude potreban, samo u docs/operations/ sa MAX(remote,local)+1 i skripta samo u incoming/.
- Za Batch 1 i dalje važi puna kontrola: php bin/static-check.php (ne zamenjivati sa php-lint.php); bez prikrivanja stvarnih failing provera.

## File Structure and Interfaces

| File | Action | Responsibility |
|---|---|---|
| apps/cms/current/public/assets/css/ald1n-liquid-glass.css | CREATE | Diskretni dark/light token overrides, osnovni glass efekti i shared CSS; bez generisanog koda |
| apps/cms/current/resources/views/layouts/app.blade.php | MODIFY | Dodavanje CSS linka odmah posle ald1n-ui-v2.css, pre @stack('styles'), sa filemtime cache bust |
| apps/cms/current/tests/Unit/LiquidGlassFoundationContractTest.php | CREATE | Read-only testovi palete, zaštitnih fallbackova, selektora i nepromenjenih shell ugovora |
| apps/cms/current/tests/Feature/DashboardLegacyDesignTest.php | MODIFY | Dokaz stvarno renderovanog stylesheet linka i očuvanog autentifikovanog shell-a u SQLite in-memory okruženju |
| docs/superpowers/specs/2026-10-18-liquid-glass-cms-design.md | EXISTING | Važeći usaglašeni dizajn, sadrži novu prigušenu paletu |
| docs/superpowers/plans/2026-10-18-liquid-glass-foundation-batch1.md | EXISTING | Ovaj plan, ne implementacioni kod |

**Theme input:** html[data-theme="dark"] / html[data-theme="light"], već upravljan postojećim JS-om. **Theme output:** CSS custom properties sa javnim imenima --ald-*, --lg-* i kompatibilni postojeći aliases; bez novog JavaScript interfejsa. **Shell output:** isti Blade markeri, forme i href; jedan novi stylesheet link. **Tests:** PHPUnit metodama ispod se proverava realan kod, a ne veštački screenshot.

## Review Focus — pet posebno rizičnih stanja

1. **CSS redosled / keš:** nova datoteka mora postojati i u renderovanom HTML-u se pojaviti *posle* generisanog UI CSS-a; test Task 1.
2. **Auto tema / svetli režim:** overrides moraju biti vezani za resolved data-theme, bez novog theme JS-a i bez ponovnog paljenja jarko narandžastih mrlja; test Task 2.
3. **Search/dropdown preko headera:** backdrop/blur ne sme stvarati clipping ili novi z-index konflikt sa postojećim search layer z-index 1400; CSS contract + ručni overlay smoke u Task 3/5.
4. **Čitljivost i fallback:** normalni tekst/polja/tabele ne smeju biti transparentni do nečitljivosti; reduced-transparency, forced-colors i unsupported-blur moraju imati jasnu neprozirnu alternativu; test Task 4 + browser smoke u Task 5.
5. **Regresija click/submit i mobile hamburger:** izmena samo CSS/asset veze, bez preimenovanja data-* markera, dozvola ili request polja; Feature test Task 1 i manifest Task 5.

---

### Task 1: Asset inclusion and render contract

**Files:**
- Modify: apps/cms/current/tests/Feature/DashboardLegacyDesignTest.php
- Create: apps/cms/current/public/assets/css/ald1n-liquid-glass.css
- Modify: apps/cms/current/resources/views/layouts/app.blade.php

**Interfaces:**
- Consumes: postojeći Blade layout i već implementirani CSS asset('assets/css/...').
- Produces: novi javni CSS fajl sa nepromenljivom putanjom i jednim linkom poslе ui-v2; Task 2–4 dopunjavaju taj isti fajl.

- [ ] **Step 1: Write failing feature test.** Dodati metodu u postojeći DashboardLegacyDesignTest (koristiti njegov private superAdmin() helper). Tačan početni ugovor:

~~~php
public function test_authenticated_dashboard_loads_liquid_glass_after_generated_css(): void
{
    $html = $this->actingAs($this->superAdmin())->get('/')->assertOk()->getContent();
    self::assertIsString($html);
    $generatedOffset = strpos($html, 'assets/css/ald1n-ui-v2.css');
    $glassOffset = strpos($html, 'assets/css/ald1n-liquid-glass.css');
    self::assertNotFalse($generatedOffset);
    self::assertNotFalse($glassOffset);
    self::assertGreaterThan($generatedOffset, $glassOffset);
    foreach (['data-theme-toggle', 'data-mobile-menu-toggle',
        'data-header-product-search-toggle', 'data-build16-shell'] as $marker) {
        self::assertStringContainsString($marker, $html);
    }
}
~~~

- [ ] **Step 2: Verify RED.** Iz izolovanog CMS root-a: php artisan test tests/Feature/DashboardLegacyDesignTest.php --filter=test_authenticated_dashboard_loads_liquid_glass_after_generated_css. Očekivano: FAIL jer drugi stylesheet nije linkovan.
- [ ] **Step 3: Implement minimum.** Dodati apps/cms/current/public/assets/css/ald1n-liquid-glass.css sa uvodnim komentarom i bez drugih efekata. U layouts/app.blade.php odmah iza postojećeg ui-v2 linka dodati stylesheet link sa asset('assets/css/ald1n-liquid-glass.css') i cache bust parametrom (@filemtime(public_path('assets/css/ald1n-liquid-glass.css')) ?: config('app.version')). Link ostaje ispred @stack('styles'); bez izmena drugih sekcija.
- [ ] **Step 4: Verify GREEN.** Ponoviti identičan PHPUnit filter; očekivano test PASS uz prethodne DashboardLegacyDesignTest testove.
- [ ] **Step 5: Review and commit.** Pregledati tačan diff samo navedenih putanja, git diff --check, eksplicitni git add i commit: feat(cms): load isolated liquid glass overlay.

### Task 2: Calm, token-driven dark/light palette and ambient background

**Files:**
- Modify: apps/cms/current/tests/Unit/LiquidGlassFoundationContractTest.php (kreirati u ovom zadatku)
- Modify: apps/cms/current/public/assets/css/ald1n-liquid-glass.css

**Interfaces:**
- Consumes: html[data-theme] i ui-v2 --ald-* token interfejs.
- Produces: dark/light vrednosti --ald-background, --ald-surface, --ald-surface-muted, --ald-surface-container, --ald-surface-container-high, --ald-text, --ald-muted, --ald-border, --ald-outline, --ald-primary, --ald-primary-strong, --ald-on-primary, --ald-primary-container i --ald-on-primary-container; zasebni --lg-* efekti za pozadinu.

- [ ] **Step 1: Write failing unit tests.** Kreirati final class Tests\Unit\LiquidGlassFoundationContractTest extends PHPUnit\Framework\TestCase. Koristiti dirname(__DIR__, 2) za CMS root i file_get_contents. Polazni test:

~~~php
public function test_dark_and_light_muted_token_palette_is_defined(): void
{
    $css = $this->css();
    foreach (['html[data-theme="dark"]', 'html[data-theme="light"]',
        '--ald-background: #13181C;', '--ald-background: #F3F5F6;',
        '--ald-surface: #1B2329;', '--ald-surface: #FCFDFE;',
        '--ald-text: #E9EEF1;', '--ald-text: #24313A;',
        '--ald-primary: #BFA590;', '--ald-primary: #68584D;'] as $required) {
        self::assertStringContainsString($required, $css);
    }
}
~~~

Definisati private css(): string koji čita isključivo public/assets/css/ald1n-liquid-glass.css i odbija nečitljiv fajl. Dodati test_no_semantic_danger_or_success_token_is_repurposed(): void sa assertStringNotContainsString('--ald-danger:', $css) i ekvivalentom za --ald-success:. Dodati test_ambient_is_low_chroma_and_non_animated(): void koji proverava --lg-ambient-cool/--lg-ambient-warm, nisku opacitetu (najviše .06) i odsustvo @keyframes.

- [ ] **Step 2: Verify RED.** php artisan test tests/Unit/LiquidGlassFoundationContractTest.php. Očekivani failovi: tokeni još nisu implementirani.
- [ ] **Step 3: Implement palette.** U CSS-u definisati dark i light deklaracije isključivo na html[data-theme="dark"] i html[data-theme="light"]. Pored vrednosti u testu koristiti muted dark #B6C2C9, border dark #36434C, muted light #52606A, border light #CBD3D9, on-primary dark #172027, on-primary light #FFFFFF. Zadržati semantic danger/warning/success/info tokene iz prethodnog CSS-a. Za --lg-ambient-cool i --lg-ambient-warm koristiti sivoplave/taupe nijanse sa alfa najviše 0.06; u body pozadini najviše dve široke radijalne mrlje plus ravna --ald-background podloga; bez saturiranih akcentnih gradijenata, animacije i svetlećih neon efekata.
- [ ] **Step 4: Verify GREEN and contrast.** Ponoviti PHPUnit fajl, zatim pregledati statički kontrast: dark primary #BFA590 na #13181C približno 7.66:1, light primary #68584D na #FCFDFE približno 6.67:1; to nije zamena za proveru stvarnih hover/disabled/render stanja. CSS novim pravilima treba da resetuje postojeći upadljiv body radial-gradient koristeći transparentne niskosaturisane mrlje.
- [ ] **Step 5: Review and commit.** git diff --check, pregled promenjenih testova i CSS, eksplicitni commit: feat(cms): add muted dark light glass tokens.

### Task 3: Shared shell, navigation and controlled glass surfaces

**Files:**
- Modify: apps/cms/current/tests/Unit/LiquidGlassFoundationContractTest.php
- Modify: apps/cms/current/public/assets/css/ald1n-liquid-glass.css

**Interfaces:**
- Consumes: Task 2 --lg-* palette i postojeći .site-header / .nav-dropdown-menu / .page-shell selektori.
- Produces: vizuelne klase bez novih HTML data markera; ne menja strukturu ili behavior.

- [ ] **Step 1: Write failing unit tests.** test_shell_targets_existing_header_nav_and_search_selectors(): void koristi CSS read helper iz Task 2 i proverava sledeće selektore:

~~~php
foreach (['.site-header', '.header-primary-row', '.header-secondary-row',
    '.nav-dropdown-menu', '.header-product-search-dialog',
    '.user-chip', '.rate-badge', '.page-shell'] as $selector) {
    self::assertStringContainsString($selector, $this->css());
}
~~~

Dodatno test_glass_surfaces_have_opaque_fallback(): void proverava @supports not i backdrop-filter; test_preserves_navigation_and_control_markers(): void čita originalni Blade layout i proverava data-mobile-menu-toggle, data-theme-toggle, data-header-product-search-toggle, data-exchange-rate-sync i role="dialog".

- [ ] **Step 2: Verify RED.** php artisan test tests/Unit/LiquidGlassFoundationContractTest.php --filter='test_shell_targets_existing_header_nav_and_search_selectors|test_glass_surfaces_have_opaque_fallback'. Očekivano: FAIL pre novih shell CSS pravila.
- [ ] **Step 3: Implement shell styling.** Dodati transparentno-tamni/svetli glass na sticky .site-header, .nav-dropdown-menu, .header-product-search-dialog uz tanke border/box-shadow i umjeren backdrop-filter 8–14px samo na floating slojevima. .header-primary-row / .header-secondary-row, .header-quick-actions, .nav-dropdown>summary, .theme-mode-button, .user-chip, .rate-badge i .page-shell dobijaju koherentan spacing/hairline tretman bez menjanja CSS display modela koji bi pokvario mobilnu navigaciju. Za kartice .panel, .hero-panel, .dashboard-kpi-card, .dashboard-panel, .filter-panel koristiti near-opaque surface (>=92% površine) i diskretne senke; nikako blanket filter/blur preko sadržaja. Ne dodavati overflow:hidden roditeljima menija, pozicioni z-index preko search layer 1400 niti JS listeners.
- [ ] **Step 4: Verify GREEN.** Proći isti test, dodatno php artisan test tests/Feature/DashboardLegacyDesignTest.php. Ručni preview: global search, dropdown, mobile hamburger i theme toggle otvaraju se preko headera bez klipovanja.
- [ ] **Step 5: Review and commit.** Provera manifest/diff i commit: feat(cms): refine liquid glass app shell and surfaces.

### Task 4: Common controls, forms, tables, responsiveness and reduced effects

**Files:**
- Modify: apps/cms/current/tests/Unit/LiquidGlassFoundationContractTest.php
- Modify: apps/cms/current/public/assets/css/ald1n-liquid-glass.css

**Interfaces:**
- Consumes: Task 2 theme tokens, Task 3 shell.
- Produces: jedinstvena prezentacija kontrola koja će biti baza za konkretne Batch 2 i Batch 3 šablone.

- [ ] **Step 1: Write failing tests.** test_common_controls_and_table_surfaces_have_focus_treatment(): void proverava .button, .button-primary, .button-secondary, .button-ghost, .icon-button, input, textarea, select, .admin-table-wrap i :focus-visible. test_accessibility_fallbacks_exist(): void ima sledeći izvršivi ugovor:

~~~php
foreach (['@media (prefers-reduced-motion: reduce)',
    '@media (prefers-reduced-transparency: reduce)',
    '@media (forced-colors: active)', '@supports not'] as $required) {
    self::assertStringContainsString($required, $this->css());
}
~~~

test_mobile_has_no_global_horizontal_or_sticky_overflow_policy(): void zahteva responsive @media (max-width: 760px), min-width: 0 za sadržaj i izostanak novih blanket body overflow:hidden, sticky footer-a i display:none za form controls.

- [ ] **Step 2: Verify RED.** php artisan test tests/Unit/LiquidGlassFoundationContractTest.php --filter='test_common_controls_and_table_surfaces_have_focus_treatment|test_accessibility_fallbacks_exist|test_mobile_has_no_global_horizontal_or_sticky_overflow_policy'. Očekivano: FAIL dok zajedničke kontrole nisu stilizovane.
- [ ] **Step 3: Implement minimal CSS.** Postaviti umeren radius i neprozirne površine za form input/select/textarea i table cells (bez blur-a na tekstu). Primarni taster muted-taupe sa kontrastnim tekstom, sekundarni near-opaque, outline vidljiv na :focus-visible; zadržati stvarni danger/success/warning/status kontrast. @media max-width:760px i <=390px sprečavaju širinsko prelivanje i čuvaju touch targets oko 44px, ali **ne menjaju** arhivski Danger Zone layout ili product-editor-action-bar (to su naredni batch-evi). @supports not za backdrop-filter i reduced-transparency daju neprozirne fallback površine, reduced-motion prekida nove tranzicije, forced-colors čuva sistemske obrise.
- [ ] **Step 4: Verify GREEN.** Ponoviti unit suite, zatim DashboardLegacyDesignTest feature i osigurati da su postojeće CSS klase button-danger/status-badge bez nenamernih override-a. Proveriti svaku definisanu boju i najmanje 4.5:1 za tekstove normalne veličine u stvarnim finalnim parovima boja.
- [ ] **Step 5: Review and commit.** git diff --check i commit: feat(cms): polish glass controls tables and accessibility.

### Task 5: Integrated Batch 1 gates and evidence handoff

**Files:**
- Test only: apps/cms/current/tests/Unit/LiquidGlassFoundationContractTest.php
- Test only: apps/cms/current/tests/Feature/DashboardLegacyDesignTest.php
- Review: exaktni manifest iz ovog plana. Ne menjati zasebne arhiva/editor šablone.

**Interfaces:**
- Consumes: Task 1–4 finalnu granu, izolovano PHP test okruženje.
- Produces: jedan Batch1 QA receipt sa SHA, test izlazima, spiskom promena i linkom na izolovani draft PR; **nije** dozvola za produkcioni deploy.

- [ ] **Step 1: Confirm test RED/GREEN receipts.** Za Task 1–4 zabeležiti po jedan očekivani RED i potonji GREEN sa stvarnim RC, bez izmišljenog PASS.
- [ ] **Step 2: Run focused tests.** Iz izolovanog apps/cms/current: php artisan test tests/Unit/LiquidGlassFoundationContractTest.php; php artisan test tests/Feature/DashboardLegacyDesignTest.php. Oba moraju RC=0; test DB mora biti SQLite in-memory, nikad produkciona.
- [ ] **Step 3: Run canonical static gate.** php bin/static-check.php mora RC=0 i 0 neuspešnih; postojeći stariji base očekuje 983 provere, ali novi batch ne pretpostavlja stale fiksni broj. Ne koristiti samo php-lint.php kao authority.
- [ ] **Step 4: Visual and semantic smoke.** U izolovanom, autentifikovanom preview-u pregledati dashboard/nav/tabele/inpute u dark/light/auto pri 1440, 1024, 768, 390 i 320px; posebno otvoreni dropdown/global-search i mobile hamburger, keyboard focus, forced-colors/reduced-effects ako browser podržava. Sačuvati screenshotove kao CI/PR artefakt van produkcionog Git worktree-a i evidentirati rezultate. Ako nije moguć stvarni browser pregled, označiti vizuelni deo **PENDING** i ne proglasiti ga PASS, ali ne izmišljati dodatni recovery batch.
- [ ] **Step 5: Diff and draft PR.** git diff --check, git diff --cached --check, pregledati git diff --name-only --no-renames i git ls-files --others --exclude-standard; jedini kodni fajlovi smeju biti: novi liquid glass CSS, app.blade.php, novi Unit test i izmenjen DashboardLegacyDesignTest.php. Uz već odobrene doc/spec/plan fajlove i **najviše jedan novi operacioni izveštaj** u docs/operations/ (broj MAX(remote,local)+1, ako se ova faza operativno izvršava), sve ostalo je HOLD. Napraviti **draft** PR iz iste izolovane grane prema main sa stvarnim test/screenshot dokazima i napomenom da Batch 2 i Batch 3 nastavljaju na toj grani. Nema merge-a, produkcionih fajlova, migracija ili EAS/Play akcija.

## Deliverable and next decision

Uspesan Batch 1 = novi CSS sloj je dokazan učitan posle generisanog CSS-a, neutralni dark/light tokeni i suptilni ambient efekti rade, zajednički UI je čitljiv i otporan na fallback, a celokupan fokusirani CI/static/diff gate je zelen. Vizuelni PASS izgovoriti samo uz stvarne screenshotove. **Nema produkcionog deploya u Batch 1.**

Tek potom započeti Batch 2 (Archive Danger Zone) i Batch 3 (Product Editor sticky actions), bez ponavljanja pre-Liquid Glass backupa/cleanup-a. Konačan cross-feature QA i release ostaju Batch 4.
