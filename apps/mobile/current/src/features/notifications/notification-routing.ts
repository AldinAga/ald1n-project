import type { BusinessNotification } from '@/types/api';

export type NotificationDestination =
  | {
      kind: 'order';
      id: number;
    }
  | {
      kind: 'after_sales_case';
      id: number;
    }
  | {
      kind: 'product';
      slug: string;
    }
  | {
      kind: 'notifications';
      reason: 'stale_assignment' | 'unsupported';
    };

type NotificationRoutingInput = {
  event?: unknown;
  target?: unknown;
  afterSalesCaseId?: unknown;
  orderId?: unknown;
  route?: unknown;
};

function positiveInteger(
  value: unknown
): number | null {
  if (
    typeof value !== 'number'
    && typeof value !== 'string'
  ) {
    return null;
  }

  const parsed = Number(value);

  return Number.isInteger(parsed)
    && parsed > 0
    ? parsed
    : null;
}

function isRecord(
  value: unknown
): value is Record<string, unknown> {
  return (
    typeof value === 'object'
    && value !== null
    && !Array.isArray(value)
  );
}

function normalizeTarget(
  value: unknown
): {
  type: string;
  id: number;
} | null {
  if (
    !isRecord(value)
    || typeof value.type !== 'string'
  ) {
    return null;
  }

  const id =
    positiveInteger(value.id);

  if (!id) {
    return null;
  }

  return {
    type:
      value.type
        .trim()
        .toLowerCase(),

    id
  };
}

function orderIdFromRoute(
  value: unknown
): number | null {
  if (typeof value !== 'string') {
    return null;
  }

  const match =
    value
      .trim()
      .match(
        /^\/orders?\/(\d+)\/?(?:\?.*)?$/i
      );

  return match?.[1]
    ? positiveInteger(match[1])
    : null;
}

function afterSalesCaseIdFromRoute(
  value: unknown
): number | null {
  if (typeof value !== 'string') {
    return null;
  }

  const match =
    value
      .trim()
      .match(
        /^\/after-sales\/(\d+)\/?(?:\?.*)?$/i
      );

  return match?.[1]
    ? positiveInteger(match[1])
    : null;
}

function productSlugFromRoute(
  value: unknown
): string | null {
  if (typeof value !== 'string') return null;

  const match = value.trim().match(
    /^\/product\/([^/?#]+)\/?(?:[?#].*)?$/i
  );

  const encoded = match?.[1];
  if (!encoded) return null;

  try {
    const slug = decodeURIComponent(encoded).trim();
    return slug === '' || /[/?#]/.test(slug) ? null : slug;
  } catch {
    return null;
  }
}
function resolveNotificationNavigation(
  input: NotificationRoutingInput
): NotificationDestination {
  const event =
    typeof input.event === 'string'
      ? input.event
          .trim()
          .toLowerCase()
      : '';

  if (
    event ===
    'order.reassigned_away'
  ) {
    return {
      kind: 'notifications',
      reason: 'stale_assignment'
    };
  }

  const target =
    normalizeTarget(
      input.target
    );

  if (target) {
    if (
      target.type === 'order'
    ) {
      return {
        kind: 'order',
        id: target.id
      };
    }

    if (
      target.type === 'after_sales_case'
    ) {
      return {
        kind: 'after_sales_case',
        id: target.id
      };
    }

    return {
      kind: 'notifications',
      reason: 'unsupported'
    };
  }

  const legacyAfterSalesCaseId =
    positiveInteger(
      input.afterSalesCaseId
    );

  if (legacyAfterSalesCaseId) {
    return {
      kind: 'after_sales_case',
      id: legacyAfterSalesCaseId
    };
  }

  const legacyOrderId =
    positiveInteger(
      input.orderId
    );

  if (legacyOrderId) {
    return {
      kind: 'order',
      id: legacyOrderId
    };
  }

  const routeOrderId =
    orderIdFromRoute(
      input.route
    );

  if (routeOrderId) {
    return {
      kind: 'order',
      id: routeOrderId
    };
  }

  const routeAfterSalesCaseId =
    afterSalesCaseIdFromRoute(
      input.route
    );

  if (routeAfterSalesCaseId) {
    return {
      kind: 'after_sales_case',
      id: routeAfterSalesCaseId
    };
  }

  const routeProductSlug =
    productSlugFromRoute(
      input.route
    );

  if (routeProductSlug) {
    return {
      kind: 'product',
      slug: routeProductSlug
    };
  }
  return {
    kind: 'notifications',
    reason: 'unsupported'
  };
}

export function resolveBusinessNotificationNavigation(
  notification: Pick<
    BusinessNotification,
    'event' | 'target' | 'route' | 'data'
  >
): NotificationDestination {
  return resolveNotificationNavigation({
    event:
      notification.event,

    target:
      notification.target,

    afterSalesCaseId:
      notification.data[
        'after_sales_case_id'
      ],

    orderId:
      notification.data[
        'order_id'
      ],

    route:
      notification.route
  });
}

export function resolvePushNotificationNavigation(
  data: Record<string, unknown>
): NotificationDestination {
  return resolveNotificationNavigation({
    event:
      data['event'],

    target:
      data['target'],

    afterSalesCaseId:
      data['after_sales_case_id'],

    orderId:
      data['order_id'],

    route:
      data['route']
  });
}
