/** Scroll only when the bottom of a focused field is obscured by the Android IME. */
export function neededKeyboardScroll(fieldBottom: number, keyboardTop: number, clearance = 40): number {
  if (!Number.isFinite(fieldBottom) || !Number.isFinite(keyboardTop) || !Number.isFinite(clearance)) return 0;
  return Math.max(0, Math.ceil(fieldBottom + Math.max(0, clearance) - keyboardTop));
}
