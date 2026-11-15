# LikeShop — i18n / Việt hóa (nhánh `vi`, hard fork)

> Hard fork từ upstream `likeadmin-likeshop/likeshop` v3.5.1. Không merge upstream nữa,
> nhánh `vi` tự duy trì. Mọi thay đổi i18n tuân theo tài liệu này.

## Mô hình i18n: msgid = chữ Trung gốc (kiểu gettext)

- **Key = đúng chuỗi tiếng Trung gốc** (msgid), không bịa key ngữ nghĩa.
  VD: `Lang::get('获取成功')`, `$t('获取成功')`, `{:lang('获取成功')}`.
- Mỗi mặt có 2 file locale: `zh.json` (msgid → msgid, identity, làm gốc tra cứu)
  và `vi.json` (msgid → bản dịch tiếng Việt). Thêm ngôn ngữ mới = thêm 1 file.
- **Fallback = msgid**: chuỗi nào chưa dịch thì tự hiện chữ Trung, không vỡ giao diện.
- Ngôn ngữ mặc định sau fork: `vi`. Fallback: `zh-cn`.
- Placeholder: giữ nguyên tên biến trong msgid, nhưng **khác cú pháp theo mặt**:
  - PHP (ThinkPHP `Lang::get`): biến assoc viết `{:name}`, biến số viết `%s`
    (sprintf). VD: `lang('商品：{:name} 当前处于售后中', ['name' => $name])`.
    Khi chuyển literal nội suy `{$var}` sang i18n phải đổi thành `{:var}`.
  - Vue/uniapp/pc (vue-i18n): biến viết `{name}`, gọi `$t('key', { name: value })`.
- Không dịch: `uniapp/utils/area.js`, `pc/utils/area.js` (dữ liệu địa danh TQ,
  ~4.100 mục). Muốn địa danh VN thì thay file data, không dịch từng tên.
- Không dịch trong code: SQL, URL, tên file, key kỹ thuật, log nội bộ.

## Các mặt và kỹ thuật

| Mặt | Kỹ thuật | Locale | Chọn ngôn ngữ lúc chạy |
|---|---|---|---|
| Backend PHP (`server/application`) | ThinkPHP Lang, bật `lang_switch_on`, `default_lang = vi` | `server/application/lang/vi.php`, `zh-cn.php` | Middleware API đọc header `lang` / `Accept-Language`; admin đọc session/cookie |
| Admin template (`server/application/admin/view`) | `{:lang('...')}` trong template ThinkPHP | dùng chung locale backend | theo session admin |
| Mobile (`uniapp/`) | vue-i18n v8 (Vue 2), `Vue.use(VueI18n)` trong `main.js` | `uniapp/locales/vi.json`, `zh.json` | locale lưu storage, mặc định `vi` |
| PC (`pc/`) | `nuxt-i18n` v6.28.1 (tên cũ của `@nuxtjs/i18n`, bản cho Nuxt 2; `@nuxtjs/i18n` v6 không tồn tại — v7+ chỉ cho Nuxt 3) + `vue-i18n` v8 | `pc/locales/vi.json`, `zh.json` | theo cookie/header, mặc định `vi` |

**File locale nào là gốc:** `tools/i18n/locales/<mặt>/{vi,zh}.json` là nguồn duy nhất
để dịch/sửa. `uniapp/locales/` và `pc/locales/` là bản copy chạy lúc runtime —
sửa thì sửa ở `tools/i18n/locales/` rồi copy lại, không sửa trực tiếp bản copy.

## Chọn ngôn ngữ lúc chạy (backend)

ThinkPHP tự detect theo thứ tự: `?lang=` → cookie `think_var` →
header `Accept-Language`. Cấu hình: `lang_switch_on = true`, `default_lang = vi`.
- **Bẫy `vi-VN`:** trình duyệt Việt thường gửi `Accept-Language: vi-VN,...`;
  `detect()` lấy tag đầu `vi-vn`, không có file `vi-vn.php` nên rơi về msgid
  (chữ Trung). `BaseMiddleware` (chạy mọi request) phải chuẩn hóa: langset
  nào không thuộc `[vi, zh-cn]` thì ép về `vi` và load lại file `vi.php` —
  cả file app VÀ file module (module lấy từ đoạn đầu của path), vì
  `loadLangPack()` đã chạy ở init với langset sai nên file module chưa nạp.
  Không nạp bù thì trang admin rơi về bản dịch backend (key trùng) hoặc chữ
  Trung (key riêng admin).
