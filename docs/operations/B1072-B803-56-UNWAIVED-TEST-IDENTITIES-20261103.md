# B1072 immutable historical B803 56 unresolved test identities

**Source:** [Original full GitHub Actions job #121144690534](https://github.com/AldinAga/ald1n-project/actions/runs/40185291552), 2026-10-25, pinned Draft PR #93 head `acb8b9c42ec9254b1a27567abca29a26c1f733f8`. Previous original baseline **451 tests, 44 failures+errors**, original PR93 candidate **553 tests, 98 failures+errors**, **56 new distinct failing test identities** from exact job lines `NEW_FAILURE=`. This is historic evidence, **not** a fresh B1072 test result and **not** a list of waived cases.

B1072 [independent isolated CI run](https://github.com/AldinAga/ald1n-project/actions/runs/41653736902) separately runs complete unfiltered before-vs-after PR93/B1071 PHPUnit on two disposable MariaDB 10.11.19 services. Its pending result must not be confused with this historical snapshot. Source B1071 patch remains UNAPPLIED in GitHub app source; workflow applies it only in an ephemeral runner. B805/B809/B815 denied test fixture edits were **not** modified, removed, skipped, or reinterpreted.

**56/56 historical failures OPEN_NOT_WAIVED until authentic full-suite evidence proves otherwise.** The list is derived from job log, not guessed:

1. `Tests\Feature\BuyerCheckoutSessionBoundaryB815Test::test_storefront_off_remains_404_before_any_session_gate`
2. `Tests\Feature\PublicOrderCapabilityB799Test::test_pending_private_url_is_closed_when_storefront_disabled`
3. `Tests\Feature\PublicOrderCapabilityB799Test::test_public_order_capability_is_random_hashed_at_rest_and_scoped_to_one_order`
4. `Tests\Feature\PublicOrderCapabilityB799Test::test_token_expiry_and_revocation_are_hard_denials`
5. `Tests\Feature\PublicReceiptSwitchBoundaryB818Test::test_disabling_new_checkout_does_not_revoke_earlier_receipt`
6. `Tests\Feature\PublicReceiptSwitchBoundaryB818Test::test_release_env_flag_remains_independent_emergency_off`
7. `Tests\Feature\PublicReceiptSwitchBoundaryB818Test::test_superadmin_emergency_off_hides_existing_valid_receipt`
8. `Tests\Feature\PublicRetailBuyerCheckoutB802Test::test_customer_uses_public_catalog_and_selects_pickup_without_internal_prices_or_commission`
9. `Tests\Feature\PublicRetailBuyerCheckoutB802Test::test_duplicate_nonce_cannot_switch_delivery_or_buyer_identity`
10. `Tests\Feature\PublicRetailBuyerCheckoutB802Test::test_missing_disabled_or_forged_delivery_method_does_not_create_an_order`
11. `Tests\Feature\PublicRetailCheckoutB799Test::test_admin_cancellation_of_guest_retail_order_restores_stock_once_without_commission`
12. `Tests\Feature\PublicRetailCheckoutB799Test::test_anonymous_checkout_creates_one_real_operational_order_with_no_commission`
13. `Tests\Feature\PublicRetailCheckoutB799Test::test_bank_transfer_uses_only_configured_active_account_and_is_not_marked_paid`
14. `Tests\Feature\PublicRetailCheckoutB799Test::test_duplicate_submission_returns_same_capability_without_rebooking_stock`
15. `Tests\Feature\PublicRetailCheckoutB799Test::test_guest_retail_order_shipment_tracking_delivery_and_single_verified_cod_payment`
16. `Tests\Feature\PublicRetailCheckoutB799Test::test_guest_warranty_document_does_not_cross_orders_and_expires_or_revokes`
17. `Tests\Feature\PublicRetailCheckoutB799Test::test_private_guest_order_link_can_be_revoked_only_by_authorized_superadmin`
18. `Tests\Feature\PublicRetailCheckoutB799Test::test_public_order_integrates_admin_processing_and_private_confirmation_pdf`
19. `Tests\Feature\PublicRetailCheckoutB799Test::test_reapproved_policy_changes_after_checkout_form_cannot_reuse_old_consent`
20. `Tests\Feature\PublicRetailCheckoutB799Test::test_reused_checkout_nonce_cannot_change_buyer_payment_or_cart_after_commit`
21. `Tests\Feature\PublicRetailCheckoutB799Test::test_second_separate_checkout_rotates_nonce_and_can_create_a_new_order`
22. `Tests\Feature\PublicRetailCheckoutB799Test::test_stock_changed_between_cart_and_checkout_rejects_without_partial_writes`
23. `Tests\Feature\PublicRetailCheckoutB799Test::test_unaccepted_terms_and_invalid_checkout_key_never_create_orders`
24. `Tests\Feature\PublicRetailCheckoutB799Test::test_unpaid_guest_bank_transfer_cannot_be_completed_or_fabricate_a_payment`
25. `Tests\Feature\PublicRetailPaymentB803Test::test_bank_transfer_and_cod_keep_their_existing_unpaid_workflows`
26. `Tests\Feature\PublicRetailPaymentB803Test::test_guest_cannot_post_a_forged_deferred_choice`
27. `Tests\Feature\PublicRetailPaymentB803Test::test_in_person_payment_cannot_be_sent_to_courier`
28. `Tests\Feature\PublicRetailPaymentB803Test::test_in_person_payment_stays_unpaid_until_operator_records_collection with data set #0`
29. `Tests\Feature\PublicRetailPaymentB803Test::test_in_person_payment_stays_unpaid_until_operator_records_collection with data set #1`
30. `Tests\Feature\StorefrontNotificationOriginB806Test::test_anonymous_host_header_cannot_poison_operator_order_notification`
31. `Tests\Feature\StorefrontRegisteredBuyerLifecycleB811Test::test_changed_merchant_policy_blocks_submission_without_reservation`
32. `Tests\Feature\StorefrontRegisteredBuyerLifecycleB811Test::test_completed_checkout_nonce_cannot_be_reused_by_another_customer`
33. `Tests\Feature\StorefrontRegistrationOnlyB809Test::test_only_registered_customer_can_buy_and_form_email_cannot_be_forged`
34. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_anonymous_guest_still_orders_without_fake_staff_customer_id`
35. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_operator_cannot_checkout_as_anonymous_buyer with data set "Administrator"`
36. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_operator_cannot_checkout_as_anonymous_buyer with data set "SubAgent"`
37. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_operator_cannot_checkout_as_anonymous_buyer with data set "SuperAdministrator"`
38. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_other_retail_order_document_cannot_be_read_with_valid_foreign_capability`
39. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_retired_customer_cannot_continue_guest_checkout_with_old_session`
40. `Tests\Feature\StorefrontRoleBoundaryB808Test::test_valid_customer_still_checks_out_with_real_identity_and_no_commission`
41. `Tests\Feature\StorefrontSecurityB806Test::test_checkout_redirect_does_not_echo_untrusted_authority_with_private_capability`
42. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "array-method"`
43. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "foreign-nonce"`
44. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "forged-credit"`
45. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "honeypot"`
46. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "invalid-delivery"`
47. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "missing-consent"`
48. `Tests\Feature\StorefrontSecurityB806Test::test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale with data set "stale-policy"`
49. `Tests\Feature\StorefrontSecurityB806Test::test_posted_prices_privileges_paid_state_and_supplier_are_not_mass_assignable`
50. `Tests\Feature\StorefrontSecurityB806Test::test_private_capability_is_hashed_expiring_revocable_and_not_numeric_order_access`
51. `Tests\Feature\StorefrontSecurityB806Test::test_product_and_buyer_markup_is_encoded_in_public_html`
52. `Tests\Feature\StorefrontSecurityB806Test::test_receipt_email_origin_never_comes_from_untrusted_request_headers with data set "forwarded-host"`
53. `Tests\Feature\StorefrontSecurityB806Test::test_receipt_email_origin_never_comes_from_untrusted_request_headers with data set "forwarded-protocol"`
54. `Tests\Feature\StorefrontSecurityB806Test::test_receipt_email_origin_never_comes_from_untrusted_request_headers with data set "host"`
55. `Tests\Feature\StorefrontSecurityB806Test::test_session_cart_and_checkout_cannot_be_stored_by_browser_or_intermediary with data set #0`
56. `Tests\Feature\StorefrontSecurityB806Test::test_session_cart_and_checkout_cannot_be_stored_by_browser_or_intermediary with data set #1`

**Safety:** no history fixture exclusions, no production merge, DB migration, enablement or release. This manifest documents test identities only, not causes or acceptance.
