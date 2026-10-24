# B808-001: authenticated operator becomes anonymous public buyer

Date 2026-10-24. Classification WSTG-ATHZ-02/03 and business-logic integrity, MEDIUM. Source-only corrected; not a production exploit claim.

B807 predecessor 89e8f7cbfe5f0c610835187775acfb4d43ae9232 let logged-in SubAgent/Admin/SuperAdmin access GET /porucivanje and PublicRetailCheckoutService treated a non-customer actor as null buyer ID. This could incorrectly attribute an operational order to the anonymous retail channel with no commission. Synthetic MariaDB PHPUnit RED: three independent authenticated role cases failed on predecessor (HTTP200, expected403), JUnit verified. The unsafe POST semantics were also established by source review; no hostile production sale was attempted.

Patch head fa05307efafc067bf13437fe0851543f6e594af8: form, POST and core checkout service reject any logged-in noncustomer. Anonymous guests and active non-retired customer/Kupac are allowed; existing OFF=>404 for everyone remains. Strict local MariaDB GREEN: 24 tests/324 assertions, with B807 real HTTP20/20, all14 CI workflows PASS. Tests include operator role matrix, inactive(retired) customer, actual customer buyer association, guest no fake user, two independent private receipt capabilities, foreign issued invoice IDOR404, cancelled document hidden, zero rejection-side payments/commission/stock. Only two runtime files changed; no migration.

Residual risk: any operator who logs out may still use intentionally permitted anonymous guest checkout; additional merchant verification/anti-abuse is a separate control. Whole legacy suite still has 42 inherited problems, zero new failure identities; no independent penetration certification. App security release remains HOLD.

Evidence: https://drive.google.com/file/d/1lut7KyzvjNuSfcMEYntRp1YLtJIwm_lr/view
Full regression: https://drive.google.com/file/d/1qvF35W_CHtfZtKpOPWT6QLP7upve21fw/view
Security artifact12560518420 sha256:46ab52a7acf182147dc33848c619769c7cabc5d6678b7d6c5b7c735b57334959; full artifact12561250748 sha256:3b3eda0cc4dff932b7130ea92f152e6ab15c763bb4a25ec9352f400f6ee38894.