- API client (uniapp/pc) gửi kèm header `Accept-Language: vi` trong request.js /
  plugin axios để backend luôn trả msg tiếng Việt. Không thêm header auth mới
  (xem AGENTS.md: không phá vỡ token header và cấu trúc `code/msg/data`).

## File ngôn ngữ ThinkPHP (backend + admin)

| File | Nguồn | Ai nạp |
|---|---|---|
| `server/application/lang/vi.php` (1.519 key) | `tools/i18n/locales/backend/vi.json` | `loadLangPack()` theo langset, mọi module |
| `server/application/lang/zh-cn.php` | `tools/i18n/locales/backend/zh.json` | như trên |
| `server/application/admin/lang/vi.php` (2.305 key) | `tools/i18n/locales/admin/vi.json` | `loadLangPack()` khi module = admin, nạp SAU file app nên ghi đè key trùng |
| `server/application/admin/lang/zh-cn.php` | `tools/i18n/locales/admin/zh.json` | như trên |

200 key trùng giữa backend và admin, 78 bản dịch khác nhau (backend lịch sự
"Vui lòng nhập X", admin ngắn gọn "Nhập X") — file module admin thắng trên
trang admin, file app thắng trên API. Sinh bằng
`python3 tools/i18n/build_lang_php.py` (ghi cả 4 file), kiểm tra bằng
`php -l` từng file. Key trong file PHẢI viết thường (Lang lower-case hai phía).

**Không dịch được ở v1 (giữ chữ Trung trong code):** giá trị mặc định của
thuộc tính class (`protected $message/$rule = [...]` của Validate,
`public static $type = [...]`), hằng `const`, giá trị mặc định của tham số hàm
(`$x = '...'`) — PHP bắt buộc là biểu thức hằng, gọi `lang()` là fatal.
`extract.py --apply` tự bỏ qua các vùng này (đếm ở `skipped_prop_default`;
backend: 512 literal). Muốn dịch thì phải refactor sang gán trong constructor
— việc của v2, không làm lén trong v1.

**Quy tắc thay code trong template admin:** giữ nguyên dấu ngoặc gốc quanh
`{:lang(...)}` — `placeholder="{:lang('...')}"`, `var x = '{:lang('...')}'`.
Bản apply đầu đã lột ngoặc làm giá trị nhiều từ bị cắt ở khoảng trắng đầu
tiên trong HTML (`placeholder=Vui lòng...` chỉ hiện "Vui") và vỡ chuỗi JS.
Text node (`>chữ<`) thì không cần ngoặc.

## Quy mô đã đo (literal chứa CJK, đã loại `area.js`)

- Backend PHP: 1.550 chuỗi riêng biệt / 2.617 lần xuất hiện / 366 file.
- Admin HTML: ~2.000 chuỗi riêng biệt (quoted 1.021 + text node 1.467, có trùng).
- uniapp: ~800 chuỗi riêng biệt (quoted ~343 + text node 474).
- pc: ~400 chuỗi riêng biệt (quoted ~171 + text node 222).
- Tổng ~4.700 chuỗi UI. Chuỗi lặp nhiều nhất: `获取成功` (87), `取消` (146),
  `确认` (136), `确定` (104) — dịch 1 lần, dùng khắp nơi.

## Quy trình làm việc

1. `tools/i18n/extract.py` quét code → sinh locale JSON (msgid, kèm file dùng nó)
   và bản đồ thay thế. Chỉ thay tự động literal "sạch" (không nội suy, không SQL/URL);
   literal nội suy (`{$var}`, `${...}`, `%s`) vào danh sách làm tay.
