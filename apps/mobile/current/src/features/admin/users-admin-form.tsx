import { useMemo, useState } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import type {
  AdminUser,
  AdminUserInput,
  AdminUserOptionsData,
  AdminUserStatus,
} from '@/features/admin/users-admin-api';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
export function AdminUserForm({
  mode,
  options,
  initial,
  serverErrors,
  busy,
  onSubmit,
}: {
  mode: 'create' | 'edit';
  options: AdminUserOptionsData;
  initial?: AdminUser;
  serverErrors?: Record<string, string>;
  busy: boolean;
  onSubmit: (input: AdminUserInput) => void;
}) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();

  const defaultRoleId = initial?.role?.id ?? options.roles[0]?.id ?? 0;
  const [roleId, setRoleId] = useState(defaultRoleId > 0 ? String(defaultRoleId) : '');
  const [groupId, setGroupId] = useState(initial?.group?.id ? String(initial.group.id) : '');
  const [username, setUsername] = useState(initial?.username ?? '');
  const [email, setEmail] = useState(initial?.email ?? '');
  const [firstName, setFirstName] = useState(initial?.first_name ?? '');
  const [lastName, setLastName] = useState(initial?.last_name ?? '');
  const [phone, setPhone] = useState(initial?.phone ?? '');
  const [status, setStatus] = useState<AdminUserStatus> (initial?.status ?? 'active');
  const [password, setPassword] = useState('');
  const [localErrors, setLocalErrors] = useState<Record<string, string>> ({});

  const errors = { ...localErrors, ...(serverErrors ?? {}) };
  const selectedRole = options.roles.find((role) => String(role.id) === roleId);
  const roleHasFullAccess = selectedRole?.slug === 'admin' || selectedRole?.slug === 'superadmin';

  const submit = () => {
    const nextErrors: Record<string, string> = {};
    const parsedRoleId = Number(roleId);
    const parsedGroupId = groupId ? Number(groupId) : null;
    const cleanUsername = username.trim();
    const cleanEmail = email.trim().toLowerCase();
    const cleanPassword = password;

    if (!Number.isInteger(parsedRoleId) || parsedRoleId <= 0) {
      nextErrors.role_id = 'Izaberi ulogu.';
    }
    if (parsedGroupId !== null && (!Number.isInteger(parsedGroupId) || parsedGroupId <= 0)) {
      nextErrors.user_group_id = 'Izaberi validnu grupu pristupa.';
    }
    if (cleanUsername.length < 3) {
      nextErrors.username = 'Korisničko ime mora imati najmanje 3 znaka.';
    }
    if (!/^\S+@\S+\.\S+$/.test(cleanEmail)) {
      nextErrors.email = 'Unesi ispravnu e-mail adresu.';
    }
    if (mode === 'create' && cleanPassword.length < 12) {
      nextErrors.password = 'Početna lozinka mora imati najmanje 12 znakova.';
    }
    if (mode === 'edit' && cleanPassword && cleanPassword.length < 12) {
      nextErrors.password = 'Nova lozinka mora imati najmanje 12 znakova.';
    }

    setLocalErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || parsedRoleId <= 0) {
      feedback.notify({
        tone: 'warning',
        title: 'Proveri podatke korisnika',
        message: 'Ispravi označena polja. Svi uneti podaci ostaju u formi.',
      });
      return;
    }

    const input: AdminUserInput = {
      role_id: parsedRoleId,
      user_group_id: parsedGroupId,
      username: cleanUsername,
      email: cleanEmail,
      first_name: firstName.trim() || null,
      last_name: lastName.trim() || null,
      phone: phone.trim() || null,
      status,
    };
    if (cleanPassword) input.password = cleanPassword;

    onSubmit(input);
  };

  return (
    <View style={styles.root}>
      {initial?.last_active_superadmin_protected ? (
        <Card style={styles.guardCard}>
          <Text style={styles.guardTitle}>Zaštita poslednjeg aktivnog SuperAdministratora</Text>
          <Text style={styles.muted}>
            Ovaj nalog ne može biti degradiran niti blokiran dok ne postoji drugi aktivni SuperAdministrator.
          </Text>
        </Card>
      ) : null}

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Identitet</Text>
        <TextField
          label="Korisničko ime"
          value={username}
          onChangeText={setUsername}
          autoCapitalize="none"
          autoCorrect={false}
          maxLength={50}
          error={errors.username}
        />
        <TextField
          label="E-mail"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoCorrect={false}
          keyboardType="email-address"
          maxLength={190}
          error={errors.email}
        />
        <TextField
          label="Ime"
          value={firstName}
          onChangeText={setFirstName}
          maxLength={100}
          error={errors.first_name}
        />
        <TextField
          label="Prezime"
          value={lastName}
          onChangeText={setLastName}
          maxLength={100}
          error={errors.last_name}
        />
        <TextField
          label="Telefon"
          value={phone}
          onChangeText={setPhone}
          keyboardType="phone-pad"
          maxLength={40}
          error={errors.phone}
        />
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Pristup</Text>
        <SelectSheet
          label="Uloga"
          value={roleId}
          options={options.roles.map((role) => ({
            value: String(role.id),
            label: role.name,
            detail: role.slug,
          }))}
          onChange={setRoleId}
          error={errors.role_id}
        />
        <SelectSheet
          label="Grupa pristupa"
          value={groupId}
          options={[
            { value: '', label: 'Bez grupe' },
            ...options.groups.map((group) => ({
              value: String(group.id),
              label: group.name,
              detail: group.status === 'active' ? 'Aktivna grupa' : 'Neaktivna grupa',
            })),
          ]}
          onChange={setGroupId}
          error={errors.user_group_id}
        />
        <SelectSheet
          label="Status naloga"
          value={status}
          options={options.statuses}
          onChange={(value) => {
            if (value === 'pending' || value === 'active' || value === 'blocked') {
              setStatus(value);
            }
          }}
          error={errors.status}
        />
        <Text style={styles.muted}>
          {roleHasFullAccess
            ? 'Administrator i SuperAdmin imaju puni permission pristup po ulozi; grupa ne sužava njihove backend dozvole.'
            : 'Za korisničku ulogu efektivni permission-i dolaze iz aktivne grupe pristupa.'}
        </Text>
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Lozinka</Text>
        <TextField
          label={mode === 'create' ? 'Početna lozinka' : 'Nova lozinka'}
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoCapitalize="none"
          autoCorrect={false}
          maxLength={200}
          error={errors.password}
          placeholder={mode === 'create' ? 'Najmanje 12 znakova' : 'Ostavi prazno bez promene'}
        />
        <Text style={styles.muted}>
          Promena lozinke opoziva sve postojeće API tokene tog korisnika. Ako menjaš sopstvenu lozinku, aplikacija će zahtevati novu prijavu.
        </Text>
      </Card>

      <Button loading={busy} onPress={submit}>
        {mode === 'create' ? 'Dodaj korisnika' : 'Sačuvaj korisnika'}
      </Button>

      <Card muted style={styles.noDeleteCard}>
        <Text style={styles.muted}>
          Trajno brisanje korisnika nije deo postojećeg Laravel User Managera i zato nije uvedeno u Android v0.8.0.
        </Text>
      </Card>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    root: { gap: spacing.lg },
    section: { gap: spacing.md },
    sectionTitle: { ...typography.h2, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    guardCard: { gap: spacing.sm, borderWidth: 1, borderColor: theme.warning },
    guardTitle: { ...typography.label, color: theme.warning },
    noDeleteCard: { gap: spacing.sm },
  });
}
