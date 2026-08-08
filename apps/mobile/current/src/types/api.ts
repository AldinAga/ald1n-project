export type Nullable<T> = T | null;

export type ApiErrorEnvelope = {
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  request_id?: string;
};

export type LaravelPaginationMeta = {
  current_page: number;
  from: number | null;
  last_page: number;
  path: string;
  per_page: number;
  to: number | null;
  total: number;
};

export type PaginatedResponse<T> = {
  data: T[];
  links?: Record<string, string | null> | Array<{ url: string | null; label: string; active: boolean }>;
  meta?: LaravelPaginationMeta;
};

export type UserSummary = {
  id: number;
  username: string;
  email: Nullable<string>;
  name: string;
  role: Nullable<string>;
  group: Nullable<string>;
  status: string;
};

export type User = {
  id: number;
  username: string;
  email: Nullable<string>;
  name: string;
  first_name: Nullable<string>;
  last_name: Nullable<string>;
  phone: Nullable<string>;
  address: Nullable<string>;
  city: Nullable<string>;
  postal_code: Nullable<string>;
  status: string;
  role: Nullable<{ id: number; name: string; slug: string }>;
  group: Nullable<{ id: number; name: string; slug: string }>;
};

export type NotificationPreferences = {
  in_app_enabled: boolean;
  email_enabled: boolean;
  push_enabled: boolean;
  order_updates: boolean;
  payment_alerts: boolean;
  document_updates: boolean;
  after_sales_updates: boolean;
  warranty_updates: boolean;
  service_updates: boolean;
  receivable_updates: boolean;
  commission_updates: boolean;
  stock_alerts: boolean;
  daily_digest: boolean;
};

export type PlatformVersion = {
  minimum_supported_version: string;
  latest_version: string;
  store_url: Nullable<string>;
};

export type BootstrapData = {
  user: User;
  permissions: string[];
  features: Record<string, boolean>;
  notification_counts: { unread: number };
  notification_preferences: NotificationPreferences;
  app: {
    name: string;
    backend_version: string;
    api_version: string;
    timezone: string;
    locale: string;
    android: PlatformVersion;
    ios: PlatformVersion;
  };
};

export type AuthTokenResponse = {
  token: string;
  token_type: 'Bearer';
  expires_at: Nullable<string>;
  user: UserSummary;
  permissions: string[];
  api_version: 'v1';
};


export type GoogleAuthPendingResponse = {
  status: 'pending';
  message: string;
  user: UserSummary;
};

export type GoogleAuthResponse = AuthTokenResponse | GoogleAuthPendingResponse;


export type Taxonomy = { id: number; name: string; slug: string };
export type Price = { amount: number; currency: string };
export type ProductImage = { id: number; url: string; primary: boolean };
export type ProductSpecification = {
  field: Nullable<string>;
  slug: Nullable<string>;
  value: string | number | boolean | null;
  detail: Nullable<string>;
  unit: Nullable<string>;
};
export type ProductVariant = {
  id: number;
  sku: string;
  name: string;
  default: boolean;
  stock_quantity: number;
  price: Nullable<Price>;
  commission_eur: number;
  images: ProductImage[];
  specifications: ProductSpecification[];
};
export type Product = {
  id: number;
  sku: string;
  name: string;
  slug: string;
  description?: Nullable<string>;
  brand: Nullable<Taxonomy>;
  line: Nullable<Taxonomy>;
  model: Nullable<string>;
  type: Nullable<Taxonomy>;
  categories?: Taxonomy[];
  price?: Nullable<Price>;
  commission_eur: number;
  stock_quantity: number;
  primary_image_url: Nullable<string>;
  images?: ProductImage[];
  variants_enabled: boolean;
  variants?: ProductVariant[];
  specifications?: ProductSpecification[];
  updated_at: Nullable<string>;
};

export type CatalogFilterOption = { value: string; label: string };
export type CatalogFilters = {
  brands: Taxonomy[];
  types: Taxonomy[];
  lines: Array<Taxonomy & { brand_id: number }>;
  categories: Taxonomy[];
  specification_fields: Array<{
    id: number;
    name: string;
    slug: string;
    filter_type: string;
    unit: Nullable<string>;
    min_value: Nullable<number>;
    max_value: Nullable<number>;
    parent_field_id: Nullable<number>;
    detail_input_enabled: boolean;
    detail_label: Nullable<string>;
    product_type_ids: number[];
    options: Array<{ id: number; label: string; value: string; parent_option_ids: number[] }>;
  }>;
  stock_filters: CatalogFilterOption[];
  sort_options: CatalogFilterOption[];
  management_filters: Nullable<{ statuses: string[]; quality: string[] }>;
};

