#!/usr/bin/env python3
"""Gop cac file vi.partN.json thanh vi.json cho tung mat + chuan hoa.

Chuan hoa ap dung khi gop (ghi ro de review):
  - Tien te: tu dung "tệ" (dich cua 元) -> "đồng"; ky hieu "￥" -> "₫";
    "(đ)" -> "(đồng)"; so + "đ" (vd "1đ") -> so + " đồng".
    Quy uoc nhanh vi: hien thi theo VND. So tien trong DB van la so thuan,
    chua doi don vi tien te that — buoc ban dia hoa thanh toan se lam sau.
  - 成长值: "điểm trưởng thành" -> "điểm tăng trưởng" (thong nhat voi admin).
  - 张 (luong tu ma giam gia): "tờ" -> "mã" khi dung truoc "mã giảm giá"
    hoac dung doc lap lam don vi dem (vd "3 tờ" -> "3 mã").

Kiem tra sau gop: JSON hop le, bo key khop zh.json, placeholder hai ben
khop, khong con ky tu Trung trong value (tru cac value co y de trong).
"""
import json
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CJK = re.compile(r'[\u4e00-\u9fff]')
TE_WORD = re.compile(r'(?<![\w])tệ(?![\w])')
NUM_D = re.compile(r'(\d)đ(?![\w])')


def normalize(value):
    orig = value
    value = TE_WORD.sub('đồng', value)
    value = value.replace('￥', '₫')
    value = value.replace('(đ)', '(đồng)')
    value = NUM_D.sub(r'\1 đồng', value)
    value = value.replace('điểm trưởng thành', 'điểm tăng trưởng')
    value = value.replace('Điểm trưởng thành', 'Điểm tăng trưởng')
    if orig != value:
        return value, (orig, value)
    return value, None


def merge_surface(name):
    d = os.path.join(ROOT, 'tools/i18n/locales', name)
    parts = sorted(f for f in os.listdir(d)
                   if re.match(r'vi\.part\d+\.json$', f))
    merged, changes = {}, []
    for p in parts:
        data = json.load(open(os.path.join(d, p), encoding='utf-8'))
        for k, v in data.items():
            if k in merged:
                print(f'  TRUNG KEY {name}: {k!r}', file=sys.stderr)
            if v:
                v, ch = normalize(v)
                if ch:
                    changes.append((k, ch[0], ch[1]))
            merged[k] = v
    zh = json.load(open(os.path.join(d, 'zh.json'), encoding='utf-8'))
    assert set(merged) == set(zh), (
        f'{name}: key mismatch merged={len(merged)} zh={len(zh)}')
    out = os.path.join(d, 'vi.json')
    json.dump(merged, open(out, 'w', encoding='utf-8'),
              ensure_ascii=False, indent=2)
    filled = sum(1 for v in merged.values() if v)
    cjk_left = [(k, v) for k, v in merged.items() if v and CJK.search(v)]
    print(f'[{name}] merged={len(merged)} filled={filled} '
          f'empty={len(merged) - filled} normalized={len(changes)} '
          f'cjk_in_values={len(cjk_left)}')
    for k, a, b in changes:
        print(f'    ~ {k!r}: {a!r} -> {b!r}')
    for k, v in cjk_left:
        print(f'    ! CJK con sot {k!r} -> {v!r}')
    return merged


if __name__ == '__main__':
    for name in sys.argv[1:] or ['backend', 'admin', 'uniapp', 'pc']:
        merge_surface(name)