2. Dịch theo lô theo file locale, có bảng thuật ngữ thống nhất (xem dưới).
   Mỗi lô dịch xong phải qua kiểm tra: JSON hợp lệ, placeholder hai bên khớp,
   không mất key.
3. `--apply`: script thay code dùng key i18n. Sau đó `php -l` toàn bộ file PHP đổi,
   kiểm tra JSON, khởi động stack, kiểm chứng admin/API/mobile/PC bằng tiếng Việt.
4. Nội dung trong DB (tên menu, cấu hình, bài viết, sản phẩm demo trong
   `like.sql`/`ys.sql`) dịch riêng bằng SQL seed/migration, không lẫn vào code.

## Bảng thuật ngữ (thống nhất toàn repo)

| Trung | Việt | Ghi chú |
|---|---|---|
| 商品 | sản phẩm | |
| 订单 | đơn hàng | |
| 购物车 | giỏ hàng | |
| 优惠券 | mã giảm giá / phiếu giảm giá | thống nhất 1 cách gọi trong lúc dịch |
| 会员 | thành viên | |
| 分销 | tiếp thị liên kết | |
| 拼团 | mua chung | |
| 砍价 | mặc cả / chém giá | thống nhất 1 cách gọi |
| 秒杀 | flash sale | |
| 积分 | điểm tích lũy | |
| 成长值 | điểm tăng trưởng | thống nhất, không dùng "điểm trưởng thành" |
| 零钱 / 微信零钱 | ví WeChat | |
| 元 | đồng | quy ước tiền tệ: hiển thị theo VND (xem dưới) |
| ￥ | ₫ | quy ước tiền tệ: hiển thị theo VND (xem dưới) |
| 张 (đơn vị đếm mã giảm giá) | mã | vd "3 mã giảm giá" |
| 余额 | số dư | |

**Quy ước tiền tệ:** toàn bộ hiển thị theo VND (`元` → "đồng", `￥` → "₫").
Số tiền trong DB vẫn là số thuần, chưa đổi đơn vị tiền tệ thật — bước bản
địa hóa thanh toán (VNPay/MoMo/ZaloPay, tỷ giá, làm tròn) làm sau, không lẫn
vào đợt dịch chữ.
| 提现 | rút tiền | |
| 售后 | hậu mãi / bảo hành đổi trả | theo ngữ cảnh |
| 发货 | giao hàng | |
| 收货 | nhận hàng | |
| 支付 | thanh toán | |
| 退款 | hoàn tiền | |
| 获取成功 | lấy dữ liệu thành công | msg API, giữ ngắn |
| 操作成功 | thao tác thành công | |
| 参数错误 / 参数缺失 | tham số không hợp lệ / thiếu tham số | |
| 确定 | xác nhận | nút bấm |
| 取消 | hủy | nút bấm |
| 保存 | lưu | |
| 删除 | xóa | |
| 编辑 | chỉnh sửa | |
| 添加 | thêm | |
| 搜索 | tìm kiếm | |
| 全部 | tất cả | |
| 设置 | cài đặt | |

## Bài học triển khai (ghi ngày 2026-11-15)

- **Pha manual (backend/admin, xong 2026-11-15):** `manual.json` của uniapp/pc
  hầu hết là false positive do quét lại code đã apply (key đã có trong locale
  + đã bọc `$t`) — kiểm tra bằng script trước khi sửa tay, không sửa mù.
  Chuỗi so sánh với giá trị API (`status == '待返佣'`, `first_leader_info !=
  '系统'`, `expressName != '顺丰快递'`) là DATA, tuyệt đối không dịch.
  Log `Log::write(...)` giữ nguyên tiếng Trung (diagnostic, không phải UI).
  `UserTask::USERTASK` là const chết (không chỗ nào dùng) — 8 chuỗi
  `{$num}` trong đó để nguyên, không refactor const. Placeholder tham số
  backend dùng `{:name}` (assoc) theo `Lang::get()` của ThinkPHP 5.1.
  `DistributionLogic` đổi định dạng tháng SQL `'%Y年%m月'` → `'%Y-%m'`
  (trung lập locale, không phải bản dịch).
