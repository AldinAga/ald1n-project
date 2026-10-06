import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(process.argv[2]);
let failures = 0;
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');
const check = (condition, label) => {
  if (condition) console.log('PASS ' + label);
  else { failures += 1; console.error('FAIL ' + label); }
};
const nav = read('src/components/layout/app-bottom-nav.tsx');
const home = read('src/app/(app)/(tabs)/home.tsx');
const order = read('src/app/(app)/order/[id].tsx');
const admin = read('src/app/(app)/admin/orders/[id].tsx');
const navItems = nav.slice(nav.indexOf('const items: NavItem[]'), nav.indexOf('if (keyboardVisible'));
check(nav.includes('MOBILE_BATCH518B_STABLE_FIVE_TAB_NAV') && !navItems.includes("key: 'admin'") && navItems.includes("key: 'notifications'") && navItems.includes("key: 'home'") && navItems.includes("key: 'catalog'") && navItems.includes("key: 'orders'") && navItems.includes("key: 'account'"), 'Bottom navigation is stable five tabs for all roles');
check(nav.includes("pathname.startsWith('/after-sales')") && nav.includes("pathname.startsWith('/warranties')") && nav.includes("pathname.startsWith('/commissions')") && nav.includes("pathname.startsWith('/portal/messages')") && nav.includes("return 'home';"), 'My Activities routes preserve Home navigation context');
check(nav.includes("pathname === '/admin'") && nav.includes("pathname.startsWith('/admin/')") && nav.includes("pathname.startsWith('/admin/catalog/')") && !nav.includes("return 'admin';"), 'Admin routes use Home context while admin catalog keeps Catalog context');
check(home.includes("adminAllowed ? { title: 'Administracija'") && !home.includes('adminAllowed && !isSuperAdmin'), 'Home exposes Administration entry to all allowed admins including SuperAdmin');
check(order.includes('MOBILE_BATCH518B_CUSTOMER_ORDER_NEXT_STEP') && order.includes('Sledeći korak') && order.includes('Pošalji potvrdu uplate') && order.includes('Uredi porudžbinu') && order.includes('Pokreni reklamaciju, povrat ili servis'), 'Customer order detail exposes contextual next step');
check(admin.includes('MOBILE_BATCH518B_ADMIN_ORDER_NEXT_STEP') && admin.includes('Sledeći korak') && admin.includes('Evidentiraj slanje') && admin.includes('Potvrdi isporuku') && admin.includes("setWorkspace(nextStep.workspace)"), 'Admin order detail exposes contextual next step and workspace continuation');
check(!nav.includes('product_variant_id') && !order.includes('product_variant_id') && !admin.includes('product_variant_id'), 'Product Variants remain decommissioned');
console.log(`BATCH518B_CONTRACT_FAIL_COUNT=${failures}`);
process.exit(failures === 0 ? 0 : 1);
