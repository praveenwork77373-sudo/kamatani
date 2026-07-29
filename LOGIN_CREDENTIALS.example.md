# ユニフォーム管理システム — ログイン認証情報（例）

> **注意:** 本ファイルはシード用の**サンプル**です。本番では必ずパスワードを変更してください。  
> 実環境用の `LOGIN_CREDENTIALS.md` および `db.sql` は `.gitignore` 対象です。

## システム概要
本ドキュメントは、ユニフォーム管理システムに用意された全6ロールのログイン認証情報の例です。

---

## ユーザーロールと認証情報

### 1. 医療機関全体管理者
**メール:** `hospital-admin@example.com`  
**パスワード:** `password123`  
**ロール:** `institution_admin`  
**権限:**
- 自医療機関内の全部署・全職員
- すべての申請および履歴
- 職員管理（作成、編集、CSV インポート／エクスポート）
- 契約の閲覧
- 部署・職種の管理

---

### 2. 部署管理者
**メール:** `department-admin@example.com`  
**パスワード:** `password123`  
**ロール:** `department_admin`  
**権限:**
- 自部署のみ
- 自部署の職員
- 自部署の申請
- 自部署の職員管理
- 自部署向け CSV インポート／エクスポート

---

### 3. システム管理者
**メール:** `kamatani-admin@example.com`  
**パスワード:** `password123`  
**ロール:** `system_admin`  
**権限:**
- システム全体へのアクセス
- 医療機関マスタ管理
- 部署・職種管理
- 配送先管理
- 契約管理（作成、編集、削除）
- 申請承認
- ユニフォーム商品管理
- ユニフォーム推奨（※現状ルート未登録の可能性あり）
- 請求データ生成

---

### 4. 運営管理者
**メール:** `operations-admin@example.com`  
**パスワード:** `password123`  
**ロール:** `operation`  
**権限:**
- 申請の管理・承認
- ユニフォーム商品管理
- ユニフォーム推奨
- 契約管理（閲覧・編集）
- 請求データへのアクセス

---

### 5. 営業管理者
**メール:** `sales-admin@example.com`  
**パスワード:** `password123`  
**ロール:** `sales`  
**権限:**
- 契約情報（閲覧のみ）
- 利用統計
- 契約の閲覧

---

### 6. 経理管理者
**メール:** `accounting-admin@example.com`  
**パスワード:** `password123`  
**ロール:** `accounting`  
**権限:**
- 請求データの生成
- 過去請求の再現
- 請求スナップショットの閲覧
- 請求データのエクスポート

---

## 注意事項

- すべてのパスワードは `password123` です
- ユーザーはデータベースシード時に作成されます
- 医療機関: 「Test Hospital」（ID: 1）
- 部署管理者は「内科」部署に割り当てられています
- 本番環境では必ずパスワードを変更してください

---

## クイック参照

| ロール | メール | パスワード |
|--------|--------|------------|
| 全体管理者 | `hospital-admin@example.com` | `password123` |
| 部署管理者 | `department-admin@example.com` | `password123` |
| システム管理者 | `kamatani-admin@example.com` | `password123` |
| 運営管理者 | `operations-admin@example.com` | `password123` |
| 営業管理者 | `sales-admin@example.com` | `password123` |
| 経理管理者 | `accounting-admin@example.com` | `password123` |

---

**システム:** ユニフォーム管理システム v1.0
