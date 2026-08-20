import type { ReactNode } from 'react';
import { Pill, type PillTone } from '@/components/ui/pill';

export function Badge({ children, tone = 'neutral' }: { children: ReactNode; tone?: PillTone }) {
  return <Pill tone={tone}>{children}</Pill>;
}
