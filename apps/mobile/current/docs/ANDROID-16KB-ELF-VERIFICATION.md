# Android 16 KB ELF verification

This gate checks the ELF properties of the bytes packaged in an existing AAB,
APK or AAR. It does not compile the application or establish release approval.
The existing `verify-native.mjs` release gate uses the same binary parser for
64-bit native libraries and retains its separate manifest, bundle configuration,
runtime, build provenance and device receipt checks.

## Coverage

`scripts/release25/elf64-archive-verifier.mjs` requires Node 22 or later and
Info-ZIP-compatible `unzip`. It validates every conventional arm64-v8a and
x86_64 shared library in the archive:

- ELF64 little-endian ET_DYN, matching machine/ABI, bounded program headers and
  segment file ranges.
- LOAD alignment is a power of two and at least `0x4000`; offsets and virtual
  addresses are congruent modulo that alignment.
- Every present GNU_RELRO either ends on a 16 KB boundary, or occupies its
  entire containing LOAD segment with no other writable LOAD overlapping the
  rounded 16 KB protection range. The latter is Android's documented dedicated
  LOAD exception. Missing GNU_RELRO is reported explicitly as `ABSENT`.
- Duplicate ZIP members, symlinks, ambiguous paths, unsupported compression,
  corrupt CRC, unsafe integer fields and failed subprocesses are rejected.

The archive parser deliberately rejects ZIP64, multidisk archives and extended
ELF program-header numbering. Limits are 128 MiB per selected library and
512 MiB total selected native data. Supported native paths are
`base/lib/<abi>/*.so`, `lib/<abi>/*.so` and `jni/<abi>/*.so`.
Matching ABI libraries outside these paths fail. At least one selected library
is required; the standalone gate does not prescribe a full ABI set.

Keep the archive immutable during inspection and independently verify its
expected SHA-256 and signed-build identity. Input identity is checked again at
the end. The parser extracts no members to disk.

## Package-level verification

From `apps/mobile/current` in an isolated development/CI checkout:

```sh
node --test scripts/release25/elf64-archive-verifier.test.mjs scripts/release25/verify-native.test.mjs
node scripts/release25/elf64-archive-verifier.mjs /absolute/path/application.aab
```

The CLI writes JSON and exits 0 only when every selected library passes.
Operator-delivered CloudLinux batches and their helpers must still be placed
under `/home/icaffeco/ald1n-project/incoming`; package source scripts are not
substitutes for a numbered, provenance-bound operator batch.

The focused workflow `android-16kb-elf-verifier-qa.yml` tests these two files and
the exact pinned vendor AAR. It does not repeat the full application suite,
prebuild, Gradle compilation or an EAS build.

The one-off `android-aab27-relro-readonly.yml` workflow reads the existing EAS
build through the already configured repository credential. It binds the
archive to its recorded SHA-256 and byte count, then checks GNU_RELRO in all
55 packaged 64-bit libraries. The known old nativegauss defect remains a
failure; any additional library or archive error fails the audit job. Its
separate immutable release checkout is used only to resolve existing build
metadata, with no build, version or publication action.

## Evidence retained from the v1.5 native2 work

- [PR82](https://github.com/AldinAga/ald1n-project/pull/82) targets the existing
  v1.5 production release branch. It is distinct from current `main`.
- [Native compiler run 39674432635](https://github.com/AldinAga/ald1n-project/actions/runs/39674432635)
  passed the root Gradle pin, Kotlin bridge compile, assembled debug nativegauss
  LOAD check and debug APK ZIP alignment. Its compiled native inputs are
  unchanged at PR82 head `41f9674393cc5356e8181680f2c214cb51f5f899`.
- Pinned `com.github.QWEA0:liquidglass:v2.0.11` AAR SHA-256 is
  `8218f8075c3c447241f2a1e96d502fc48718c6d007e9e05347bb9669901f13a9`.
  Independent inspection of those exact bytes on 2026-10-21 found three LOAD
  segments aligned to `0x4000` and GNU_RELRO ending at `0x48000`.
- [Signed AAB27 audit](https://github.com/AldinAga/ald1n-project/actions/runs/39671783825)
  previously identified the old `libnativegauss.so` as a 4 KB LOAD failure.
  AAB27 cannot be repaired by an OTA or by this source-only verifier change.

## Remaining signed artifact gates

The final new production AAB must independently prove all packaged 64-bit ELF
LOAD and GNU_RELRO checks, `PAGE_ALIGNMENT_16K` bundle configuration, ZIP
alignment of bundletool-generated APKs, preserved signing identity, exact
native2 source/runtime and a freshly attested higher versionCode. Targeted
runtime behavior on an actual 16 KB environment remains a separate check;
previously accepted broad functional UAT stays accepted.

A passing vendor AAR or disposable debug APK is not proof of a new signed
production AAB. This change creates no EAS build and performs no Play or OTA
publication. Laravel CloudLinux deployment and signed Android compatibility
closure remain ahead of the actual SDK 58 upgrade.

Primary platform requirements:
https://developer.android.com/guide/practices/page-sizes
