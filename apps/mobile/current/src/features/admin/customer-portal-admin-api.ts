import { apiRequest, queryString } from '@/lib/api/client';

export type AdminCustomer360Summary = {
  orders_count: number;
  lifetime_revenue_rsd: number;
  average_order_value_rsd: number;
  outstanding_rsd: number;
  last_purchase_at: string | null;
  active_after_sales_count: number;
  active_warranties_count: number;
  open_conversations_count: number;
  unread_staff_messages_count: number;
};

export type AdminCustomer360TimelineItem = {
  type: 'order' | 'order_link' | 'crm_note' | 'after_sales' | 'conversation';
  occurred_at: string | null;
  title: string;
  summary: string | null;
  order_id: number | null;
  conversation_id: number | null;
  after_sales_case_id: number | null;
  actor: { id: number; name: string } | null;
};

export type AdminCustomerCrmNote = {
  id: number;
  body: string;
  created_at: string | null;
  author: { id: number; name: string } | null;
};

export type AdminCustomerCrmNoteCreateRequest = {
  body: string;
};

export type AdminCustomerUnlinkedBuyer = {
  id: number;
  number: string;
  status: string;
  name: string | null;
  email: string | null;
  phone: string | null;
  subtotal_rsd: number;
  created_at: string | null;
};
export type AdminPortalCustomer = {
  id: number;
  name: string;
  username: string;
  email: string | null;
  phone: string | null;
  status: string;
  group: { id: number; name: string } | null;
  orders_count: number;
  conversations_count: number;
  activation: {
    expires_at: string | null;
    used_at: string | null;
    valid: boolean;
  } | null;
};

export type AdminPortalConversationSummary = {
  id: number;
  subject: string;
  status: string;
  status_label: string;
  priority: string;
  customer: { id: number; name: string; email: string | null } | null;
  order: { id: number; order_number: string } | null;
  assignee: { id: number; name: string } | null;
  unread_staff_count: number;
  last_message_at: string | null;
};

export type AdminPortalIndex = {
  users: AdminPortalCustomer[];
  conversations: AdminPortalConversationSummary[];
  stats: {
    pending_users: number;
    active_users: number;
    open_conversations: number;
    unread_messages: number;
  };
  status_labels: Record<string, string>;
};

export type AdminPortalOrder = {
  id: number;
  order_number: string;
  status: string;
  shipping_full_name: string | null;
  shipping_phone: string | null;
  subtotal_rsd: number;
  owner?: { id: number; name: string } | null;
};

export type AdminPortalUserDetail = {
  customer: AdminPortalCustomer;
  customer_360: {
    summary: AdminCustomer360Summary;
    timeline: AdminCustomer360TimelineItem[];
    crm_notes: AdminCustomerCrmNote[];
  };
  orders: AdminPortalOrder[];
  order_search: AdminPortalOrder[];
  active_web_sessions: Array<{
    id: number;
    device_label: string;
    ip_address: string | null;
    remembered: boolean;
    logged_in_at: string | null;
    last_seen_at: string | null;
  }>;
  conversations: AdminPortalConversationSummary[];
  status_labels: Record<string, string>;
};

export type AdminPortalConversationDetail = {
  id: number;
  subject: string;
  status: string;
  status_label: string;
  priority: string;
  customer: { id: number; name: string; email: string | null } | null;
  order: { id: number; order_number: string; status: string } | null;
  assignee: { id: number; name: string } | null;
  messages: Array<{
    id: number;
    body: string;
    visibility: 'public' | 'internal';
    sender: { id: number; name: string };
    sent_at: string | null;
  }>;
  staff_options: Array<{ id: number; name: string; role: string | null }>;
  status_labels: Record<string, string>;
  priority_labels: Record<string, string>;
  last_message_at: string | null;
};

export const apiAdminCustomerPortal = {
  index: async (params: { q?: string; status?: string } = {}) => {
    const response = await apiRequest<{ data: AdminPortalIndex }> (
      `admin/customer-portal${queryString(params)}`,
    );
    return response.data;
  },
  createCustomer: (input: {
    email: string;
    first_name: string;
    last_name?: string | null;
    phone?: string | null;
    address?: string | null;
    city?: string | null;
    postal_code?: string | null;
  }) => apiRequest<{
    message: string;
    data: AdminPortalCustomer;
    invitation_sent: boolean;
    invitation_error: string | null;
  }> ('admin/customer-portal/users', { method: 'POST', body: input }),
  user: async (id: number, orderQ = '') => {
    const response = await apiRequest<{ data: AdminPortalUserDetail }> (
      `admin/customer-portal/users/${id}${queryString({ order_q: orderQ || undefined })}`,
    );
    return response.data;
  },
  unlinkedBuyers: async (q = '') => {
    const response = await apiRequest<{ data: AdminCustomerUnlinkedBuyer[] }> (
      `admin/customer-portal/unlinked-buyers${queryString({ q: q || undefined })}`,
    );
    return response.data;
  },
  appendCrmNote: async (userId: number, body: string) => {
    const response = await apiRequest<{ message: string; data: AdminCustomerCrmNote }> (
      `admin/customer-portal/users/${userId}/crm-notes`,
      { method: 'POST', body: { body } },
    );
    return response.data;
  },  invite: (id: number) =>
    apiRequest<{ message: string; data: { expires_at: string | null } }> (
      `admin/customer-portal/users/${id}/invite`,
      { method: 'POST' },
    ),
  linkOrder: (id: number, input: {
    order_id: number;
    reason: string;
    confirm_reassign: boolean;
    move_related_portal_data: boolean;
  }) => apiRequest<{
    message: string;
    data: {
      changed: boolean;
      order: AdminPortalOrder;
      warranties_updated: number;
      conversations_updated: number;
    };
  }> (`admin/customer-portal/users/${id}/orders/link`, { method: 'POST', body: input }),
  revokeSessions: (id: number) =>
    apiRequest<{
      message: string;
      data: {
        revoked_web_sessions: number;
        revoked_api_sessions: number;
        revoked_mobile_devices: number;
      };
    }> (`admin/customer-portal/users/${id}/sessions`, { method: 'DELETE' }),
  conversation: async (id: number) => {
    const response = await apiRequest<{ data: AdminPortalConversationDetail }> (
      `admin/customer-portal/conversations/${id}`,
    );
    return response.data;
  },
  reply: async (id: number, input: {
    body: string;
    visibility: 'public' | 'internal';
    status?: string | null;
  }) => {
    const response = await apiRequest<{ data: AdminPortalConversationDetail }> (
      `admin/customer-portal/conversations/${id}/reply`,
      { method: 'POST', body: input },
    );
    return response.data;
  },
  updateConversation: async (id: number, input: {
    status: string;
    priority: string;
    assigned_to: number | null;
  }) => {
    const response = await apiRequest<{ data: AdminPortalConversationDetail }> (
      `admin/customer-portal/conversations/${id}`,
      { method: 'PATCH', body: input },
    );
    return response.data;
  },
};
