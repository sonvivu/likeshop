# -*- coding: utf-8 -*-
"""Bu sung: boc cac text node nhieu dong / trong script cua admin template
ma extract.py --scan bo sot (chu yeu vi text nam tren dong rieng hoac sau mustache).
- Key = chuoi tieng Trung goc (msgid), giong quy uoc chung.
- Chi boc nhung dong da liet ke trong /tmp/admin-segs2.json (khong quet lai toan bo).
- Cac truong hop dac biet (cau hoan chinh, thu tu tu) xu ly rieng o cuoi file.
"""
import json, re, sys

REPO = '/home/hatch/workspace/likeshop-test/likeshop'
TRANSLATIONS = {
 '1、商品至少需要选择一项配送方式；2、还需前往设置->': '1. Mỗi sản phẩm cần chọn ít nhất một phương thức giao hàng; 2. Cần vào Cài đặt ->',
 '一级佣金': 'Hoa hồng cấp 1',
 '三级佣金': 'Hoa hồng cấp 3',
 '上传图片': 'Tải ảnh lên',
 '不可提现金额': 'Số tiền không thể rút',
 '二级佣金': 'Hoa hồng cấp 2',
 '今日入账佣金/元': 'Hoa hồng đã vào hôm nay (đồng)',
 '今日新增待结算佣金/元': 'Hoa hồng chờ quyết toán mới hôm nay (đồng)',
 '付款方式': 'Phương thức thanh toán',
 '件': 'cái',
 '会员信息缺失': 'Thiếu thông tin thành viên',
 '会员消息': 'Tin nhắn thành viên',
 '余额': 'Số dư',
 '佣金比例': 'Tỷ lệ hoa hồng',
 '佣金金额': 'Số tiền hoa hồng',
 '使用默认说明': 'Dùng hướng dẫn mặc định',
 '修改物流信息': 'Sửa thông tin vận chuyển',
 '允许发放佣金的分销层级': 'Cấp tiếp thị liên kết được phát hoa hồng',
 '关闭分销功能后不会再产生新的分销佣金，商城端分销入口会关闭。': 'Sau khi tắt tính năng tiếp thị liên kết sẽ không phát sinh hoa hồng tiếp thị mới, lối vào tiếp thị ở cửa hàng sẽ đóng.',
 '关闭功能后用户之间不能建立新的上下级关系。': 'Sau khi tắt tính năng, người dùng không thể thiết lập quan hệ cấp trên/cấp dưới mới.',
 '分销会员/人': 'Thành viên tiếp thị liên kết (người)',
 '分销会员占比/百分比': 'Tỷ lệ thành viên tiếp thị liên kết (%)',
 '分销会员昵称': 'Biệt danh thành viên tiếp thị liên kết',
 '分销会员编号': 'Mã thành viên tiếp thị liên kết',
 '删除图片': 'Xóa ảnh',
 '勾选全部用户可以邀请表示系统所有用户都有邀请下级的资格': 'Tích chọn tất cả người dùng có thể mời nghĩa là mọi người dùng trong hệ thống đều có quyền mời cấp dưới',
 '区域分红比例': 'Tỷ lệ chia lợi nhuận khu vực',
 '可提现金额': 'Số tiền có thể rút',
 '启动': 'Khởi động',
 '售后保障': 'Bảo đảm hậu mãi',
 '商品编号': 'Mã sản phẩm',
 '商品金额': 'Số tiền sản phẩm',
 '商家售后单数量': 'Số đơn hậu mãi của cửa hàng',
 '如何接入企业微信客服请查看': 'Cách kết nối CSKH WeChat doanh nghiệp, vui lòng xem',
 '官网创建你的Clarity统计应用': 'Tạo ứng dụng thống kê Clarity của bạn trên trang chủ',
 '小程序账号登录微信公众平台，点击开发>基本配置>': 'Đăng nhập tài khoản mini-program vào nền tảng công khai WeChat, bấm Phát triển > Cấu hình cơ bản >',
 '小程序账号登录微信公众平台，点击设置>公众号设置>': 'Đăng nhập tài khoản mini-program vào nền tảng công khai WeChat, bấm Cài đặt > Cài đặt tài khoản công khai >',
 '平台消息': 'Tin nhắn nền tảng',
 '应付金额': 'Số tiền phải trả',
 '开通分销会员条件切换至无条件时，所有用户都将开通分销会员资格。': 'Khi điều kiện mở thành viên tiếp thị liên kết chuyển thành không điều kiện, mọi người dùng đều sẽ được mở tư cách thành viên tiếp thị liên kết.',
 '待结算佣金/元': 'Hoa hồng chờ quyết toán (đồng)',
 '微信号': 'ID WeChat',
 '快递100Key: 快递100企业管理后台 -> 我的信息 -> 企业信息': 'Key Kuaidi100: Trang quản trị doanh nghiệp Kuaidi100 -> Thông tin của tôi -> Thông tin doanh nghiệp',
 '快递100Secret: 快递100企业管理后台 -> 我的信息 -> 企业信息': 'Secret Kuaidi100: Trang quản trị doanh nghiệp Kuaidi100 -> Thông tin của tôi -> Thông tin doanh nghiệp',
 '性别': 'Giới tính',
 '总资产': 'Tổng tài sản',
 '成交订单（笔）': 'Đơn hàng đã giao dịch (đơn)',
 '手机号': 'Số điện thoại',
 '提现失败': 'Rút tiền thất bại',
 '提货码': 'Mã lấy hàng',
 '支付宝账号': 'Tài khoản Alipay',
 '收货人': 'Người nhận hàng',
 '新增会员（人）': 'Thành viên mới (người)',
 '易联云': 'Yilianyun',
 '昵称': 'Biệt danh',
 '未填写评价': 'Chưa điền đánh giá',
 '根据商品实际支付金额及相应的佣金比例计算佣金': 'Hoa hồng được tính theo số tiền thực trả của sản phẩm và tỷ lệ hoa hồng tương ứng',
 '消息加密方式': 'Cách mã hóa tin nhắn',
 '消息推送': 'Đẩy tin nhắn',
 '用户登录后首次绑定邀请码建立上下级关系。包括扫码，点击分享链接，输入邀请码等场景。': 'Người dùng lần đầu ràng buộc mã mời sau khi đăng nhập để thiết lập quan hệ cấp trên/cấp dưới. Bao gồm các trường hợp quét mã, bấm liên kết chia sẻ, nhập mã mời.',
 '用户访问量（人）': 'Lượt truy cập người dùng (người)',
 '登录微信公众平台，点击开发>基本配置>': 'Đăng nhập nền tảng công khai WeChat, bấm Phát triển > Cấu hình cơ bản >',
 '登录微信公众平台，点击设置>公众号设置>': 'Đăng nhập nền tảng công khai WeChat, bấm Cài đặt > Cài đặt tài khoản công khai >',
 '短信': 'SMS',
 '积分；赠送': 'Điểm tích lũy; tặng',
 '系统': 'Hệ thống',
 '累计已入账佣金/元': 'Hoa hồng đã vào lũy kế (đồng)',
 '级': 'cấp',
 '结算时间': 'Thời gian quyết toán',
 '编号': 'Mã số',
 '股东分红比例': 'Tỷ lệ chia lợi nhuận cổ đông',
 '自提门店': 'Cửa hàng tự lấy hàng',
 '规格名称': 'Tên thông số',
 '订单来源': 'Nguồn đơn hàng',
 '请参考部署文档配置系统客服': 'Vui lòng tham khảo tài liệu triển khai để cấu hình CSKH hệ thống',
 '请访问': 'Vui lòng truy cập',
 '赠送': 'Tặng',
 '运费金额': 'Số tiền vận chuyển',
 '近15天用户访问量UV（人）': 'Lượt truy cập người dùng UV 15 ngày gần nhất (người)',
 '近15天销售金额（元）': 'Doanh số bán hàng 15 ngày gần nhất (đồng)',
 '销售金额（元）': 'Doanh số bán hàng (đồng)',
 '需前往小程序后台绑定客服人员': 'Cần vào trang quản trị mini-program để ràng buộc nhân viên CSKH',
 '预估佣金结算后无法进行回收，请谨慎设置结算天数': 'Hoa hồng dự kiến sau khi quyết toán không thể thu hồi, vui lòng đặt số ngày quyết toán cẩn thận',
 '*售后保障协议，在退款申请页面显示。': '*Thỏa thuận bảo đảm hậu mãi, hiển thị ở trang đăng ký hoàn tiền.',
 '*填写微信公众号开发配置，请前往微信公众平台申请服务号并完成认证。': '*Điền cấu hình phát triển tài khoản WeChat chính thức, vui lòng vào nền tảng công khai WeChat để đăng ký tài khoản dịch vụ và hoàn tất xác thực.',
 '*填写微信小程序开发配置，请前往微信公众平台申请小程序并完成认证。': '*Điền cấu hình phát triển mini-program WeChat, vui lòng vào nền tảng công khai WeChat để đăng ký mini-program và hoàn tất xác thực.',
 '*填写微信开放平台开发配置，请前往微信开放平台创建应用并完成认证。': '*Điền cấu hình phát triển nền tảng mở WeChat, vui lòng vào nền tảng mở WeChat để tạo ứng dụng và hoàn tất xác thực.',
 '*短信消息需要先在设置->基础设置->': '*Tin nhắn SMS cần vào Cài đặt -> Cài đặt cơ bản ->',
 '*设置会员下单的奖励规则': '*Đặt quy tắc thưởng khi thành viên đặt hàng',
 '*设置会员注册的奖励规则': '*Đặt quy tắc thưởng khi thành viên đăng ký',
 '*设置会员邀请的奖励规则。': '*Đặt quy tắc thưởng khi thành viên mời người khác.',
 '*设置商城分享标题，分享简介。': '*Đặt tiêu đề chia sẻ cửa hàng, giới thiệu chia sẻ.',
 '*设置店铺的配送方式': '*Đặt phương thức giao hàng của cửa hàng',
 '*设置积分营销的规则，可以关闭积分抵现功能': '*Đặt quy tắc marketing điểm tích lũy, có thể tắt tính năng khấu trừ tiền bằng điểm',
 '*设置移动端商城个人中心->我的服务的菜单。支持选择系统模块，填写自定义链接': '*Đặt menu mục Dịch vụ của tôi trong trang cá nhân của cửa hàng mobile. Hỗ trợ chọn module hệ thống, điền liên kết tùy chỉnh',
 '*设置移动端商城底部导航图标和名称': '*Đặt biểu tượng và tên điều hướng dưới cùng của cửa hàng mobile',
 '*设置移动端商城首页导航区的菜单。支持选择系统模块、填写自定义链接': '*Đặt menu khu vực điều hướng trang chủ cửa hàng mobile. Hỗ trợ chọn module hệ thống, điền liên kết tùy chỉnh',
 '*配置微信公众号菜单，点击确认，保存菜单并发布至微信公众号。': '*Cấu hình menu tài khoản WeChat chính thức, bấm xác nhận để lưu menu và phát hành lên tài khoản WeChat chính thức.',
 '更新时间': 'Thời gian cập nhật',
 '添加规格项目': 'Thêm mục thông số',
 '成功': 'Thành công',
 '失败': 'Thất bại',
 # cau hoan chinh (xu ly rieng): key giu nguyen mustache trong ban dich de laytpl render sau
 '{{first.DT}}的内容，支持嵌入场景变量，复制场景变量的值填入即可生效': 'Nội dung của {{first.DT}}, hỗ trợ nhúng biến cảnh, sao chép giá trị biến cảnh rồi điền vào là có hiệu lực',
 '{{remrk.DT}}的内容，支持嵌入场景变量，复制场景变量的值填入即可生效': 'Nội dung của {{remrk.DT}}, hỗ trợ nhúng biến cảnh, sao chép giá trị biến cảnh rồi điền vào là có hiệu lực',
 '如：https://www.likecms.net/。可设置关联公众号的文章，其它地址需登录微信小程序管理后台配置业务域名': 'Ví dụ: https://www.likecms.net/. Có thể đặt bài viết liên kết tới tài khoản công khai, địa chỉ khác cần đăng nhập trang quản trị mini-program WeChat để cấu hình tên miền nghiệp vụ',
}
# cac key dac biet: (chuoi goc trong dong) -> (key dung, ban dich lay tu TRANSLATIONS hoac locale)
SPECIAL = {
 'delivery_batch/delivery.html:27': ('成功', None),
 'delivery_batch/delivery.html:28': ('失败', None),
}

