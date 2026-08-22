import { apiRequest } from '@/lib/api/client';

// MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
export type AdminCourierService = {
  id: number;
  name: string;
  tracking_url: string;
  sort_order: number;
  is_active: boolean;
  is_default: boolean;
};

export type AdminCourierInput = {
  name: string;
  tracking_url: string;
  sort_order: number;
  is_active: boolean;
  is_default: boolean;
};

type CourierListResponse = { data: AdminCourierService[] };
type CourierMutationResponse = { message: string; data: AdminCourierService };

export const apiAdminCouriers = {
  list: () => apiRequest<CourierListResponse> ('admin/couriers'),
  create: (input: AdminCourierInput) => apiRequest<CourierMutationResponse> ('admin/couriers', { method: 'POST', body: input }),
  update: (id: number, input: AdminCourierInput) => apiRequest<CourierMutationResponse> (`admin/couriers/${id}`, { method: 'PUT', body: input }),
};
