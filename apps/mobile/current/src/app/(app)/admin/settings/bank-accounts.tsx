import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useMemo, useState } from 'react';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminSystemSettings, type AdminBankAccount, type AdminBankAccountInput } from '@/features/admin/system-settings-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const EMPTY: AdminBankAccountInput = { label:'',recipient_name:'',recipient_address:'',account_number:'',payment_code:'289',is_active:true };

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET10
export default function AdminBankAccountsScreen(){
  const {can,bootstrap}=useAuth(); const {colors:theme}=useAppTheme(); const styles=useMemo(()=>createStyles(theme),[theme]); const feedback=useAppFeedback(); const client=useQueryClient(); const allowed=can('system.manage_settings');
  const query=useQuery({queryKey:adminQueryKeys.systemSettingsBankAccounts(),queryFn:apiAdminSystemSettings.bankAccounts.state,enabled:allowed});
  const [editing,setEditing]=useState<number|null>(null); const [form,setForm]=useState<AdminBankAccountInput>(EMPTY);
  const save=useMutation({mutationFn:()=>editing===null?apiAdminSystemSettings.bankAccounts.create(form):apiAdminSystemSettings.bankAccounts.update(editing,form),onSuccess:(response)=>{client.setQueryData(adminQueryKeys.systemSettingsBankAccounts(),response);setEditing(null);setForm(EMPTY);feedback.notify({tone:'success',title:'Žiro račun je sačuvan'});},onError:(error)=>feedback.notify({tone:'danger',title:'Račun nije sačuvan',message:error instanceof Error?error.message:'Greška.'})});
  const remove=useMutation({mutationFn:apiAdminSystemSettings.bankAccounts.remove,onSuccess:(response)=>client.setQueryData(adminQueryKeys.systemSettingsBankAccounts(),response),onError:(error)=>feedback.notify({tone:'danger',title:'Račun nije obrisan',message:error instanceof Error?error.message:'Greška.'})});
  const edit=(account:AdminBankAccount)=>{setEditing(account.id);setForm({label:account.label,recipient_name:account.recipient_name,recipient_address:account.recipient_address??'',account_number:account.account_number_display||account.account_number,payment_code:account.payment_code,is_active:account.is_active});};
  if(!allowed)return <UnavailableState title="Žiro računi nisu dostupni"/>; if(query.isLoading)return <LoadingState label="Učitavanje žiro računa…"/>; if(query.isError||!query.data)return <ErrorState error={query.error} onRetry={()=>void query.refetch()}/>;
  const accounts=query.data.data.accounts; const set=<K extends keyof AdminBankAccountInput>(key:K,value:AdminBankAccountInput[K])=>setForm((current)=>({...current,[key]:value}));
  return <Screen contentStyle={styles.content}><PageHeader title="Žiro računi" eyebrow="Sistem · SET-10" name={bootstrap?.user.name}/><Card style={styles.card}><Text style={styles.title}>{editing===null?'Novi račun':'Izmena računa'}</Text><TextField label="Naziv računa" value={form.label} onChangeText={(v)=>set('label',v)}/><TextField label="Primalac" value={form.recipient_name} onChangeText={(v)=>set('recipient_name',v)}/><TextField label="Adresa primaoca" value={form.recipient_address??''} onChangeText={(v)=>set('recipient_address',v)}/><TextField label="Broj računa" value={form.account_number} keyboardType="number-pad" onChangeText={(v)=>set('account_number',v)}/><TextField label="Šifra plaćanja" value={form.payment_code} keyboardType="number-pad" onChangeText={(v)=>set('payment_code',v)}/><View style={styles.row}><Text style={styles.body}>Aktivan</Text><Switch value={form.is_active} onValueChange={(v)=>set('is_active',v)}/></View><View style={styles.actions}><Button loading={save.isPending} onPress={()=>save.mutate()}>{editing===null?'Dodaj račun':'Sačuvaj izmene'}</Button>{editing!==null?<Button variant="secondary" onPress={()=>{setEditing(null);setForm(EMPTY);}}>Otkaži izmenu</Button>:null}</View></Card><Card style={styles.card}><Text style={styles.title}>Postojeći računi</Text>{accounts.length===0?<Text style={styles.meta}>Nema podešenih računa.</Text>:accounts.map((account)=><View key={account.id} style={styles.item}><Text style={styles.body}>{account.label} · {account.account_number_display}</Text><Text style={styles.meta}>{account.recipient_name} · šifra {account.payment_code} · {account.is_active?'aktivan':'neaktivan'}</Text><View style={styles.actions}><Button variant="secondary" onPress={()=>edit(account)}>Uredi</Button><Button variant="secondary" loading={remove.isPending} onPress={()=>remove.mutate(account.id)}>Obriši</Button></View></View>)}</Card></Screen>;
}
function createStyles(theme:AppColors){return StyleSheet.create({content:{gap:spacing.lg,paddingBottom:140},card:{gap:spacing.md},title:{...typography.h2,color:theme.ink},body:{...typography.body,color:theme.ink,flex:1},meta:{...typography.small,color:theme.muted},row:{flexDirection:'row',alignItems:'center',gap:spacing.md},actions:{gap:spacing.sm},item:{gap:spacing.sm,paddingVertical:spacing.sm}});}
