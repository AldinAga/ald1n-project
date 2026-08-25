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

export type AccountProfileInput = {
  first_name?: string;
  last_name?: Nullable<string>;
  phone?: Nullable<string>;
  address?: Nullable<string>;
  city?: Nullable<string>;
  postal_code?: Nullable<string>;
};

export type AccountPasswordInput = {
  current_password: string;
  password: string;
  password_confirmation: string;
};

export type AccountPasswordResponse = {
  message: string;
  reauthenticate: true;
};

// MOBILE_V1_0_AUTH_ACCOUNT_SECURITY_PARITY_BATCH32
export type PasswordResetRequestInput = { email: string };
export type PasswordResetInput = { token: string; password: string; password_confirmation: string };
export type AuthRecoveryMessageResponse = { message: string; reauthenticate?: true };
export type CustomerActivationState = { valid: boolean; expires_in_hours: number };
export type CustomerActivationInput = { token: string; password: string; password_confirmation: string };
export type AccountApiSession = {
  id: number;
  kind: 'api';
  device_name: string;
  platform: Nullable<'android' | 'ios'>;
  app_version: Nullable<string>;
  last_seen_at: Nullable<string>;
  created_at: Nullable<string>;
  expires_at: Nullable<string>;
  is_current: boolean;
};
export type AccountWebSession = {
  id: number;
  kind: 'web';
  device_label: string;
  ip_address: Nullable<string>;
  remembered: boolean;
  logged_in_at: Nullable<string>;
  last_seen_at: Nullable<string>;
};
export type AccountSessionsData = {
  api_sessions: AccountApiSession[];
  web_sessions: AccountWebSession[];
  capabilities: { revoke_others: boolean };
};
export type AccountSessionRevokeData = {
  kind: 'api' | 'web' | 'others';
  id?: number;
  reauthenticate: boolean;
  revoked_api_sessions?: number;
  revoked_web_sessions?: number;
};
export type AccountSessionRevokeResponse = { message: string; data: AccountSessionRevokeData };

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
  sku: string;
  name: string;
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

export type OrderPrivateFile = {
  original_name: string;
  mime_type: string;
  size_bytes: number;
};

export type OrderPaymentLedgerEntry = {
  id: number;
  number: string;
  entry_type: 'payment' | 'refund' | string;
  entry_label: string;
  status: string;
  amount_rsd: number;
  payment_method: string;
  paid_at: Nullable<string>;
  rejection_reason: Nullable<string>;
  has_proof: boolean;
  proof: Nullable<OrderPrivateFile>;
};

export type OrderDocumentSummary = {
  id: number;
  number: string;
  type: string;
  revision_number: number;
  status: 'issued';
  issued_at: Nullable<string>;
  due_at: Nullable<string>;
  currency: string;
  total_rsd: number;
};

export type OrderDeliverySummary = {
  id: number;
  delivery_method: string;
  delivery_method_label: string;
  delivered_at: Nullable<string>;
  recipient_name: Nullable<string>;
  recipient_phone: Nullable<string>;
  reference: Nullable<string>;
  note: Nullable<string>;
  has_proof: boolean;
  proof: Nullable<OrderPrivateFile>;
};

export type OrderBankTransferSnapshot = {
  account_label: Nullable<string>;
  account_number: Nullable<string>;
  recipient_name: Nullable<string>;
  recipient_address: Nullable<string>;
  payment_code: Nullable<string>;
  purpose: Nullable<string>;
  reference: Nullable<string>;
};

export type OrderPostCreateCapabilities = {
  can_view_payments: boolean;
  can_upload_payment_proof: boolean;
  can_view_documents: boolean;
  can_issue_order_confirmation: boolean;
  can_view_delivery_proof: boolean;
};

export type OrderPaymentProofLimits = {
  max_bytes: number;
  extensions: string[];
  mime_types: string[];
};

export type OrderPostCreate = {
  order: {
    id: number;
    order_number: string;
    status: string;
    payment_method: string;
    payment_status: string;
    payment_state: string;
    subtotal_rsd: number;
    paid_total_rsd: number;
    remaining_rsd: number;
    payment_due_at: Nullable<string>;
    tracking_number: Nullable<string>;
    completed_at: Nullable<string>;
    completion_note: Nullable<string>;
  };
  bank_transfer: Nullable<OrderBankTransferSnapshot>;
  payments: OrderPaymentLedgerEntry[];
  documents: OrderDocumentSummary[];
  delivery: Nullable<OrderDeliverySummary>;
  capabilities: OrderPostCreateCapabilities;
  payment_proof_limits: OrderPaymentProofLimits;
};

