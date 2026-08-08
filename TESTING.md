# Testing

## Automated

```bash
composer install   # once
php artisan test
```

### Key feature suites

| Filter | What it proves |
|--------|----------------|
| `EndToEndFlowTest` | Apply → accept → bill; forged returns blocked; pending double-submit; stock clamp; slot_category keep |
| `ExcelClientRulesTest` | Category caps; hide unsized products; partner price not billed; CCP dates; import caps |
| `ClientBillingAndApplyRulesTest` | Hard-block over limit; individual-only apply; closing-day auto billing |
| `PlanBillingBasisTest` | Headcount × unit price; date windows; dedupe; regenerate |
| `LoanLifecycleGuardTest` | Delete/retire with loans; return qty; cancel inventory |
| `ApplicationLifecycleFixTest` | Return / transfer / cancel lifecycle |
| `BaseDataImportTest` | `260805` base Excel import |

```bash
php artisan test --filter=EndToEndFlowTest
php artisan test --filter=ExcelClientRulesTest
```

## Manual E2E

Follow **`E2E_USER_GUIDE.md`** from Phase 0 (empty DB) through Phase 6.

```bash
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Seeded Kamatani logins and hospital setup steps are in that guide.

## Client rules checklist (smoke)

- [ ] Individual apply only (departmental rejected)
- [ ] Category caps hard-block (no special approval bypass)
- [ ] Stock shortage → special approval only
- [ ] Size empty/`-` hidden; `F` allowed
- [ ] CCP confirm needs start + end
- [ ] Billing = assigned × customer unit price (ex-tax); partner price ignored
- [ ] 得意先 on hospital UI; オノエ on Excel only
- [ ] Auto billing on 締め日 and/or manual generate

## Open client item

- Excel Q2 (gender / same plan code) — still 確認中; not implemented as a separate gender-plan model.