- **Nội dung DB:** seed nằm ở 2 file — `like.sql` (cấu trúc + menu
  `ls_dev_auth`) và `ys.sql` (dữ liệu demo: config/article/goods/...).
  Nâng cấp DB hiện có: `tools/i18n/db/upgrade-vi.sql` (UPDATE theo id).
  `ls_goods.name` là `varchar(64)` — tên dịch phải ≤64 ký tự.
  `ls_config` chỉ có 18 dòng; `website.*`/`copyright.*` (tiêu đề login,
  footer) là default trong `server/config/default.php`, không nằm trong DB —
  sửa file đó (số ICP Trung Quốc gỡ luôn, không dịch). Sau sửa `default.php`
  phải xóa `server/runtime/cache/*` (ConfigServer cache file) thì trang
  login mới đổi. Nội dung HTML chi tiết sản phẩm (`ls_goods` detail) là
  HTML scrape từ Taobao — cố ý để nguyên.
- **`--scan` chỉ chạy trên code CHƯA apply.** Sau khi code đã bọc `$t()`/
  `lang()`, quét lại sẽ nhiễm: attribute `:placeholder="$t('x')"` bị bắt
  thành key `$t('x')`, ternary trong attribute bị bắt nguyên biểu thức, và
  classifier lật display→data vì dòng có `$t(`. Muốn tái sinh `zh.json`/
  `vi.json` sau apply thì dựng từ part file (zh = identity map của key trong
  part), rồi chạy `merge.py <mặt>` — không quét lại code.
- **PC build trên VM này:** Node 16 ở `~/workspace/tools/node16/`, phải để
  node16 đầu PATH khi chạy `npm run generate` (node hệ thống v24 → lỗi
  `ERR_OSSL_EVP_UNSUPPORTED` do webpack 4 + OpenSSL 3); `npm ci`/`generate`
  phải chạy dưới user **`builder`** (npm 8 chạy root bị sandbox chặn `chown`
  nên chết `EPERM`); `cp -a` giữ ownership cũng chết tương tự — dùng
  `cp -r --no-preserve=ownership`. **Lần generate lỗi sẽ xóa sạch `pc/dist/`
  trước khi build** — build lỗi thì phải build lại mới có dist.
- **Đổi bundle `server/public/pc/` để test là thao tác hủy diệt:** `rm -rf`
  xóa cả file tracked (109 file), `git checkout HEAD -- server/public/pc`
  chỉ khôi phục file tracked, file thừa untracked phải dọn bằng
  `git clean -f server/public/pc`; sau checkout phải `chmod -R o+rX`
  (umask 0007 → file 660, www-data không đọc → 403). Kiểm chứng xong phải
  khôi phục ngay và đối chiếu `git status` sạch trước khi báo xong.
- **VM bị thay mới giữa chừng:** toàn bộ môi trường (PHP/MariaDB/nginx/redis)
  và database mất; chỉ `~/workspace` còn. Cài lại + chạy lại wizard, dữ liệu
  demo về như cũ. Không tin tưởng `/var`, `/etc` qua lần restart VM.
- **Umask 0007:** file mới do `git checkout` hoặc script sinh ra có mode 660,
  www-data không đọc được → 500 `Permission denied`. Sau mọi lần checkout/
  sinh file trong `server/`, chạy lại `chmod -R o+rX` trên repo rồi mới test.
- **Template cache:** ThinkPHP biên dịch `.html` vào `server/runtime/temp/`;
  sau khi sửa template phải xóa `runtime/temp/*` (nó tự biên dịch lại).
  Chỉ xóa cache biên dịch, không xóa log.
- **Quét chuỗi bằng regex:** phải match literal một pass duy nhất (SQ|DQ|BT),
  không quét riêng từng loại ngoặc — nếu không literal lồng nhau
  (`'...\"...\"...'`) bị bắt nhầm phần trong, vỡ cú pháp.
- **Kiểm chứng bằng trình duyệt phải giả đúng header:** Chrome headless mặc
  định gửi `Accept-Language: en-US` — sau khi sửa middleware thì vẫn ra
  tiếng Việt, nhưng trước đó nó lòi ra đúng lỗi module lang chưa nạp.
