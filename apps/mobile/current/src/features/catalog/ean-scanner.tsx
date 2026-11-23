import { CameraView } from 'expo-camera';
import { useEffect, useRef, useState } from 'react';
import { Platform } from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';

// Same rule as the server (ProductRequest): 8 to 14 digits.
const EAN_PATTERN = /^[0-9]{8,14}$/;

export function normalizeEan(raw: string): string {
  return raw.replace(/[\s-]+/g, '');
}

export function isValidEan(raw: string): boolean {
  return EAN_PATTERN.test(normalizeEan(raw));
}

/**
 * Opens the Google barcode scanner (no own camera view, no image reaches the app).
 * Falls back to the manual EAN field when the scanner is not available.
 */
export function EanScanButton({
  onScanned,
  disabled,
}: {
  onScanned: (ean: string) => void;
  disabled?: boolean;
}) {
  const feedback = useAppFeedback();
  const [scanning, setScanning] = useState(false);
  const onScannedRef = useRef(onScanned);
  onScannedRef.current = onScanned;
  const subscription = useRef<{ remove: () => void } | null>(null);

  useEffect(() => () => subscription.current?.remove(), []);

  const scan = async () => {
    if (!CameraView.isModernBarcodeScannerAvailable) {
      feedback.notify({
        tone: 'warning',
        title: 'Skener nije dostupan',
        message: 'Na ovom uređaju nema Google skenera. Upiši EAN ručno u polje.',
      });
      return;
    }

    setScanning(true);
    subscription.current?.remove();
    subscription.current = CameraView.onModernBarcodeScanned((result) => {
      subscription.current?.remove();
      subscription.current = null;
      // Android closes the Google scanner itself after a hit; dismissScanner is iOS-only.
      if (Platform.OS === 'ios') void CameraView.dismissScanner();
      const ean = normalizeEan(result.data);
      if (isValidEan(ean)) {
        onScannedRef.current(ean);
      } else {
        feedback.notify({
          tone: 'warning',
          title: 'Bar-kod nije EAN',
          message: 'Očitani kod nema 8 do 14 cifara. Probaj ponovo ili upiši EAN ručno.',
        });
      }
      setScanning(false);
    });

    try {
      await CameraView.launchScanner({ barcodeTypes: ['ean13', 'ean8', 'upc_e'] });
    } catch (error) {
      subscription.current?.remove();
      subscription.current = null;
      // The Google scanner rejects (instead of resolving) when the user closes it: not an error.
      const code = (error as { code?: unknown } | null)?.code;
      if (code === 'ERR_BARCODE_SCANNING_CANCELLED') return;
      feedback.notify({
        tone: 'danger',
        title: 'Skeniranje nije uspelo',
        message: error instanceof Error && error.message ? error.message : 'Upiši EAN ručno u polje.',
      });
    } finally {
      setScanning(false);
    }
  };

  return (
    <Button variant="secondary" onPress={() => void scan()} loading={scanning} disabled={disabled}>
      Skeniraj EAN
    </Button>
  );
}
