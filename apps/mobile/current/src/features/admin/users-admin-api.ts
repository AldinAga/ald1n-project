import { apiRequest, queryString } from '@/lib/api/client';

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
export type AdminUserStatus = 'pending' | 'active' | 'blocked';
export type AdminUsersPerPage = 20 | 50 | 100;

export type AdminUserRole = {
  id: number;
  name: string;
  slug: string;
};

export type AdminUserGroup = {
  id: number;
  name: string;
  slug: string;
  status: string;
};

export type AdminUser = {
  id: number;
  username: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  name: string;
  phone: string | null;
  status: AdminUserStatus;
  role: AdminUserRole | null;
  group: AdminUserGroup | null;
  effective_access: 'full' | 'group' | 'restricted';
  last_login_at: string | null;
  password_changed_at: string | null;
  approved_at: string | null;
  last_active_superadmin_protected: boolean;
};

export type AdminUserStatusOption = {
  value: AdminUserStatus;
  label: string;
};

export type AdminUserOptionsData = {
  roles: AdminUserRole[];
  groups: AdminUserGroup[];
  statuses: AdminUserStatusOption[];
  per_page: AdminUsersPerPage[];
};

export type AdminUserCapabilities = {
  create: boolean;
  update: boolean;
  password_management: boolean;
  delete: false;
};

export type AdminUserPagination = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type AdminUserListParams = {
  q?: string;
  status?: AdminUserStatus;
  page?: number;
  per_page?: AdminUsersPerPage;
};

export type AdminUserListResponse = {
  data: AdminUser[];
  pagination: AdminUserPagination;
  filters: {
    q: string | null;
    status: AdminUserStatus | null;
    page: number;
    per_page: number;
  };
  filter_options: AdminUserOptionsData;
  capabilities: AdminUserCapabilities;
};

export type AdminUserOptionsResponse = {
  data: AdminUserOptionsData;
  capabilities: AdminUserCapabilities;
};

export type AdminUserDetailResponse = {
  data: AdminUser;
  capabilities: AdminUserCapabilities;
};

export type AdminUserInput = {
  role_id: number;
  user_group_id: number | null;
  username: string;
  email: string;
  first_name: string | null;
  last_name: string | null;
  phone: string | null;
  status: AdminUserStatus;
  password?: string;
};

export type AdminUserCreateInput = AdminUserInput & {
  password: string;
};

export type AdminUserMutationResponse = {
  message: string;
  data: AdminUser;
  password_changed: boolean;
  reauthenticate: boolean;
};

export const apiAdminUsers = {
  list: (params: AdminUserListParams = {}) =>
    apiRequest<AdminUserListResponse> (`admin/users${queryString(params)}`),
  options: () => apiRequest<AdminUserOptionsResponse> ('admin/users/options'),
  detail: (userId: number) => apiRequest<AdminUserDetailResponse> (`admin/users/${userId}`),
  create: (input: AdminUserCreateInput) =>
    apiRequest<AdminUserMutationResponse> ('admin/users', { method: 'POST', body: input }),
  update: (userId: number, input: AdminUserInput) =>
    apiRequest<AdminUserMutationResponse> (`admin/users/${userId}`, { method: 'PUT', body: input }),
};
