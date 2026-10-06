import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(process.argv[2]);
let failures = 0;
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');
const check = (condition, label) => {
  if (condition) console.log('PASS ' + label);
  else { failures += 1; console.error('FAIL ' + label); }
};
const endpoints = read('src/lib/api/endpoints.ts');
const client = read('src/lib/api/client.ts');
const order = read('src/app/(app)/order/[id].tsx');
const actions = read('src/features/admin/orders-admin-actions.tsx');
const admin = read('src/app/(app)/admin/orders/[id].tsx');
const proofFn = endpoints.slice(endpoints.indexOf('function orderPaymentProofFormData'), endpoints.indexOf('export const api ='));
const submitFn = endpoints.slice(endpoints.indexOf('submitPaymentProof:'), endpoints.indexOf('paymentProofPath:'));
check(proofFn.includes("formData.append('proof', new File(input.proof.uri))") && !proofFn.includes('uri: input.proof.uri'), 'Payment proof uses Expo File multipart body');
check(submitFn.includes('apiExpoMultipartRequest') && !submitFn.includes('apiRequest<'), 'Payment proof uses Expo multipart transport');
check(client.includes('Slanje fajla je isteklo') && client.includes('Mrezna greska pri slanju fajla'), 'Multipart transport errors are generic for files');
check(order.includes('proofSuccess') && order.includes('postCreateQuery.refetch()') && order.includes('Potvrda uplate je poslata i evidencija je osvežena'), 'Payment proof success refreshes order/payment context');
check(actions.includes('onShipmentSuccess?: () => void') && actions.includes('onSuccess?: () => void | Promise<void>') && actions.includes('onShipmentSuccess'), 'Shipment mutation exposes contextual success continuation');
check(admin.includes("onShipmentSuccess={() => setWorkspace('fulfillment')}") && admin.includes('Kopiraj broj') && admin.includes('Otvori praćenje'), 'Admin order moves to fulfillment and exposes tracking actions');
check(admin.includes("trackingUrl.toLowerCase().startsWith('https://')"), 'Tracking open action requires HTTPS');
check(!admin.includes('product_variant_id') && !actions.includes('product_variant_id'), 'Product Variants remain decommissioned');
console.log(`BATCH518A_CONTRACT_FAIL_COUNT=${failures}`);
process.exit(failures === 0 ? 0 : 1);