export type OrderPaymentProofUploadFile = {
  uri: string;
  name: string;
  type: string;
  size?: number;
};

export type SubmitOrderPaymentProofInput = {
  amount_rsd: number;
  paid_at: string;
  reference?: Nullable<string>;
  note?: Nullable<string>;
  proof: OrderPaymentProofUploadFile;
};
export type AfterSalesCaseType = 'complaint' | 'return' | 'service';
export type AfterSalesPriority = 'low' | 'normal' | 'high' | 'urgent';
export type AfterSalesStatus = 'open' | 'under_review' | 'awaiting_customer' | 'approved' | 'in_service' | 'resolved' | 'rejected' | 'closed';
export type AfterSalesResolution = 'repair' | 'replacement' | 'partial_refund' | 'full_refund' | 'return' | 'inspection' | 'rejected' | 'other';
export type AfterSalesRequestedResolution = Exclude<AfterSalesResolution, 'rejected'>;

export type AfterSalesCaseSummary = {
  id: number;
  case_number: string;
  order: { id: number; order_number: string };
  case_type: AfterSalesCaseType;
  case_type_label: string;
  priority: AfterSalesPriority;
  priority_label: string;
  status: AfterSalesStatus;
  status_label: string;
  subject: string;
  assignee: Nullable<{ id: number; name: string }>;
  due_at: Nullable<string>;
  can_message: boolean;
  created_at: Nullable<string>;
  updated_at: Nullable<string>;
};

export type AfterSalesAttachment = {
  id: number;
  original_name: string;
  mime_type: string;
  size_bytes: number;
  download_path: string;
  created_at: Nullable<string>;
};

export type AfterSalesMessage = {
  id: number;
  body: string;
  author: Nullable<{ id: number; name: string; kind: 'staff' | 'customer' }>;
  attachments: AfterSalesAttachment[];
  created_at: Nullable<string>;
};

export type AfterSalesAction = {
  id: number;
  action_number: string;
  action_type: string;
  action_type_label: string;
  status: string;
  status_label: string;
  scheduled_at: Nullable<string>;
  due_at: Nullable<string>;
  amount_rsd: Nullable<number>;
  reference: Nullable<string>;
  public_note: Nullable<string>;
  completion_note: Nullable<string>;
  items: Array<{ id: number; name: string; quantity: number }>;
  work_order: Nullable<{
    id: number;
    work_order_number: string;
    status: string;
    status_label: string;
    planned_start_at: Nullable<string>;
    planned_end_at: Nullable<string>;
    completion_result: Nullable<string>;
    team: Nullable<{ id: number; name: string; phone: Nullable<string> }>;
    attachments: AfterSalesAttachment[];
  }>;
};

export type AfterSalesCase = AfterSalesCaseSummary & {
  description: string;
  requested_resolution: Nullable<AfterSalesRequestedResolution>;
  requested_resolution_label: Nullable<string>;
  resolution_type: Nullable<AfterSalesResolution>;
  resolution_type_label: Nullable<string>;
  resolution_summary: Nullable<string>;
  first_response_at: Nullable<string>;
  resolved_at: Nullable<string>;
  closed_at: Nullable<string>;
  limits: AfterSalesOptions['limits'];
  items: Array<{
    id: number;
    order_item_id: Nullable<number>;
    product_id: Nullable<number>;
    sku: string;
    name: string;
    quantity: number;
    issue_description: Nullable<string>;
  }>;
  actions: AfterSalesAction[];
  messages: AfterSalesMessage[];
  attachments: AfterSalesAttachment[];
};

export type AfterSalesOrderItemOption = {
  id: number;
  product_id: Nullable<number>;
  sku: string;
  name: string;
  quantity: number;
};