- **Kiểm chứng admin bằng headless Chrome trên chính VM (2026-11-15):**
  browser cloud không tới được `localhost:20208` (VM khác nhau), tunnel
  cũng bị chặn — phải tự chạy headless: Chrome `--headless=new
  --remote-debugging-port=9222` + script Node v24 (WebSocket global) qua
  CDP: `Network.setCookie` gắn PHPSESSID đã đăng nhập bằng curl trước,
  `Page.navigate` tới `/admin/index/index.html`, `Page.captureScreenshot`.
  Script mẫu: `tools/i18n/headless-admin-shot.mjs` (session lấy lại bằng
  curl mỗi lần hết hạn — captcha đọc bằng mắt, RSA qua openssl, mật khẩu
  phải có tiền tố `RSA:` + `password_key_id`, captcha cần header
  `X-Requested-With: XMLHttpRequest`).
- **Scanner bỏ sót text node nhiều dòng của admin template (2026-11-15):**
  `extract.py` chỉ bắt literal trên một dòng — text node đứng riêng một
  dòng (`>启动<`, tiêu đề thẻ stat, nút trong `<button>`) và chuỗi sau
  mustache (`{{d.days}}天`, `{$var}人`, `{$var}级`) không vào locale.
  Lần quét bù: 386 ứng viên → 197 key riêng, 82 đã có bản dịch, 115 key
  mới (part4, admin lên 2.413 key). Bài học khi bọc bù: thay key DÀI
  trước key NGẮN trên cùng một dòng (không thì `级` chèn vào giữa
  `分销层级` thành `{:lang('分销层{:lang('级')}')}` — đã gặp 1 lần, phải
  sửa tay); key chứa mustache `{{first.DT}}...` thì để nguyên mustache
  trong key để laytpl render sau khi server render lang (dùng cho câu
  `{{first.DT}}的内容，...` — đảo thứ tự từ cho tiếng Việt); đơn vị
  `天` đổi `Ngày` → `ngày` + thêm khoảng trắng (`{{d.days}}
  {:lang('天')}` → `2 ngày`); `￥` trong template chi tiết đơn hàng đổi
  thành `₫` theo quy ước VND. Sau khi bọc bù, quét lại còn 7 dòng — toàn
  comment JS/HTML, không phải UI. **Chưa quét bù tương tự cho backend
  PHP** (chuỗi trong `echo`/HTML của controller) — để phase 2 nếu cần.

## Lưu ý đã biết

- Bug PC: `pc/plugins/axios.js`, `$axios.onResponse` là `async` nhưng không
  `return response` ở đường thường → mọi `$get` resolve `undefined` → trang chủ
  PC gãy. Sửa 1 dòng `return response` cuối handler, rebuild bundle PC mới có
  hiệu lực (cần Node 16.20.2 + npm 8.19.4 theo AGENTS.md; cấm Node 24).
- `pc/utils/area.js` và `uniapp/utils/area.js` là bản sao dữ liệu địa danh TQ.
- Build PC/mobile trong `server/public/` là bundle cũ, rebuild sẽ ghi đè
  (`autoRelease.sh` xóa và tạo lại `server/public/pc/`).

## Phase 2 (2026-11-15, xong cùng ngày — hoàn thiện 100% hiển thị)

- **Backend Validate dịch qua `__construct`:** 81 validate class thêm
  `__construct(array $rules, array $message, array $field)` gọi
  `parent::__construct(...)` rồi dịch từng `$this->message` bằng
  `lang($value)` + dịch rule-key title (`'field|标题'` → re-key
  `'field|'.lang('标题')`). TP5.1 `check()` tách `|` lấy title cho message
  lỗi nên title cũng phải dịch. 14 key mới (5 message + 8 title + 1 câu
  hậu mãi). `api/validate/Login.php` đã có sẵn `__construct` nên chỉ chèn
  2 vòng lặp. `DeliveryBatchImport` extends FQCN `\think\Validate`;
  `SelffetchVerifier` không có `$message`. Đã kiểm chứng live: POST
  `/admin/ad/add.html` rỗng → `{"code":0,"msg":"Nhập tiêu đề quảng cáo"}`.
