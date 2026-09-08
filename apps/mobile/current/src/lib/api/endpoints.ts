import { File } from 'expo-file-system';

import { apiExpoMultipartRequest, apiRequest, queryString } from '@/lib/api/client';
import type {
  AccountPasswordInput,
  AccountPasswordResponse,
  AccountProfileInput,
  AccountSessionRevokeResponse,
  AccountSessionsData,
  AuthRecoveryMessageResponse,
  CustomerActivationInput,
  CustomerActivationState,
  PasswordResetInput,
  PasswordResetRequestInput,
  AfterSalesCase,
  AfterSalesCaseSummary,
  AfterSalesMessage,
  AfterSalesOptions,
  AfterSalesUploadFile,
  AuthTokenResponse,
  GoogleAuthResponse,
  BootstrapData,
  BusinessNotification,
  CatalogFilters,
  Commission,
  CommissionListParams,
  CommissionListResponse,
  CreateAfterSalesCaseInput,
  CreateAfterSalesMessageInput,
  CreateOrderInput,
  MobileDevice,
  MobileDeviceInput,
  MobileDeviceUpdateInput,
  NotificationPreferences,
  Order,
  OrderOptions,
  OrderPaymentLedgerEntry,
  OrderPostCreate,
  SubmitOrderPaymentProofInput,
  PaginatedResponse,
  Product,
  User,
  Warranty,
  WarrantySummary
} from '@/types/api';

function appendAfterSalesAttachments(
  formData: FormData,
  attachments: AfterSalesUploadFile[] | undefined
): void {
  for (const attachment of attachments ?? []) {
    formData.append('attachments[]', {
      uri: attachment.uri,
      name: attachment.name,
      type: attachment.type
    });
  }
}

function afterSalesCaseFormData(input: CreateAfterSalesCaseInput): FormData {
  const formData = new FormData();

  formData.append('case_type', input.case_type);
  formData.append('priority', input.priority);
  formData.append('subject', input.subject);
  formData.append('description', input.description);

  if (input.requested_resolution) {
    formData.append('requested_resolution', input.requested_resolution);
  }

  for (const item of input.items) {
    const key = `items[${item.order_item_id}]`;
    formData.append(`${key}[selected]`, '1');
    formData.append(`${key}[quantity]`, String(item.quantity));

    if (item.issue_description) {
      formData.append(`${key}[issue_description]`, item.issue_description);
    }
  }

  appendAfterSalesAttachments(formData, input.attachments);

  return formData;
}

