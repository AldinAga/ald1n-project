import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useMemo, useState } from 'react';
import { StyleSheet, Switch, Text } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { appendAdminSystemFile, apiAdminSystemSettings, pickAdminSystemFiles, type AdminSystemFile } from '@/features/admin/system-settings-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET09
export default function AdminDocumentSettingsScreen() {
  const { can, bootstrap } = useAuth(); const { colors: theme } = useAppTheme(); const styles = useMemo(() => createStyles(theme), [theme]); const feedback = useAppFeedback(); const client = useQueryClient(); const allowed = can('system.manage_settings');
  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsDocuments(), queryFn: apiAdminSystemSettings.documents.state, enabled: allowed });
  const [values,setValues] = useState<Record<string,string>>({}); const [logo,setLogo] = useState<AdminSystemFile | null>(null); useEffect(() => { if(query.data){setValues(query.data.data.settings);setLogo(null);} },[query.data]);
  const save = useMutation({ mutationFn: async () => { const form = new FormData(); ['documents_company_name','documents_company_address','documents_company_city','documents_company_tax_id','documents_company_registration_number','documents_company_phone','documents_company_email','documents_company_website','documents_vat_rate','documents_payment_due_days','documents_default_note','documents_footer_note'].forEach((key)=>form.append(key,values[key]??'')); form.append('documents_vat_enabled',values.documents_vat_enabled==='1'?'1':'0'); if(logo) appendAdminSystemFile(form,'documents_logo',logo); return apiAdminSystemSettings.documents.update(form); }, onSuccess:(response)=>{client.setQueryData(adminQueryKeys.systemSettingsDocuments(),response);feedback.notify({tone:'success',title:'Dokument podešavanja su sačuvana'});},onError:(error)=>feedback.notify({tone:'danger',title:'Podešavanja nisu sačuvana',message:error instanceof Error?error.message:'Greška.'}) });
  const remove = useMutation({ mutationFn: apiAdminSystemSettings.documents.removeLogo, onSuccess:(response)=>client.setQueryData(adminQueryKeys.systemSettingsDocuments(),response), onError:(error)=>feedback.notify({tone:'danger',title:'Logo nije uklonjen',message:error instanceof Error?error.message:'Greška.'}) });
  if(!allowed)return <UnavailableState title="Poslovni dokumenti nisu dostupni"/>; if(query.isLoading)return <LoadingState label="Učitavanje podešavanja dokumenata…"/>; if(query.isError||!query.data)return <ErrorState error={query.error} onRetry={()=>void query.refetch()}/>;
  const data=query.data.data; const set=(key:string,value:string)=>setValues((current)=>({...current,[key]:value})); const fields:[string,string,boolean?][]=[['documents_company_name','Naziv kompanije'],['documents_company_address','Adresa'],['documents_company_city','Grad'],['documents_company_tax_id','PIB'],['documents_company_registration_number','Matični broj'],['documents_company_phone','Telefon'],['documents_company_email','E-mail'],['documents_company_website','Web sajt'],['documents_vat_rate','PDV stopa %',true],['documents_payment_due_days','Rok plaćanja (dana)',true],['documents_default_note','Podrazumevana napomena'],['documents_footer_note','Footer napomena']];
  return <Screen contentStyle={styles.content}><PageHeader title="Poslovni dokumenti" eyebrow="Sistem · SET-09" name={bootstrap?.user.name}/><Card style={styles.card}><Text style={styles.title}>Podaci na PDF dokumentima</Text>{fields.map(([key,label,numeric])=><TextField key={key} label={label} value={values[key]??''} keyboardType={numeric?'number-pad':'default'} autoCapitalize={key.includes('email')||key.includes('website')?'none':'sentences'} onChangeText={(v)=>set(key,v)}/>) }<Text style={styles.body}>PDV uključen</Text><Switch value={values.documents_vat_enabled==='1'} onValueChange={(v)=>set('documents_vat_enabled',v?'1':'0')}/></Card><Card style={styles.card}><Text style={styles.title}>PDF logo</Text><Text style={styles.meta}>{data.document_logo_url?'Logo je podešen.':'Nema posebnog PDF logotipa.'}</Text><Button variant="secondary" onPress={async()=>{try{const files=await pickAdminSystemFiles(data.upload_limits);if(files[0])setLogo(files[0]);}catch(error){feedback.notify({tone:'danger',title:'Logo nije prihvaćen',message:error instanceof Error?error.message:'Greška.'});}}}>Izaberi logo{logo?` · ${logo.name}`:''}</Button>{data.document_logo_url?<Button variant="secondary" loading={remove.isPending} onPress={()=>remove.mutate()}>Ukloni PDF logo</Button>:null}</Card><Button loading={save.isPending} onPress={()=>save.mutate()}>Sačuvaj dokument podešavanja</Button></Screen>;
}
function createStyles(theme:AppColors){return StyleSheet.create({content:{gap:spacing.lg,paddingBottom:140},card:{gap:spacing.md},title:{...typography.h2,color:theme.ink},body:{...typography.body,color:theme.ink},meta:{...typography.small,color:theme.muted}});}
