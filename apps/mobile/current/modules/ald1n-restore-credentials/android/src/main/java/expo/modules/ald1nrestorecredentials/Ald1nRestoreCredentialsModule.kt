package expo.modules.ald1nrestorecredentials

import android.os.Build
import androidx.credentials.ClearCredentialStateRequest
import androidx.credentials.ClearCredentialStateRequest.Companion.TYPE_CLEAR_RESTORE_CREDENTIAL
import androidx.credentials.CreateRestoreCredentialRequest
import androidx.credentials.CreateRestoreCredentialResponse
import androidx.credentials.CredentialManager
import androidx.credentials.GetCredentialRequest
import androidx.credentials.GetRestoreCredentialOption
import androidx.credentials.RestoreCredential
import androidx.credentials.exceptions.restorecredential.E2eeUnavailableException
import expo.modules.kotlin.functions.Coroutine
import expo.modules.kotlin.modules.Module
import expo.modules.kotlin.modules.ModuleDefinition

class Ald1nRestoreCredentialsModule : Module() {
  override fun definition() = ModuleDefinition {
    Name("Ald1nRestoreCredentials")

    Function("isSupported") {
      Build.VERSION.SDK_INT >= Build.VERSION_CODES.P
    }

    AsyncFunction("createRestoreCredential") Coroutine { requestJson: String ->
      val activity = appContext.currentActivity ?: throw IllegalStateException("Android Activity nije dostupna.")
      val manager = CredentialManager.create(activity)
      val response = try {
        manager.createCredential(
          activity,
          CreateRestoreCredentialRequest(requestJson = requestJson, isCloudBackupEnabled = true),
        ) as CreateRestoreCredentialResponse
      } catch (_: E2eeUnavailableException) {
        manager.createCredential(
          activity,
          CreateRestoreCredentialRequest(requestJson = requestJson, isCloudBackupEnabled = false),
        ) as CreateRestoreCredentialResponse
      }
      return@Coroutine response.responseJson
    }

    AsyncFunction("getRestoreCredential") Coroutine { authenticationJson: String ->
      val activity = appContext.currentActivity ?: throw IllegalStateException("Android Activity nije dostupna.")
      val manager = CredentialManager.create(activity)
      val option = GetRestoreCredentialOption(authenticationJson)
      val response = manager.getCredential(activity, GetCredentialRequest(listOf(option)))
      val credential = response.credential as? RestoreCredential
        ?: throw IllegalStateException("Credential Manager nije vratio RestoreCredential.")
      return@Coroutine credential.authenticationResponseJson
    }

    AsyncFunction("clearRestoreCredential") Coroutine {
      val context = appContext.reactContext ?: throw IllegalStateException("Android context nije dostupan.")
      val manager = CredentialManager.create(context)
      manager.clearCredentialState(ClearCredentialStateRequest(requestType = TYPE_CLEAR_RESTORE_CREDENTIAL))
      return@Coroutine null
    }
  }
}
