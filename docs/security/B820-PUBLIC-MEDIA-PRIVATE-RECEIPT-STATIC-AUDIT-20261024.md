# B820 — source-verified public media and private receipt boundary audit

Date 2026-10-24. Evidence: exact unmerged PR93 source `fab3f4991fe5792e28e5a402dbf402617fb5c303`; `PublicShopController.php`, `PublicStorefrontStateService.php`, `PublicOrderAccessController.php`. This document is **static source review**, not an executed penetration test.

## Results grounded directly in source

1. Public product-image route first calls the **fresh storefront ON guard**, then checks public product visibility and positive sale price. Allowed disks: `legacy` and `public`. Path components `..` are rejected. Disk-specific prefixes are required; both resolved file and allowed directory are checked through `realpath` with a directory-separator-bounded prefix. The accepted MIME list is JPEG/PNG/WebP. This is a defensive implementation, not proof against every filesystem/encoding/symlink attack.
2. Private order receipt, documents and warranties all call the same `isOpen()` check before bearer lookup. Document IDs must belong to the token's order and be issued with a permitted document type; warranties require matching order/item and non-void activated data. B818 RED/GREEN demonstrates the SuperAdmin OFF receipt regression is fixed on synthetic MariaDB. It does **not** demonstrate real PDF byte/render validity or all horizontal-access cases.
3. `PublicStorefrontStateService::requestedOpen()` reads the `public_storefront_open` setting row afresh (rather than the Settings cache). `isOpen()` also requires static `public_storefront.enabled===true`. An OFF toggle is transactional and requires active, non-retired SuperAdmin.
4. Public media responses use `Cache-Control: public, max-age=3600`. This is legitimate for public imagery, **but a previously cached image may remain visible after emergency OFF without a server request**. Do not describe emergency OFF as revoking copies already in user or intermediary caches. For personal/order data, the private receipt responses instead use `private, no-store`, `noindex`, `no-referrer` and `nosniff`. Whether edge/CDN/browser honors those directives is NOT verified.

## B820 test matrix: tests to implement and run in isolated disposable CI

| ID | Setup | Request/assertion | Status |
|---|---|---|---|
| B820-M01 | Synthetic visible product/image, storefront ON | Allowed JPEG resolves exactly inside product directory, expected MIME and cache header | NOT_RUN |
| B820-M02 | Same image, storefront OFF via SuperAdmin | New uncached GET returns 404; no DB mutation | NOT_RUN |
| B820-M03 | Synthetic stored path contains parent component and encoded variant | 404, no file escape or source disclosure | NOT_RUN |
| B820-M04 | Synthetic symlink target outside allowed directory | 404 for both allowed disk types | NOT_RUN |
| B820-M05 | Valid path, disallowed text/html or SVG MIME | 404 and no active content response | NOT_RUN |
| B820-M06 | Product inactive/retired/archived or nonpositive price | 404 on image endpoint | NOT_RUN |
| B820-R01 | Two distinct synthetic buyer orders, capabilities issued | Cross-order document and warranty IDs consistently 404 | NOT_RUN |
| B820-R02 | Existing issued receipt, dynamic OFF | Receipt/document/warranty 404, even if valid bearer; scoped B818 partially proven | PARTIAL_EVIDENCE |
| B820-R03 | Receipt ON, checkout-only OFF | Existing receipt still readable, new checkout denied; scoped B818 proven for receipt | PARTIAL_EVIDENCE |
| B820-R04 | Delivered valid PDF sample under approved synthetic fixtures | PDF bytes and response headers, revocation and no metadata leak | NOT_RUN |
| B820-C01 | Isolated browser and caching reverse proxy | Prove public imagery stale-cache semantics after OFF and distinguish private receipt no-store behavior | NOT_RUN |

## Disposition

No exploitable new vulnerability is claimed on static inspection. Cached **public** product imagery surviving the OFF switch is a defined privacy/operational caveat, not classified as a customer-data leak. It requires stakeholder scope clarity before changing shared media cache policy. No test run, scanner, CI PASS, patch, merge, SSH, deploy or production traffic is implied. All OWASP WSTG v4.2 / ASVS 5.0 L2 item-level inventory, historical 49 exact identities, B815 safety denial, merchant policy, authenticated SMTP, restore/FPM/OPcache and owner UAT remain release gates. Do not treat this subreview as closure of SEC05, SEC06, SEC13 or SEC22.

Production B798 storefront OFF; PR93 Draft; accepted program 7/17 (41.2%), CMS 7/15 (46.7%), initial UI 6/6.