- **Chuỗi nối trong PHP (lớp lỗi lớn nhất phase 2):** tiếng Trung không
  cần space nên code gốc viết `lang('X').$var.lang('Y')` — render tiếng
  Việt bị dính chữ (`Tặng10 điểm tích lũy;Tặng10Điểm tăng trưởng`,
  `Nạp100Tặng50đồng`, `Chúc mừngHưngĐã trúng...`). Hai cách sửa:
  (a) key kết thúc bằng `:`/`：` (68 key) — thêm 1 space cuối value trong
  part file, một lần sửa cho ~100 chỗ nối (value không bao giờ bị so sánh
  `== lang()`, space cuối vô hại trong HTML/JSON);
  (b) ~45 chỗ còn lại — thêm `' '` rõ ràng trong code (SignDailyLogic,
  RechargeLogic, Luckdraw, UserLevelLogic, Coupon, Footprint,
  AfterSaleLogic, FileServer, v.v.). Value có space đầu kiểu hack
  (`积分` app=`' Điểm tích lũy'` vs admin=`'Điểm tích lũy'`,
  `级` app=`' cấp'` vs admin=`'cấp'`) thì dùng `ltrim(lang(...))` tại
  chỗ, KHÔNG chuẩn hóa value toàn cục (template khác đang dựa vào
  space đầu đó, vd `{$val.name}({$val.weights}){:lang('级')}`).
  Ngoại lệ đã kiểm chứng là đúng sẵn, không sửa: `LuckdrawLogic:382`
  (`Tiêu hao ` + số + ` Điểm tích lũy`), `QrCodeLogic`
  (`Từ ` + tên + ` đã chia sẻ`), `BargainLogic:422`, `UserLevelLogic:49`,
  `UserLogic:77`, `QrCodeLogic:362`, `AdLogic:238`.
- **Bài học then chốt — chuỗi hiển thị không phải lúc nào cũng từ
  template:** trang điểm danh hiển thị `award_tips` do
  `SignDailyLogic::lists()` nối trong PHP
  (`lang('赠送').$sign['integral'].lang('积分；')`), còn template
  laytpl `#continuous_integral` (đã thêm space) chỉ dùng cho tab lịch
  sử (type=2). Đã chứng minh bằng DOM dump (cell textContent) + chạy
  trực tiếp `layui.laytpl(tpl).render(...)` trong trình duyệt (ra đúng
  có space) rồi mới lần ra backend. Khi thấy chữ dính, kiểm tra nguồn
  chuỗi trước (DOM/API), đừng sửa template mù.
- **PayServer / refund / Crontab:** `AliPayServer`/`BalancePayServer`/
  `WeChatPayServer::getError()` bọc `lang()` (giá trị không-key fallback
  nguyên vẹn); `WeChatPayServer:278` `return $fail(lang('通信失败'))`;
  `OrderRefundLogic:210` default param `'退款成功'` → `null` + `?? lang()`;
  `CrontabLogic:46` dịch const tại điểm dùng
  (`lang(Crontab_::$type[$v['type']])`, const giữ nguyên).
- **`Index.php` thêm action `pcLists()`** (trả `PcLogic::pcLists()` qua
  `_success`): whitelist `$like_not_need_login` đã có 'pcLists' từ trước
  nhưng thiếu method → 404. Đã kiểm chứng live: `GET /api/index/pcLists`
  → code 1, tên sản phẩm tiếng Việt.
- **uView (62 key mới, `uniapp/vi.part3.json`):** text node template →
  `{{$t('key')}}`; script → `this.$t('key')`; **prop default →
  `default() { return this.$t('key') }`** (Vue 2 gọi default function
  với vm làm `this`); map trong `data()` (u-empty icons) →
  `this.$t(...)`; `u-calendar` weekday → `$t('星期' + [...][week])`,
  title → `$t('年月', {year, month})`; `u-picker` 年/月/日/时/分/秒;
  `u-count-down` 时/分 ternary; toast u-upload/u-checkbox (kể cả
  `不允许选择{ext}格式的文件`, `最多可选{max}项`).
