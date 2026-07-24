# Language Setting Implementation

## Overview
The Uniform Management System now supports full bilingual functionality with Japanese (ja) and English (en) language switching.

## Implementation Details

### 1. Translation Files Created

#### Menu Translations
- `resources/lang/ja/menu.php` - Japanese menu items
- `resources/lang/en/menu.php` - English menu items

#### Page Content Translations
- `resources/lang/ja/pages.php` - Japanese page content
- `resources/lang/en/pages.php` - English page content

#### Role Translations
- `resources/lang/ja/roles.php` - Japanese role names
- `resources/lang/en/roles.php` - English role names

#### Authentication Translations
- `resources/lang/ja/auth.php` - Japanese auth text
- `resources/lang/en/auth.php` - English auth text

#### Common Translations
- `resources/lang/ja/common.php` - Japanese common terms (updated)
- `resources/lang/en/common.php` - English common terms (updated)

### 2. Language Switching

The language switcher is located in the header (globe icon). Users can switch between:
- **日本語 (Japanese)** - `ja`
- **English** - `en`

The selected language is stored in the session and persists across page navigation.

### 3. Updated Views

The following views have been updated to use translation helpers:

#### Layouts
- `resources/views/layouts/app.blade.php`
  - Sidebar menu items
  - Header search placeholder
  - Role names
  - Language switcher

#### Authentication
- `resources/views/auth/login.blade.php`
  - System title and subtitle
  - Form labels and placeholders
  - Button text

#### Dashboard
- `resources/views/dashboard.blade.php`
  - Welcome message
  - Role names
  - Contract period labels
  - Date formatting (locale-aware)

### 4. Usage in Views

To use translations in Blade templates:

```blade
{{ __('menu.dashboard') }}
{{ __('pages.welcome', ['name' => $user->name]) }}
{{ __('roles.system_admin') }}
{{ __('auth.login') }}
{{ __('common.search') }}
```

### 5. Language Controller

The `LanguageController` handles language switching:
- Route: `/language/switch/{locale}`
- Validates locale (only 'ja' and 'en' allowed)
- Stores in session
- Redirects back to previous page

### 6. Middleware

The `SetLocale` middleware:
- Runs on every request
- Reads locale from session
- Sets application locale using `App::setLocale()`

### 7. Date Formatting

Dates are formatted based on locale:
- Japanese: `Y年n月j日` (e.g., 2025年12月19日)
- English: `Y-m-d` (e.g., 2025-12-19)

## How to Add More Translations

1. Add translation keys to the appropriate language file
2. Use `__('file.key')` in Blade templates
3. For parameters: `__('file.key', ['param' => $value])`

## Testing

1. Log in to the system
2. Click the globe icon in the header
3. Select "English" or "日本語"
4. Verify all text changes to the selected language
5. Navigate to different pages to confirm persistence

## Notes

- The default locale is set in `config/app.php`
- Session-based language switching persists across requests
- All menu items, role names, and common UI elements are translatable
- More views can be updated incrementally as needed