export type OrderItem = {
  id: number;
  product_id: number;
  product_variant_id: Nullable<number>;
  sku: string;
  name: string;
  variant_name: Nullable<string>;
  variant_attributes: Record<string, unknown> | unknown[];
  quantity: number;
  unit_price_rsd: number;
  line_total_rsd: number;
  commission_total_eur: number;
};
export type Order = {
  id: number;
  order_number: string;
  source_system: string;
  status: string;
  workflow_status: string;
  completed_at: Nullable<string>;
  completed_by: Nullable<number>;
  completion_note: Nullable<string>;
  inventory_state: Nullable<string>;
  payment_method: string;
  payment_status: string;
  subtotal_rsd: number;
  supplier: { id: Nullable<number>; name: Nullable<string>; email: Nullable<string>; phone: Nullable<string>; role: Nullable<string> };
  shipping: { full_name: string; address: string; city: string; postal_code: string; phone: string };
  tracking_number: Nullable<string>;
  items?: OrderItem[];
  commission?: Nullable<{ total_eur: number; status: string }>;
  created_at: Nullable<string>;
  updated_at: Nullable<string>;
};

export type BusinessNotification = {
  id: string;
  event: Nullable<string>;
  title: string;
  message: Nullable<string>;
  icon: Nullable<string>;
  severity: 'info' | 'success' | 'warning' | 'danger' | string;
  action_label: Nullable<string>;
  route: Nullable<string>;
  target: Nullable<{ type: string; id: number }>;
  data: Record<string, unknown>;
  read: boolean;
  read_at: Nullable<string>;
  created_at: Nullable<string>;
};

export type MobileDevice = {
  id: number;
  installation_id: string;
  platform: 'android' | 'ios';
  device_name: Nullable<string>;
  push_provider: Nullable<'expo' | 'fcm' | 'apns'>;
  push_registered: boolean;
  app_version: Nullable<string>;
  build_number: Nullable<string>;
  locale: Nullable<string>;
  timezone: Nullable<string>;
  notifications_enabled: boolean;
  is_current: boolean;
  active: boolean;
  last_seen_at: Nullable<string>;
  revoked_at: Nullable<string>;
  created_at: Nullable<string>;
  updated_at: Nullable<string>;
};

export type MobileDeviceInput = {
  installation_id: string;
  platform: 'android' | 'ios';
  device_name?: Nullable<string>;
  push_provider?: Nullable<'expo' | 'fcm' | 'apns'>;
  push_token?: Nullable<string>;
  app_version?: Nullable<string>;
  build_number?: Nullable<string>;
  locale?: Nullable<string>;
  timezone?: Nullable<string>;
  notifications_enabled?: boolean;
};

export type MobileDeviceUpdateInput = {
  device_name?: Nullable<string>;
  push_provider?: Nullable<'expo' | 'fcm' | 'apns'>;
  push_token?: Nullable<string>;
  app_version?: Nullable<string>;
  build_number?: Nullable<string>;
  locale?: Nullable<string>;
  timezone?: Nullable<string>;
  notifications_enabled?: boolean;
};

export type PaymentMethod = 'cash_on_delivery' | 'bank_transfer';

export type OrderPaymentMethodOption = {
  value: PaymentMethod;
  label: string;
  requires_bank_account: boolean;
};

export type OrderBankAccountOption = {
  id: number;
  label: string;
  recipient_name: string;
  recipient_address: Nullable<string>;
  account_number: string;
  payment_code: Nullable<string>;
};

export type OrderSupplierOption = {
  id: number;
  name: string;
  email: Nullable<string>;
  phone: Nullable<string>;
  role: Nullable<string>;
};

export type OrderOptions = {
  idempotency_key: string;
  payment_methods: OrderPaymentMethodOption[];
  bank_accounts: OrderBankAccountOption[];
  suppliers: OrderSupplierOption[];
  shipping_defaults: {
    full_name: string;
    address: Nullable<string>;
    city: Nullable<string>;
    postal_code: Nullable<string>;
    phone: Nullable<string>;
  };
  limits: {
    max_items: number;
    max_quantity_per_item: number;
    customer_note_max_length: number;
  };
};

export type CreateOrderItemInput = {
  product_id: number;
  product_variant_id: Nullable<number>;
  quantity: number;
};

export type CreateOrderInput = {
  supplier_user_id: Nullable<number>;
  shipping_full_name: string;
  shipping_address: string;
  shipping_city: string;
  shipping_postal_code: string;
  shipping_phone: string;
  customer_note: Nullable<string>;
  payment_method: PaymentMethod;
  bank_account_id: Nullable<number>;
  items: CreateOrderItemInput[];
};