function afterSalesMessageFormData(input: CreateAfterSalesMessageInput): FormData {
  const formData = new FormData();

  formData.append('body', input.body);
  appendAfterSalesAttachments(formData, input.attachments);

  return formData;
}
function orderPaymentProofFormData(input: SubmitOrderPaymentProofInput): FormData {
  const formData = new FormData();
  formData.append('amount_rsd', String(input.amount_rsd));
  formData.append('paid_at', input.paid_at);

  if (input.reference) {
    formData.append('reference', input.reference);
  }

  if (input.note) {
    formData.append('note', input.note);
  }

  formData.append('proof', {
    uri: input.proof.uri,
    name: input.proof.name,
    type: input.proof.type
  });

  return formData;
}
export const api = {
  admin: {
    catalog: {
      options: async () => {
        const response = await apiRequest<{ data: import('@/types/api').AdminCatalogCreateOptions }> ('admin/catalog/options');
        return response.data;
      },
      // MOBILE_BUILD16_PRODUCT_CREATE_IDEMPOTENCY_BATCH134
      createProduct: (input: import('@/types/api').AdminProductCreateInput, idempotencyKey?: string) =>
        apiRequest<import('@/types/api').AdminProductCreateResponse> ('admin/catalog/products', {
          method: 'POST',
          body: input,
          headers: idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : undefined,
          timeoutMs: 60_000,
        }),
      // MOBILE_PRODUCT_IMAGE_EXPO_FILE_TRANSPORT_V06
      uploadProductImages: (productId: number, files: import('@/types/api').AdminProductImageUploadFile[]) => {
        const body = new FormData();
        for (const file of files) {
          body.append('images[]', new File(file.uri));
        }
        return apiExpoMultipartRequest<import('@/types/api').AdminProductImageUploadResponse> (
          `admin/catalog/products/${productId}/images`,
          body,
        );
      },
    },
  },
  auth: {
    login: (input: { login: string; password: string; device_name: string }) =>
      apiRequest<AuthTokenResponse>('auth/token', { method: 'POST', auth: false, body: input }),
    google: (input: { id_token: string; device_name: string }) =>
      apiRequest<GoogleAuthResponse> ('auth/google', { method: 'POST', auth: false, body: input }),
    requestPasswordReset: (input: PasswordResetRequestInput) =>
      apiRequest<AuthRecoveryMessageResponse> ('auth/password/forgot', { method: 'POST', auth: false, body: input }),
    resetPassword: (input: PasswordResetInput) =>
      apiRequest<AuthRecoveryMessageResponse> ('auth/password/reset', { method: 'POST', auth: false, body: input }),
    customerActivationState: async (token: string) => {
      const response = await apiRequest<{ data: CustomerActivationState }> (`auth/customer-activation${queryString({ token })}`, { auth: false });
      return response.data;
    },
    activateAccount: (input: CustomerActivationInput) =>
      apiRequest<{ message: string; data: { activated: true } }> ('auth/customer-activation', { method: 'POST', auth: false, body: input }),
    logout: () => apiRequest<void> ('auth/token', { method: 'DELETE' }),
    bootstrap: async () => {
      const response = await apiRequest<{ data: BootstrapData }>('bootstrap');
      return response.data;
    }
  },
  account: {
    updateProfile: async (input: AccountProfileInput) => {
      const response = await apiRequest<{ data: User }>('me', {
        method: 'PATCH',
        body: input
      });
      return response.data;
    },
    changePassword: (input: AccountPasswordInput) =>
      apiRequest<AccountPasswordResponse>('me/password', {
        method: 'PUT',
        body: input
      }),
    notificationPreferences: async (input: Partial<NotificationPreferences>) => {
      const response = await apiRequest<{ data: NotificationPreferences }> ('me/notification-preferences', {
        method: 'PUT',
        body: input
      });
      return response.data;
    },
    sessions: async () => {
      const response = await apiRequest<{ data: AccountSessionsData }> ('me/sessions');
      return response.data;
    },
    revokeSession: (kind: 'api' | 'web', id: number) =>
      apiRequest<AccountSessionRevokeResponse> (`me/sessions/${kind}/${id}`, { method: 'DELETE' }),
    revokeOtherSessions: () =>
      apiRequest<AccountSessionRevokeResponse> ('me/sessions/others', { method: 'DELETE' })
  },
  catalog: {
    filters: async () => {
      const response = await apiRequest<{ data: CatalogFilters }>('catalog/filters');
      return response.data;
    },
    products: (params: {
      q?: string;
      stock?: string;
      sort?: string;
      page?: number;
      per_page?: number;
      brand_id?: number;
      product_type_id?: number;
      product_line_id?: number;
      category_id?: number;
    }) =>
      apiRequest<PaginatedResponse<Product>>(`products${queryString(params)}`),
    product: async (slug: string) => {
      const response = await apiRequest<{ data: Product }>(`products/${encodeURIComponent(slug)}`);
      return response.data;
    }
  },
  orders: {
    options: async () => {
      const response = await apiRequest<{ data: OrderOptions }>('orders/options');
      return response.data;
    },
    create: async (input: CreateOrderInput, idempotencyKey: string) => {
      const response = await apiRequest<{ data: Order }>('orders', {
        method: 'POST',
        headers: { 'Idempotency-Key': idempotencyKey },
        body: input
      });
      return response.data;
    },
    list: (page = 1) => apiRequest<PaginatedResponse<Order>>(`orders${queryString({ page })}`),
    assignedList: (page = 1) =>
      apiRequest<PaginatedResponse<Order>>(`orders/assigned${queryString({ page })}`),
    assignedDetail: async (id: number) => {
      const response = await apiRequest<{ data: Order }>(`orders/assigned/${id}`);
      return response.data;
    },
    detail: async (id: number) => {
      const response = await apiRequest<{ data: Order }>(`orders/${id}`);
      return response.data;
    },
    cancel: async (id: number, note?: string) => {
      const response = await apiRequest<{ data: Order }>(`orders/${id}/cancel`, {
        method: 'POST',
        body: { note: note || null }
      });
      return response.data;
    },
    postCreate: async (id: number) => {
      const response = await apiRequest<{ data: OrderPostCreate }>(`orders/${id}/post-create`);
      return response.data;
    },
    submitPaymentProof: async (id: number, input: SubmitOrderPaymentProofInput) => {
      const response = await apiRequest<{ message: string; data: OrderPaymentLedgerEntry }>(`orders/${id}/payments/proof`, {
        method: 'POST',
        body: orderPaymentProofFormData(input)
      });
      return response;
    },
    paymentProofPath: (orderId: number, paymentId: number) =>
      `/api/v1/orders/${orderId}/payments/${paymentId}/proof`,
    confirmationPdfPath: (orderId: number) =>
      `/api/v1/orders/${orderId}/documents/confirmation.pdf`,
    documentPdfPath: (orderId: number, documentId: number) =>
      `/api/v1/orders/${orderId}/documents/${documentId}.pdf`,
    deliveryProofPath: (orderId: number) =>
      `/api/v1/orders/${orderId}/delivery-proof`
  },
  afterSales: {
    list: (page = 1) =>
      apiRequest<PaginatedResponse<AfterSalesCaseSummary>>(`after-sales${queryString({ page })}`),
    detail: async (id: number) => {
      const response = await apiRequest<{ data: AfterSalesCase }>(`after-sales/${id}`);
      return response.data;
    },
    options: async (orderId: number) => {
      const response = await apiRequest<{ data: AfterSalesOptions }>(`orders/${orderId}/after-sales/options`);
      return response.data;
    },
    create: async (orderId: number, input: CreateAfterSalesCaseInput) => {
      const response = await apiRequest<{ data: AfterSalesCase }>(`orders/${orderId}/after-sales`, {
        method: 'POST',
        body: afterSalesCaseFormData(input)
      });
      return response.data;
    },
    message: async (caseId: number, input: CreateAfterSalesMessageInput) => {
      const response = await apiRequest<{ data: AfterSalesMessage }>(`after-sales/${caseId}/messages`, {
        method: 'POST',
        body: afterSalesMessageFormData(input)
      });
      return response.data;
    }
  },
  warranties: {
    list: (page = 1) =>
      apiRequest<PaginatedResponse<WarrantySummary>>(`warranties${queryString({ page })}`),
    detail: async (id: number) => {
      const response = await apiRequest<{ data: Warranty }>(`warranties/${id}`);
      return response.data;
    }
  },
  commissions: {
    list: (params: CommissionListParams = {}) =>
      apiRequest<CommissionListResponse>(`commissions${queryString(params)}`),
    detail: async (id: number) => {
      const response = await apiRequest<{ data: Commission }>(`commissions/${id}`);
      return response.data;
    }
  },
  notifications: {
    list: (unread = false) =>
      apiRequest<PaginatedResponse<BusinessNotification>>(`notifications${queryString({ unread, per_page: 60 })}`),
    read: async (id: string) => {
      const response = await apiRequest<{ data: BusinessNotification }>(`notifications/${id}/read`, { method: 'POST' });
      return response.data;
    },
    readAll: async () => {
      const response = await apiRequest<{ data: { marked_read: number; unread: number } }>('notifications/read-all', { method: 'POST' });
      return response.data;
    }
  },
  devices: {
    list: async () => {
      const response = await apiRequest<{ data: MobileDevice[] }>('devices');
      return response.data;
    },
    register: async (input: MobileDeviceInput) => {
      const response = await apiRequest<{ data: MobileDevice }>('devices', { method: 'POST', body: input });
      return response.data;
    },
    update: async (id: number, input: MobileDeviceUpdateInput) => {
      const response = await apiRequest<{ data: MobileDevice }>(`devices/${id}`, { method: 'PATCH', body: input });
      return response.data;
    },
    revoke: (id: number) => apiRequest<void>(`devices/${id}`, { method: 'DELETE' })
  }
};
