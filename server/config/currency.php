<?php
// Cấu hình tiền tệ hiển thị của cửa hàng.
//
// Trước đây ký hiệu tiền tệ (￥) bị hardcode rải rác trong code PHP và
// template admin (40 chỗ PHP + 23 chỗ HTML). Đổi tiền tệ phải sửa từng
// chỗ. Từ nay mọi chỗ hiển thị tiền đều lấy ký hiệu từ đây:
//   - PHP:     config('currency.symbol')
//   - Template: {:config('currency.symbol')}
// Số tiền trong DB vẫn là số thuần, không kèm ký hiệu hay đơn vị.
//
// Muốn đổi sang tiền khác (vd USD/$): chỉ cần sửa 3 giá trị dưới đây,
// không cần đụng code. Lưu ý: tên/mã trong locale (lang files) là bản
// dịch của chuỗi tiếng Trung gốc, không đọc config này.

return [
    // Mã tiền tệ ISO 4217 (VND, USD, CNY, EUR...)
    'code'   => 'VND',

    // Ký hiệu hiển thị kèm số tiền trong giao diện (vd ₫, $, ¥, €)
    'symbol' => '₫',

    // Tên tiền tệ hiển thị khi cần gọi tên (vd đồng, Dollar, Nhân dân tệ)
    'name'   => 'đồng',
];
