import { apiRequest, queryString } from '@/lib/api/client';

export type AdminUserGroupStatus = 'active' | 'inactive';
export type AdminUserGroupCategoryMode = 'all' | 'selected' | 'none';

export type AdminUserGroupPermission = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
};

export type AdminUserGroupCategory = {
  id: number;
  name: string;
};

export type AdminUserGroupRecord = {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  status: AdminUserGroupStatus;
  category_access_mode: AdminUserGroupCategoryMode;
  include_uncategorized: boolean;
  sort_order: number;
  users_count: number;
  can_delete: boolean;
  permissions: AdminUserGroupPermission[];
  categories: AdminUserGroupCategory[];
};

export type AdminUserGroupChoice<T extends string> = {
  value: T;
  label: string;
};

export type AdminUserGroupOptions = {
  permissions: AdminUserGroupPermission[];
  categories: AdminUserGroupCategory[];
  statuses: Array<AdminUserGroupChoice<AdminUserGroupStatus>>;
  category_access_modes: Array<AdminUserGroupChoice<AdminUserGroupCategoryMode>>;
};

export type AdminUserGroupCapabilities = {
  create: boolean;
  update: boolean;
  delete_empty: boolean;
};

export type AdminUserGroupListParams = {
  q?: string;
  status?: AdminUserGroupStatus;
};

export type AdminUserGroupListResponse = {
  data: AdminUserGroupRecord[];
  options: AdminUserGroupOptions;
  capabilities: AdminUserGroupCapabilities;
};

export type AdminUserGroupInput = {
  name: string;
  slug: string | null;
  description: string | null;
  status: AdminUserGroupStatus;
  category_access_mode: AdminUserGroupCategoryMode;
  include_uncategorized: boolean;
  sort_order: number;
  permissions: number[];
  categories: number[];
};

export type AdminUserGroupMutationResponse = {
  message: string;
  data: AdminUserGroupRecord;
};

export const apiAdminUserGroups = {
  list: (params: AdminUserGroupListParams = {}) =>
    apiRequest<AdminUserGroupListResponse>(`admin/user-groups${queryString(params)}`),
  create: (input: AdminUserGroupInput) =>
    apiRequest<AdminUserGroupMutationResponse>('admin/user-groups', { method: 'POST', body: input }),
  update: (groupId: number, input: AdminUserGroupInput) =>
    apiRequest<AdminUserGroupMutationResponse>(`admin/user-groups/${groupId}`, { method: 'PUT', body: input }),
  remove: (groupId: number) =>
    apiRequest<{ message: string }>(`admin/user-groups/${groupId}`, { method: 'DELETE' }),
};
