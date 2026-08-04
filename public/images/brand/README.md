# Brand assets (Phase 0 — rebuilt from your files)

**Source of truth (you provided):**

| File | Role |
|------|------|
| `public/images/brand/logo.png` | Master logo |
| `public/images/brand/favicon.png` | Master favicon art |

All other sizes below were **generated from those two files**.

## Generated files

```
public/images/brand/
  logo.png                 ← YOUR master logo (do not replace casually)
  favicon.png              ← YOUR master favicon art
  logo-mark.png            512×512 UI mark
  logo-mark-192.png
  logo-mark-64.png
  logo-mark-48.png
  logo-display.png         512×512 on white (headers if needed)
  logo-mark.svg            Vector approximation for crisp UI
  favicon-16.png
  favicon-32.png
  favicon-48.png
  apple-touch-icon.png
  reference/design-system-board.png

public/
  favicon.png              32×32 site favicon
  favicon.ico              Multi-size ICO
```

## Colors

| Token | Hex |
|-------|-----|
| Brand teal | `#0F6B6B` |
| Slate | `#5C6B7A` |

Tokens file: `public/css/brand-tokens.css` (also `resources/css/brand-tokens.css`)

## Use in Blade (Phase 1+)

```blade
{{-- Header / sidebar mark --}}
<img src="{{ asset('images/brand/logo-mark.svg') }}" alt="" width="40" height="40">

{{-- Or PNG --}}
<img src="{{ asset('images/brand/logo-mark-64.png') }}" alt="ユニフォーム管理システム" width="40" height="40">

{{-- Full master --}}
<img src="{{ asset('images/brand/logo.png') }}" alt="ユニフォーム管理システム" class="h-16 w-auto">
```

Favicon in layout `<head>`:

```blade
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/png" href="{{ asset('images/brand/favicon-32.png') }}">
<link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">
```

---

## Design board vs app (audit 2026-08-04)

Reference: `reference/design-system-board.png` + `design.md`

| Board / README item | Status |
|---------------------|--------|
| Brand teal `#0F6B6B` + slate `#5C6B7A` | [x] |
| Noto Sans JP / brand tokens CSS | [x] |
| Logo mark + favicons in layouts | [x] |
| Login: teal bg, white card, logo | [x] |
| Login: no hospital/admin cross-links | [x] |
| Login mobile ~375px readable | [x] |
| **Navbar** Kamatani: dark teal sidebar + outline Lucide icons | [x] |
| **Navbar** active pill / section labels | [x] |
| **Navbar** hospital: light sidebar (not dark teal) | [x] |
| **Navbar** role-based menus | [x] |
| Mobile hamburger → sidebar overlay | [x] |
| Hospital mobile bottom tabs (ホーム/申請/新規/職員) | [x] |
| Header bell + language switcher | [x] |
| Stats cards: label + big number + circular icon (right) | [x] |
| Tables: brand header, row hover, status pills | [x] partial (key lists) |
| Table actions: icon-only one-row (`x-table-action`) | [x] partial (key lists) |
| Pending applications badge in nav | [x] |
| Hospital name under sidebar logo | [x] |
| Header CTA「新しい申請」(hospital) | [x] |
| Kamatani home tabs 今日の対応 / 概況 / 履歴 | [x] |
| Board donut「貸与状況」exact mock | [ ] optional — product uses trend charts |
| Board menu labels (クリーニング等) | [ ] SPEC menus used instead (申請/商品/契約…) |
| Header search bar (board mock) | [ ] not in product |

---

## Phase 0 checklist — assets

1. [x] `/images/brand/logo.png` — master logo
2. [x] `/images/brand/favicon.png` — favicon art
3. [x] `/images/brand/logo-mark-64.png` — small mark
4. [x] `/images/brand/logo-mark.svg` — OK
5. [x] `/favicon.ico` — browser tab
6. [x] `/images/brand/favicon-32.png`
7. [x] Assets ready (login restyle moved to Phase 1)

## Phase 1 checklist — guest / login

1. [x] `/login` — teal background, white card, logo
2. [x] Favicon in browser tab
3. [x] `/password/forgot` — same guest style
4. [x] Password show/hide on login
5. [x] Login works (hospital `/login` + kamatani `/admin/login`)
6. [x] Mobile ~375px: card readable
7. [x] Cross-links between hospital/admin login removed

## Phase 2 checklist — Kamatani shell (navbar)

1. [x] Login as **system_admin** → teal sidebar, logo mark, role label
2. [x] Dashboard / notifications use Kamatani shell
3. [x] Sidebar menus match role (system_admin / operation / sales / accounting)
4. [x] Pending applications badge (system_admin / operation)
5. [x] Bell + language switcher in header
6. [x] Mobile: hamburger opens sidebar overlay
7. [x] Hospital uses **light** shell (`shell-hospital`) on same layout
8. [x] List restyle started (tables / icon actions / status pills)

## Phase 3 checklist — Kamatani home / lists

1. [x] Kamatani home tabs: 今日の対応 / 概況 / 履歴
2. [x] Applications list & detail use brand shell patterns (pills + icon actions)

## Phase 4 checklist — Hospital shell

1. [x] Hospital user → **light** sidebar (not dark teal)
2. [x] Hospital name under logo
3. [x] Short menu: ホーム / 申請 / 職員 (+ マスタ for institution_admin)
4. [x] Header「新しい申請」+ bell + language
5. [x] Mobile bottom tabs: ホーム / 申請 / 新規 / 職員
6. [x] Kamatani still uses dark teal shell
7. [x] Page content brand pass started (tables / mobile)

## Phase 5 — remaining polish

1. [x] Show hospital name under sidebar logo (hospital shell)
2. [x] Add header「新しい申請」button (hospital)
3. [x] Kamatani home queue tabs per `design.md`
4. [x] Roll `ui-table` + `x-table-action` on key list pages (institutions / employees / CCP / etc.)
5. [ ] Optional: board-style donut if product wants visual parity
6. [ ] Optional: header search (board mock only — not required by SPEC)
