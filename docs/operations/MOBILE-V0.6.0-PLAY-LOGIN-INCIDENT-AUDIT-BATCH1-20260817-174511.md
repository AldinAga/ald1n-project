
============================================================
MOBILE v0.6.0 - PLAY LOGIN INCIDENT AUDIT - BATCH 1
============================================================
MODE=READ_ONLY
PROJECT=/home/icaffeco/ald1n-project
CMS=/home/icaffeco/ald1n-project/apps/cms/current
MOBILE=/home/icaffeco/ald1n-project/apps/mobile/current
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.6.0-PLAY-LOGIN-INCIDENT-AUDIT-BATCH1-20260817-174511.md
DEVICE_RUNTIME_REPORTED_BY_USER=SM-S928B_ANDROID16_ONE_UI8.5
INCIDENT_A=PASSWORD_LOGIN_FORM_OBSCURED_OR_TRAPPED_BY_SOFTWARE_KEYBOARD
INCIDENT_B=GOOGLE_SIGN_IN_FAILS_ON_PLAY_DISTRIBUTED_BUILD
SOURCE_WRITES_EXPECTED=0
DATABASE_WRITES_EXPECTED=0
MIGRATIONS_RUN=NO
EAS_BUILD=NO

============================================================
0. PREFLIGHT
============================================================
PASS command: bash
PASS command: grep
PASS command: sed
PASS command: awk
PASS command: cut
PASS command: head
PASS command: tail
PASS command: cat
PASS command: date
PASS command: mkdir
PASS command: rmdir
PASS command: rm
PASS command: sha256sum
PASS command: sort
PASS command: wc
PASS command: find
PASS command: git
PASS command: php
NODE_BIN=/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node
v22.23.2
NODE_VERSION=v22.23.2
PASS file: apps/mobile/current/src/app/(auth)/login.tsx
PASS file: apps/mobile/current/src/components/ui/text-field.tsx
PASS file: apps/mobile/current/src/features/auth/google-auth.ts
PASS file: apps/mobile/current/src/features/auth/auth-provider.tsx
PASS file: apps/mobile/current/app.config.js
PASS file: apps/mobile/current/eas.json
PASS file: apps/mobile/current/google-services.json
PASS file: apps/mobile/current/package.json
PASS file: apps/cms/current/config/mobile.php
PASS file: apps/cms/current/routes/api.php
PASS file: apps/cms/current/app/Services/GoogleIdentityVerifier.php
PASS file: apps/cms/current/app/Http/Controllers/Api/V1/GoogleAuthController.php
LOGIN_SHA256=ae4ac9272d00751d832334a835ec29eb76422bc73724af10c0ecf3b243ecf926
TEXT_FIELD_SHA256=0cae272e421074b643a66647b885760de7d3348457f3a9bc8195be04c3b836ec
GOOGLE_AUTH_SHA256=13115291398d0618e9823ba97abc0f4cc6102beacd4b113573c7270ff7141b51
AUTH_PROVIDER_SHA256=1a795fae045ae4941e3ce1d1ec802199b321ec58fe04c5ed733ab5eca8158dfd
APP_CONFIG_SHA256=0f7ab785cebca9aebeaeb6f0f0aa4f3cbe65ddbf1d2976cac6299c24352aeff6
GOOGLE_SERVICES_SHA256=46331f0d3ec34838e32d773dde77a73fc23a478ab8352bbd2a5b0e13612f3efd

============================================================
1. EXACT LOGIN SCREEN + TEXT FIELD CONTRACT
============================================================
--- src/app/(auth)/login.tsx ---
     1	import { zodResolver } from '@hookform/resolvers/zod';
     2	import { router } from 'expo-router';
     3	import { GoogleSignInButton } from 'react-native-nitro-google-signin';
     4	import { Controller, useForm } from 'react-hook-form';
     5	import { useState } from 'react';
     6	import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, View } from 'react-native';
     7	import { z } from 'zod';
     8	import { BrandMark } from '@/components/ui/brand-mark';
     9	import { Button } from '@/components/ui/button';
    10	import { Glyph } from '@/components/ui/glyph';
    11	import { TextField } from '@/components/ui/text-field';
    12	import { radii, shadow, spacing, typography, type AppColors } from '@/constants/theme';
    13	import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
    14	import { useAuth } from '@/features/auth/auth-provider';
    15	import { API_URL, ApiError } from '@/lib/api/client';
    16
    17	const schema = z.object({
    18	  login: z.string().trim().min(1, 'Unesi korisničko ime ili e-mail.'),
    19	  password: z.string().min(1, 'Unesi lozinku.')
    20	});
    21
    22	type FormValues = z.infer<typeof schema>;
    23
    24	export default function LoginScreen() {
    25	  const { scheme, colors: themeColors } = useAppTheme();
    26	  const styles = useThemedStyles(createStyles);
    27
    28	  const { signIn, signInWithGoogle } = useAuth();
    29	  const [googleLoading, setGoogleLoading] = useState(false);
    30	  const { control, handleSubmit, setError, clearErrors, formState: { errors, isSubmitting } } = useForm<FormValues>({
    31	    resolver: zodResolver(schema),
    32	    defaultValues: { login: '', password: '' }
    33	  });
    34
    35	  const submit = handleSubmit(async (values) => {
    36	    try {
    37	      await signIn(values.login, values.password);
    38	      router.replace('/home');
    39	    } catch (error) {
    40	      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : 'Prijava nije uspela.';
    41	      setError('root', { message });
    42	    }
    43	  });
    44
    45	  const google = async () => {
    46	    if (googleLoading) return;
    47	    setGoogleLoading(true);
    48	    clearErrors('root');
    49	    try {
    50	      const outcome = await signInWithGoogle();
    51	      if (outcome.status === 'pending') {
    52	        setError('root', { message: outcome.message });
    53	        return;
    54	      }
    55	      router.replace('/home');
    56	    } catch (error) {
    57	      const message = error instanceof ApiError ? error.firstFieldError() ?? error.message : error instanceof Error ? error.message : 'Google prijava nije uspela.';
    58	      setError('root', { message });
    59	    } finally {
    60	      setGoogleLoading(false);
    61	    }
    62	  };
    63
    64	  return (
    65	    <KeyboardAvoidingView style={styles.page} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
    66	      <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
    67	        <View style={styles.hero}>
    68	          <View style={styles.orbOne} /><View style={styles.orbTwo} />
    69	          <BrandMark size={58} inverse />
    70	          <Text style={styles.kicker}>ALD1N MOBILE</Text>
    71	          <Text style={styles.heroTitle}>Sve što ti treba, odmah pri ruci.</Text>
    72	          <Text style={styles.heroCopy}>Katalog, porudžbine i obaveštenja u brzom mobilnom interfejsu.</Text>
    73	        </View>
    74
    75	        <View style={styles.formCard}>
    76	          <View style={styles.formHeading}>
    77	            <View style={styles.headingCopy}><Text style={styles.title}>Dobro došli</Text><Text style={styles.subtitle}>Prijavi se CMS nalogom ili nastavi sa Google nalogom.</Text></View>
    78	            <View style={styles.secure}><Glyph name="lock" size={18} color={themeColors.success} /></View>
    79	          </View>
    80
    81	          <View style={styles.googleButtonWrap}>
    82	            <GoogleSignInButton
    83	              size="wide"
    84	              colorScheme={scheme}
    85	              signInBehavior="none"
    86	              loading={googleLoading}
    87	              disabled={googleLoading || isSubmitting}
    88	              onPress={() => void google()}
    89	              style={styles.googleButton}
    90	              accessibilityLabel="Nastavi sa Google nalogom"
    91	            />
    92	          </View>
    93
    94	          <View style={styles.divider}><View style={styles.dividerLine} /><Text style={styles.dividerText}>ILI</Text><View style={styles.dividerLine} /></View>
    95
    96	          <Controller control={control} name="login" render={({ field: { value, onBlur, onChange } }) => (
    97	            <TextField label="Korisničko ime ili e-mail" autoCapitalize="none" autoCorrect={false} value={value} onBlur={onBlur} onChangeText={onChange} error={errors.login?.message} returnKeyType="next" />
    98	          )} />
    99	          <Controller control={control} name="password" render={({ field: { value, onBlur, onChange } }) => (
   100	            <TextField label="Lozinka" secureTextEntry value={value} onBlur={onBlur} onChangeText={onChange} error={errors.password?.message} returnKeyType="done" onSubmitEditing={() => void submit()} />
   101	          )} />
   102	          {errors.root?.message ? <Text style={styles.rootError}>{errors.root.message}</Text> : null}
   103	          <Button onPress={submit} loading={isSubmitting}>Prijavi se</Button>
   104	          <Text style={styles.registrationNote}>Novi Google nalog može automatski da kreira registraciju. Aktivacija i dalje prati pravila CMS-a.</Text>
   105	          <View style={styles.apiLine}><View style={styles.onlineDot} /><Text style={styles.apiText} numberOfLines={1}>{API_URL}</Text></View>
   106	        </View>
   107	      </ScrollView>
   108	    </KeyboardAvoidingView>
   109	  );
   110	}
   111
   112	function createStyles(theme: AppColors) {
   113	  return StyleSheet.create({
   114	  page: { flex: 1, backgroundColor: theme.background },
   115	  scroll: { flexGrow: 1, justifyContent: 'center', padding: spacing.lg, gap: spacing.lg },
   116	  hero: { minHeight: 292, overflow: 'hidden', borderRadius: radii.xxl, backgroundColor: theme.hero, padding: spacing.xxl, justifyContent: 'flex-end', ...shadow },
   117	  orbOne: { position: 'absolute', width: 230, height: 230, borderRadius: 115, right: -85, top: -75, backgroundColor: theme.primary, opacity: 0.62 },
   118	  orbTwo: { position: 'absolute', width: 138, height: 138, borderRadius: 69, right: 52, top: 72, backgroundColor: theme.accent, opacity: 0.24 },
   119	  kicker: { ...typography.small, color: theme.accent, letterSpacing: 1.8, fontWeight: '900', marginTop: spacing.xl },
   120	  heroTitle: { ...typography.hero, color: theme.white, marginTop: spacing.sm, maxWidth: 340 },
   121	  heroCopy: { ...typography.body, color: theme.heroMuted, marginTop: spacing.md, maxWidth: 350 },
   122	  formCard: { backgroundColor: theme.surface, borderRadius: radii.xxl, padding: spacing.xl, gap: spacing.lg, ...shadow },
   123	  formHeading: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
   124	  headingCopy: { flex: 1 },
   125	  title: { ...typography.h1, color: theme.ink },
   126	  subtitle: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
   127	  secure: { width: 44, height: 44, borderRadius: radii.pill, backgroundColor: theme.successSoft, alignItems: 'center', justifyContent: 'center' },
   128	  googleButtonWrap: { width: '100%', alignItems: 'center', justifyContent: 'center' },
   129	  googleButton: { width: '100%', height: 48 },
   130	  divider: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
   131	  dividerLine: { flex: 1, height: 1, backgroundColor: theme.line },
   132	  dividerText: { ...typography.small, color: theme.muted, letterSpacing: 1.2 },
   133	  rootError: { ...typography.small, color: theme.danger, backgroundColor: theme.dangerSoft, padding: spacing.md, borderRadius: radii.lg },
   134	  registrationNote: { ...typography.small, color: theme.muted, textAlign: 'center', paddingHorizontal: spacing.md },
   135	  apiLine: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: spacing.sm },
   136	  onlineDot: { width: 7, height: 7, borderRadius: 4, backgroundColor: theme.success },
   137	  apiText: { ...typography.small, color: theme.muted, maxWidth: '85%' }
   138	});
   139	}
--- src/components/ui/text-field.tsx ---
     1	import {
     2	  forwardRef,
     3	  type ComponentProps,
     4	  type ComponentRef,
     5	} from 'react';
     6	import { Input, Text, YStack } from 'tamagui';
     7
     8	import { radii, spacing, typography } from '@/constants/theme';
     9
    10	type TamaguiInputProps = ComponentProps<typeof Input>;
    11
    12	export type TextFieldProps = Omit<TamaguiInputProps, 'ref'> & {
    13	  label: string;
    14	  error?: string;
    15	};
    16
    17	type TextFieldRef = ComponentRef<typeof Input>;
    18
    19	export const TextField = forwardRef<TextFieldRef, TextFieldProps>(
    20	  function TextField(
    21	    {
    22	      label,
    23	      error,
    24	      style,
    25	      placeholderTextColor = '$placeholderColor',
    26	      selectionColor = '$brand',
    27	      ...inputProps
    28	    },
    29	    ref,
    30	  ) {
    31	    return (
    32	      <YStack gap={spacing.sm}>
    33	        <Text
    34	          paddingHorizontal={spacing.xs}
    35	          fontSize={typography.label.fontSize}
    36	          lineHeight={typography.label.lineHeight}
    37	          fontWeight="800"
    38	          color="$color"
    39	        >
    40	          {label}
    41	        </Text>
    42
    43	        <Input
    44	          unstyled
    45	          ref={ref}
    46	          minHeight={56}
    47	          borderWidth={1}
    48	          borderColor={error ? '$danger' : '$line'}
    49	          borderRadius={radii.xl}
    50	          backgroundColor="$surfaceContainer"
    51	          color="$color"
    52	          paddingHorizontal={spacing.lg}
    53	          fontSize={16}
    54	          transition="100ms"
    55	          focusStyle={{
    56	            borderColor: error ? '$danger' : '$brand',
    57	            backgroundColor: '$surface',
    58	          }}
    59	          style={style}
    60	          {...inputProps}
    61	          placeholderTextColor={placeholderTextColor}
    62	          selectionColor={selectionColor}
    63	        />
    64
    65	        {error ? (
    66	          <Text
    67	            paddingHorizontal={spacing.xs}
    68	            fontSize={typography.small.fontSize}
    69	            lineHeight={typography.small.lineHeight}
    70	            fontWeight="600"
    71	            color="$danger"
    72	          >
    73	            {error}
    74	          </Text>
    75	        ) : null}
    76	      </YStack>
    77	    );
    78	  },
    79	);
    80
    81	TextField.displayName = 'TextField';
