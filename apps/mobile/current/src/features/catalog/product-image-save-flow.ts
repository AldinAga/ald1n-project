// Pure save flow for product photos (no React Native or Expo imports, so the release25 test
// can run it against fakes). The Expo wiring lives in product-image-download.ts.

export type SaveFolderFile = { write(bytes: Uint8Array): void; delete(): void };

export type SaveFolder = { uri: string; createFile(name: string, mimeType: string): SaveFolderFile };

export type FetchedOriginal = { bytes: Uint8Array; mimeType: string; fileName: string; discard(): void };

export type SaveFlowDeps<I> = {
  readStoredFolder(): Promise<string | null>;
  rememberFolder(uri: string): Promise<void>;
  forgetFolder(): Promise<void>;
  // Null when the remembered folder no longer exists or its grant was revoked.
  openFolder(uri: string): SaveFolder | null;
  // Null when the user closed the picker.
  pickFolder(): Promise<SaveFolder | null>;
  // False when the network is down; checked before the picker opens.
  isOnline(first: I): Promise<boolean>;
  fetchOriginal(input: I): Promise<FetchedOriginal>;
};

export type ProductImageSaveResult = { saved: number; total: number; cancelled: boolean; firstError: string | null };

export const OFFLINE_MESSAGE = 'Nema internet veze. Poveži se i pokušaj ponovo.';
export const WRITE_FAILED_MESSAGE = 'Upis nije uspeo. Proveri slobodan prostor na telefonu i pokušaj ponovo.';
export const FOLDER_FAILED_MESSAGE = 'Izabrani folder nije dostupan. Izaberi drugi folder.';

// Name collisions: SAF createDocument never overwrites; the provider appends " (1)", " (2)".
export async function runProductImageSave<I>(inputs: I[], deps: SaveFlowDeps<I>): Promise<ProductImageSaveResult> {
  const result: ProductImageSaveResult = { saved: 0, total: inputs.length, cancelled: false, firstError: null };
  const first = inputs[0];
  if (first === undefined) return result;
  if (!(await deps.isOnline(first))) return { ...result, firstError: OFFLINE_MESSAGE };

  let storedUri: string | null = null;
  try {
    storedUri = await deps.readStoredFolder();
  } catch {
    storedUri = null;
  }
  let folder = storedUri ? deps.openFolder(storedUri) : null;
  let fromStore = folder !== null;
  if (storedUri && folder === null) await deps.forgetFolder();

  const pick = async (): Promise<SaveFolder | null> => {
    // Changing folders keeps the old persistable grant: expo-file-system has no release API.
    const picked = await deps.pickFolder();
    if (picked !== null) await deps.rememberFolder(picked.uri);
    return picked;
  };

  if (folder === null) {
    folder = await pick();
    if (folder === null) return { ...result, cancelled: true };
  }

  // Sequential on purpose: one original in memory at a time.
  for (const input of inputs) {
    let original: FetchedOriginal;
    try {
      original = await deps.fetchOriginal(input);
    } catch (error) {
      result.firstError ??= error instanceof Error ? error.message : 'Preuzimanje nije uspelo.';
      if (result.firstError === OFFLINE_MESSAGE) break;
      continue;
    }
    try {
      let target: SaveFolderFile;
      try {
        target = folder.createFile(original.fileName, original.mimeType);
      } catch {
        // createFile fails on a deleted folder or a revoked grant: forget it and ask once.
        if (!fromStore) {
          result.firstError ??= FOLDER_FAILED_MESSAGE;
          break;
        }
        fromStore = false;
        await deps.forgetFolder();
        const repicked = await pick();
        if (repicked === null) {
          result.cancelled = true;
          break;
        }
        folder = repicked;
        try {
          target = folder.createFile(original.fileName, original.mimeType);
        } catch {
          result.firstError ??= FOLDER_FAILED_MESSAGE;
          break;
        }
      }
      try {
        target.write(original.bytes);
        result.saved += 1;
      } catch {
        // Disk full or I/O error: the folder is fine, only drop the partial 0 B file.
        try {
          target.delete();
        } catch {
          // Nothing more to clean up.
        }
        result.firstError ??= WRITE_FAILED_MESSAGE;
      }
    } finally {
      try {
        original.discard();
      } catch {
        // A cache leftover must not stop the remaining images.
      }
    }
  }
  return result;
}
