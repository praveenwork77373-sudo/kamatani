# MySQL データベースセットアップガイド

## 事前準備
1. MySQL サーバーがインストールされ、起動していること
2. MySQL の root ユーザー認証情報（既定: パスワードなしの root、または設定済みパスワード）

## ステップ1: MySQL サーバーを起動する

### Windows:
- サービス（services.msc）を開く
- 「MySQL」または「MySQL80」サービスを探す
- 右クリックして「開始」を選ぶ

またはコマンドライン:
```powershell
net start MySQL80
```

### Linux / Mac:
```bash
sudo systemctl start mysql
# または
sudo service mysql start
```

## ステップ2: データベースを作成する

### 方法 A: MySQL コマンドラインを使う
```bash
mysql -u root -p
```

続けて実行:
```sql
CREATE DATABASE IF NOT EXISTS uniform_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 方法 B: Laravel を使う（MySQL 起動済みの場合）
ユーザーに CREATE DATABASE 権限があれば、マイグレーション時にデータベース作成を試みます。

## ステップ3: .env ファイルを更新する

`.env` は次のように設定します。
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=uniform_system
DB_USERNAME=root
DB_PASSWORD=
```

**重要:** MySQL の root にパスワードがある場合は、`.env` の `DB_PASSWORD` を更新してください。

ロケール（日本語既定）の例:
```
APP_LOCALE=ja
APP_FALLBACK_LOCALE=ja
APP_FAKER_LOCALE=ja_JP
```

## ステップ4: マイグレーションとシーダーを実行する

MySQL が起動し、データベースが作成できたら:

```bash
php artisan migrate:fresh --seed
```

これにより次が行われます。
1. 既存テーブルをすべて削除
2. すべてのマイグレーションを実行
3. 次のテストデータでシード:
   - 医療機関
   - 部署、職種、配送先
   - ユニフォーム商品
   - 職員
   - 6ユーザーアカウント（全ロール）

## トラブルシューティング

### エラー: "Can't connect to MySQL server"
- MySQL サービスが起動しているか確認する
- ポート 3306 で待ち受けているか確認する
- `.env` の `DB_HOST` が MySQL 設定と一致しているか確認する

### エラー: "Access denied for user"
- `.env` の `DB_USERNAME` と `DB_PASSWORD` を確認する
- ユーザーに CREATE DATABASE 権限があるか確認する

### エラー: "Unknown database"
- ステップ2で手動作成する
- または MySQL ユーザーに CREATE DATABASE 権限を付与する

## シード後のログイン情報

すべてのユーザーのパスワード: `password123`

1. **全体管理者**: hospital-admin@example.com
2. **部署管理者**: department-admin@example.com
3. **システム管理者**: kamatani-admin@example.com
4. **運営管理者**: operations-admin@example.com
5. **営業管理者**: sales-admin@example.com
6. **経理管理者**: accounting-admin@example.com

詳細は `LOGIN_CREDENTIALS.md` を参照してください。
