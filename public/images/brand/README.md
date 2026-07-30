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

Tokens file: `resources/css/brand-tokens.css`

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

## Phase 0 checklist — verify again

1. [ ] http://localhost:8001/images/brand/logo.png — **your** logo
2. [ ] http://localhost:8001/images/brand/favicon.png — **your** favicon art
3. [ ] http://localhost:8001/images/brand/logo-mark-64.png — clear small mark
4. [ ] http://localhost:8001/images/brand/logo-mark.svg — opens OK
5. [ ] http://localhost:8001/favicon.ico — browser tab icon candidate
6. [ ] http://localhost:8001/images/brand/favicon-32.png
7. [ ] Login page still unchanged (Phase 0 = assets only)

## Phase 1 checklist — guest / login (実装済)

1. [ ] http://localhost:8001/login — teal background, white card, your logo
2. [ ] Favicon in browser tab
3. [ ] http://localhost:8001/password/forgot — same guest style
4. [ ] Password show/hide on login works
5. [ ] Login still works (hospital + kamatani)
6. [ ] Mobile ~375px: card readable
7. [ ] After login, app chrome still **old** (expected until Phase 2)

When OK → **「Phase 2 進めて」**
