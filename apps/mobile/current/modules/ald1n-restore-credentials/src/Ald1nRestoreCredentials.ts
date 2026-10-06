import { requireOptionalNativeModule } from 'expo-modules-core';

type RestoreCredentialsNative = {
  isSupported(): boolean;
  createRestoreCredential(requestJson: string): Promise<string>;
  getRestoreCredential(authenticationJson: string): Promise<string>;
  clearRestoreCredential(): Promise<void>;
};

export default requireOptionalNativeModule<RestoreCredentialsNative> ('Ald1nRestoreCredentials');
