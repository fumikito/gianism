# Gianism プラグイン開発ガイド

## 概要

GianismはWordPressをSNS（Google, Facebook, X/Twitter, LINE等）と連携させるプラグインです。
利用方法はREADME.mdをみてください。

## ローカル環境

Local by Flywheelで管理しています。URLは `https://local.gianism.info` です。
管理者でのログイン情報は `admin/password` です。
Chrome MCPサーバーなどを使って、アクセスすることができます。
CLI操作は以下を参照してください。

```
~/bin/local-wp gianism-local -- [ここでwp-cliコマンド]
```

NOTE: このローカル情報はDockerにおきかえ予定です。

## ビルドシステム

Node.js 22以上が必要です（voltaで管理）。

### コマンド

```bash
# 依存関係インストール
npm install

# 本番ビルド（CSS, JS, 依存関係ダンプ）
npm run package

# ファイル監視（開発時）
npm run watch

# 個別ビルド
npm run build:css   # SCSS → CSS
npm run build:js    # JS バンドル
npm run dump        # wp-dependencies.json 生成

# Lint
npm run lint        # CSS と JS 両方
npm run lint:css    # stylelint
npm run lint:js     # eslint

# 自動修正
npm run fix         # CSS と JS 両方
npm run fix:css
npm run fix:js
```

### 使用ツール

- **JS**: `@kunoichi/grab-deps` - WordPress依存関係を解析してバンドル
- **CSS**: `sass` CLI + `postcss` with `autoprefixer`
- **Lint**: `@wordpress/scripts` (eslint, stylelint)

### ディレクトリ構造

```
src/
  js/          # JavaScript ソース
  sass/        # SCSS ソース
  blocks/      # Gutenberg ブロックソース
assets/
  js/          # ビルド済み JS（gitignore）
  css/         # ビルド済み CSS（gitignore）
  blocks/      # ビルド済みブロック（gitignore）
  vendor/      # コピーされたライブラリ（gitignore）
  fonts/       # フォント（git管理）
  img/         # 画像（git管理）
```

### ブロック開発

```bash
# 新しいブロックを作成（namespace=gianism, textdomain=wp-gianism が自動設定）
npm run create-block -- [block-slug]
# 例: npm run create-block -- login → src/blocks/login/ に gianism/login ブロックを作成

# ブロックのビルド
npm run build:blocks
```

## PHP

### 静的解析

```bash
composer analyze   # PHPStan
```

### コーディング規約

```bash
composer lint     # チェック
composer fix    # 自動修正
```

### クラス構造

- `app/Gianism/Service/` - 各SNSサービスの実装
- `app/Gianism/Plugins/` - 拡張プラグイン
- `app/Gianism/Controller/` - コントローラー
- `templates/` - テンプレートファイル

## Git運用

- メインブランチ: `master`
- コミットは `git cc-commit "メッセージ"` を使用（Co-Authored-By が自動追加される）
- プルリクエストは `gh pr create` で作成

## GitHub Actions

- `.github/workflows/wordpress.yml` - WordPress.org へのデプロイ（release 公開時にトリガー）
- `.github/workflows/test.yml` - PHPUnit などの自動テスト
- リリース時は GitHub Release を作成すると自動的に WordPress.org にデプロイされ、zip が Release に添付される

## リリース手順

1. `README.md` の Changelog を更新。このファイルがWordPress用の `readme.txt` に変換されます。
2. GitHub で Release を作成（タグは `v1.2.3` 形式）
3. GitHub Actions が自動でビルド・デプロイ

## 注意事項

- `assets/js/`, `assets/css/`, `assets/vendor/` はビルドで生成されるため git 管理しない
- `assets/fonts/`, `assets/img/` は git 管理する
- JSファイルのヘッダーコメント( `/*!` で始めること )で `@handle`, `@deps`, `@strategy` を指定すると `grab-deps` が WordPress の依存関係を自動処理する