export type AfterSalesOptions = {
  order: {
    id: number;
    order_number: string;
    shipping: {
      full_name: string;
      address: string;
      city: string;
      postal_code: string;
      phone: string;
    };
    items: AfterSalesOrderItemOption[];
  };
  case_types: Record<AfterSalesCaseType, string>;
  priorities: Record<AfterSalesPriority, string>;
  requested_resolutions: Record<AfterSalesRequestedResolution, string>;
  defaults: { priority: AfterSalesPriority };
  limits: {
    subject_max_length: number;
    description_min_length: number;
    description_max_length: number;
    issue_description_max_length: number;
    message_max_length: number;
    max_attachments: number;
    max_attachment_bytes: number;
    attachment_mime_types: string[];
  };
};

export type AfterSalesUploadFile = {
  uri: string;
  name: string;
  type: string;
  size?: number;
};

declare global {
  interface FormData {
    append(name: string, value: Pick<AfterSalesUploadFile, 'uri' | 'name' | 'type'>): void;
  }
}

export type CreateAfterSalesCaseItemInput = {
  order_item_id: number;
  quantity: number;
  issue_description?: Nullable<string>;
};

export type CreateAfterSalesCaseInput = {
  case_type: AfterSalesCaseType;
  priority: AfterSalesPriority;
  subject: string;
  description: string;
  requested_resolution?: Nullable<AfterSalesRequestedResolution>;
  items: CreateAfterSalesCaseItemInput[];
  attachments?: AfterSalesUploadFile[];
};

export type CreateAfterSalesMessageInput = {
  body: string;
  attachments?: AfterSalesUploadFile[];
};
export type WarrantyStatus = 'active' | 'expired' | 'void';
export type WarrantyMaintenanceStatus = 'due' | 'scheduled' | 'completed' | 'cancelled';

export type WarrantySummary = {
  id: number;
  warranty_number: string;
  order: { id: number; order_number: string };
  status: WarrantyStatus;
  status_label: string;
  product_name: string;
  product_sku: Nullable<string>;
  starts_at: Nullable<string>;
  expires_at: Nullable<string>;
  next_maintenance_at: Nullable<string>;
  created_at: Nullable<string>;
  updated_at: Nullable<string>;
};

export type WarrantyMaintenanceRecord = {
  id: number;
  status: WarrantyMaintenanceStatus;
  status_label: string;
  due_at: Nullable<string>;
  scheduled_at: Nullable<string>;
  completed_at: Nullable<string>;
  result: Nullable<string>;
};

export type Warranty = WarrantySummary & {
  quantity: number;
  serial_numbers: string[];
  duration_months: Nullable<number>;
  duration_days: Nullable<number>;
  maintenance_interval_months: Nullable<number>;
  last_maintenance_at: Nullable<string>;
  terms: Nullable<string>;
  void_reason: Nullable<string>;
  maintenance_records: WarrantyMaintenanceRecord[];
};
export type CommissionStatus = 'pending' | 'approved' | 'paid' | 'cancelled';
export type CommissionPaymentMethod = 'bank_transfer' | 'cash' | 'other';

export type CommissionPayment = {
  method: Nullable<CommissionPaymentMethod>;
  method_label: Nullable<string>;
  reference: Nullable<string>;
  paid_at: Nullable<string>;
};

export type Commission = {
  id: number;
  order: { id: number; order_number: string };
  total_eur: number;
  status: CommissionStatus;
  status_label: string;
  status_note: Nullable<string>;
  responsible_name: string;
  payment: Nullable<CommissionPayment>;
  status_updated_at: Nullable<string>;
  created_at: Nullable<string>;
  updated_at: Nullable<string>;
};

export type CommissionTotals = {
  pending_eur: number;
  approved_eur: number;
  paid_eur: number;
  count: number;
};

export type CommissionListParams = {
  q?: string;
  status?: CommissionStatus;
  date_from?: string;
  date_to?: string;
  page?: number;
};

