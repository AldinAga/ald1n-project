import { StyleSheet, Text, TextInput, View } from 'react-native';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
export type DateTimeFieldMode = 'date' | 'datetime';
type Props = { label:string; value:string; onChangeText:(value:string)=>void; mode?:DateTimeFieldMode; placeholder?:string; error?:string|null; disabled?:boolean; required?:boolean };
export function normalizeDateTimeInput(input:string, mode:DateTimeFieldMode):string { return input.replace(/[^0-9:\- ]/g,'').slice(0, mode === 'date' ? 10 : 16); }
export function DateTimeField({ label, value, onChangeText, mode='datetime', placeholder, error=null, disabled=false, required=false }:Props) {
  const { colors: theme } = useAppTheme(); const styles=createStyles(theme); const hint=mode === 'date' ? 'YYYY-MM-DD' : 'YYYY-MM-DD HH:mm';
  return <View style={styles.wrap}><Text style={styles.label}>{label}{required ? ' *' : ''}</Text><TextInput accessibilityLabel={label} autoCapitalize="none" autoCorrect={false} editable={!disabled} maxLength={mode === 'date' ? 10 : 16} onChangeText={(next)=>onChangeText(normalizeDateTimeInput(next,mode))} placeholder={placeholder ?? hint} placeholderTextColor={theme.muted} style={[styles.input,error?styles.errorBorder:null,disabled?styles.disabled:null]} value={value}/><Text style={styles.hint}>Format: {hint}</Text>{error?<Text style={styles.error}>{error}</Text>:null}</View>;
}
function createStyles(theme:AppColors){return StyleSheet.create({wrap:{gap:spacing.xs},label:{...typography.label,color:theme.ink},input:{minHeight:48,borderRadius:14,borderWidth:1,borderColor:theme.line,backgroundColor:theme.surface,color:theme.ink,paddingHorizontal:spacing.md,paddingVertical:spacing.sm,fontSize:16},errorBorder:{borderColor:theme.danger},disabled:{opacity:.55},hint:{...typography.small,color:theme.muted},error:{...typography.small,color:theme.danger,fontWeight:'700'}});}
