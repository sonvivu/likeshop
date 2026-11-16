# CLAUDE.md

## 使用方式

开始任何任务前先阅读根目录 `AGENTS.md`。它是完整架构与开发规范的事实源，本文件是同一规则的执行摘要；二者冲突时先以实际代码核实，再修正文档。架构、接口契约或构建发布方式变化时，必须同步更新两个文件。

## 项目记忆

这是 Likeshop 单商户 B2C 商城免费企业版 `v3.5.1`：

- `server/`：PHP 8.x + ThinkPHP 8.1.4 单体工程（分支 `upgrade/tp8-php82`，2026-11-16 从 ThinkPHP 6.1.5 升级；迁移细节见 `AGENTS.md` 的“ThinkPHP 8 迁移”，TP6 迁移见“ThinkPHP 6 迁移”）。
- admin：`server/application/admin/` 中的服务端渲染后台，使用 Think 模板、Layui/Layui Admin、jQuery；没有独立 admin 前端工程。
- mobile：`uniapp/` 中的 Vue 3 + uni-app（CLI + Vite）+ Vuex 4 + uview-plus JavaScript 工程，面向 H5、微信小程序和 App（分支 `upgrade/frontend-major`，2026-11-16 从 Vue 2 + uView 升级）。
- PC 商城：Nuxt 4.6.4/Vue 3 + Element Plus + Pinia 源码位于 `pc/`；`server/public/pc/` 是生成部署目录。
- `server/public/mobile/`：H5 编译产物，不是源码。
- 当前业务代码没有可用自动化测试套件，也没有完整的 lint 或 type-check 流程；前端已有 CLI 开发/构建流程（`npm run build:h5`、`nuxi generate`）。

不要引入 TypeScript 等参考项目约定，除非用户明确批准独立升级任务（Vue 3、Vite、Element Plus、Pinia 现已是本仓库的实际技术栈）。

## 目录速记

- `server/application/admin/controller|logic|validate|view/`：后台 HTTP、业务、校验和页面。
- `server/application/api/controller|logic|validate|http/`：移动端 API 与 token 中间件。
- `server/application/common/model|logic|server|cache/`：跨端公共能力。
- `server/application/index/`：站点根路径的 PC/mobile 选择。
- `server/config/`、`server/route/`：全局配置和显式路由。
- `server/public/static/`：后台与公共静态资源。
- `server/public/install/db/like.sql`：新安装数据库基线，默认表前缀 `ls_`。
- `uniapp/pages/`：主包页面；`uniapp/bundle/pages/`：分包页面。
- `uniapp/api/`：业务接口封装；`uniapp/utils/request.js`：统一请求和响应拦截。
- `uniapp/store/`：Vuex 4；`uniapp/components/`：业务组件（uview-plus 来自 npm，不再 vendored）。
- `uniapp/pages.json`、`uniapp/manifest.json`：页面/分包与平台配置。
- `docker/`：旧版开发容器配置，仅作为环境参考。

禁止把功能实现写进 `server/vendor/`、`server/thinkphp/`、`server/runtime/`、真实 uploads、`uniapp/unpackage/` 或压缩后的 `server/public/mobile/`。

## 请求链路

### Mobile API

`uniapp 页面 -> uniapp/api/*.js -> utils/request.js -> /api/<controller>/<action> -> API Login middleware -> ApiBase controller -> validate/logic -> common model/server`

