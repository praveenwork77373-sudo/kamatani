# ユニフォーム管理システム

医療機関向けユニフォーム貸与・請求を管理する Laravel 12 アプリケーションです。

## 概要

- **医療機関側**: 職員管理、ユニフォーム申請、部署／職種／配送先マスタ（全体管理者）
- **鎌谷（管理者）側**: 申請承認、商品・在庫、契約、請求、マスタ管理
- **技術**: PHP 8.2+ / Laravel 12 / Blade / Vite / Tailwind CSS / MySQL
- **言語**: 日本語（既定）・英語の切り替え対応

## 必要環境

- PHP 8.2 以上
- Composer
- Node.js / npm（フロントエンドビルド用）
- MySQL（推奨）または SQLite

## セットアップ

1. 依存関係をインストールする
```bash
composer install
npm install
```

2. `.env` を用意し、設定する（MySQL 例）
```
APP_NAME=ユニフォーム管理システム
APP_URL=http://localhost/uniform-system/public
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
APP_FAKER_LOCALE=ja_JP

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uniform_system
DB_USERNAME=root
DB_PASSWORD=
```

3. アプリケーションキーを生成する
```bash
php artisan key:generate
```

4. データベースを作成し、マイグレーション＋シードを実行する  
詳細は `MYSQL_SETUP.md` を参照。
```bash
php artisan migrate:fresh --seed
```

5. フロントエンドをビルドする
```bash
npm run build
# 開発時: npm run dev
```

6. ストレージリンク（商品画像など）
```bash
php artisan storage:link
```

## ドキュメント

| ファイル | 内容 |
|----------|------|
| `E2E_USER_GUIDE.md` | Fresh-start E2E 手順とシードログイン情報 |
| `PRODUCTION.md` | 本番チェックリスト・メール／キュー・セキュリティ |
| `design.md` | 病院／カマタニ UI シェル方針 |
| `MYSQL_SETUP.md` | MySQL セットアップ手順 |
| `LOAN_REGISTRATION_GUIDE.md` | 貸与登録の流れ |
| `LANGUAGE_SETUP.md` | 日本語／英語切り替えの実装 |
| `adjust.md` | 不具合修正・DBバックアップ等のレポート |

## メール（ローカルでもフロー確認可）

既定は `MAIL_MAILER=log`（実SMTP不要。内容は `storage/logs` に出力）。申請・特別承認・取消・ロック・利用開始・プラン本登録・パスワード再設定はすべて実装済みで `ShouldQueue` 対応です。本番では SMTP 設定後に `php artisan queue:work` を起動してください。詳細は `PRODUCTION.md`。

## 主な機能

- 職員の登録・編集・CSV 入出力（兼務＝複数部署対応）
- ユニフォーム申請（新規貸与／返却／交換／サイズ変更／異動＝部分変更）
- 管理者による申請承認と貸与レコード自動作成
- 契約・請求スナップショット（人数×単価）
- ロール別メニューと権限
- セッションによる言語切り替え（ja / en）

## ライセンス

本プロジェクトは Laravel フレームワーク（MIT）上に構築されています。
