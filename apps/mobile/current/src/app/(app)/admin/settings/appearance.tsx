import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useMemo, useState } from 'react';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { appendAdminSystemFile, appendAdminSystemFiles, apiAdminSystemSettings, pickAdminSystemFiles, type AdminAppearanceSlide, type AdminSystemFile } from '@/features/admin/system-settings-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type PickedAssets = { logoLight?: AdminSystemFile; logoDark?: AdminSystemFile; favicon?: AdminSystemFile; background?: AdminSystemFile; fallback?: AdminSystemFile; slides: AdminSystemFile[] };

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET05
export default function AdminAppearanceSettingsScreen() {
  const { can, bootstrap } = useAuth();
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('system.manage_settings');
  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsAppearance(), queryFn: apiAdminSystemSettings.appearance.state, enabled: allowed });
  const [values, setValues] = useState<Record<string, string>>({});
  const [picked, setPicked] = useState<PickedAssets>({ slides: [] });
  const [slideState, setSlideState] = useState<AdminAppearanceSlide[]>([]);

  useEffect(() => { if (!query.data) return; setValues(query.data.data.settings); setSlideState(query.data.data.assets.slides); setPicked({ slides: [] }); }, [query.data]);

  const mutation = useMutation({
    mutationFn: async () => {
      const data = query.data!.data;
      const form = new FormData();
      const textKeys = ['site_name','site_logo_alt','site_header_logo_height','site_footer_layout','site_footer_copyright_text','site_footer_secondary_text','site_footer_link_1_label','site_footer_link_1_url','site_footer_link_2_label','site_footer_link_2_url','site_footer_link_3_label','site_footer_link_3_url'];
      textKeys.forEach((key) => form.append(key, values[key] ?? ''));
      form.append('site_footer_show_logo', values.site_footer_show_logo === '1' ? '1' : '0');
      form.append('site_footer_links_new_tab', values.site_footer_links_new_tab === '1' ? '1' : '0');
      if (data.can_manage_login_background) {
        ['login_background_mode','login_background_overlay_opacity','login_background_blur_px','login_background_slide_interval'].forEach((key) => form.append(key, values[key] ?? ''));
        form.append('login_background_youtube_url', values.login_background_youtube_id ?? '');
        form.append('login_background_mobile_static', values.login_background_mobile_static === '1' ? '1' : '0');
        form.append('login_background_slide_state_present', '1');
        slideState.forEach((slide) => {
          if (slide.active && slide.path) form.append('login_background_slide_active[]', String(slide.slot));
          if (slide.path) form.append(`login_background_slide_order[${slide.slot}]`, String(slide.order));
        });
      }
      if (picked.logoLight) appendAdminSystemFile(form, 'site_logo_light', picked.logoLight);
      if (picked.logoDark) appendAdminSystemFile(form, 'site_logo_dark', picked.logoDark);
      if (picked.favicon) appendAdminSystemFile(form, 'site_favicon', picked.favicon);
      if (picked.background) appendAdminSystemFile(form, 'login_background_image', picked.background);
      if (picked.fallback) appendAdminSystemFile(form, 'login_background_fallback', picked.fallback);
      if (picked.slides.length) appendAdminSystemFiles(form, 'login_slideshow_images', picked.slides);
      return apiAdminSystemSettings.appearance.update(form);
    },
    onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsAppearance(), response); feedback.notify({ tone: 'success', title: 'Izgled sajta je sačuvan' }); },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Izgled nije sačuvan', message: error instanceof Error ? error.message : 'Greška.' }),
  });
  const removeMutation = useMutation({ mutationFn: apiAdminSystemSettings.appearance.removeAsset, onSuccess: (response) => client.setQueryData(adminQueryKeys.systemSettingsAppearance(), response), onError: (error) => feedback.notify({ tone: 'danger', title: 'Fajl nije uklonjen', message: error instanceof Error ? error.message : 'Greška.' }) });

  const pickOne = async (key: keyof Omit<PickedAssets, 'slides'>, limits: { mime_types: string[]; max_bytes: number }) => { try { const files = await pickAdminSystemFiles(limits); if (files[0]) setPicked((current) => ({ ...current, [key]: files[0] })); } catch (error) { feedback.notify({ tone: 'danger', title: 'Fajl nije prihvaćen', message: error instanceof Error ? error.message : 'Greška.' }); } };

  if (!allowed) return <UnavailableState title="Izgled sajta nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje izgleda sajta…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const data = query.data.data;
  const set = (key: string, value: string) => setValues((current) => ({ ...current, [key]: value }));

  return <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
    <PageHeader title="Izgled sajta" eyebrow="Sistem · SET-05" name={bootstrap?.user.name} />
    <Card style={styles.card}><Text style={styles.title}>Brending</Text><TextField label="Naziv sajta" value={values.site_name ?? ''} onChangeText={(v) => set('site_name', v)} /><TextField label="Alt tekst logotipa" value={values.site_logo_alt ?? ''} onChangeText={(v) => set('site_logo_alt', v)} /><TextField label="Visina logotipa (24–80)" value={values.site_header_logo_height ?? '38'} keyboardType="number-pad" onChangeText={(v) => set('site_header_logo_height', v)} /><Button variant="secondary" onPress={() => void pickOne('logoLight', data.upload_limits.images)}>Izaberi svetli logo{picked.logoLight ? ` · ${picked.logoLight.name}` : ''}</Button><Button variant="secondary" onPress={() => void pickOne('logoDark', data.upload_limits.images)}>Izaberi tamni logo{picked.logoDark ? ` · ${picked.logoDark.name}` : ''}</Button><Button variant="secondary" onPress={() => void pickOne('favicon', data.upload_limits.favicon)}>Izaberi favicon{picked.favicon ? ` · ${picked.favicon.name}` : ''}</Button><View style={styles.actions}>{data.assets.logo_light_url ? <Button variant="secondary" onPress={() => removeMutation.mutate('logo-light')}>Ukloni svetli logo</Button> : null}{data.assets.logo_dark_url ? <Button variant="secondary" onPress={() => removeMutation.mutate('logo-dark')}>Ukloni tamni logo</Button> : null}{data.assets.favicon_url ? <Button variant="secondary" onPress={() => removeMutation.mutate('favicon')}>Ukloni favicon</Button> : null}</View></Card>
    <Card style={styles.card}><Text style={styles.title}>Footer</Text><View style={styles.row}><Text style={styles.body}>Prikaži logo</Text><Switch value={values.site_footer_show_logo === '1'} onValueChange={(v) => set('site_footer_show_logo', v ? '1' : '0')} /></View><SelectSheet label="Raspored" value={values.site_footer_layout ?? 'split'} options={[{ value: 'split', label: 'Podeljen' }, { value: 'centered', label: 'Centriran' }]} onChange={(v) => set('site_footer_layout', v)} /><TextField label="Copyright" value={values.site_footer_copyright_text ?? ''} onChangeText={(v) => set('site_footer_copyright_text', v)} /><TextField label="Sekundarni tekst" value={values.site_footer_secondary_text ?? ''} onChangeText={(v) => set('site_footer_secondary_text', v)} /><View style={styles.row}><Text style={styles.body}>Linkovi u novom tabu</Text><Switch value={values.site_footer_links_new_tab === '1'} onValueChange={(v) => set('site_footer_links_new_tab', v ? '1' : '0')} /></View>{[1,2,3].map((n) => <View key={n} style={styles.sub}><TextField label={`Link ${n} · naziv`} value={values[`site_footer_link_${n}_label`] ?? ''} onChangeText={(v) => set(`site_footer_link_${n}_label`, v)} /><TextField label={`Link ${n} · URL`} value={values[`site_footer_link_${n}_url`] ?? ''} autoCapitalize="none" onChangeText={(v) => set(`site_footer_link_${n}_url`, v)} /></View>)}</Card>
    {data.can_manage_login_background ? <Card style={styles.card}><Text style={styles.title}>Pozadina prijave · SuperAdmin</Text><SelectSheet label="Režim" value={values.login_background_mode ?? 'default'} options={[{ value: 'default', label: 'Podrazumevano' },{ value: 'image', label: 'Jedna slika' },{ value: 'slideshow', label: 'Slideshow' },{ value: 'youtube', label: 'YouTube' }]} onChange={(v) => set('login_background_mode', v)} /><TextField label="YouTube URL ili ID" value={values.login_background_youtube_id ?? ''} autoCapitalize="none" onChangeText={(v) => set('login_background_youtube_id', v)} /><TextField label="Overlay 0–90" value={values.login_background_overlay_opacity ?? '45'} keyboardType="number-pad" onChangeText={(v) => set('login_background_overlay_opacity', v)} /><TextField label="Blur 0–10" value={values.login_background_blur_px ?? '0'} keyboardType="number-pad" onChangeText={(v) => set('login_background_blur_px', v)} /><TextField label="Slajd interval 3–30 s" value={values.login_background_slide_interval ?? '6'} keyboardType="number-pad" onChangeText={(v) => set('login_background_slide_interval', v)} /><View style={styles.row}><Text style={styles.body}>Mobilni statični fallback</Text><Switch value={values.login_background_mobile_static === '1'} onValueChange={(v) => set('login_background_mobile_static', v ? '1' : '0')} /></View><Button variant="secondary" onPress={() => void pickOne('background', data.upload_limits.images)}>Izaberi glavnu pozadinu{picked.background ? ` · ${picked.background.name}` : ''}</Button><Button variant="secondary" onPress={() => void pickOne('fallback', data.upload_limits.images)}>Izaberi fallback{picked.fallback ? ` · ${picked.fallback.name}` : ''}</Button><Button variant="secondary" onPress={async () => { try { const files = await pickAdminSystemFiles(data.upload_limits.images, true); setPicked((current) => ({ ...current, slides: [...current.slides, ...files].slice(0, 8) })); } catch (error) { feedback.notify({ tone: 'danger', title: 'Slajdovi nisu prihvaćeni', message: error instanceof Error ? error.message : 'Greška.' }); } }}>Dodaj slideshow slike{picked.slides.length ? ` · ${picked.slides.length}` : ''}</Button>{slideState.filter((slide) => slide.path).map((slide) => <View key={slide.slot} style={styles.sub}><View style={styles.row}><Text style={styles.body}>Slajd {slide.slot}</Text><Switch value={slide.active} onValueChange={(active) => setSlideState((current) => current.map((item) => item.slot === slide.slot ? { ...item, active } : item))} /></View><TextField label="Redosled" value={String(slide.order)} keyboardType="number-pad" onChangeText={(value) => setSlideState((current) => current.map((item) => item.slot === slide.slot ? { ...item, order: Number(value) || item.order } : item))} /><Button variant="secondary" onPress={() => removeMutation.mutate(`login-slide-${slide.slot}`)}>Ukloni slajd</Button></View>)}<View style={styles.actions}>{data.assets.login_background_url ? <Button variant="secondary" onPress={() => removeMutation.mutate('login-background')}>Ukloni glavnu pozadinu</Button> : null}{data.assets.login_fallback_url ? <Button variant="secondary" onPress={() => removeMutation.mutate('login-fallback')}>Ukloni fallback</Button> : null}</View></Card> : null}
    <Button loading={mutation.isPending} onPress={() => mutation.mutate()}>Sačuvaj izgled sajta</Button>
  </Screen>;
}

function createStyles(theme: AppColors) { return StyleSheet.create({ content: { gap: spacing.lg, paddingBottom: 160 }, card: { gap: spacing.md }, title: { ...typography.h2, color: theme.ink }, body: { ...typography.body, color: theme.ink, flex: 1 }, row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md }, actions: { gap: spacing.sm }, sub: { gap: spacing.sm, paddingVertical: spacing.sm } }); }