- 登录 token 使用 `token` header；消费票据功能开启时，需登录接口还要求 `X-Consume-Token`。
- controller 继承 `ApiBase`。
- 免登录 action 配置在 `$like_not_need_login`。
- 统一返回 `code/msg/data/show/time`。
- `code = 1` 成功，`code = 0` 业务失败，`code = -1` 登录失效。
- 分页沿用 `page_no/page_size`，默认 15，最大 100。
- 新接口先添加/修改 `uniapp/api/` 封装，页面不要直接拼完整 URL。
- `GET /api/account/captcha` 提供一次性图形验证码；发送短信的 `sms/send`、`user/send` 及账号密码登录 `account/login` 必须同时提交 `captcha_key` 与 `captcha`。
- admin 登录使用 `admin/account/captcha` 获取一次性验证码，后台登录请求同样必须提交 `captcha_key` 与 `captcha`。
- 密码传输统一使用 `account/passwordKey` 获取短时公钥（API 和 admin 各自模块路径）；密码字段以 `RSA:` 前缀密文提交，并携带 `password_key_id`。服务端在 API/Admin 的 Login middleware 中解密后再进入 controller 校验和业务逻辑，密钥默认 120 秒且只消费一次。新增密码字段必须加入 `PasswordCryptoService` 及三端公共请求层的字段白名单。
- `server/.env` 的 `[consume_token] enabled` 控制用户 API 的附加消费票据校验，默认关闭；`ttl_seconds` 被限制在 1 到 600 秒。PC/uni-app 从 `POST /api/account/consumeToken` 签发短时、会话绑定且 TTL 内可复用的票据，统一请求层负责缓存和注入。开启前先部署新 PC/mobile 构建，多实例环境使用共享缓存。

### Admin

`Layui 模板 -> 同 action AJAX/表单 -> AdminBase controller -> validate/logic -> model/server -> _success/_error`

- admin 使用 Session 登录，Login/Auth 中间件负责登录和角色权限。
- action 常同时处理 GET 渲染和 AJAX；沿用邻近 controller 的 `isAjax()` + `fetch()` 模式。
- 页面在 `application/admin/view/<controller>/<action>.html`，静态资源在 `public/static/`。
- 交互优先复用 Layui table/form/layer 和 `like.ajax`，不要为单页引入新 SPA 框架。
- 新增菜单或受控 action 时同步角色权限、缓存逻辑和安装 SQL 中的菜单/权限数据。

### 安全基线

- `config/app.php` 的 `app_debug` 默认 `true`（未配置 `.env` 时调试模式开启）、`app_trace` 默认 `false`；生产部署在 `.env` 显式 `[app] app_debug = false` 关闭调试；判断开关用 `filter_var(..., FILTER_VALIDATE_BOOLEAN)`，`.env` 解析值是字符串，不能 `=== true` 严格比较。
- trace 输出的 `debug.request.header` 会掩码 token/X-Consume-Token/authorization/cookie/set-cookie。
- `admin/upgrade/index|choosePage|handleUpgrade|addUpdatePkgLog` 与 `admin/upgrade/fixAncestorRelation` 仅超管（admin id=1）可用；存量环境执行 `server/public/install/db/security_3.5.1.sql` 移除菜单 245 授权并清权限缓存。
- 提现申请在事务内锁行重读余额 + 条件原子 `setDec`，`checkMoney()` 拒绝 0/负数/非数字且最低提现金额未配置时拒绝。
- 远程下载经 `check_url_safety()`（仅 http/https，拒绝回环/私网/链路本地/云元数据地址），安全 HTTP 助手限时 10s/30s、不跟随重定向、150MB 上限并用 `CURLOPT_RESOLVE` 固定 IP；`download_file()`、海报合成、`check_file_exists()` 远程分支统一走该助手。
- 微信 URL 验证仅在 `echostr` GET 分支做 token/timestamp/nonce 排序 SHA1 + `hash_equals` 校验，POST 回调仍走 EasyWeChat。

## PC 商城边界

管理后台不是 PC 商城。PC 前台源码位于 `pc/`，后端接入位于以下位置：

- `server/route/route.php` 的 `/pc/:any`。
- `server/application/index/controller/Index.php` 的桌面模板选择。
- admin 的 PC 配置入口和数据库开关；PC 构建使用 Nuxt 4 的 `npm run generate`，发布脚本会将 `.output/public/` 复制到 `server/public/pc/`。

PC 页面需求直接在 `pc/` 源码中实现，必须保持现有 API、账号、支付、SEO 和 `/pc/` history 部署约定；不要修改生成后的 `server/public/pc/` 代替源码。

PC 本地构建/启动约定：