- **Uniapp pass 2 (quét bù đa dòng):** scanner template đa dòng tìm thêm
  42 segment mà báo cáo 133 chuỗi gốc bỏ qua (chỉ quét 1 dòng, cắt tối
  đa 3 location/chuỗi): occurrence thứ 4+ của chuỗi đã dịch + vài trang
  chưa đụng (user_profile, user_spread, order_details, login, register).
  12 key mới vào part2. File trùng `uniapp/bundle/pages/contact_offical/
  contact_offical copy.vue` đã xóa (untracked, không ai tham chiếu).
  Sau pass 2 quét lại chỉ còn 4 segment cố ý: 3 trong HTML comment
  (after_sales_detail) + `中文` (nhãn ngôn ngữ).
- **UI chuyển ngôn ngữ:** `bundle/pages/user_set/user_set.vue` thêm hàng
  "Ngôn ngữ" (Tiếng Việt / 中文, tên ngôn ngữ giữ nguyên ngữ),
  `changeLocale()` gọi `setLocale()` (`utils/i18n.js`, đổi `i18n.locale`
  reactive + lưu cache `LOCALE`).
- **PC CSS `content`:** `pc/pages/user/profile.vue:431`
  `content: '更换头像'` trong `&::after` không dịch được → bỏ `content`,
  đưa nhãn vào template `<div class="mask white">{{ $t('更换头像') }}
  </div>`, chuyển style overlay sang `.mask`. Build mới đã verify trong
  `pc/dist/`: không còn `content:` trong CSS, mask render
  `$t("更换头像")`. 4 segment PC còn lại đều trong HTML comment.
- **CRLF (quan trọng):** nhiều `.vue` là CRLF thuần (85 file uview-ui)
  hoặc mixed (vd `license.vue` 2 CRLF + 69 LF). Script Python đọc/ghi
  text mode mặc định dịch `\r\n`→`\n` làm hỏng toàn file — mọi script
  sửa file phải mở `newline=''`; file mixed sửa byte-level từ HEAD.
  Đã sửa 5 file bị hỏng trong phase 2 (diff cuối 1/1 dòng).
- **Session admin rất ngắn (vài phút):** mỗi lần chụp headless phải
  login lại (captcha → đọc bằng mắt → RSA openssl → POST). Chrome phải
  dùng profile mới + `Network.setCacheDisabled`, nếu không Chrome cache
  trang cũ — đã gặp 3 lần chụp ra ảnh byte-identical dù template đã sửa.
  `pkill -f` với pattern chứa chuỗi trong lệnh của chính mình sẽ tự giết
  shell — dùng PID cụ thể.
- **PC generate trên VM mới:** user `builder` không còn sau khi VM bị thay
  (15/11) — lần này chạy `npm run generate` bằng root vẫn OK (15:52,
  `pc/dist/` 3.0M, EXIT=0). Vẫn giữ quy tắc builder khi user đó tồn tại.
  `pc/dist/` mới CHƯA publish vào `server/public/pc/` (chờ duyệt riêng);
  đã kiểm chứng bằng location nginx tạm `/pc/` → `pc/dist/` rồi gỡ ngay.
- **Đồng bộ locale runtime:** sau `merge.py` phải copy
  `tools/i18n/locales/<mặt>/vi.json` + `zh.json` sang `uniapp/locales/`
  và `pc/locales/` (giữ 0 diff) — phase 2 từng lệch 3 file.
- **Số liệu sau phase 2:** backend 1.550 key (1.544 filled, 6 empty cố ý:
  tên sản phẩm demo/địa chỉ), admin 2.413 key (0 empty), uniapp 854 key
  (845 filled, 9 empty), pc 363 key; `cjk_in_values=0` cả 4 mặt.
  `php -l` sạch toàn bộ file PHP đã sửa. Quét kiểm chứng cuối: uniapp +
  PC chỉ còn segment cố ý trong comment/nhãn ngôn ngữ.
