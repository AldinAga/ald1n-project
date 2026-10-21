import { createContext } from 'react';

// A long-form Screen can reveal a focused native TextInput without changing
// keyboard behavior or scroll positions throughout the rest of the app.
export const KeyboardScrollContext = createContext<((nativeTarget: number) => void) | null>(null);