- 已验证 Node `24.22.1` + npm 11（Nuxt 4 要求 Node >= 20.19）。
- 在 `pc/` 使用 `npm ci --no-audit --no-fund` 安装。不要混用 npm 与 pnpm；pnpm 的 `node_modules/.pnpm/` 会使 npm 产生错误的 `ERESOLVE` 依赖树。
- `npm run dev` 启动开发服务，默认访问 `http://localhost:3000/pc/`。
- `npm run generate` 生成静态产物到 `pc/.output/public/`；确认发布后再运行 `bash autoRelease.sh` 复制到 `server/public/pc/`。
- `npm run build` + `npm run start` 是 Nuxt Node 生产运行方式，不替代静态发布。旧 `package-lock.json`（`registry.nlark.com`）已删除重建。

## 多语言 i18n（分支 `vi`）

- 键 = 中文原文；语言 `vi` 默认、`zh` 回退；译文唯一来源 `tools/i18n/locales/<surface>/vi.part*.json`，用 `merge.py <surface>` 合并。不要对已包 `$t()`/`lang()` 的代码重跑 `--scan`，会污染 locale。
- 后端 `lang()`、Admin `{:lang()}`、uniapp vue-i18n v11 legacy、PC `@nuxtjs/i18n@10.6.0` + vue-i18n v11。货币按 VND 显示，唯一来源 `server/config/currency.php`（`symbol: ₫`）：PHP 用 `config('currency.symbol')`，Admin 模板用 `{:config('currency.symbol')}`；`server/application/` 内已无 `￥` hardcode（uniapp 9 file chưa động，见 `AGENTS.md`）。新文件须 www-data 可读，否则配置静默为空（2026-11-16 曾因此丢符号）。
- `pc/plugins/api.ts` 的拦截器必须返回 response，缺失会使首页报错。`server/public/pc/` 是生成目录，发布前确认再跑 `autoRelease.sh`。
- Validate 的 `$message` 在 `__construct` 里逐条 `lang()` 翻译（含 rule-key 标题）。中文拼接 `lang().$var` 在越南语会粘字：冒号结尾的 key 在 part 文件值尾加空格，其余在代码里显式加 `' '`；`积分`/`级` 两份 locale 前导空格不一致，用 `ltrim()` 处理。uview-plus 内部文案用 `$t`/`this.$t`，prop 默认值 **禁止用 `this.$t`**（Vue 3 下 `this` 在 prop default 会被编译成 `void 0`，import chunk 抛 `TypeError: Cannot read properties of undefined (reading '$t')`，trang dùng component đó trắng trang — vụ mobile 16/11/2026）：dùng `import i18n from '@/utils/i18n'` + `default: () => i18n.global.t('key')`。`.vue` 的 CRLF 文件改写须用 `newline=''`。

## 后端开发规则

- 当前基线是 PHP 8.x / ThinkPHP 8.1.4（分支 `upgrade/tp8-php82`）。新增代码可使用 PHP 8 语法，但仍以邻近代码风格为准。
- TP8 迁移要点（详见 `AGENTS.md`）：`field('...', true)` 已失效，排除列改 `withoutField()`；时间字段读出变字符串，`config/database.php` 顶层用 `'datetime_format' => false`；Model 数组赋值 `['inc'/'dec', $v]` 已删，改 `$model->inc()/dec()`（Query Builder 的 `->update(['f' => ['inc', $v]])` 仍可用）。
- TP6 迁移要点（详见 `AGENTS.md`）：`server/app` 是指向 `application/` 的 symlink；事件文件在 `server/application/event.php`；`Cache::rm()` 已删，改 `delete()`；`url()` 返回对象，存库须 `(string)`；`Validate::remove('field', true)`；`config/database.php` 顶层须有 `auto_timestamp => false`；`find(数组)` 是按主键查；Db-Collection 须 `->toArray()` 后改写；controller 不得直接 `return` Logic 的数组，须包 `_success()/_error()`；Session 须挂 `SessionInit`。
- 遵循当前 `controller -> validate/logic -> model/server` 分层。
- controller 只处理 HTTP 上下文、校验和响应，复杂业务进入 logic。
- 公共模型/逻辑/第三方能力优先放 `application/common/`；新增前搜索已有实现。
- 项目历史上公共服务目录名是 `server/`，不要在普通任务中批量改名。
- API 用 `ApiBase::_success/_error`，admin 用 `AdminBase::_success/_error`，不要自造返回结构。
- 业务配置优先通过 `ConfigServer` 和配置表，不硬编码环境值。
- 多表写入遵循邻近 logic 的事务模式。
- 数据表名不手写 `ls_`，让 ThinkPHP 的 prefix 配置处理。
- 当前无 migration 框架。表结构变更要同时考虑 `like.sql` 新安装基线和存量环境升级步骤。

