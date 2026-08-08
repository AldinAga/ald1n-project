import { apiRequest, queryString } from '@/lib/api/client';
import type {
  AuthTokenResponse,
  GoogleAuthResponse,
  BootstrapData,
  BusinessNotification,
  CatalogFilters,
  CreateOrderInput,
  MobileDevice,
  MobileDeviceInput,
  MobileDeviceUpdateInput,
  NotificationPreferences,
  Order,
  OrderOptions,
  PaginatedResponse,
  Product
} from '@/types/api';

export const api = {
  auth: {
    login: (input: { login: string; password: string; device_name: string }) =>
      apiRequest<AuthTokenResponse>('auth/token', { method: 'POST', auth: false, body: input }),
    google: (input: { id_token: string; device_name: string }) =>
      apiRequest<GoogleAuthResponse>('auth/google', { method: 'POST', auth: false, body: input }),
    logout: () => apiRequest<void>('auth/token', { method: 'DELETE' }),
    bootstrap: async () => {
      const response = await apiRequest<{ data: BootstrapData }>('bootstrap');
      return response.data;
    }
  },
  account: {
    notificationPreferences: async (input: Partial<NotificationPreferences>) => {
      const response = await apiRequest<{ data: NotificationPreferences }>('me/notification-preferences', {
        method: 'PUT',
        body: input
      });
      return response.data;
    }
  },
  catalog: {
    filters: async () => {
      const response = await apiRequest<{ data: CatalogFilters }>('catalog/filters');
      return response.data;
    },
    products: (params: { q?: string; stock?: string; sort?: string; page?: number; per_page?: number }) =>
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
