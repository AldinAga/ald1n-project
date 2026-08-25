import { apiRequest } from '@/lib/api/client';

export type PortalOrderOption = {
  id: number;
  order_number: string;
  status: string;
  subtotal_rsd: number;
};

export type PortalMessage = {
  id: number;
  body: string;
  visibility: 'public';
  sender: { id: number; name: string; is_me: boolean };
  sent_at: string | null;
};

export type PortalConversationSummary = {
  id: number;
  subject: string;
  status: string;
  status_label: string;
  priority: string;
  order: { id: number; order_number: string } | null;
  unread_count: number;
  latest_message: PortalMessage | null;
  last_message_at: string | null;
  created_at: string | null;
};

export type PortalConversationDetail = {
  id: number;
  subject: string;
  status: string;
  status_label: string;
  priority: string;
  order: { id: number; order_number: string; status: string } | null;
  messages: PortalMessage[];
  can_reply: boolean;
  last_message_at: string | null;
  created_at: string | null;
};

export type PortalInboxResponse = {
  data: PortalConversationSummary[];
  orders: PortalOrderOption[];
  status_labels: Record<string, string>;
};

export const apiPortal = {
  list: () => apiRequest<PortalInboxResponse> ('portal/messages'),
  create: (input: { subject: string; body: string; order_id: number | null }) =>
    apiRequest<{ data: PortalConversationDetail }> ('portal/messages', {
      method: 'POST',
      body: input,
    }).then((response) => response.data),
  detail: (id: number) =>
    apiRequest<{ data: PortalConversationDetail }> (`portal/messages/${id}`)
      .then((response) => response.data),
  reply: (id: number, body: string) =>
    apiRequest<{ data: PortalConversationDetail }> (`portal/messages/${id}`, {
      method: 'POST',
      body: { body },
    }).then((response) => response.data),
};
