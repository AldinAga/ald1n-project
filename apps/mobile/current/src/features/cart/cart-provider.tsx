import React, { createContext, useCallback, useContext, useEffect, useMemo, useRef, useState } from 'react';
import { useAuth } from '@/features/auth/auth-provider';
import type { Nullable, Price } from '@/types/api';

export type CartItem = {
  key: string;
  productId: number;
  productSlug: string;
  productName: string;
  productSku: string;
  sku: string;
  price: Nullable<Price>;
  quantity: number;
  maxQuantity: number;
  imageUrl: Nullable<string>;
};

type AddCartItem = Omit<CartItem, 'key' | 'quantity'> & { quantity?: number };

type CartContextValue = {
  items: CartItem[];
  itemCount: number;
  addItem: (input: AddCartItem) => void;
  setQuantity: (key: string, quantity: number) => void;
  removeItem: (key: string) => void;
  clearCart: () => void;
};

const CartContext = createContext<CartContextValue | null>(null);

function cartKey(productId: number): string {
  return String(productId);
}

export function CartProvider({ children }: { children: React.ReactNode }) {
  const { status, bootstrap } = useAuth();
  const [items, setItems] = useState<CartItem[]>([]);
  const ownerIdRef = useRef<number | null>(null);
  const currentUserId = bootstrap?.user.id ?? null;

  useEffect(() => {
    if (status === 'anonymous') {
      ownerIdRef.current = null;
      setItems([]);
      return;
    }

    if (status === 'authenticated' && currentUserId !== null) {
      if (ownerIdRef.current !== null && ownerIdRef.current !== currentUserId) {
        setItems([]);
      }
      ownerIdRef.current = currentUserId;
    }
  }, [currentUserId, status]);

  const addItem = useCallback((input: AddCartItem) => {
    const key = String(input.productId);
    const addQuantity = Math.max(1, input.quantity ?? 1);
    const maxQuantity = Math.max(0, input.maxQuantity);
    if (maxQuantity < 1) return;

    setItems((current) => {
      const existing = current.find((item) => item.key === key);
      if (!existing) {
        return [...current, { ...input, key, quantity: Math.min(addQuantity, maxQuantity), maxQuantity }];
      }
      return current.map((item) => item.key === key
        ? {
            ...item,
            ...input,
            key,
            maxQuantity,
            quantity: Math.min(item.quantity + addQuantity, maxQuantity)
          }
        : item);
    });
  }, []);

  const setQuantity = useCallback((key: string, quantity: number) => {
    setItems((current) => current.map((item) => {
      if (item.key !== key) return item;
      return { ...item, quantity: Math.max(1, Math.min(Math.trunc(quantity), item.maxQuantity)) };
    }));
  }, []);

  const removeItem = useCallback((key: string) => {
    setItems((current) => current.filter((item) => item.key !== key));
  }, []);

  const clearCart = useCallback(() => setItems([]), []);
  const itemCount = useMemo(() => items.reduce((sum, item) => sum + item.quantity, 0), [items]);

  const value = useMemo<CartContextValue>(() => ({
    items,
    itemCount,
    addItem,
    setQuantity,
    removeItem,
    clearCart
  }), [addItem, clearCart, itemCount, items, removeItem, setQuantity]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartContextValue {
  const value = useContext(CartContext);
  if (!value) throw new Error('useCart mora biti korišćen unutar CartProvider-a.');
  return value;
}