def norm_key(k):
    k = k.lstrip('>').lstrip('/').strip()
    k = k.rstrip('：:').strip()
    return k

def main():
    d = json.load(open('/tmp/admin-segs2.json', encoding='utf-8'))
    vi = json.load(open(f'{REPO}/tools/i18n/locales/admin/vi.json', encoding='utf-8'))
    part4 = {}
    wrapped = 0
    skipped = []
    by_file = {}
    for c in d['cands']:
        by_file.setdefault(c['f'], {})[c['n']] = by_file.setdefault(c['f'], {}).get(c['n'], []) + [c['key']]
    for f, lines_map in by_file.items():
        path = f'{REPO}/{f}'
        lines = open(path, encoding='utf-8', errors='replace').read().split('\n')
        changed = False
        for n, keys in lines_map.items():
            line = lines[n - 1]
            if '{:lang(' in line and all(k in line for k in keys):
                pass
            for raw_key in keys:
                key = norm_key(raw_key)
                if not key or key.startswith('//') or key.startswith('* '):
                    skipped.append((f, n, raw_key, 'junk/comment'))
                    continue
                if key not in vi and key not in TRANSLATIONS:
                    skipped.append((f, n, raw_key, 'NO TRANSLATION'))
                    continue
                if "'" in key:
                    skipped.append((f, n, raw_key, 'quote in key'))
                    continue
                # tim chuoi goc trong dong: uu tien key day du, roi key da strip
                target = None
                for cand in (raw_key, key):
                    if cand and cand in line:
                        target = cand
                        break
                if target is None:
                    skipped.append((f, n, raw_key, 'not found in line'))
                    continue
                if "{:lang('" + key + "')}" in line:
                    continue
                # khong boc lan 2: neu ngay truoc target da co {:lang(
                idx = line.find(target)
                if "{:lang('" in line[max(0, idx - 9):idx]:
                    continue
                line = line[:idx] + "{:lang('" + key + "')}" + line[idx + len(target):]
                wrapped += 1
                if key in TRANSLATIONS and key not in vi:
                    part4[key] = TRANSLATIONS[key]
            if line != lines[n - 1]:
                lines[n - 1] = line
                changed = True
        if changed:
            open(path, 'w', encoding='utf-8').write('\n'.join(lines))
    # dac biet: delivery_batch / 成功 / 失败 (key da co trong locale)
    for rel, (key, _) in SPECIAL.items():
        f, n = rel.split(':')
        path = f'{REPO}/server/application/admin/view/{f}'
        lines = open(path, encoding='utf-8').read().split('\n')
        line = lines[int(n) - 1]
        assert key in line, f'{rel}: {key} not in line'
        if "{:lang('" + key + "')}" in line:
            print('special already wrapped:', rel, key)
            continue
        lines[int(n) - 1] = line.replace(key, "{:lang('" + key + "')}", 1)
        open(path, 'w', encoding='utf-8').write('\n'.join(lines))
        wrapped += 1
        print('special:', rel, key)
    # menu_decorate: cau vi du day du
    for rel in ('server/application/admin/view/menu_decorate/add.html',
                'server/application/admin/view/menu_decorate/edit.html'):
        path = f'{REPO}/{rel}'
        s = open(path, encoding='utf-8').read()
        old = '如：https://www.likecms.net/。可设置关联公众号的文章，其它地址需登录微信小程序管理后台配置业务域名'
        assert s.count(old) == 1, f'{rel}: count {s.count(old)}'
        s = s.replace(old, "{:lang('" + old + "')}", 1)
        open(path, 'w', encoding='utf-8').write(s)
        wrapped += 1
        part4[old] = TRANSLATIONS[old]
        print('manual:', rel)
    # notice_setting: key chua mustache (laytpl render sau khi server render lang)
    for rel, var in (('server/application/admin/view/notice_setting/set_oa.html', 'first'),
                     ('server/application/admin/view/notice_setting/set_oa.html', 'remrk')):
        path = f'{REPO}/{rel}'
        s = open(path, encoding='utf-8').read()
        old = '{{%s.DT}}的内容，支持嵌入场景变量，复制场景变量的值填入即可生效' % var
        assert s.count(old) == 1, f'{rel} {var}: count {s.count(old)}'
        s = s.replace(old, "{:lang('" + old + "')}", 1)
        open(path, 'w', encoding='utf-8').write(s)
        wrapped += 1
        part4[old] = TRANSLATIONS[old]
        print('manual:', rel, var)
    print('wrapped:', wrapped)
    print('part4 new keys:', len(part4))
    json.dump(part4, open(f'{REPO}/tools/i18n/locales/admin/vi.part4.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
    print('SKIPPED:')
    for s in skipped:
        print('  ', s)

if __name__ == '__main__':
    main()
