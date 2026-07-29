# ユニフォーム管理システム — 修正・調整レポート

監査日: 2026-07-29  
最終更新: 2026-07-29  
対象: Laravel 12 医療ユニフォーム貸与・請求アプリ

---

## 修正状況サマリー

| 優先度 | 項目 | 状態 |
|--------|------|------|
| 重大 1〜7 | 承認分岐・認可・IDOR・FK・ブロックログイン | ✅ |
| 高 8〜15 | 在庫・部署ID・請求統一・AutoBilling | ✅ |
| 中 16〜21 | ルート・スキーマ・通知・残骸 | ✅ |
| 残: 移管先 UI / 交換旧品 UI | ✅ 実装済 |
| 残: Policy + Feature テスト | ✅ 導入済 |
| 残: LOGIN_CREDENTIALS / db.sql | ✅ `.gitignore` + example |
| **新機能: DBバックアップ管理** | ✅ system_admin 向け |

---

## 新機能: データベース管理（システム管理者）

**画面:** `/admin/database-backups`（サイドバー「データベース管理」）

| 機能 | 説明 |
|------|------|
| 手動バックアップ | 「今すぐバックアップ」または `php artisan db:backup` |
| スケジュール | 日次 / 週次 / 月次 + 時刻・曜日・日付・保持日数 |
| ダウンロード | 成功した `.sql` をダウンロード |
| 復元 | 確認語 `RESTORE` 入力後に実行（現行 DB を上書き） |
| 削除 | ファイルと履歴を削除。保持日数超過は自動 prune |

**自動実行の前提（重要）:**  
Windows タスクスケジューラ等で毎分実行:

```
php c:\xampp\htdocs\uniform-system\artisan schedule:run
```

コマンドは設定を見て、該当時刻のみ実バックアップを作成します（`db:backup --scheduled`）。

バックアップ保存先: `storage/app/backups/`（gitignore 済み）

---

## 移管・交換 UI

- **移管:** 移管先職員必須。承認時に貸与を元職員 → 移管先へ移動（在庫変動なし）
- **返却 / 交換 / サイズ変更:** 職員選択後に「現在の貸与」チェックリスト（`return_items`）
- **交換 / サイズ変更:** 旧品 = `exchange_out`、新品 = カートの `items`（`loan`）
- API: `GET /api/employees/{employee}/loans`

---

## Policy / テスト

- `ApplicationPolicy` / `EmployeePolicy` 登録
- Gate `manage-database`（system_admin）
- Feature テスト: 管理画面拒否、DB画面許可、申請 IDOR、ブロックログイン  
  `php artisan test --filter=AuthorizationAndApplicationTest`

---

## 認証情報の運用

| ファイル | 扱い |
|----------|------|
| `LOGIN_CREDENTIALS.example.md` | リポジトリに残すサンプル |
| `LOGIN_CREDENTIALS.md` | `.gitignore`（ローカルのみ） |
| `db.sql` | `.gitignore` |

---

## 推奨の残作業（任意）

1. Windows タスクに `schedule:run` を登録する手順書（運用）  
2. 交換 UI で旧品数量のバリデーション強化（貸与数量超過防止をサーバ側で厳密化）  
3. Policy を他コントローラへ段階展開  
4. バックアップの暗号化・リモート保管（S3 等）

---

## 関連ファイル（今回追加）

| 領域 | ファイル |
|------|----------|
| DBバックアップ | `DatabaseBackupService`, `DatabaseBackupController`, `db:backup`, migrations |
| 移管 | `transfer_to_employee_id` migration, 申請 create UI, 承認ロジック |
| Policy | `app/Policies/*`, `AppServiceProvider` |
| テスト | `tests/Feature/AuthorizationAndApplicationTest.php` |
