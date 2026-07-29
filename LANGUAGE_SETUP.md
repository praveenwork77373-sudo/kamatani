# 言語設定の実装

## 概要
ユニフォーム管理システムは、日本語（ja）と英語（en）の切り替えによる二言語対応を実装しています。

## 実装内容

### 1. 作成した翻訳ファイル

#### メニュー翻訳
- `resources/lang/ja/menu.php` - 日本語のメニュー項目
- `resources/lang/en/menu.php` - 英語のメニュー項目

#### ページ内容の翻訳
- `resources/lang/ja/pages.php` - 日本語のページ文言
- `resources/lang/en/pages.php` - 英語のページ文言

#### ロール翻訳
- `resources/lang/ja/roles.php` - 日本語のロール名
- `resources/lang/en/roles.php` - 英語のロール名

#### 認証関連の翻訳
- `resources/lang/ja/auth.php` - 日本語の認証文言
- `resources/lang/en/auth.php` - 英語の認証文言

#### 共通翻訳
- `resources/lang/ja/common.php` - 日本語の共通用語
- `resources/lang/en/common.php` - 英語の共通用語

### 2. 言語切り替え

言語スイッチャーはヘッダー（地球アイコン）にあります。次の言語を切り替えられます。
- **日本語** - `ja`
- **English（英語）** - `en`

選択した言語はセッションに保存され、ページ遷移後も維持されます。

### 3. 翻訳対応済みビュー

次のビューは翻訳ヘルパーを使うよう更新済みです。

#### レイアウト
- `resources/views/layouts/app.blade.php`
  - サイドバーのメニュー項目
  - ヘッダーの検索プレースホルダ
  - ロール名
  - 言語スイッチャー

#### 認証
- `resources/views/auth/login.blade.php`
  - システムタイトル・サブタイトル
  - フォームのラベル・プレースホルダ
  - ボタン文言

#### ダッシュボード
- `resources/views/dashboard.blade.php`
  - ようこそメッセージ
  - ロール名
  - 契約期間ラベル
  - 日付表示（ロケール対応）

### 4. ビューでの使い方

Blade テンプレートでは次のように書きます。

```blade
{{ __('menu.dashboard') }}
{{ __('pages.welcome', ['name' => $user->name]) }}
{{ __('roles.system_admin') }}
{{ __('auth.login') }}
{{ __('common.search') }}
```

### 5. LanguageController

`LanguageController` が言語切り替えを処理します。
- ルート: `/language/{locale}`
- ロケール検証（`ja` と `en` のみ許可）
- セッションへ保存
- 前のページへリダイレクト

### 6. ミドルウェア

`SetLocale` ミドルウェア:
- すべてのリクエストで実行
- セッションからロケールを読み取り
- `App::setLocale()` でアプリケーションロケールを設定

### 7. 日付フォーマット

ロケールに応じて日付を表示します。
- 日本語: `Y年n月j日`（例: 2025年12月19日）
- 英語: `Y-m-d`（例: 2025-12-19）

## 翻訳の追加方法

1. 適切な言語ファイルに翻訳キーを追加する
2. Blade で `__('file.key')` を使う
3. パラメータ付き: `__('file.key', ['param' => $value])`

## 動作確認

1. システムにログインする
2. ヘッダーの地球アイコンをクリックする
3. 「English」または「日本語」を選ぶ
4. 表示文言が選択言語に変わることを確認する
5. 別ページへ遷移し、言語が維持されることを確認する

## 注意事項

- 既定ロケールは `config/app.php` および `.env` の `APP_LOCALE` で設定します（既定は `ja`）
- セッションベースの切り替えはリクエスト間で維持されます
- メニュー、ロール名、共通 UI は翻訳可能です
- 未対応のビューは必要に応じて段階的に更新できます
