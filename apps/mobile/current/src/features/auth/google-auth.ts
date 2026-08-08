import {
  GoogleOneTapSignIn,
  isNoSavedCredentialFoundResponse,
  isSuccessResponse,
} from 'react-native-nitro-google-signin';

let configured = false;

function configureGoogle(): void {
  if (configured) return;
  GoogleOneTapSignIn.configure({ webClientId: 'autoDetect' });
  configured = true;
}

export async function getGoogleIdToken(): Promise<string> {
  configureGoogle();
  await GoogleOneTapSignIn.checkPlayServices();

  let response = await GoogleOneTapSignIn.signIn();
  if (isNoSavedCredentialFoundResponse(response)) {
    response = await GoogleOneTapSignIn.createAccount();
  }
  if (isNoSavedCredentialFoundResponse(response)) {
    response = await GoogleOneTapSignIn.presentExplicitSignIn();
  }

  if (!isSuccessResponse(response) || !response.data.idToken) {
    throw new Error('Google prijava je otkazana ili nije vratila validan identitet.');
  }

  return response.data.idToken;
}

export async function clearGoogleCredentialState(): Promise<void> {
  try {
    configureGoogle();
    await GoogleOneTapSignIn.signOut();
  } catch {
    // Laravel/Sanctum logout ne sme da zavisi od Google provider cleanup-a.
  }
}
