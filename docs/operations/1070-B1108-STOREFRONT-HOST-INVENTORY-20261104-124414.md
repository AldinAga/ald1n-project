# B1108 read-only hosting source/dependency inventory
SCRIPT=/home/icaffeco/ald1n-project/incoming/b1108-storefront-host-inventory.sh
SCRIPT_SHA256=0011225f5426c3874f54892db286c761a6887246e0604b51742ee4a12a74c028
BODY_INTEGRITY=PASS REPORT_SEQUENCE_REMOTE=1016 LOCAL=1069
LOCAL_HEAD=b5b942e645d28d3ed5f248eaae4180ebe9a54ee6
REMOTE_MAIN=2869b414e15d68b21b345f7e191dd8f2c8273025
BRANCH=main
MUTATION=REPORT_ONLY_AND_GIT_FETCH_METADATA; NO_SOURCE_DB_ENV_CACHE_MAIL_FPM_CHANGE
INVENTORY_ONLY: backup/restore, owner UAT, current deployment and release approval are NOT attested.
PHP_BIN=/usr/local/bin/php
PHP_VERSION=8.4.27
{"source_path":"apps/cms/current/composer.lock","sha256":"27ad4a0ca3845bc10967329ef61583f7f4a81f5d9e67ad5e5c304fbf9df7d014","matches_reviewed_main":false}
{"source_path":"apps/mobile/current/package-lock.json","sha256":"1db0f93d5e869773b1bc5a4fd4752a204a45ee192dad59de507aca82c7e1bef2","matches_reviewed_main":false}
{"source_path":"apps/cms/current/public/assets/css/ald1n-ui-refinement.css","sha256":"f77a4ccad3efccb1f4bc1288bf8783b875648db8acb5a30592c92901aea114c9","matches_reviewed_main":true}
{"source_path":"apps/cms/current/resources/views/layouts/app.blade.php","sha256":"26bf2f54ec706d5c8d9e9b52ccf2ff4ca439c62eead6606525232a3bf22e488d","matches_reviewed_main":true}
{"package":"guzzlehttp/guzzle","source_lock_version":"7.15.1","installed_version":"7.15.1","installed_matches_lock":true}
{"package":"guzzlehttp/promises","source_lock_version":"2.5.1","installed_version":"2.5.1","installed_matches_lock":true}
{"package":"guzzlehttp/psr7","source_lock_version":"2.13.0","installed_version":"2.13.0","installed_matches_lock":true}
{"package":"laravel/framework","source_lock_version":"v13.20.0","installed_version":"v13.20.0","installed_matches_lock":true}
{"package":"league/commonmark","source_lock_version":"2.8.3","installed_version":"2.8.3","installed_matches_lock":true}
{"package":"league/flysystem","source_lock_version":"3.35.2","installed_version":"3.35.2","installed_matches_lock":true}
{"package":"symfony/http-foundation","source_lock_version":"v8.1.1","installed_version":"v8.1.1","installed_matches_lock":true}
{"package":"symfony/mime","source_lock_version":"v8.1.0","installed_version":"v8.1.0","installed_matches_lock":true}
SOURCE_VENDOR_INVENTORY=HOLD_DRIFT_OR_NOT_YET_DEPLOYED
RELEASE_APPROVAL=NOT_EVALUATED; NO_DATABASE_CONNECTION_OR_SECRET_FILE_READ
PHP_INVENTORY_RC=2
REPORT=/home/icaffeco/ald1n-project/docs/operations/1070-B1108-STOREFRONT-HOST-INVENTORY-20261104-124414.md