export type CommissionListResponse = PaginatedResponse<Commission> & {
  summary: CommissionTotals;
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

// MOBILE_V0_8_DEFERRED_PAYMENT_RECEIVABLES_BATCH4
export type PaymentMethod = 'cash_on_delivery' | 'bank_transfer' | 'deferred_payment';

export type OrderPaymentMethodOption = {
  value: PaymentMethod;
  label: string;
  requires_bank_account: boolean;
  requires_due_date: boolean;
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
  payment_due_at: Nullable<string>;
  items: CreateOrderItemInput[];
};

// MOBILE_ADMIN_PRODUCT_CREATE_TYPES_V06
// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2_TYPES_V06
// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2B_TYPES_V06
export type AdminCatalogSpecificationOption = {
  id: number;
  label: string;
  value: string;
  parent_option_ids: number[];
};

export type AdminCatalogSpecificationField = {
  id: number;
  name: string;
  slug: string;
  data_type: string;
  filter_type: string | null;
  unit: string | null;
  min_value: number | null;
  max_value: number | null;
  parent_field_id: number | null;
  detail_input_enabled: boolean;
  detail_label: string | null;
  required: boolean;
  default_value: string | number | boolean | null;
  read_only_derived: boolean;
  storage_repeater: {
    enabled: true;
    total_field_id: number;
    total_field_name: string;
    total_unit: string | null;
    max_items: number;
    capacity_unit: 'GB';
  } | null;
  options: AdminCatalogSpecificationOption[];
};

export type AdminCatalogTypeOption = {
  id: number;
  name: string;
  category_id: number | null;
  category_name: string | null;
  auto_name_enabled: boolean;
  fields: AdminCatalogSpecificationField[];
};

export type AdminCatalogBrandOption = {
  id: number;
  name: string;
  product_type_ids: number[];
};

export type AdminCatalogLineOption = {
  id: number;
  name: string;
  brand_id: number | null;
  product_type_ids: number[];
};

export type AdminProductImageLimits = {
  max_files: number;
  max_bytes: number;
  mime_types: string[];
  extensions: string[];
};

export type AdminCatalogCreateOptions = {
  types: AdminCatalogTypeOption[];
  brands: AdminCatalogBrandOption[];
  lines: AdminCatalogLineOption[];
  defaults: {
    status: 'draft';
    price_currency: 'EUR';
    stock_quantity: 0;
    low_stock_threshold: 1;
  };
  currencies: Array<{ value: 'EUR' | 'RSD'; label: string }>;
  statuses: Array<{ value: 'draft' | 'active' | 'inactive'; label: string }>;
  capabilities: {
    advanced_specifications: boolean;
    image_upload: boolean;
    specialized_storage_repeater: boolean;
  };
  image_limits: AdminProductImageLimits;
};

export type AdminProductCreateInput = {
  product_type_id?: number;
  brand_id?: number;
  product_line_id?: number;
  model_name?: string;
  name?: string;
  regenerate_sku?: boolean;
  regenerate_name?: boolean;
  price_amount: number;
  price_currency: 'EUR' | 'RSD';
  purchase_price_rsd?: number;
  manual_commission_eur?: number;
  description: string;
  notes?: string;
  stock_quantity: number;
  low_stock_threshold: number;
  status: 'draft' | 'active' | 'inactive';
  category_ids?: number[];
  specs?: Record<string, string | number | boolean>;
  spec_details?: Record<string, string>;
  spec_lists?: Record<string, string[]>;
  spec_capacities?: Record<string, number[]>;
  spec_structured?: Record<string, Array<{ type: string; capacity_gb?: number }>>;
};

export type AdminProductCreated = {
  id: number;
  slug: string;
  sku: string;
  name: string;
  status: 'draft' | 'active' | 'inactive';
};

export type AdminProductCreateResponse = {
  message: string;
  data: AdminProductCreated;
  announcement_count: number;
};

export type AdminProductImageUploadFile = {
  uri: string;
  name: string;
  type: string;
  size: number;
};

// MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
export type AdminProductManagedImage = {
  id: number;
  url: string | null;
  download_url: string | null;
  original_filename: string | null;
  mime_type: string | null;
  file_size: number | null;
  storage_disk: string;
  rotation_degrees: number;
  sort_order: number;
  is_primary: boolean;
  can_delete: boolean;
};

export type AdminProductImageCollectionResponse = {
  message?: string;
  data: AdminProductManagedImage[];
  capabilities: {
    primary: boolean;
    rotate: boolean;
    reorder: boolean;
    delete_public: boolean;
    legacy_copy_on_write: boolean;
  };
  image_limits: AdminProductImageLimits;
};

export type AdminProductImageMutationResponse = AdminProductImageCollectionResponse & {
  message: string;
};

export type AdminProductImageUploadResponse = AdminProductImageCollectionResponse & {
  message: string;
  uploaded_count: number;
  skipped_duplicate_count: number;
  skipped_input_indexes: number[];
  uploaded_images: AdminProductManagedImage[];
};