## Mobile 开发规则

- 使用现有 Vue 3 Options API、Vuex 4、uview-plus 和 JavaScript 风格。
- 新页面注册到 `pages.json`；按体积和业务归入主包或 `bundle` 分包。
- 复用 `api/`、`utils/`、Vuex、mixin 和现有组件，避免页面内重复基础能力。
- 使用 uni-app 条件编译处理 H5、`MP-WEIXIN`、`APP-PLUS` 差异。
- 登录、支付、分享、定位、WebView 变更必须逐平台检查。
- H5 history base 是 `/mobile/`；修改它时同步 `manifest.json`、后端路由和 Nginx。
- 平台 AppID、SDK key、支付/微信/短信配置不得写入文档、输出或新硬编码。

`uniapp/package.json` 已有标准 uni-app CLI（Vite）工具链。H5 用 CLI 编译：

- H5 输出：`uniapp/unpackage/dist/build/web/`（`npm run build:h5`）。
- 微信小程序/App：在 HBuilderX 对应发行入口构建并用目标平台工具验证（`manifest.json` 的 runtimeVersion 仍为 3.3.13）。
- `npm run build` 只是执行 `autoRelease.sh`：它会删除 `server/public/mobile/`，再复制已有 web 构建。它不是编译命令，普通源码任务禁止顺手运行。

## 任务执行流程

1. 识别改动端：admin、mobile、API、PC 接入、数据库或部署。
2. 阅读入口及邻近实现，追踪到 controller、logic、model/server 和表/配置。
3. 确认接口参数、返回码、登录/权限和调用端，再开始修改。
4. 用最小范围实现，并同步所有真实调用方；不做无关升级或格式化。
5. 表结构变更同时规划新装与存量升级；权限变更同步菜单/角色数据。
6. mobile 变更检查页面注册与多平台分支；发布产物仅在明确发布任务中更新。
7. 执行可用验证，明确报告无法运行的检查。

跨端开发计划至少写明：用户入口、API、权限、数据/配置、兼容性、验证方式、是否交付编译产物。当前 PC 迁入已完成首轮，PHP/ThinkPHP 升级已在分支 `upgrade/tp8-php82` 完成两轮（TP6、TP8；尚未合入 `vi`），后续架构演进应先固化环境和关键业务基线，再补测试、拆分非破坏性 build/deploy，之后才做公共逻辑收敛。该路线不是自动授权。

## 安全与变更边界

- 不提交或复述 `.env`、数据库密码、token、证书、keystore、AppID、地图/支付/短信/微信/存储密钥。
- 仓库已有敏感资产也不要擅自删除、轮换或打印；需要处理时先确认影响。
- 不修改版权/授权逻辑，不移除版权标识。
- 不执行 `composer update`、数据库安装/写入、线上发布、清缓存、删除构建目录或 Git 提交，除非用户明确要求。
- 不覆盖用户已有修改，不回退无关文件。
- `server/public/mobile/`、`server/runtime/`、uploads 和 `uniapp/unpackage/` 都不是常规源码修改目标。

## 验证速记

服务端可用时：

- `composer install`
- `composer validate --no-check-publish`
- `php think list`
- `php -l path/to/changed.php`

Admin 至少手测相关登录/权限、列表、提交、弹层和刷新；API 至少覆盖相关的未登录、已登录、参数错误、成功分支；mobile 至少在目标平台运行，涉及平台能力时补测 H5/微信小程序/App。

当前桌面环境可能没有 PHP、Composer 或 HBuilderX CLI。工具不可用时如实说明，不能声称测试通过。Docker Compose 锁定旧版依赖且包含历史路径，启动前必须检查，不默认用于生产。

## 文档同步

新增顶级工程、引入 PC 源码、升级框架、改变统一 API/认证/分页、移动端构建目录或部署路径时，同一个任务中更新 `AGENTS.md` 与本文件。只写实际仓库可验证的信息，不把商业版或规划能力描述为当前实现。
