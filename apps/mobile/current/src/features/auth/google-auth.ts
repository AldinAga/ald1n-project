import {
  GoogleOneTapSignIn,
  isCancelledResponse,
  isErrorWithCode,
  isNoSavedCredentialFoundResponse,
  isSuccessResponse,
} from 'react-native-nitro-google-signin';

let configured = false;

function configureGoogle(): void {
  if (configured) return;
  GoogleOneTapSignIn.configure({ webClientId: 'autoDetect' });
  configured = true;
}

function googleNativeFailure(error: unknown): Error {
  if (isErrorWithCode(error)) {
    const detail = error.message?.trim() || 'Google native provider nije vratio dodatni opis.';
    return new Error(`Google prijava nije uspela [${error.code}]: ${detail}`);
  }
  if (error instanceof Error) {
    return new Error(`Google prijava nije uspela: ${error.message || 'nepoznata native greška.'}`);
  }
  return new Error('Google prijava nije uspela: nepoznata native greška.');
}

export async function getGoogleIdToken(): Promise<string> {
  try {
    configureGoogle();
    await GoogleOneTapSignIn.checkPlayServices();

    let response = await GoogleOneTapSignIn.signIn();
    if (isNoSavedCredentialFoundResponse(response)) {
      response = await GoogleOneTapSignIn.createAccount();
    }
    if (isNoSavedCredentialFoundResponse(response)) {
      response = await GoogleOneTapSignIn.presentExplicitSignIn();
    }
    if (isCancelledResponse(response)) {
      throw new Error('Google prijava je otkazana.');
    }
    if (!isSuccessResponse(response) || !response.data.idToken) {
      throw new Error('Google Credential Manager nije vratio validan ID token.');
    }

    return response.data.idToken;
  } catch (error) {
    if (error instanceof Error && (error.message === 'Google prijava je otkazana.' || error.message === 'Google Credential Manager nije vratio validan ID token.')) {
      throw error;
    }
    throw googleNativeFailure(error);
  }
}

export async function clearGoogleCredentialState(): Promise<void> {
  try {
    configureGoogle();
    await GoogleOneTapSignIn.signOut();
  } catch {
    // Laravel/Sanctum logout ne sme da zavisi od Google provider cleanup-a.
  }
}
