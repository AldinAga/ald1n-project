import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';

// SubAgent saves product photos straight from the APK (play3), without any new permission.
// Source-only contract; nothing here builds or runs the app.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const read=f=>readFileSync(path.join(mobile,f),'utf8');
const download=read('src/features/catalog/product-image-download.ts');
const gallery=read('src/components/catalog/product-image-gallery.tsx');
const storage=read('src/lib/storage.ts');
const pkg=JSON.parse(read('package.json'));
const lock=JSON.parse(read('package-lock.json'));
const appSource=read('app.config.js');

test('save goes through the SAF folder picker of expo-file-system, not MediaStore or media-library',()=>{
 assert.match(download,/import \{ Directory, File, Paths \} from 'expo-file-system'/);
 assert.match(download,/Directory\.pickDirectoryAsync\(PICTURES_TREE_URI\)/);
 assert.match(download,/folder\.createFile\(original\.fileName, original\.mimeType\)/);
 assert.ok(!/expo-media-library|MediaLibrary|createAssetAsync|saveToLibraryAsync|PermissionsAndroid|requestPermissions/.test(download+gallery));
});

test('no new native dependency or permission (Data safety unchanged)',()=>{
 for(const n of ['expo-media-library','expo-camera','expo-image-picker']){
  assert.ok(!(n in pkg.dependencies),n+' must not be a dependency');
  assert.ok(!lock.packages['node_modules/'+n],n+' must not be in the lock');
 }
 assert.ok(!/READ_MEDIA_|WRITE_EXTERNAL_STORAGE|MANAGE_EXTERNAL_STORAGE|ACCESS_MEDIA_LOCATION|CAMERA|RECORD_AUDIO/.test(appSource));
});

test('download stays on the canonical original URL and keeps the share path',()=>{
 assert.ok(download.includes('fetch(input.originalUrl'));
 assert.ok(!download.includes('fetch(input.displayUrl')&&!download.includes('fetch(input.thumbnailUrl'));
 assert.ok(download.includes('Sharing.shareAsync(file.uri'));
 assert.ok(download.includes('MAX_PRODUCT_IMAGE_BYTES'));
 assert.ok(gallery.includes('originalUrl: image.originalUrl'));
});

test('cancel is silent, offline has its own message, a revoked folder is re-picked once',()=>{
 assert.match(download,/\/cancel\/i\.test/);
 assert.ok(download.includes("'Nema internet veze. Poveži se i pokušaj ponovo.'"));
 assert.ok(download.includes('await imageSaveFolderStore.clear()'));
 assert.match(storage,/IMAGE_SAVE_FOLDER_KEY = 'ald1n\.catalog\.image-save-folder'/);
 assert.ok(gallery.includes('if (result.cancelled && result.saved === 0) return;'));
 // Sequential save: one original in memory at a time, cache copy removed afterwards.
 assert.match(download,/for \(const input of inputs\)/);
 assert.ok(download.includes('original.file.delete()'));
});

test('gallery offers Sačuvaj, Podeli and Sačuvaj sve with labels and no word breaking',()=>{
 assert.ok(gallery.includes("'Sačuvaj sve'")&&gallery.includes("'Sačuvaj'")&&gallery.includes("'Podeli'"));
 assert.ok(gallery.includes('accessibilityLabel={`Sačuvaj originalnu sliku ${index + 1} na telefon`}'));
 assert.ok(gallery.includes('accessibilityLabel={`Podeli originalnu sliku ${index + 1}`}'));
 assert.ok(gallery.includes('accessibilityLabel={`Sačuvaj svih ${resolvedImages.length} originalnih slika na telefon`}'));
 assert.ok(gallery.includes('canSaveProductImagesToDevice ?')&&download.includes("Platform.OS === 'android'"));
 const buttonTexts=gallery.match(/<Text style=\{styles\.(downloadText|shareText)\}[^>]*>/g)??[];
 assert.ok(buttonTexts.length>=3&&buttonTexts.every(t=>t.includes('numberOfLines={1}')));
 assert.ok(!gallery.includes('Preuzmi original'));
});