LOGIN_HAS_KEYBOARD_AVOIDING_VIEW=YES
LOGIN_HAS_SCROLL_VIEW=YES
LOGIN_HAS_FLEX_GROW_CONTENT=YES
LOGIN_HAS_KEYBOARD_SHOULD_PERSIST_TAPS=YES
LOGIN_HAS_KEYBOARD_DISMISS_MODE=NO
LOGIN_KAV_BEHAVIOR_EXPLICIT=YES
LOGIN_USES_PLATFORM_BRANCH_FOR_KEYBOARD=YES
LOGIN_HAS_FIXED_HEIGHT_STYLE=YES
LOGIN_HAS_MIN_HEIGHT_STYLE=YES
LOGIN_HAS_CENTER_JUSTIFICATION=YES
TEXT_FIELD_HAS_FIXED_HEIGHT_STYLE=NO
TEXT_FIELD_USES_TEXT_INPUT=NO
TEXT_FIELD_SECURE_ENTRY_SUPPORTED=NO

============================================================
2. ANDROID KEYBOARD / EDGE-TO-EDGE CONFIG SIGNALS
============================================================
--- app.config.js relevant Android lines ---
67:    android: {
68:      ...(config.android || {}),
69:      package: `com.ald1n.mobile${suffix}`,
123:              androidGoogleServicesFile: GOOGLE_SERVICES_FILE,
APP_CONFIG_SOFTWARE_KEYBOARD_LAYOUT_MODE_EXPLICIT=NO
APP_CONFIG_KEYBOARD_MODE_RESIZE=NO
APP_CONFIG_KEYBOARD_MODE_PAN=NO
APP_CONFIG_EDGE_TO_EDGE_EXPLICIT=NO
NATIVE_ANDROID_MANIFEST_PRESENT=NO_MANAGED_PROJECT_OR_NOT_COMMITTED

============================================================
3. GOOGLE SIGN-IN MOBILE SOURCE CONTRACT
============================================================
--- src/features/auth/google-auth.ts ---
     1	import {
     2	  GoogleOneTapSignIn,
     3	  isNoSavedCredentialFoundResponse,
     4	  isSuccessResponse,
     5	} from 'react-native-nitro-google-signin';
     6
     7	let configured = false;
     8
     9	function configureGoogle(): void {
    10	  if (configured) return;
    11	  GoogleOneTapSignIn.configure({ webClientId: 'autoDetect' });
    12	  configured = true;
    13	}
    14
    15	export async function getGoogleIdToken(): Promise<string> {
    16	  configureGoogle();
    17	  await GoogleOneTapSignIn.checkPlayServices();
    18
    19	  let response = await GoogleOneTapSignIn.signIn();
    20	  if (isNoSavedCredentialFoundResponse(response)) {
    21	    response = await GoogleOneTapSignIn.createAccount();
    22	  }
    23	  if (isNoSavedCredentialFoundResponse(response)) {
    24	    response = await GoogleOneTapSignIn.presentExplicitSignIn();
    25	  }
    26
    27	  if (!isSuccessResponse(response) || !response.data.idToken) {
    28	    throw new Error('Google prijava je otkazana ili nije vratila validan identitet.');
    29	  }
    30
    31	  return response.data.idToken;
    32	}
    33
    34	export async function clearGoogleCredentialState(): Promise<void> {
    35	  try {
    36	    configureGoogle();
    37	    await GoogleOneTapSignIn.signOut();
    38	  } catch {
    39	    // Laravel/Sanctum logout ne sme da zavisi od Google provider cleanup-a.
    40	  }
    41	}
--- auth-provider Google-related lines ---
7:import { clearGoogleCredentialState, getGoogleIdToken } from '@/features/auth/google-auth';
11:export type GoogleSignInOutcome = { status: 'authenticated' } | { status: 'pending'; message: string };
17:  signInWithGoogle: () => Promise<GoogleSignInOutcome>;
65:      await clearGoogleCredentialState();
116:  const signInWithGoogle = useCallback(async (): Promise<GoogleSignInOutcome> => {
117:    const idToken = await getGoogleIdToken();
119:    const response = await api.auth.google({ id_token: idToken, device_name: deviceName });
133:      await Promise.all([clearSession(), clearGoogleCredentialState()]);
141:    status, bootstrap, signIn, signInWithGoogle, signOut, refreshBootstrap,
145:    signIn, signInWithGoogle, signOut, status
GOOGLE_SOURCE_USES_ONE_TAP=YES
GOOGLE_SOURCE_USES_AUTO_DETECT_WEB_CLIENT=YES
GOOGLE_SOURCE_HAS_EXPLICIT_WEB_CLIENT_ID=NO
GOOGLE_SOURCE_SURFACES_NATIVE_ERROR_CODE=NO
GOOGLE_SOURCE_SURFACES_NATIVE_ERROR_MESSAGE=NO
AUTH_PROVIDER_POSTS_GOOGLE_ID_TOKEN=YES
PACKAGE_VERSION=0.6.0
DEPENDENCY_EXPO=~57.0.12
DEPENDENCY_REACT_NATIVE=0.86.2
DEPENDENCY_NITRO_GOOGLE_SIGNIN=1.0.2
DEPENDENCY_NITRO_MODULES=0.36.1

============================================================
4. GOOGLE SERVICES JSON - NON-SECRET OAUTH INVENTORY
============================================================
FIREBASE_PROJECT_ID=ald1nmobileapk
GOOGLE_SERVICES_CLIENT_COUNT=2
ANDROID_CLIENT_PACKAGE[0]=com.ald1n.mobile
OAUTH_CLIENT[0.0].TYPE=1
OAUTH_CLIENT[0.0].ID=270925336895-1h1uf5bqh7aq3aoe64rbcur4tfac9uqu.apps.googleusercontent.com
OAUTH_CLIENT[0.0].ANDROID_PACKAGE=com.ald1n.mobile
OAUTH_CLIENT[0.0].CERTIFICATE_HASH=4faa0b430ddf189354aa2f672b280bc5bc13563b
OAUTH_CLIENT[0.1].TYPE=1
OAUTH_CLIENT[0.1].ID=270925336895-qev924rl4rmoiudeo59rg8cels623gl4.apps.googleusercontent.com
OAUTH_CLIENT[0.1].ANDROID_PACKAGE=com.ald1n.mobile
OAUTH_CLIENT[0.1].CERTIFICATE_HASH=dae44641bf0953747e659445fd08b9e7d5af2529
OAUTH_CLIENT[0.2].TYPE=3
OAUTH_CLIENT[0.2].ID=270925336895-dpmgc8e7cek9uq814ffsqjmdfd4i8diq.apps.googleusercontent.com
ANDROID_CLIENT_PACKAGE[1]=com.ald1n.mobile.preview
OAUTH_CLIENT[1.0].TYPE=1
OAUTH_CLIENT[1.0].ID=270925336895-u5sagsbu1p0c8bi2hs1b5dbms54o7ptj.apps.googleusercontent.com
OAUTH_CLIENT[1.0].ANDROID_PACKAGE=com.ald1n.mobile.preview
OAUTH_CLIENT[1.0].CERTIFICATE_HASH=e04866612ecdd3354c6fc4c1de71df4ba1d556d7
OAUTH_CLIENT[1.1].TYPE=3
OAUTH_CLIENT[1.1].ID=270925336895-dpmgc8e7cek9uq814ffsqjmdfd4i8diq.apps.googleusercontent.com
GOOGLE_SERVICES_OAUTH_CLIENT_COUNT=5
GOOGLE_SERVICES_WEB_OAUTH_CLIENT_COUNT=2
GOOGLE_SERVICES_ANDROID_OAUTH_CLIENT_COUNT=3

============================================================
5. BACKEND GOOGLE AUTH RUNTIME - SANITIZED
============================================================
No syntax errors detected in /tmp/ald1n-play-login-audit.2igiQ5/backend-google-probe.php
BACKEND_GOOGLE_AUTH_ENABLED=YES
BACKEND_GOOGLE_WEB_CLIENT_ID_PRESENT=YES
BACKEND_GOOGLE_WEB_CLIENT_ID=270925336895-dpmgc8e7cek9uq814ffsqjmdfd4i8diq.apps.googleusercontent.com
BACKEND_GOOGLE_AUTH_ROUTE_COUNT=1
BACKEND_GOOGLE_AUTH_ROUTE[0]=POST api/v1/auth/google api.v1.auth.google
--- config/mobile.php Google auth lines ---
5:    'google_auth' => [
6:        'enabled' => (bool) env('MOBILE_GOOGLE_AUTH_ENABLED', false),
9:        'web_client_id' => env('GOOGLE_OAUTH_WEB_CLIENT_ID'),
10:        'certs_url' => env('GOOGLE_OAUTH_CERTS_URL', 'https://www.googleapis.com/oauth2/v1/certs'),
--- routes/api.php Google auth lines ---
22:use App\Http\Controllers\Api\V1\GoogleAuthController;
36:    Route::post('/auth/google', [GoogleAuthController::class, 'store'])

============================================================
6. GOOGLE VERIFIER + CONTROLLER CONTRACT
============================================================
--- GoogleIdentityVerifier relevant lines ---
9:use Illuminate\Validation\ValidationException;
11:use RuntimeException;
15:    private const CACHE_KEY = 'mobile-google-auth-certificates-v1';
22:        if (!(bool) config('mobile.google_auth.enabled', false)) {
23:            throw ValidationException::withMessages([
28:        $clientId = trim((string) config('mobile.google_auth.web_client_id', ''));
30:            throw new RuntimeException('GOOGLE_OAUTH_WEB_CLIENT_ID nije konfigurisan.');
33:        $parts = explode('.', trim($idToken));
43:        $kid = isset($header['kid']) ? trim((string) $header['kid']) : '';
48:        $certificate = $this->certificate($kid, false) ?? $this->certificate($kid, true);
49:        if ($certificate === null) {
53:        $verified = openssl_verify($headerPart.'.'.$payloadPart, $signature, $certificate, OPENSSL_ALGO_SHA256);
59:        $issuer = (string) ($claims['iss'] ?? '');
60:        $audience = $claims['aud'] ?? null;
61:        $audienceValid = is_string($audience)
62:            ? hash_equals($clientId, $audience)
63:            : (is_array($audience) && in_array($clientId, $audience, true));
65:        if (!in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true) || !$audienceValid) {
69:        if (is_array($audience) && count($audience) > 1 && !hash_equals($clientId, (string) ($claims['azp'] ?? ''))) {
73:        $expiresAt = (int) ($claims['exp'] ?? 0);
74:        $issuedAt = (int) ($claims['iat'] ?? 0);
75:        if ($expiresAt < ($now - 30) || $issuedAt <= 0 || $issuedAt > ($now + 120)) {
98:    private function certificate(string $kid, bool $refresh): ?string
104:        $certificates = Cache::remember(self::CACHE_KEY, now()->addHours(6), function (): array {
105:            $url = (string) config('mobile.google_auth.certs_url', 'https://www.googleapis.com/oauth2/v1/certs');
106:            $timeout = max(2, (int) config('mobile.google_auth.timeout_seconds', 8));
111:                throw new RuntimeException('Google certificates response nije validan JSON objekat.');
117:        $certificate = $certificates[$kid] ?? null;
118:        return is_string($certificate) && str_contains($certificate, 'BEGIN CERTIFICATE') ? $certificate : null;
166:        throw ValidationException::withMessages([
--- GoogleAuthController relevant lines ---
13:use Illuminate\Http\JsonResponse;
25:    ): JsonResponse {
26:        $identity = $verifier->verify((string) $request->validated('id_token'));
27:        $result = $googleAuth->resolve($identity);
40:        $token = $user->createToken(
89:            if (Schema::hasColumn('users', 'last_login_at')) {
90:                User::query()->whereKey($user->getKey())->update(['last_login_at' => now()]);
93:            Log::warning('Google API login succeeded, but last_login_at could not be updated.', [
BACKEND_VERIFIER_CHECKS_AUDIENCE=YES
BACKEND_CONTROLLER_USES_GOOGLE_VERIFIER=YES
BACKEND_CONTROLLER_USES_GOOGLE_AUTH_SERVICE=YES

============================================================
7. READ-ONLY QUALITY GATES
============================================================

> ald1n-mobile@0.6.0 typecheck
> tsc --noEmit

MOBILE_TYPECHECK=PASS
PASS package.json postoji.
PASS app.config.js postoji.
PASS eas.json postoji.
PASS .env.example postoji.
PASS assets/icon.png postoji.
PASS assets/adaptive-icon.png postoji.
PASS assets/splash-icon.png postoji.
PASS src/app/_layout.tsx postoji.
PASS src/app/(auth)/login.tsx postoji.
PASS src/app/(app)/(tabs)/home.tsx postoji.
PASS src/app/(app)/(tabs)/catalog.tsx postoji.
PASS src/app/(app)/(tabs)/orders.tsx postoji.
PASS src/app/(app)/(tabs)/notifications.tsx postoji.
PASS src/app/(app)/(tabs)/account.tsx postoji.
PASS src/app/(app)/product/[slug].tsx postoji.
PASS src/app/(app)/order/[id].tsx postoji.
PASS src/app/(app)/devices.tsx postoji.
PASS src/app/(app)/cart.tsx postoji.
PASS src/app/(app)/checkout.tsx postoji.
PASS src/app/(app)/notification-settings.tsx postoji.
PASS src/app/(app)/after-sales/index.tsx postoji.
PASS src/app/(app)/after-sales/[id].tsx postoji.
PASS src/app/(app)/after-sales/create/[orderId].tsx postoji.
PASS src/app/(app)/warranties/index.tsx postoji.
PASS src/app/(app)/warranties/[id].tsx postoji.
PASS src/app/(app)/commissions/index.tsx postoji.
PASS src/app/(app)/commissions/[id].tsx postoji.
PASS src/app/(app)/assigned-orders/index.tsx postoji.
PASS src/app/(app)/assigned-orders/[id].tsx postoji.
PASS src/features/warranties/warranty-pdf.ts postoji.
PASS src/features/orders/order-post-create-files.ts postoji.
PASS src/features/after-sales/attachment-picker.ts postoji.
PASS src/features/after-sales/attachment-download.ts postoji.
PASS src/lib/api/client.ts postoji.
PASS src/lib/api/endpoints.ts postoji.
PASS src/features/auth/auth-provider.tsx postoji.
PASS src/features/auth/google-auth.ts postoji.
PASS src/features/device/device-registrar.tsx postoji.
PASS src/features/cart/cart-provider.tsx postoji.
PASS src/features/notifications/push-service.ts postoji.
PASS src/features/notifications/push-notification-bridge.tsx postoji.
PASS docs/openapi.yaml postoji.
PASS tamagui.config.ts postoji.
PASS src/design/ald1n-tokens.generated.ts postoji.
PASS Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.
PASS Tamagui onBrand koristi canonical onPrimary semantic token.
PASS package.json je validan JSON.
PASS eas.json je validan JSON.
PASS Expo SDK 57 verzija prati zvanični template.
PASS React Native verzija prati Expo SDK 57 template.
PASS Expo Router verzija je zaključana.
PASS Expo development client je uključen.
PASS SecureStore zavisnost postoji.
PASS TanStack Query zavisnost postoji.
PASS Minimalna Node.js verzija odgovara SDK 57 zahtevu.
PASS Aplikaciona package verzija je 0.6.0.
PASS package-lock release verzija je 0.6.0.
PASS expo-notifications prati SDK 57 preporučenu verziju.
PASS Expo Symbols je uključen za native Material/SF ikonice.
PASS Moderni Google Credential Manager bridge je uključen.
PASS Nitro Modules runtime je pinovan.
PASS Tamagui 2 runtime je pinovan.
PASS Tamagui Config v5 paket je pinovan.
PASS Tamagui Reanimated driver je pinovan.
PASS Expo System UI prati SDK 57 preporucenu verziju.
PASS Expo Status Bar prati SDK 57 preporucenu verziju.
PASS Expo FileSystem je direktno zakljucan za after-sales izbor priloga.
PASS Expo Sharing je zakljucan za bezbedno otvaranje privatnih after-sales priloga.
PASS Static colors consumeri su uklonjeni iz aplikacionog source-a.
PASS Legacy colors.* usage ne postoji van RN theme adaptera.
PASS Unsafe as never / as unknown as castovi ne postoje u source-u.
PASS 92 TypeScript/TSX fajlova prolazi sintaksnu proveru.
PASS app.config.ts prolazi TypeScript sintaksnu proveru.
PASS 530 lokalnih @/ importa je razrešeno.
PASS Bearer token header je implementiran.
PASS Globalni 401 logout je implementiran.
PASS Request ID je sačuvan u API grešci.
PASS API timeout je implementiran.
PASS Secure auth lifecycle je implementiran.
PASS Neuspešan bootstrap posle logina vraća aplikaciju u bezbedno anonymous stanje.
PASS API klijent koristi auth/token ugovor.
PASS API klijent koristi auth/google ugovor.
PASS API klijent koristi bootstrap ugovor.
PASS API klijent koristi catalog/filters ugovor.
PASS API klijent koristi products ugovor.
PASS API klijent koristi orders/options ugovor.
PASS API klijent koristi Idempotency-Key ugovor.
PASS API klijent koristi orders ugovor.
PASS API klijent koristi notifications ugovor.
PASS API klijent koristi devices ugovor.
PASS API klijent koristi me/notification-preferences ugovor.
PASS API klijent koristi PATCH ugovor.
PASS Order API client exposes Assigned-to-me list/detail contract.
PASS Assigned Orders client reuses the canonical Order contract for list/detail.
PASS Assigned Orders customer/mobile contract adds discovery only and no workflow mutation methods.
PASS Order post-create API types cover summary, payment ledger and proof upload.
PASS Order API client covers post-create summary, proof upload and secure binary path contracts.
PASS Order post-create Mobile types do not expose internal actor IDs or storage paths.
PASS Order customer API client does not expose admin payment or delivery workflow actions.
PASS Order private-file paths are prepared for the existing authenticated apiDownload transport.
PASS Orders ekran otvara Assigned-to-me inbox samo korisniku sa orders.manage dozvolom.
PASS Assigned Orders lista koristi dedicated API, permission gate, detail rutu i server pagination.
PASS Assigned Order detalj koristi dedicated detail API i prikazuje canonical Order customer/assignment podatke.
PASS Assigned Orders UI ostaje read-only i ne izlaže owner post-create ili admin workflow mutacije/interne storage podatke.
PASS Order detalj prikazuje server-driven payment/document/delivery post-create summary.
PASS Order detalj šalje payment proof samo kada server capability to dozvoli i koristi server file limite.
PASS Order payment-proof picker koristi postojeći Expo FileSystem i server MIME/extension/size limite.
PASS Order privatni fajlovi koriste Bearer binary transport i provereni privatni cache.
PASS Order PDF/proof helper validira PDF i otvara privatne fajlove kroz postojeći Expo Sharing flow.
PASS Order private-file helper prihvata samo tipizovane customer API path buildere.
PASS Order detalj ne otvara privatne URL-ove direktno već koristi secure Bearer/cache/share helper.
PASS Post-create UI čuva postojeći customer cancel i After-sales create tok.
PASS Order customer post-create UI/helper ne izlažu admin akcije, actor ID-jeve ili storage putanje.
PASS API klijent sadrži after-sales ugovor.
PASS After-sales lista koristi API, dozvolu i detalj rutu.
PASS After-sales detalj prikazuje slučaj, radnje i javnu komunikaciju.
PASS After-sales detalj podržava slanje javne poruke samo kada je komunikacija otvorena.
PASS After-sales create ekran koristi server options, create endpoint, create dozvolu i izabrane stavke.
PASS After-sales attachment picker koristi Expo FileSystem i server limite bez novog picker paketa.
PASS API klijent podržava autentifikovan binary download uz postojeći Bearer lifecycle.
PASS After-sales privatni prilog se preuzima samo kroz očekivanu API putanju i čuva u provereni privatni cache.
PASS After-sales privatni prilog koristi Expo Sharing tek nakon provere platforme i dostupnosti sistema.
PASS After-sales detalj otvara privatne priloge kroz bezbedan Bearer download umesto direktnog privatnog URL-a.
PASS After-sales work-order tip izlaže javne field-work priloge.
PASS Secure attachment helper dozvoljava samo očekivanu field-work Bearer putanju i odvaja cache namespace.
PASS After-sales detalj prikazuje javnu terensku dokumentaciju i otvara je kroz postojeći secure flow.
PASS After-sales create ekran bira, prikazuje i šalje priloge prema server limitima.
PASS After-sales detail tip izlaže server-driven limite.
PASS After-sales message composer bira, prikazuje i šalje priloge prema server limitima.
PASS Order detalj otvara create-from-order ekran samo korisniku sa after_sales.create dozvolom.
PASS Orders ekran otvara after-sales listu samo korisniku sa view_own dozvolom.
PASS Warranty API tipovi pokrivaju listu, detalj i maintenance timeline.
PASS API klijent sadrži Warranty list/detail ugovor.
PASS Warranty lista koristi API, permission gate, detail rutu i maintenance summary.
PASS Warranty detalj prikazuje customer-safe garantni list, uslove, serijske brojeve, status i maintenance timeline.
PASS Warranty PDF se preuzima Bearer transportom, validira kao PDF i čuva u provereni privatni cache.
PASS Warranty PDF koristi postojeći Expo Sharing tek nakon platform/device provere.
PASS Warranty detalj otvara privatni PDF kroz bezbedan Bearer/cache/share flow bez direktnog URL-a.
PASS Orders ekran otvara Warranty listu samo korisniku sa warranties.view_own dozvolom.
PASS Commission API tipovi pokrivaju customer list/detail, statuse, summary i pagination ugovor.
PASS API klijent sadrži Commission list/filter/detail ugovor.
PASS Commission Mobile contract ne izlaže admin actor/history/payment-batch interne identifikatore.
PASS Commission lista koristi customer permission, q/status/date filtere, server summary, pagination i detail rutu.
PASS Commission detalj prikazuje customer-safe obračun, status, napomenu, isplatu i link ka porudžbini.
PASS Orders ekran otvara Commission listu samo korisniku sa commissions.view_own dozvolom.
PASS Commission customer UI ne izlaže admin/interne workflow identifikatore ili akcije.
PASS Lokalna korpa čuva proizvod, varijantu i količinu.
PASS Korpa se čisti pri odjavi/promeni korisnika.
PASS Checkout čuva stabilan idempotency ključ za retry istog payload-a.
PASS Checkout podržava uslovni izbor računa za bank transfer.
PASS Device heartbeat više ne gasi push registraciju pri svakom startu.
PASS Android kanal se kreira pre Expo push tokena.
PASS Expo push token koristi EAS projectId.
PASS Push token se registruje kao Expo device token.
PASS Push token se ne loguje u klijentu.
PASS Foreground i tap push listeneri su implementirani.
PASS Cold-start notification response se čisti nakon obrade.
PASS Push order deep link vodi na detalj porudžbine.
PASS Notification settings uređuju push i poslovne kategorije.
PASS Notification settings podržavaju per-device push uključivanje i isključivanje.
PASS Account ekran podrzava izmenu profila i lokalno osvezavanje bootstrap korisnika.
PASS Account ekran podrzava promenu lozinke i obaveznu ponovnu prijavu.
PASS Account ekran zahteva najmanje 12 znakova za novu lozinku.
PASS Account ekran proverava potvrdu nove lozinke.
PASS API klijent koristi PATCH /me za profil.
PASS API klijent koristi PUT /me/password za lozinku.
PASS Google Sign-In koristi web client ID iz google-services.json i vraća ID token backendu.
PASS Google login ima saved-account, registration/account-picker i explicit fallback tok.
PASS Google Sign-In dugme prati aktivnu light/dark temu.
PASS Bottom navigation ima Material 3 tonalni aktivni indikator.
PASS Tab badge koristi semantic danger/onDanger foreground par.
PASS UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.
PASS Canonical packages/api-contract/openapi.yaml postoji.
PASS Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS CMS OpenAPI kopija postoji.
PASS CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.
PASS OpenAPI documents Assigned-to-me list/detail routes.
PASS Assigned Orders OpenAPI documents permission denial and strict detail not-found behavior.
PASS Assigned Orders OpenAPI contains no workflow mutation operations.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/post-create:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/payments/{payment}/proof:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/confirmation.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/documents/{document}.pdf:.
PASS OpenAPI contains Order post-create route /api/v1/orders/{order}/delivery-proof:.
PASS OpenAPI contains OrderPrivateFile: schema.
PASS OpenAPI contains OrderPaymentLedgerEntry: schema.
PASS OpenAPI contains OrderDocumentSummary: schema.
PASS OpenAPI contains OrderDeliverySummary: schema.
PASS OpenAPI contains OrderBankTransferSnapshot: schema.
PASS OpenAPI contains OrderPostCreateCapabilities: schema.
PASS OpenAPI contains OrderPaymentProofLimits: schema.
PASS OpenAPI contains OrderPostCreate: schema.
PASS Order post-create OpenAPI covers proof upload, binary downloads and private no-store cache policy.
PASS Order post-create OpenAPI does not expose internal actor/storage fields or admin workflow actions.
PASS OpenAPI dokumentuje Commission list/filter/detail, summary i pagination ugovor.
PASS Commission OpenAPI customer ugovor ne izlaže admin/interne identifikatore.
PASS OpenAPI dokumentuje Warranty list/detail i maintenance schema ugovor.
PASS OpenAPI dokumentuje privatni Warranty PDF Bearer download ugovor.
PASS OpenAPI AfterSalesCase detalj izlaže server-driven limite za poruke i priloge.
PASS OpenAPI work-order schema izlaže javne field-work priloge.
PASS OpenAPI field-work attachment ruta dokumentuje Bearer download ugovor.
PASS OpenAPI kopija sadrži /auth/token.
PASS OpenAPI kopija sadrži /auth/google.
PASS OpenAPI kopija sadrži /bootstrap.
PASS OpenAPI kopija sadrži /catalog/filters.
PASS OpenAPI kopija sadrži /products.
PASS OpenAPI kopija sadrži /orders/options.
PASS OpenAPI kopija sadrži Idempotency-Key.
PASS OpenAPI kopija sadrži /orders.
PASS OpenAPI kopija sadrži /notifications.
PASS OpenAPI kopija sadrži /devices.
PASS Deep-link scheme je postavljen.
PASS Android/iOS identifikatori su postavljeni.
PASS Expo Router typed routes su uključene.
PASS Dinamički EAS project ID je podržan.
PASS Expo app verzija je 0.6.0.
PASS Expo display naziv je Ald1n CMS bez Preview suffixa.
PASS Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.
PASS App runtime version fallback je 0.6.0.
PASS Account version fallback je 0.6.0.
PASS Android config podržava Firebase google-services.json kada postoji.
PASS App config uključuje Google Sign-In plugin kada je Firebase config prisutan.
PASS Expo userInterfaceStyle prati sistemsku light/dark temu.
PASS App theme mode je zakljucan na system.
PASS App theme resolver koristi React Native system color scheme.
PASS RN theme adapter koristi canonical onDanger semantic token.
PASS TamaguiProvider je povezan na root aplikacije.
PASS Root Tamagui, StatusBar i navigation background prate isti resolved scheme.
PASS Tamagui Config v5 i Reanimated driver su aktivni.
PASS Ald1n Light/Dark Tamagui palette su povezane.
PASS Tamagui onDanger koristi canonical onDanger semantic token.
PASS Product detail omogućava kopiranje ručno unetog opisa na Android/iOS.
PASS Mobile ima SDK 57 expo-clipboard zavisnost za kopiranje opisa.
PASS Admin Product Create ekran koristi catalog.manage_products i canonical admin catalog API.
PASS Admin Product Create prikazuje server validation grešku i posle uspeha otvara novi artikal.
PASS SelectSheet primitive postoji bez dodatnog native dependency-ja.
PASS API klijent sadrži Admin Catalog options/create ugovor.
PASS Mobile tipovi pokrivaju Admin Product Create metadata/input/response.
PASS Home prikazuje Dodaj artikal samo korisniku sa catalog.manage_products dozvolom.
PASS OpenAPI dokumentuje Admin Catalog options i product create rute.
PASS Admin Product Create renderuje dinamičke specifikacije, zavisne select opcije i detaljna polja.
PASS Admin Product Create fotografije su permission-gated i šalju se kroz canonical image API.
PASS Product image picker koristi postojeći Expo FileSystem i server-driven limite bez novog native dependency-ja.
PASS API klijent podržava multipart upload slika posle kreiranja artikla.
PASS Mobile tipovi pokrivaju dinamičke specifikacije, image limite i storage contract za sledeći specijalizovani korak.
PASS OpenAPI dokumentuje napredne spec metadata podatke i multipart product-image upload.
PASS Admin Product Create ima specijalizovani multi-disk repeater i skriva izvedeni total iz standardnih polja.
PASS Storage repeater šalje canonical specs/spec_lists/spec_capacities/spec_structured payload bez ručnog derived total-a.
PASS Mobile tipovi izlažu server-driven storage repeater i read-only derived metadata.
PASS OpenAPI dokumentuje server-driven storage repeater metadata i derived total polje.
PASS P2 Admin hub koristi centralni access helper, API i query-key foundation.
PASS P2 Admin access helper centralizuje administratorske dozvole i admin/superadmin role fallback.
PASS P2 Admin API helper koristi canonical /api/v1/admin foundation endpoint.
PASS P2 Admin query-key family je centralizovana.
PASS Home prikazuje centralni Admin entry kroz isti access helper.
PASS OpenAPI dokumentuje P2 Admin foundation endpoint i schema ugovor.
PASS P2 FilterBar ima chips, active count i clear contract.
PASS P2 DateTimeField je dependency-free kontrolisani date/datetime input.
PASS P2 MoneyField centralizuje decimalni unos i currency prikaz.
PASS P2 AsyncLookup je server-query friendly lookup bez duplog cache-a.
PASS P2 DataList je mobile-first virtualizovana lista sa refresh i empty state contractom.
PASS P2 ActionSheet koristi dependency-free Modal i aktuelni RN absoluteFill API.
PASS P2 ConfirmAction reuse-uje ActionSheet i odvaja confirm/cancel tok.
PASS P2 StatusTimeline ima reusable server-driven timeline contract.
PASS P2 postojeći SelectSheet i AppFeedback ostaju očuvani.
PASS P3 Admin Commissions API klijent pokriva list/detail/status/bulk-pay ugovor.
PASS P3 Admin Commissions CSV/PDF koristi relativnu API putanju i postojeći Bearer binary/cache/share flow.
PASS P3 Admin Commissions lista ima permission gate, filtere, bulk-pay i izvoze.
PASS P3 Admin Commissions detalj koristi server-driven prelaze i shared timeline.
PASS P3 Admin hub izlaže Provizije samo commissions.manage korisniku.
PASS P3 Admin Commissions query keys su centralizovani.
PASS OpenAPI dokumentuje kompletan P3 Admin Commissions route surface.
PASS OpenAPI dokumentuje P3 Admin Commissions schema ugovor.
PASS P3 Admin Warranties API klijent pokriva list/detail/update/void/maintenance ugovor.
PASS P3 Admin Warranties lista ima permission gate, filtere, statistiku i detail rutu.
PASS P3 Admin Warranties detalj koristi server-side warranty i maintenance mutacije.
PASS P3 Admin hub izlaže Garancije samo warranties.manage korisniku.
PASS P3 Admin Warranties query keys su centralizovani.
PASS OpenAPI dokumentuje P3 Admin Warranties core route i schema ugovor.
PASS P3 Admin Warranties 2E zaključava rules/backfill i relativni Admin PDF API ugovor.
PASS P3 Admin Warranties 2E zaključava Rules UI i Backfill tok.
PASS P3 Admin Warranties 2E zaključava Rules navigaciju i Admin PDF UI entry.
PASS P3 Admin Warranties 2E zaključava secure relativni Admin PDF Bearer/cache/share flow.
PASS OpenAPI dokumentuje kompletan P3 Admin Warranties Rules/Backfill/Admin PDF ugovor.
PASS P3 Admin Reports 2G zaključava read/schedule Mobile API ugovor i relativne Admin putanje.
PASS P3 Admin Reports 2G zaključava secure CSV/PDF Bearer/cache/share export tok.
PASS P3 Admin Reports 2G zaključava management dashboard, permission gate i schedule manager UI.
PASS P3 Admin Reports 2G zaključava centralizovane Reports query-key ugovore.
PASS OpenAPI dokumentuje kompletan P3 Admin Reports read/export/schedule ugovor od 10 operacija.
PASS P3 Admin System Health 2C zaključava read-only Mobile API ugovor i relativnu admin/system-health putanju.
PASS P3 Admin System Health 2C zaključava centralizovani System Health query key.
PASS P3 Admin System Health 2C zaključava permission-gated read-only UI, refresh, checks, metrics i history tok.
PASS P3 Admin System Health 2C zaključava Admin hub ulaz samo za system.health.
PASS OpenAPI dokumentuje samo read-only P3 Admin System Health GET ugovor bez snapshot/backup/prune mutacija.
PASS P3 Admin Audit 2C zakljucava relativni read-only Mobile API ugovor bez raw user_agent/context_json polja.
PASS P3 Admin Audit 2C zakljucava centralizovane Audit list/detail query key ugovore.
PASS P3 Admin Audit 2C zakljucava security.view list/filter/pagination/refetch read-only UI.
PASS P3 Admin Audit 2C zakljucava permission-gated safe detail UI i server-driven read-only capabilities.
PASS P3 Admin Audit 2C zakljucava Admin hub ulaz samo za security.view.
PASS OpenAPI dokumentuje samo P3 Admin Audit read/filter list i safe detail ugovor bez export/mutation ruta.
PASS Product image upload koristi eksplicitni Expo fetch transport sa postojecim auth/error lifecycle-om.
PASS Product image multipart koristi pravi Expo File umesto legacy uri/name/type pseudo-fajla.
PASS Product image multipart ne postavlja rucno Content-Type boundary.
PASS Product image picker prihvata Android image provider fajl bez ekstenzije kada je MIME dozvoljen, uz zadrzan MIME/extension guard za ostale fajlove.
PASS iOS Google Sign-In koristi canonical GoogleService-Info.plist kroz Expo i Nitro config plugin.
PASS iOS GoogleService-Info.plist sadrži preview bundle, iOS OAuth, reversed scheme i web client ID za autoDetect.
PASS iOS koristi zaseban 1024x1024 opaque RGB app icon bez alpha/tRNS transparentnosti.

Ukupno FAIL: 0
MOBILE_PROJECT_VALIDATOR=PASS
PASS  postoji artisan
PASS  postoji composer.json
PASS  postoji composer.lock
PASS  postoji .env.example
PASS  postoji VERSION
PASS  postoji RELEASE-TAG
PASS  postoji UPGRADE-FROM
PASS  postoji docs/UPGRADE-V2.1-BETA1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA1.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA3.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA4.md
PASS  postoji docs/UPGRADE-V2.1-BETA5.md
PASS  postoji docs/UPGRADE-V2.1-BETA6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.3.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.4.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.5.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.6.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.7.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.8.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.9.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.10.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.11.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.12.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.13.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.14.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.15.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.16.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.17.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.20.md
PASS  postoji DATABASE-MIGRATION-REQUIRED.txt
PASS  postoji app/Models/Order.php
PASS  postoji app/Models/OrderItem.php
PASS  postoji app/Models/OrderDocument.php
PASS  postoji app/Models/OrderCommission.php
PASS  postoji app/Models/CommissionStatusHistory.php
PASS  postoji app/Models/CommissionPaymentBatch.php
PASS  postoji app/Models/OrderInternalNote.php
PASS  postoji app/Models/OrderAssignment.php
PASS  postoji app/Models/OrderStatusHistory.php
PASS  postoji app/Models/StockMovement.php
PASS  postoji app/Models/IdempotencyKey.php
PASS  postoji app/Models/OrderPayment.php
PASS  postoji app/Models/OrderDelivery.php
PASS  postoji app/Models/AfterSalesCase.php
PASS  postoji app/Models/AfterSalesCaseItem.php
PASS  postoji app/Models/AfterSalesMessage.php
PASS  postoji app/Models/AfterSalesAttachment.php
PASS  postoji app/Models/AfterSalesStatusHistory.php
PASS  postoji app/Models/AfterSalesAction.php
PASS  postoji app/Models/AfterSalesActionItem.php
PASS  postoji app/Models/FieldServiceTeam.php
PASS  postoji app/Models/FieldWorkOrder.php
PASS  postoji app/Models/FieldWorkOrderAttachment.php
PASS  postoji app/Models/ServicePartSupplier.php
PASS  postoji app/Models/ServicePart.php
PASS  postoji app/Models/FieldWorkOrderPart.php
PASS  postoji app/Models/ServicePartMovement.php
PASS  postoji app/Models/ServicePartPurchaseRequest.php
PASS  postoji app/Models/ServicePartPurchaseRequestItem.php
PASS  postoji app/Models/WarrantyRule.php
PASS  postoji app/Models/ProductWarranty.php
PASS  postoji app/Models/WarrantyMaintenanceRecord.php
PASS  postoji app/Models/OrderEmailOutbox.php
PASS  postoji app/Models/StockReceipt.php
PASS  postoji app/Models/StockReceiptItem.php
PASS  postoji app/Models/InventoryCount.php
PASS  postoji app/Models/InventoryCountItem.php
PASS  postoji app/Models/AutomationRun.php
PASS  postoji app/Models/OperationalAlert.php
PASS  postoji app/Models/NotificationPreference.php
PASS  postoji app/Models/BackupRun.php
PASS  postoji app/Models/SystemHealthSnapshot.php
PASS  postoji app/Models/SystemRuntimeState.php
PASS  postoji app/Models/SecurityEvent.php
PASS  postoji app/Services/OrderService.php
PASS  postoji app/Services/OrderWorkflowService.php
PASS  postoji app/Services/InventoryService.php
PASS  postoji app/Services/IdempotencyService.php
PASS  postoji app/Services/OrderPaymentService.php
PASS  postoji app/Services/IpsPaymentPayloadService.php
PASS  postoji app/Services/AdvancedInventoryService.php
PASS  postoji app/Services/LegacyReadOnlyGuard.php
PASS  postoji app/Services/OrderAccessService.php
PASS  postoji app/Services/OrderReportService.php
PASS  postoji app/Services/CommissionReportService.php
PASS  postoji app/Services/CommissionWorkflowService.php
PASS  postoji app/Services/OrderOperationalService.php
PASS  postoji app/Services/OrderTimelineService.php
PASS  postoji app/Services/OperationalNotificationService.php
PASS  postoji app/Notifications/OperationalNotification.php
PASS  postoji app/Services/OperationalAutomationService.php
PASS  postoji app/Services/AutomationReadinessService.php
PASS  postoji app/Services/BackupService.php
PASS  postoji app/Services/SystemHealthService.php
PASS  postoji app/Services/SecurityEventLogger.php
PASS  postoji app/Services/SensitiveDataSanitizer.php
PASS  postoji app/Services/OrderIndexService.php
PASS  postoji app/Services/OrderDetailService.php
PASS  postoji app/Services/OrderDetailPresenter.php
PASS  postoji app/Support/ViewValue.php
PASS  postoji app/Services/OrderDocumentService.php
PASS  postoji app/Services/AfterSalesAccessService.php
PASS  postoji app/Services/AfterSalesCaseService.php
PASS  postoji app/Services/AfterSalesActionService.php
PASS  postoji app/Services/FieldWorkOrderPlanner.php
PASS  postoji app/Services/FieldOperationsService.php
PASS  postoji app/Services/ServicePartsInventoryService.php
PASS  postoji app/Services/WarrantyService.php
PASS  postoji app/Services/OrderEmailOutboxService.php
PASS  postoji app/Services/OrderEmailDispatcher.php
PASS  postoji app/Services/NbsIpsQrService.php
PASS  postoji app/Services/DocumentNumberService.php
PASS  postoji app/Services/Pdf/SimplePdfWriter.php
PASS  postoji app/Services/Pdf/BusinessDocumentPdfService.php
PASS  postoji app/Services/Pdf/WarrantyCertificatePdfService.php
PASS  postoji app/Http/Requests/StoreOrderRequest.php
PASS  postoji app/Http/Requests/AdjustStockRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesMessageRequest.php
PASS  postoji app/Http/Requests/UpdateAfterSalesCaseRequest.php
PASS  postoji app/Http/Requests/StoreAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CompleteAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/CancelAfterSalesActionRequest.php
PASS  postoji app/Http/Requests/StoreFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/UpdateFieldServiceTeamRequest.php
PASS  postoji app/Http/Requests/ScheduleFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CompleteFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/CancelFieldWorkOrderRequest.php
PASS  postoji app/Http/Requests/StoreServicePartRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartRequest.php
PASS  postoji app/Http/Requests/AdjustServicePartStockRequest.php
PASS  postoji app/Http/Requests/StoreServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/UpdateServicePartSupplierRequest.php
PASS  postoji app/Http/Requests/StoreFieldWorkOrderPartRequest.php
PASS  postoji app/Http/Requests/StoreServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/CancelServicePartPurchaseRequest.php
PASS  postoji app/Http/Requests/StoreWarrantyRuleRequest.php
PASS  postoji app/Http/Requests/UpdateProductWarrantyRequest.php
PASS  postoji app/Http/Requests/ScheduleWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Requests/CompleteWarrantyMaintenanceRequest.php
PASS  postoji app/Http/Controllers/OrderController.php
PASS  postoji app/Http/Controllers/WarrantyController.php
PASS  postoji app/Http/Controllers/AfterSalesController.php
PASS  postoji app/Http/Controllers/AfterSalesAttachmentController.php
PASS  postoji app/Http/Controllers/FieldWorkOrderAttachmentController.php
PASS  postoji app/Http/Controllers/CommissionController.php
PASS  postoji app/Http/Controllers/NotificationController.php
PASS  postoji app/Http/Controllers/Api/V1/OrderController.php
PASS  postoji app/Http/Controllers/Admin/OrderController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesController.php
PASS  postoji app/Http/Controllers/Admin/AfterSalesActionController.php
PASS  postoji app/Http/Controllers/Admin/FieldOperationsController.php
PASS  postoji app/Http/Controllers/Admin/FieldServiceTeamController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartSupplierController.php
PASS  postoji app/Http/Controllers/Admin/FieldWorkOrderPartController.php
PASS  postoji app/Http/Controllers/Admin/ServicePartPurchaseRequestController.php
PASS  postoji app/Http/Controllers/Admin/WarrantyController.php
PASS  postoji app/Http/Controllers/Admin/CommissionController.php
PASS  postoji app/Http/Controllers/Admin/StockAdjustmentController.php
PASS  postoji app/Http/Controllers/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/OrderDocumentController.php
PASS  postoji app/Http/Controllers/Admin/ReportController.php
PASS  postoji app/Http/Controllers/Admin/DocumentSettingsController.php
PASS  postoji app/Http/Controllers/OrderPaymentController.php
PASS  postoji app/Http/Controllers/OrderDeliveryController.php
PASS  postoji app/Http/Controllers/Admin/PaymentController.php
PASS  postoji app/Http/Controllers/Admin/InventoryController.php
PASS  postoji app/Http/Controllers/Admin/AutomationController.php
PASS  postoji app/Http/Controllers/Admin/SystemHealthController.php
PASS  postoji app/Http/Controllers/Admin/TurnstileSettingsController.php
PASS  postoji app/Http/Controllers/Admin/OrderEmailSettingsController.php
PASS  postoji app/Http/Resources/OrderResource.php
PASS  postoji app/Console/Commands/OrdersDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCreateDoctorCommand.php
PASS  postoji app/Console/Commands/CatalogOwnershipDoctorCommand.php
PASS  postoji app/Console/Commands/DetailPagesDoctorCommand.php
PASS  postoji app/Console/Commands/ReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OperationsDoctorCommand.php
PASS  postoji app/Console/Commands/PaymentsInventoryDoctorCommand.php
PASS  postoji app/Console/Commands/RunOperationalAutomationCommand.php
PASS  postoji app/Console/Commands/AutomationDoctorCommand.php
PASS  postoji app/Console/Commands/CreateBackupCommand.php
PASS  postoji app/Console/Commands/BackupDoctorCommand.php
PASS  postoji app/Console/Commands/SystemHealthCommand.php
PASS  postoji app/Console/Commands/SchedulerHeartbeatCommand.php
PASS  postoji app/Console/Commands/TestDatabaseDoctorCommand.php
PASS  postoji app/Console/Commands/AfterSalesDoctorCommand.php
PASS  postoji app/Console/Commands/FieldOperationsDoctorCommand.php
PASS  postoji app/Console/Commands/ServicePartsDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesDoctorCommand.php
PASS  postoji app/Console/Commands/WarrantiesBackfillCommand.php
PASS  postoji app/Console/Commands/OrderEmailDispatchCommand.php
PASS  postoji app/Console/Commands/OrderEmailsDoctorCommand.php
PASS  postoji database/migrations/2026_07_22_000006_enable_production_orders_inventory.php
PASS  postoji database/migrations/2026_07_23_000007_repair_production_schema_beta5.php
PASS  postoji database/migrations/2026_07_23_000008_repair_authenticated_runtime_beta6.php
PASS  postoji database/migrations/2026_07_23_000009_create_reports_documents_and_supplier_assignment.php
PASS  postoji database/migrations/2026_07_23_000010_repair_reports_schema_beta1_2.php
PASS  postoji database/migrations/2026_07_23_000011_create_operational_orders_commissions_beta2.php
PASS  postoji database/migrations/2026_07_23_000012_create_payments_advanced_inventory_beta3.php
PASS  postoji database/migrations/2026_07_23_000013_create_automation_alerts_beta4.php
PASS  postoji database/migrations/2026_07_23_000014_create_security_backup_health_beta6.php
PASS  postoji database/migrations/2026_07_29_000015_repair_order_documents_and_payments_beta7_5.php
PASS  postoji database/migrations/2026_07_30_000016_add_order_completion_beta7_7.php
PASS  postoji database/migrations/2026_07_30_000017_add_delivery_workflow_beta7_8.php
PASS  postoji database/migrations/2026_07_30_000018_fix_delivery_note_document_type_beta7_9.php
PASS  postoji database/migrations/2026_07_30_000019_create_after_sales_cases_beta7_10.php
PASS  postoji database/migrations/2026_07_30_000020_create_after_sales_actions_beta7_11.php
PASS  postoji database/migrations/2026_07_30_000021_create_field_operations_beta7_12.php
PASS  postoji database/migrations/2026_07_30_000022_create_service_parts_procurement_beta7_13.php
PASS  postoji database/migrations/2026_07_30_000023_enable_document_revisions_beta7_14.php
PASS  postoji database/migrations/2026_07_30_000024_create_warranties_preventive_maintenance_beta7_15.php
PASS  postoji database/migrations/2026_07_30_000025_create_order_email_outbox_beta7_16.php
PASS  postoji resources/views/orders/index.blade.php
PASS  postoji resources/views/admin/orders/show.blade.php
PASS  postoji resources/views/commissions/index.blade.php
PASS  postoji resources/views/notifications/index.blade.php
PASS  postoji resources/views/admin/commissions/index.blade.php
PASS  postoji resources/views/orders/create.blade.php
PASS  postoji resources/views/orders/show.blade.php
PASS  postoji resources/views/admin/reports/index.blade.php
PASS  postoji resources/views/admin/settings/documents.blade.php
PASS  postoji resources/views/admin/inventory/index.blade.php
PASS  postoji resources/views/admin/orders/partials/payments.blade.php
PASS  postoji resources/views/orders/partials/payments.blade.php
PASS  postoji resources/views/after-sales/index.blade.php
PASS  postoji resources/views/after-sales/create.blade.php
PASS  postoji resources/views/after-sales/show.blade.php
PASS  postoji resources/views/admin/after-sales/index.blade.php
PASS  postoji resources/views/admin/after-sales/show.blade.php
PASS  postoji resources/views/admin/field-operations/index.blade.php
PASS  postoji resources/views/admin/field-operations/show.blade.php
PASS  postoji resources/views/admin/field-operations/teams.blade.php
PASS  postoji resources/views/admin/service-parts/index.blade.php
PASS  postoji resources/views/admin/service-parts/suppliers.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-requests.blade.php
PASS  postoji resources/views/admin/service-parts/purchase-show.blade.php
PASS  postoji resources/views/admin/settings/automation.blade.php
PASS  postoji resources/views/admin/settings/system-health.blade.php
PASS  postoji resources/views/admin/settings/turnstile.blade.php
PASS  postoji resources/views/admin/settings/order-emails.blade.php
PASS  postoji resources/views/emails/order-events.blade.php
PASS  postoji tests/Feature/AdminOrdersImageRotationTest.php
PASS  postoji tests/Feature/OperationalOrdersCommissionsTest.php
PASS  postoji tests/Feature/OperationalAutomationTest.php
PASS  postoji tests/Feature/PaymentsAdvancedInventoryTest.php
PASS  postoji tests/Feature/OrderDeliveryWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesWorkflowTest.php
PASS  postoji tests/Feature/AfterSalesActionExecutionTest.php
PASS  postoji tests/Feature/FieldOperationsWorkflowTest.php
PASS  postoji tests/Feature/ServicePartsWorkflowTest.php
PASS  postoji tests/Feature/InventoryWorkspaceUiTest.php
PASS  postoji tests/Feature/SecurityHealthBackupTest.php
PASS  postoji tests/Feature/MySqlTestDatabaseSafetyTest.php
PASS  postoji tests/Unit/SensitiveDataSanitizerTest.php
PASS  postoji tests/Feature/ProductionOrderInventoryTest.php
PASS  postoji tests/Feature/InventoryAdjustmentTest.php
PASS  postoji tests/Feature/ProductionPermissionsTest.php
PASS  postoji tests/Feature/DashboardLegacyDesignTest.php
PASS  postoji tests/Feature/ReportsDocumentsSupplierTest.php
PASS  postoji tests/Fixtures/pdf-logo.jpg
PASS  postoji tests/Unit/BusinessDocumentPdfServiceTest.php
PASS  postoji tests/Unit/DeliveryNoteMigrationContractTest.php
PASS  postoji tests/Unit/DocumentRevisionMigrationContractTest.php
PASS  postoji tests/Unit/CommissionReportPdfServiceTest.php
PASS  postoji tests/Feature/OrderEmailsIpsWarrantyTest.php
PASS  postoji tests/Unit/OrderEmailIpsMigrationContractTest.php
PASS  postoji tests/Unit/ReceivablesPermissionMigrationContractTest.php
PASS  postoji tests/Unit/LegacyReadOnlyGuardTest.php
PASS  postoji tests/Unit/OrderDetailPresenterTest.php
PASS  postoji tests/Unit/ViewValueTest.php
PASS  postoji tests/Feature/CatalogDetailPageTest.php
PASS  postoji tests/Feature/LoginDashboardFallbackTest.php
PASS  postoji resources/views/components/icon.blade.php
PASS  postoji app/Http/Middleware/EnsureRuntimeDirectories.php
PASS  postoji app/Http/Middleware/AttachRequestId.php
PASS  postoji app/Http/Middleware/SecurityHeaders.php
PASS  postoji app/Console/Commands/AuthDoctorCommand.php
PASS  postoji .env.testing.mysql.example
PASS  postoji phpunit.mysql.xml
PASS  postoji bin/php-lint.php
PASS  postoji bin/autoload-check.php
PASS  postoji bin/pdf-smoke.php
PASS  postoji bin/delivery-note-smoke.php
PASS  postoji bin/warranty-pdf-smoke.php
PASS  postoji bin/ips-qr-pdf-smoke.php
PASS  postoji storage/framework/cache/data/.gitignore
PASS  postoji storage/framework/sessions/.gitignore
PASS  postoji storage/framework/views/.gitignore
PASS  postoji storage/logs/.gitignore
PASS  postoji storage/app/backups/.gitignore
PASS  postoji config/backup.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.md
PASS  postoji docs/RELEASE-CHECK.md
PASS  postoji app/Console/Commands/ReleaseCheckCommand.php
PASS  postoji config/release.php
PASS  postoji bin/release-check-smoke.php
PASS  postoji tests/Unit/ReleaseCheckContractTest.php
PASS  postoji tests/Feature/ReleaseCheckCommandTest.php
PASS  postoji storage/app/release-check/.gitignore
PASS  postoji docs/UPGRADE-V2.1-BETA7.22.1.md
PASS  postoji bin/theme-css-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.21.md
PASS  postoji database/migrations/2026_07_31_000030_create_management_reports_beta7_21.php
PASS  postoji app/Models/ReportSchedule.php
PASS  postoji app/Models/ReportDelivery.php
PASS  postoji app/Services/ManagementReportService.php
PASS  postoji app/Services/ReportScheduleService.php
PASS  postoji app/Services/Pdf/ManagementReportPdfService.php
PASS  postoji app/Http/Controllers/Admin/ManagementReportController.php
PASS  postoji app/Http/Controllers/Admin/ReportScheduleController.php
PASS  postoji app/Console/Commands/ManagementReportsDoctorCommand.php
PASS  postoji app/Console/Commands/OrderCostSnapshotsCommand.php
PASS  postoji app/Services/OrderItemCostSnapshotService.php
PASS  postoji app/Console/Commands/ManagementReportsDispatchCommand.php
PASS  postoji resources/views/admin/reports/management.blade.php
PASS  postoji resources/views/emails/management-report.blade.php
PASS  postoji tests/Feature/ManagementReportsProfitabilityTest.php
PASS  postoji tests/Unit/OrderCostSnapshotRepairContractTest.php
PASS  postoji bin/management-report-smoke.php
PASS  postoji bin/order-cost-snapshot-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji database/migrations/2026_07_31_000028_create_smart_product_management_beta7_19.php
PASS  postoji app/Services/ProductTemplateService.php
PASS  postoji app/Services/ProductCompletenessService.php
PASS  postoji app/Services/ProductBulkService.php
PASS  postoji app/Http/Controllers/Admin/ProductBulkController.php
PASS  postoji app/Console/Commands/SmartProductsDoctorCommand.php
PASS  postoji resources/views/admin/products/clone.blade.php
PASS  postoji resources/views/admin/products/bulk.blade.php
PASS  postoji tests/Unit/SmartProductManagementMigrationContractTest.php
PASS  postoji tests/Unit/SmartProductManagementUiContractTest.php
PASS  postoji tests/Feature/SmartProductManagementTest.php
PASS  postoji bin/smart-product-smoke.php
PASS  postoji docs/UPGRADE-V2.1-BETA7.18.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.19.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.1.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.23.2.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.md
PASS  postoji docs/UPGRADE-V2.1-BETA7.24.1.md
PASS  postoji docs/UPGRADE-V2.1-RC1.md
PASS  postoji docs/RC-OPERATIONS.md
PASS  postoji docs/UPGRADE-V2.1-STABLE.md
PASS  postoji docs/UPGRADE-V2.1.1.md
PASS  postoji docs/UPGRADE-V2.1.2.md
PASS  postoji docs/UPGRADE-V2.1.3.md
PASS  postoji docs/UPGRADE-V2.1.3.1.md
PASS  postoji docs/UPGRADE-V2.1.3.2.md
PASS  postoji docs/UPGRADE-V2.1.3.3.md
PASS  postoji docs/STABLE-OPERATIONS.md
PASS  postoji docs/BACKUP-RESTORE-DRILL.md
PASS  postoji bin/rc-hardening-smoke.php
PASS  postoji bin/stable-hardening-smoke.php
PASS  postoji bin/stable-maintenance-smoke.php
PASS  postoji bin/product-media-ux-smoke.php
PASS  postoji bin/product-announcement-smoke.php
PASS  postoji bin/catalog-settings-product-data-smoke.php
PASS  postoji bin/catalog-settings-integrity-hotfix-smoke.php
PASS  postoji bin/product-save-regex-hotfix-smoke.php
PASS  postoji bin/storage-capacity-total-smoke.php
PASS  postoji tests/Unit/ProductSaveRegexHotfixContractTest.php
PASS  postoji tests/Unit/StorageCapacityTotalContractTest.php
PASS  postoji tests/Unit/CatalogSettingsProductDataContractTest.php
PASS  postoji tests/Unit/CatalogSettingsIntegrityHotfixContractTest.php
PASS  postoji app/Console/Commands/CatalogSettingsDoctorCommand.php
PASS  postoji app/Services/ProductTypeCategoryService.php
PASS  postoji app/Services/SpecificationFieldLifecycleService.php
PASS  postoji app/Services/StorageSpecificationService.php
PASS  postoji database/migrations/2026_08_04_000033_create_catalog_type_layout_v2_1_3.php
PASS  postoji database/migrations/2026_08_04_000034_repair_catalog_category_and_spec_integrity_v2_1_3_1.php
PASS  postoji database/migrations/2026_08_04_000035_link_storage_components_and_total_capacity_v2_1_3_3.php
PASS  postoji public/assets/js/dictionary-sort-manager.js
PASS  postoji resources/views/admin/dictionary/product-type.blade.php
PASS  postoji tests/Unit/ProductMediaUxContractTest.php
PASS  postoji tests/Unit/ProductAnnouncementContractTest.php
PASS  postoji app/Services/ProductAnnouncementService.php
PASS  postoji tests/Unit/ReleaseCandidateHardeningContractTest.php
PASS  postoji tests/Unit/StableReleaseContractTest.php
PASS  postoji tests/Unit/StableMaintenanceContractTest.php
PASS  postoji app/Console/Commands/SecurityHardeningDoctorCommand.php
PASS  postoji app/Console/Commands/MigrationsDoctorCommand.php
PASS  postoji app/Console/Commands/AccessControlDoctorCommand.php
PASS  postoji app/Console/Commands/ReleaseIntegrityCommand.php
PASS  postoji app/Console/Commands/BackupVerifyCommand.php
PASS  postoji app/Console/Commands/ProductMediaDoctorCommand.php
PASS  postoji app/Http/Controllers/ProductMediaDownloadController.php
PASS  postoji public/assets/js/product-media-manager.js
PASS  postoji resources/views/admin/products/partials/image-card.blade.php
PASS  postoji resources/views/admin/products/partials/image-upload.blade.php
PASS  postoji bin/catalog-detail-smoke.php
PASS  postoji bin/detail-pages-doctor-smoke.php
PASS  postoji tests/Unit/CatalogDetailBladeContractTest.php
PASS  postoji tests/Unit/SystemHealthRemediationContractTest.php
PASS  postoji tests/Unit/DetailPagesDoctorContractTest.php
PASS  postoji database/migrations/2026_07_31_000027_create_correlated_specifications_beta7_18.php
PASS  postoji app/Models/SpecificationOption.php
PASS  postoji app/Services/SpecificationDependencyService.php
PASS  postoji app/Services/CatalogSpecificationFilterService.php
PASS  postoji app/Console/Commands/CatalogCorrelationsDoctorCommand.php
PASS  postoji resources/views/partials/correlated-specification-filters.blade.php
PASS  postoji resources/views/partials/correlated-specification-filter-script.blade.php
PASS  postoji tests/Unit/CorrelatedSpecificationsMigrationContractTest.php
PASS  postoji tests/Unit/CorrelatedSpecificationUiContractTest.php
PASS  postoji docs/UPGRADE-V2.1.4.md
PASS  postoji bin/cms-v2.1.4-smoke.php
PASS  postoji tests/Unit/CmsV214ContractTest.php
PASS  postoji app/Console/Commands/CmsV214DoctorCommand.php
PASS  postoji app/Services/ProductDeletionService.php
PASS  postoji database/migrations/2026_08_04_000036_add_product_model_and_name_templates_v2_1_4.php
PASS  postoji docs/UPGRADE-V2.1.4.1.md
PASS  postoji bin/product-type-page-render-hotfix-smoke.php
PASS  postoji tests/Unit/ProductTypePageRenderHotfixContractTest.php
PASS  postoji docs/UPGRADE-V2.1.5.md
PASS  postoji bin/cms-v2.1.5-smoke.php
PASS  postoji tests/Unit/CmsV215ContractTest.php
PASS  postoji app/Console/Commands/CmsV215DoctorCommand.php
PASS  postoji public/assets/js/ux-runtime.js
PASS  postoji resources/views/errors/minimal.blade.php
PASS  postoji resources/views/errors/403.blade.php
PASS  postoji resources/views/errors/404.blade.php
PASS  postoji resources/views/errors/419.blade.php
PASS  postoji resources/views/errors/429.blade.php
PASS  postoji resources/views/errors/500.blade.php
PASS  postoji resources/views/errors/503.blade.php
PASS  postoji database/migrations/2026_08_05_000037_place_desktop_power_supply_field_v2_1_5.php
PASS  postoji docs/UPGRADE-V2.2.0.md
PASS  postoji docs/openapi.yaml
PASS  postoji bin/cms-v2.2.0-smoke.php
PASS  postoji tests/Feature/MobileApiFoundationTest.php
PASS  postoji tests/Unit/MobileApiFoundationContractTest.php
PASS  postoji app/Console/Commands/CmsV220DoctorCommand.php
PASS  postoji app/Http/Controllers/Api/V1/BootstrapController.php
PASS  postoji app/Http/Controllers/Api/V1/MobileDeviceController.php
PASS  postoji app/Models/MobileDevice.php
PASS  postoji database/migrations/2026_08_06_000039_create_mobile_devices_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000040_add_push_notification_preference_v2_2_0.php
PASS  postoji database/migrations/2026_08_06_000041_create_database_queue_tables_v2_2_0.php
PASS  verzija je 2.2.0 Mobile API Foundation
PASS  release tag je v2.2.0
PASS  upgrade osnova je v2.1.6
PASS  composer.json validan
PASS  PHP minimum 8.4
PASS  Laravel 13
PASS  Composer lint/autoload/test/release skripte postoje
PASS  runtime verzija je 2.2.0
PASS  migracija sadrži idempotency_keys
PASS  migracija sadrži source_system
PASS  migracija sadrži inventory_state
PASS  migracija sadrži inventory_returned_at
PASS  migracija sadrži event_key
PASS  migracija sadrži orders_user_idempotency_unique
PASS  porudžbina zaključava proizvode
PASS  porudžbina umanjuje lager u transakciji
PASS  povrat lagera ima jedinstveni event key
PASS  idempotency koristi unique zapis i row lock
PASS  legacy porudžbine su blokirane
PASS  legacy SQL guard je registrovan pre izvršavanja
PASS  legacy MySQL sesija je READ ONLY
PASS  Redis je uklonjen iz database konfiguracije
PASS  Redis je uklonjen iz cache konfiguracije
PASS  Redis je uklonjen iz queue konfiguracije
PASS  login rate limiter koristi file store
PASS  dozvola orders.create
PASS  dozvola orders.view_own
PASS  dozvola orders.cancel_own
PASS  dozvola orders.manage
PASS  dozvola stock.view
PASS  dozvola stock.adjust
PASS  dozvola reports.view
PASS  dozvola reports.export
PASS  dozvola invoices.manage
PASS  dozvola invoices.view_own
PASS  web ruta orders.store
PASS  web ruta orders.cancel
PASS  web ruta admin.orders.status
PASS  web ruta admin.orders.payment
PASS  web ruta admin.orders.tracking
PASS  web ruta admin.stock.adjust
PASS  API porudžbine postoje
PASS  porudžbina ima dodeljenog SuperAdmin/Admin dobavljača
PASS  admin scope vidi samo njemu dodeljene porudžbine
PASS  izveštaji podržavaju filtere i CSV/PDF
PASS  poslovni dokumenti koriste nepromenljivi snapshot
PASS  PDF renderer je lokalni i bez Redis/eksternog servisa
PASS  brojevi dokumenata su transakcioni i jedinstveni
PASS  web rute imaju reports CSV/PDF i dokumente
PASS  reports stranica ima schema fallback umesto 500
PASS  reports render je unutar zaštićenog controller toka
PASS  reports view ima render marker i bezbedne URL-ove
PASS  reports export vraća kontrolisani 503
PASS  reports doctor izvršava repair i stvarne SQL upite
PASS  reports doctor renderuje controller Blade i layout
PASS  reports logging je best-effort
PASS  beta1.2 repair migracija je nedestruktivna
PASS  hamburger dugme postoji
PASS  mobilni meni ima kontrolni JavaScript
PASS  mobilni meni nema horizontalni scroll
PASS  direktne mobilne stavke koriste zajednički levi wrapper
PASS  Početna Provizije i Izveštaji su poravnati ulevo
PASS  CSS ima pouzdan cache busting
PASS  legacy desktop header ima dva reda
PASS  legacy mobilni header zadržava kurs temu nalog i hamburger
PASS  dashboard ima moderni hero KPI prioritete i module
PASS  dashboard CSS ima 4 desktop i 2 mobilne kolone
PASS  admin gridovi su poravnati na vrh
PASS  forme koriste sadržajnu visinu
PASS  deployment check ima bezbedan repair režim
PASS  deployment check razlikuje runtime zaštitu i grant warning
PASS  deployment check proverava i operativne kolone
PASS  dashboard koristi DB fallback umesto 500
PASS  login telemetry je best-effort
PASS  login hvata session i remember-token probleme
PASS  authenticated layout nema direktan SettingsService upit
PASS  authenticated layout koristi bezbedne user helper metode
PASS  dashboard logging ne može da obori fallback
PASS  runtime middleware prethodi session/cache middleware-u
PASS  deployment repair kreira runtime direktorijume i kompajlira Blade
PASS  auth doctor može da renderuje kompletan dashboard
PASS  Turnstile hvata sve transportne/JSON greške
PASS  Turnstile podešavanja imaju DB prioritet i env fallback
PASS  Turnstile secret se čuva šifrovano i ne izlaže kroz all
PASS  Turnstile admin ekran i ruta postoje
PASS  beta6 repair migracija popravlja core login šemu
PASS  static check razdvaja runtime i ZIP režim
PASS  operativna migracija sadrži order_internal_notes
PASS  operativna migracija sadrži order_assignments
PASS  operativna migracija sadrži commission_payment_batches
PASS  operativna migracija sadrži notifications
PASS  operativna migracija sadrži payment_batch_id
PASS  operativna migracija sadrži status_updated_at
PASS  operativna migracija sadrži last_internal_note_at
PASS  beta2 dozvola commissions.view_own
PASS  beta2 dozvola orders.reassign
PASS  beta2 dozvola orders.internal_notes
PASS  beta2 dozvola notifications.view
PASS  provizije imaju odobravanje isplatu storniranje i istoriju
PASS  masovna isplata koristi transakciju row lock i batch
PASS  korisnik vidi samo svoje provizije i minimum 20 EUR
PASS  interne napomene nisu u javnom timeline-u
PASS  ponovna dodela je ograničena na SuperAdministratora
PASS  preuzimanje i rokovi porudžbine imaju audit i obaveštenja
PASS  database notifikacije su neblokirajuće i mail je opcioni
PASS  operativni doctor proverava šemu SQL i render
PASS  admin provizije imaju filtere CSV PDF i masovnu isplatu
PASS  commission tabela nema unutrašnji vertikalni scroll pri obradi
PASS  obrada provizije koristi veliki viewport modal
PASS  commission modal ima naslov i eksplicitno zatvaranje
PASS  otvaranje commission modala zatvara prethodni
PASS  porudžbina ima timeline interne napomene preuzimanje rokove i reassignment UI
PASS  inbox obaveštenja podržava read i read-all
PASS  operativni feature testovi postoje
PASS  commission modal regresioni feature test postoji
PASS  beta3.1 migracija nema globalni use Throwable
PASS  beta3.1 migracija koristi potpuno kvalifikovani Throwable
PASS  PHP lint odbija warning deprecated i notice izlaz
PASS  beta3 migracija sadrži order_payments
PASS  beta3 migracija sadrži stock_receipts
PASS  beta3 migracija sadrži stock_receipt_items
PASS  beta3 migracija sadrži inventory_counts
PASS  beta3 migracija sadrži inventory_count_items
PASS  beta3 migracija sadrži payment_state
PASS  beta3 migracija sadrži paid_total_rsd
PASS  beta3 migracija sadrži payment_due_at
PASS  beta3 dozvola payments.manage
PASS  beta3 dozvola payments.upload_proof
PASS  beta3 dozvola payments.view_own
PASS  beta3 dozvola inventory.receive
PASS  beta3 dozvola inventory.count
PASS  beta3 dozvola inventory.export
PASS  uplate koriste transakciju row lock audit i saldo
PASS  potvrde uplate su privatne i autorizovane
PASS  IPS podaci koriste snapshot porudžbine
PASS  predračun i račun postavljaju dospeće porudžbine
PASS  ulaz robe i popis koriste idempotency transakciju i row lock
PASS  napredni lager ima readiness fallback umesto 500
PASS  reports beta3 sažeci i izvozi su zaštićeni
PASS  beta3 doctor proverava repair SQL i render
PASS  beta3 feature testovi pokrivaju uplate ulaz i popis
PASS  beta7.5 repair migracija obnavlja PDF i payment šemu
PASS  beta7.5 repair migracija je nedestruktivna
PASS  beta7.5 potvrda koristi site name fallback
PASS  beta7.5 ručno evidentiranje uplate ima regresioni test
PASS  beta7.5 doctor proverava dokument i payment tabele
PASS  beta7.6 PDF dozvoljava lokalno uvezene porudžbine
PASS  beta7.6 uplate dozvoljavaju lokalno uvezene porudžbine
PASS  beta7.6 legacy lager zaštita ostaje aktivna
PASS  beta7.7 migracija dodaje terminalno stanje porudžbine
PASS  beta7.7 PDF podešavanja imaju upload pregled i uklanjanje logotipa
PASS  beta7.7 PDF logo se ugrađuje kao lokalni JPEG
PASS  beta7.7 PDF ne prikazuje subagent email kupca
PASS  beta7.7 kompletiranje COD porudžbine evidentira preostali saldo
PASS  beta7.7 kompletirana porudžbina zaključava dalje izmene
PASS  beta7.7 kompletiranje je jasno dostupno u detalju i listi
PASS  beta7.8 migracija dodaje evidenciju isporuke i reopening stanje
PASS  beta7.8 kompletiranje čuva dokaz isporuke privatno
PASS  beta7.8 otpremnica koristi OTP broj i delivery snapshot
PASS  beta7.8 ponovno otvaranje je superadmin-only i auditovano
PASS  beta7.8 detalj prikazuje strukturiranu evidenciju isporuke
PASS  beta7.8 doctor proverava novu šemu i dozvole
PASS  beta7.8 feature testovi pokrivaju dokaz otpremnicu i reopening
PASS  beta7.8 UI ima delivery workflow responsive stilove
PASS  beta7.9 migracija uklanja legacy ENUM blokadu za delivery_note
PASS  beta7.9 servis radi schema preflight pre izdavanja otpremnice
PASS  beta7.9 pomoćni notification kvar ne obara izdat dokument, a IPS važi samo za finansijske dokumente
PASS  beta7.9 kontroleri vraćaju incident poruku umesto Error 500
PASS  beta7.9 doctor proverava stvarni MySQL tip dokumenta
PASS  beta7.9 ima migration contract i delivery note PDF smoke test
PASS  detail koristi eksplicitan slug upit
PASS  slug upit primenjuje objedinjeni visibility scope
PASS  API detail koristi isti slug upit
PASS  neispravna slika ne obara detail
PASS  detail filtrira slike bez validnog URL-a
PASS  katalog generiše eksplicitan slug link
PASS  detail ima interaktivnu thumbnail galeriju
PASS  detail ima fullscreen lightbox i zoom kontrole
PASS  gallery podržava tastaturu swipe i preload
PASS  gallery radi i sa jednom slikom
PASS  gallery CSS ima fullscreen viewport i responsive mobile
PASS  gallery feature testovi postoje
PASS  beta4 migracija sadrži automation_runs
PASS  beta4 migracija sadrži operational_alerts
PASS  beta4 migracija sadrži notification_preferences
PASS  beta4 nema Redis i koristi scheduler/file lock
PASS  beta4 detektuje nepreuzete porudžbine dospele obaveze i nizak lager
PASS  beta4 upozorenja su deduplikovana i razrešavaju se
PASS  notification preferences upravljaju kanalima i kategorijama
PASS  automation settings UI i ručno pokretanje postoje
PASS  automation doctor proverava repair scheduler i run
PASS  beta4 dozvola automation.manage postoji
PASS  beta4 feature testovi pokrivaju deduplikaciju i preference
PASS  beta5 inventory koristi jednu aktivnu operaciju
PASS  beta5 inventory čuva filter i limit nakon knjiženja
PASS  beta5 inventory nema unutrašnji vertikalni scrollbar
PASS  beta5 inventory responsive tabela koristi data-label kartice
PASS  beta5 feature test pokriva inventory workspace
PASS  beta6 migracija sadrži backup_runs
PASS  beta6 migracija sadrži system_health_snapshots
PASS  beta6 migracija sadrži system_runtime_states
PASS  beta6 migracija sadrži security_events
PASS  beta6 migracija sadrži system.health
PASS  beta6 migracija sadrži backups.manage
PASS  beta6 migracija sadrži audit.export
PASS  beta6 migracija sadrži security.view
PASS  beta6 backup koristi mysqldump bez lozinke u argumentima
PASS  beta6 backup odbija public putanju i pravi SHA-256 manifest
PASS  beta6 system health proverava scheduler backup migracije i legacy
PASS  beta6 security header-i i request ID postoje
PASS  beta6 audit koristi rekurzivnu sanitizaciju i request ID
PASS  beta6 rate limiter-i pokrivaju upload export admin i backup
PASS  beta6 test DB doctor ima višestruku zaštitu
PASS  beta6 system health UI i backup akcije postoje
PASS  beta6 scheduler ima heartbeat backup i health snapshot
PASS  beta6 feature i unit testovi postoje
PASS  beta7.1 orders ima readiness SQL i render zaštitu
PASS  beta7.1 orders doctor proverava isti browser render
PASS  beta7.1 orders recovery ne završava generičkim 500
PASS  beta7.1 edit artikla ima rotaciju ulevo i udesno
PASS  beta7.1 legacy rotacija koristi copy-on-write
PASS  beta7.1 rotacija koristi privremeni fajl i kontrolisani Imagick/GD fallback
PASS  beta7.1 feature testovi postoje
PASS  beta7.2 order detail koristi opcioni schema-aware loader
PASS  beta7.2 admin i user detail imaju protected render
PASS  beta7.2 admin i user detail imaju readiness markere
PASS  beta7.2 timeline i IPS ne mogu oboriti detalj
PASS  beta7.2 orders doctor renderuje oba detalja
PASS  beta7.2 detail-pages doctor proverava ključne detail stranice
PASS  beta7.2 feature testovi pokrivaju detail i opcione tabele
PASS  beta7.3 detail koristi scalar presenter umesto Eloquent objekata u Blade-u
PASS  beta7.3 presenter bezbedno obrađuje raw i zero datume
PASS  beta7.3 presenter bezbedno generiše named rute
PASS  beta7.3 detail view nema direktne auth, relation ili datetime pozive
PASS  beta7.3 admin i user detail imaju ne-503 read-only fallback
PASS  beta7.3 orders doctor prikazuje tačan exception uzrok za oba detaila
PASS  beta7.3 orders doctor nastavlja admin i user audit
PASS  beta7.3 payment i inventory Gates su definisani
PASS  beta7.3 presenter i ViewValue regresioni testovi postoje
PASS  beta7.10 migracija sadrži after_sales_cases
PASS  beta7.10 migracija sadrži after_sales_case_items
PASS  beta7.10 migracija sadrži after_sales_messages
PASS  beta7.10 migracija sadrži after_sales_attachments
PASS  beta7.10 migracija sadrži after_sales_status_history
PASS  beta7.10 ima tri postprodajne dozvole
PASS  beta7.10 pristup poštuje vlasnika dodeljenog admina i superadmin scope
PASS  beta7.10 slučaj zahteva isporučenu ili kompletiranu porudžbinu
PASS  beta7.10 čuva pogođene stavke snapshot i SLA rok
PASS  beta7.10 privatni prilozi proveravaju MIME veličinu i autorizaciju
PASS  beta7.10 javne i interne poruke su odvojene
PASS  beta7.10 statusni tok zahteva obrazloženje konačne odluke
PASS  beta7.10 automatizacija upozorava na probijene rokove slučaja
PASS  beta7.10 UI ima korisnički i administratorski postprodajni tok
PASS  beta7.10 doctor proverava šemu dozvole i SQL
PASS  beta7.10 feature test pokriva privatni prilog i obradu
PASS  beta7.10 privatni download zabranjuje browser cache
PASS  beta7.10 konkurentno zatvaranje ne propušta novu poruku
PASS  beta7.10 reopening zahteva razlog i čuva vreme prethodnog rešenja
PASS  beta7.10 nedodeljeni slučajevi obaveštavaju superadministratore
PASS  beta7.10 dashboard prikazuje aktivne probijene i waiting slučajeve
PASS  beta7.11 migracija sadrži after_sales_actions
PASS  beta7.11 migracija sadrži after_sales_action_items
PASS  beta7.11 migracija sadrži after_sales_action_id
PASS  beta7.11 migracija sadrži after_sales.execute
PASS  beta7.11 podržava četiri izvršne radnje
PASS  beta7.11 lager efekti su zaključani i idempotentni
PASS  beta7.11 refundacija je vezana za radnju i ograničena neto uplatom
PASS  beta7.11 slučaj čeka završetak aktivnih radnji
PASS  beta7.11 UI ima planiranje pokretanje izvršenje i otkazivanje
PASS  beta7.11 Gate i permission middleware štite izvršne kontrole
PASS  beta7.11 controller ima sve izvršne endpoint-e
PASS  beta7.11 automatizacija prati rok izvršne radnje
PASS  beta7.11 dashboard prikazuje radnje za izvršenje
PASS  beta7.11 testovi pokrivaju idempotentni lager povrat i refundaciju
PASS  beta7.12 migracija sadrži field_service_teams
PASS  beta7.12 migracija sadrži field_work_orders
PASS  beta7.12 migracija sadrži field_work_order_attachments
PASS  beta7.12 migracija sadrži field_operations.view
PASS  beta7.12 migracija sadrži field_operations.manage
PASS  beta7.12 fizičke radnje automatski dobijaju radni nalog
PASS  beta7.12 sprečava preklapanje termina iste ekipe
PASS  beta7.12 završetak zahteva dolazak na lokaciju
PASS  beta7.12 radni nalog čuva troškove kilometražu i privatne dokaze
PASS  beta7.12 UI ima kalendar ekipe i operativne statuse
PASS  beta7.12 rute i Gate štite terenske operacije
PASS  beta7.12 automatizacija prati neplanirane i probijene radne naloge
PASS  beta7.12 doctor proverava tabele dozvole rute i SQL
PASS  beta7.12 test pokriva auto nalog konflikt i on-site završetak
PASS  beta7.13 migracija sadrži service_part_suppliers
PASS  beta7.13 migracija sadrži service_parts
PASS  beta7.13 migracija sadrži field_work_order_parts
PASS  beta7.13 migracija sadrži service_part_movements
PASS  beta7.13 migracija sadrži service_part_purchase_requests
PASS  beta7.13 migracija sadrži service_part_purchase_request_items
PASS  beta7.13 migracija sadrži service_parts.view
PASS  beta7.13 migracija sadrži service_parts.manage
PASS  beta7.13 migracija sadrži service_parts.procurement
PASS  beta7.13 početno stanje ulazi u movement ledger
PASS  beta7.13 rezervacija ne umanjuje fizičko stanje
PASS  beta7.13 završetak skida stvarni utrošak i oslobađa ostatak
PASS  beta7.13 otkazivanje oslobađa sve rezervacije
PASS  beta7.13 kretanja servisnog lagera su idempotentna i ponovo proverena pod lockom
PASS  beta7.13 nacrt nabavke koristi konkurentno bezbedan privremeni broj
PASS  beta7.13 prijem nabavke računa ponderisanu prosečnu cenu
PASS  beta7.13 UI ima servisni lager dobavljače nabavku i utrošak
PASS  beta7.13 Gate i rute štite lager i nabavku
PASS  beta7.13 automatizacija prati nizak lager i kašnjenje nabavke
PASS  beta7.13 doctor proverava tabele dozvole rute i SQL
PASS  beta7.13 testovi pokrivaju ledger rezervaciju utrošak i ponderisanu cenu
PASS  beta7.13 UI ima responsive stilove servisnog lagera
PASS  beta7.14.1 migracija prvo obezbeđuje FK indeks
PASS  beta7.14 migracija uklanja unique order/type ograničenje
PASS  beta7.14 migracija uvodi revizije i vezu sa prethodnim dokumentom
PASS  beta7.14 servis vraća samo aktivan dokument ili izdaje novu reviziju
PASS  beta7.14 storniranje zahteva razlog i čuva audit podatak
PASS  beta7.14 model podržava supersedes relaciju
PASS  beta7.14 UI razlikuje aktivan dokument i novu reviziju
PASS  beta7.14 PDF prikazuje broj revizije
PASS  beta7.14 regresioni test pokriva ponovno izdavanje
PASS  beta7.15 migracija sadrži warranty_rules
PASS  beta7.15 migracija sadrži product_warranties
PASS  beta7.15 migracija sadrži warranty_maintenance_records
PASS  beta7.15 migracija sadrži warranties.view_own
PASS  beta7.15 migracija sadrži warranties.manage
PASS  beta7.15 pravila imaju product category global prioritet
PASS  beta7.15 kompletiranje automatski izdaje garanciju bez obaranja porudžbine
PASS  beta7.15 otkazivanje poništava aktivne garancije
PASS  beta7.15 GAR poslovni broj je registrovan
PASS  beta7.15 garancija čuva snapshot kupca artikla uslova i serijskih brojeva
PASS  beta7.15 preventivno održavanje generiše sledeći termin
PASS  beta7.15 zakazivanje ne menja vreme tokom provere datuma
PASS  beta7.15 backfill bira samo stavke bez garancije
PASS  beta7.15 PDF garantni list prikazuje ključne snapshot podatke
PASS  beta7.15 korisnički i administratorski prikazi postoje
PASS  beta7.15 rute Gates i administratorski scope štite garancije
PASS  beta7.15 automatizacija prati istek i održavanje
PASS  beta7.15 dashboard prikazuje garancije
PASS  beta7.15 doctor i backfill komande postoje
PASS  beta7.15 feature test pokriva automatsko izdavanje i prioritet pravila
PASS  beta7.15 warranty PDF smoke postoji
PASS  beta7.16 migracija uvodi outbox QR snapshot i dane garancije
PASS  beta7.16 migracija ima recovery putanju za delimičan MariaDB DDL
PASS  beta7.16 e-mail outbox ima dedupe intervale i pojedinačne primaoce
PASS  beta7.16 e-mail prima autor odgovorno lice i dodatne adrese
PASS  beta7.16 workflow šalje status tracking plaćanje i dokumente
PASS  beta7.16 dispatcher ima retry stuck recovery i zaštitu storniranog priloga
PASS  beta7.16 scheduler šalje outbox svake minute
PASS  beta7.16 admin podešava intervale događaje i dokumente
PASS  beta7.16 e-mail šablon ima događaje i bezbedan action link
PASS  beta7.16 NBS payload koristi zvanične oznake i RSD zarez
PASS  beta7.16 NBS servis koristi zvanični HTTPS endpoint i čuva privatni PNG snapshot
PASS  beta7.16 stornirani istorijski dokument ostaje pregledljiv bez ponovnog NBS poziva
PASS  beta7.16 finansijski dokument bez validnog NBS QR se ne izdaje
PASS  beta7.16 PDF crta PNG bez GD i prikazuje NBS IPS QR oznaku
PASS  beta7.16 IPS QR smoke potvrđuje sliku oznaku i tačan RSD iznos
PASS  beta7.16 garancija podržava kombinaciju meseci i dana
PASS  beta7.16 admin može kreirati porudžbinu
PASS  beta7.16 doctor proverava outbox SMTP scheduler NBS i garancijske dane
PASS  beta7.16 feature test pokriva admin porudžbinu događaje NBS QR i dane garancije
PASS  beta7.17 migracija uvodi predmete rate i komunikaciju naplate
PASS  beta7.17 migracija je recovery-safe za delimičan DDL
PASS  beta7.17 servis automatski otvara zatvara i usklađuje predmete
PASS  beta7.17 rate se raspoređuju prema stvarno plaćenom iznosu
PASS  beta7.17 automatske opomene koriste faze dedupe i outbox
PASS  beta7.17 admin ima aging pregled plan i evidenciju komunikacije
PASS  beta7.17 podmeni se zatvara klikom van escape i izborom stavke
PASS  beta7.17 checkbox i radio imaju normalnu globalnu veličinu
PASS  beta7.17 doctor proverava šemu dozvolu i scheduler
PASS  beta7.17.1 permission seed je schema-aware
PASS  beta7.17.1 seeder ne zahteva permissions.updated_at
PASS  beta7.17.2 hover podmeni ima grace period i click pin
PASS  beta7.17.2 CSS premošćava razmak do podmenija
PASS  beta7.18 migracija uvodi strukturirane opcije i korelacije
PASS  beta7.18 migracija je recovery-safe za MariaDB
PASS  beta7.18 procesor ima porodicu i tačan model
PASS  beta7.18 brend filtrira samo sopstvene linije
PASS  beta7.18 generičke zavisnosti imaju server validaciju i zaštitu ciklusa
PASS  beta7.18 forma skriva nepovezane opcije i čuva detalj
PASS  beta7.18 kataloški filteri podržavaju select range boolean text i detalj
PASS  beta7.18 oba kataloga koriste korelisane filtere
PASS  beta7.18 doctor proverava procesor linije veze i tipove
PASS  beta7.18.1 forma artikla ne koristi nedostupni index filter servis
PASS  beta7.18.1 jedinstveni katalog dobija podatke za korelisane filtere
PASS  beta7.18.1 šifarnici dobijaju podatke za roditelje i mape zavisnosti
PASS  beta7.19 migracija uvodi šablone kompletnost i poreklo klona
PASS  beta7.19 migracija je recovery-safe i obračunava postojeći katalog
PASS  beta7.19 template servis podržava alias placeholdere
PASS  beta7.19 completeness servis vraća nepotpun aktivan artikal u nacrt
PASS  beta7.19 kloniranje čuva novi SKU i nulti lager
PASS  beta7.19 clone checkboxi eksplicitno šalju nulu
PASS  beta7.19 bulk zahteva pregled i blokira praznu operaciju
PASS  beta7.19 bulk promena brenda čisti neusklađenu liniju
PASS  beta7.19 preview naziva uklanja method spoof
PASS  beta7.19 doctor proverava šemu rute i kompletnost
PASS  beta7.19 feature test pokriva naziv klon i bulk
PASS  beta7.20 migracija uvodi varijante specifikacije slike i snapshot
PASS  beta7.20 migracija je recovery-safe za MariaDB i proširuje istorijske module
PASS  beta7.20 varijanta ima SKU cenu lager status default i garanciju
PASS  beta7.20 default preferira aktivnu varijantu i roditelj sabira aktivan lager
PASS  beta7.20 serverska validacija štiti SKU i korelisane specifikacije
PASS  beta7.20 admin ima CRUD lager slike i default varijantu
PASS  beta7.20 UI filtrira zavisne specifikacije varijante
PASS  beta7.20 porudžbina čuva variant snapshot i vraća isti lager
PASS  beta7.20 postprodaja garancija i stock movement nose variant id
PASS  beta7.20 clone kopira varijante bez lagera i sa novim SKU
PASS  beta7.20 parent inventory korekcija je blokirana
PASS  beta7.20 filter i pretraga vide aktivne varijante
PASS  beta7.20 doctor proverava SKU default snapshot i aggregate
PASS  beta7.20 feature i smoke testovi postoje
PASS  beta7.17 feature test pokriva dedupe rate zatvaranje i UI regresiju
PASS  beta7.21 migracija uvodi nabavne snapshotove i rasporede
PASS  beta7.21 migracija je recovery-safe i permission schema-aware
PASS  beta7.21 marža koristi snapshot i prikazuje pokrivenost troška
PASS  beta7.21 filteri važe za KPI trend i segmente
PASS  beta7.21 dashboard pokriva lager potraživanja postprodaju i tim
PASS  beta7.21 PDF upravljačkog izveštaja postoji
PASS  beta7.21 raspored ima retry dedupe i zasebne primaoce
PASS  beta7.21 ekran je bezbedan pre migracije
PASS  beta7.21 UI ima CSV PDF rasporede i cost coverage
PASS  beta7.22.1 management analytics koristi aktivnu temu bez belog fallback-a
PASS  beta7.22.1 CSS kompatibilni aliasi postoje
PASS  beta7.21 feature test pokriva ekran export i raspored
PASS  beta7.22 portal servis i fallback podaci postoje
PASS  beta7.22 portal objedinjuje porudžbine dokumente uplate garancije i servis
PASS  beta7.22 report grouping je kompatibilan sa ONLY_FULL_GROUP_BY
PASS  beta7.22 dashboard ima trend prioritete brze akcije i operativne module
PASS  beta7.22.1 portal doctor prosleđuje ViewErrorBag
PASS  beta7.22.1 layout bezbedno proverava errors bag
PASS  beta7.23 release-check komanda ima profile i kontrolisane režime
PASS  beta7.23 release registry ima quick standard i full profile
PASS  beta7.23 release plan ne dispatchuje poslovne akcije
PASS  beta7.23 release metadata i atomski JSON report postoje
PASS  beta7.23 release rezultat ima READY i NOT READY ugovor
PASS  beta7.23 smoke i dokumentacija postoje
PASS  beta7.23.1 catalog detail nema problematične inline Blade lance
PASS  beta7.23.1 catalog detail Blade direktive su izbalansirane
PASS  beta7.23.1 variant detail Feature i smoke regresija postoje
PASS  beta7.23.1 health daje čitljive runtime remediation komande
PASS  beta7.23.2 detail doctor rešava controller zavisnosti kroz container
PASS  beta7.23.2 detail doctor nema direktan edit poziv sa jednim argumentom
PASS  beta7.23.2 detail doctor smoke i contract regresija postoje
PASS  beta7.24 migracija uvodi aktivacije sesije komunikaciju i order-link audit
PASS  beta7.24 aktivacioni token je hashiran jednokratan i vremenski ograničen
PASS  beta7.24 session registry koristi hash i podržava revoke
PASS  beta7.24 kupac vidi samo javne poruke a admin interne
PASS  beta7.24 portal rute aktivacija i admin centar postoje
PASS  beta7.24 komunikacija razdvaja public i internal
PASS  beta7.24 smoke i PHPUnit regresije postoje
PASS  beta7.24 maintenance čisti tokene i stare session evidencije
PASS  beta7.24 reinvite ne deaktivira aktivnog kupca i aktivacija nije cache-ovana
PASS  beta7.24.1 management repair obrađuje missing snapshotove
PASS  beta7.24.1 repair ne prepisuje kompletne snapshotove
PASS  beta7.24.1 repair je transakcioni i koristi row lock
PASS  beta7.24.1 kandidati imaju transparentan izvor
PASS  beta7.24.1 ručna finansijska promena zahteva razlog i audit
PASS  beta7.24.1 audit/repair komanda i regresije postoje
PASS  rc1 profil sadrzi final hardening provere
PASS  rc1 security doctor proverava production debug HTTPS session i public fajlove
PASS  rc1 migration doctor proverava pending SQL mode i foreign keys
PASS  rc1 access doctor proverava route permission i superadmin
PASS  rc1 release integrity proverava SHA-256 i path traversal
PASS  rc1 backup verify je read-only i proverava SQL gzip i file hash
PASS  rc1 smoke i contract regresije postoje
PASS  rc1 nema novu migration datoteku
PASS  stable profil je identican potvrdenom rc profilu
PASS  stable smoke i contract regresije postoje
PASS  stable početna je univerzalni dashboard sa integrisanim korisničkim centrom
PASS  stable nema zasebnu Moj portal stranicu ni stavku menija
PASS  stable nema novu migration datoteku
PASS  v2.1.2 obaveštenja o novom artiklu su opt-in i koriste outbox
PASS  v2.1.2 novi artikal se šalje aktivnim registrovanim korisnicima bez duplikata
PASS  v2.1.2 mail podešavanja i šablon podržavaju nove artikle
PASS  v2.1.2 product announcement regresije postoje
PASS  v2.1.2 nema novu migration datoteku
PASS  APP_ENV production
PASS  Redis nije obavezan za database queue
PASS  file session/cache/limiter i database queue
PASS  secret vrednosti su prazne
PASS  import ne upisuje legacy konekciju
INFO  ZIP hygiene provere su preskočene na instaliranoj aplikaciji; za raspakovani sanitized ZIP koristi --package.
PASS  v2.1.3 tipovi proizvoda imaju posebne stranice i Drag & Drop
PASS  v2.1.3 specifikaciona polja mogu trajno da se obrišu
PASS  v2.1.3 tip automatski određuje kategoriju
PASS  v2.1.3 diskovi imaju pojedinačne celobrojne GB kapacitete
PASS  v2.1.3 catalog settings doctor postoji
PASS  v2.1.3 grana ima tri kontrolisane migration datoteke
PASS  v2.1.3.3 ProductRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 ProductVariantRequest zadržava validan SKU regex delimiter
PASS  v2.1.3.3 migracija povezuje listu diskova i izvedeni ukupni kapacitet
PASS  v2.1.3.3 stari kapacitet se bezbedno prenosi na prvi disk
PASS  v2.1.3.3 backend ne veruje ručnom ukupnom zbiru
PASS  v2.1.3.3 ukupni kapacitet je ispod diskova i readonly
PASS  v2.1.3.3 frontend sabira diskove i čuva početni legacy zbir
PASS  v2.1.3.3 proizvod i varijante dele isti storage model
PASS  v2.1.3.3 storage smoke i contract test postoje
PASS  v2.1.4 migracija dodaje model proizvoda i usklađuje šablone
PASS  v2.1.4 model se validira čuva i koristi u nazivu
PASS  v2.1.4 forma ima model proizvoda posle linije
PASS  v2.1.4 trajno brisanje ima SKU potvrdu i izbor brisanja slika
PASS  v2.1.4 poslovna istorija blokira destruktivno brisanje
PASS  v2.1.4 semantički sistem tastera pokriva sve uloge
PASS  v2.1.4 route i stable doctor postoje
PASS  v2.1.4 smoke i contract test postoje
PASS  v2.1.4.1 controller priprema i prosledjuje orderedFields
PASS  v2.1.4.1 Blade bezbedno inicijalizuje orderedFields
PASS  v2.1.4.1 doctor renderuje formulare svih tipova
PASS  v2.1.4.1 smoke i contract test postoje
PASS  v2.1.5 globalni UX runtime štiti submit i nesačuvane izmene
PASS  v2.1.5 mobilni action dock koristi originalni submit
PASS  v2.1.5 validacija i accessibility markeri postoje
PASS  v2.1.5 dugi formulari su eksplicitno označeni
PASS  v2.1.5 sistemske error stranice postoje
PASS  v2.1.5 migracija koristi postojeće snaga-napajanja polje
PASS  v2.1.5 migracija postavlja napajanje u sredinu
PASS  v2.1.5 doctor proverava Blade, route akcije i napajanje
PASS  v2.1.5 stable release koristi render i repair
PASS  v2.1.5 smoke i contract test postoje
PASS  v2.1.6 migracija kreira snapshot istoriju i ciljane indekse
PASS  v2.1.6 Data Quality audit pokriva katalog slike varijante i specifikacije
PASS  v2.1.6 repair je nedestruktivan i preračunava izvedene vrednosti
PASS  v2.1.6 performance doctor proverava indekse cache i SQL pragove
PASS  v2.1.6 dashboard kešira schema metadata po requestu
PASS  v2.1.6 Data Quality Center rute i prikaz postoje
PASS  v2.1.6 katalog ima quality filtere
PASS  v2.1.6 doctor renderuje centar i pokreće performance audit
PASS  v2.1.6 Stable release uključuje render repair i strict
PASS  v2.1.6 smoke i contract test postoje
PASS  v2.2.0 bootstrap device catalog order i notification rute postoje
PASS  v2.2.0 bootstrap vraća permissions features i app policy
PASS  v2.2.0 uređaji deduplikuju push tokene i podržavaju opoziv
PASS  v2.2.0 API greške imaju stabilan envelope
PASS  v2.2.0 OpenAPI i Stable doctor su povezani

Ukupno: 983, neuspešno: 0
CMS_STATIC_CHECK=PASS

============================================================
8. GIT IMMUTABILITY
============================================================
TARGETED_GIT_DIFF_CHECK=PASS
SOURCE_WRITES_DURING_AUDIT=0
DATABASE_WRITES_DURING_AUDIT=0
MIGRATIONS_RUN=NO
EAS_BUILD=NO

============================================================
9. FINAL
============================================================
PLAY_STORE_INSTALL=PASS_USER_REPORTED
PASSWORD_LOGIN_UI_ON_SM_S928B_ANDROID16=FAIL_KEYBOARD_OBSCURES_OR_TRAPS_FORM
GOOGLE_LOGIN_ON_PLAY_DISTRIBUTED_BUILD=FAIL_USER_REPORTED
ANDROID_V0_6_DEVICE_ACCEPTANCE=BLOCKED
GOOGLE_WEB_LOGIN_4B_PLUS=BLOCKED_UNTIL_ANDROID_DEVICE_ACCEPTANCE
NEXT_ACTION=PREPARE_TARGETED_LOGIN_RELIABILITY_PATCH_FROM_THIS_REPORT
REPORT=/home/icaffeco/ald1n-project/docs/operations/MOBILE-V0.6.0-PLAY-LOGIN-INCIDENT-AUDIT-BATCH1-20260817-174511.md
MOBILE_V0_6_PLAY_LOGIN_INCIDENT_AUDIT_BATCH1=PASS
PASS: MOBILE v0.6.0 PLAY LOGIN INCIDENT AUDIT BATCH 1 COMPLETE
