#!/usr/bin/env python3
"""Sinh file ngon ngu ThinkPHP tu locale JSON.

Doc: server/application/lang/{vi,zh-cn}.php — ThinkPHP tu nap file nay o
loadLangPack() theo langset hien tai. Key trong file PHẢI viet thuong vi
Lang::get() va Lang::load() deu strtolower key (array_change_key_case).
Key goc (msgid tieng Trung) khong phan biet hoa/thuong, nhung key co ky tu
Latin ma chi khac nhau o hoa/thuong se DUNG DO sau khi lowercase — script
bao loi neu gap truong hop do.

Chay: python3 tools/i18n/build_lang_php.py
"""
import json
import os
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))


def php_escape(s):
    return s.replace('\\', '\\\\').replace("'", "\\'")


def build(lang, src, dest_dir, src_label):
    data = json.load(open(src, encoding='utf-8'))
    out = {}
    collisions = []
    for k, v in data.items():
        lk = k.lower()
        if lk in out and out[lk] != v:
            collisions.append((k, lk))
        out[lk] = v
    if collisions:
        # ThinkPHP runtime cung lower-case key (array_change_key_case, last wins)
        # nen 2 key chi khac hoa/thuong se dung do thanh 1 — giu ban cuoi nhu
        # runtime, chi canh bao de review (vd 'H5商城' vs 'h5商城').
        print(f'[{lang}] COLLISION sau lowercase (giu ban cuoi): {len(collisions)}',
              file=sys.stderr)
        for k, lk in collisions[:10]:
            print(f'  {k!r} -> {lk!r}', file=sys.stderr)
    lines = [
        '<?php',
        '// LikeShop i18n — nhánh vi (hard fork).',
        '// Sinh tự động từ tools/i18n/locales/%s — KHÔNG sửa tay.' % src_label,
        '// Key = chuỗi tiếng Trung gốc (msgid, kiểu gettext); thiếu bản dịch thì',
        '// Lang::get() trả về đúng key. Thêm ngôn ngữ mới = thêm 1 file.',
        'return [',
    ]
    for k in sorted(out):
        lines.append("    '%s' => '%s'," % (php_escape(k), php_escape(out[k])))
    lines.append('];')
    dest = os.path.join(ROOT, dest_dir, lang + '.php')
    os.makedirs(os.path.dirname(dest), exist_ok=True)
    with open(dest, 'w', encoding='utf-8') as f:
        f.write('\n'.join(lines) + '\n')
    print(f'[{lang}] {dest} — {len(out)} keys')


if __name__ == '__main__':
    # File app: key backend (controller/logic/api) — ThinkPHP nap o loadLangPack.
    base = os.path.join(ROOT, 'tools/i18n/locales/backend')
    build('vi', os.path.join(base, 'vi.json'), 'server/application/lang',
          'backend/vi.json')
    build('zh-cn', os.path.join(base, 'zh.json'), 'server/application/lang',
          'backend/zh.json')
    # File module admin: key rieng cua template admin — ThinkPHP nap them o
    # loadLangPack() (application/admin/lang/{langset}.php), ghi de key app
    # khi trung (vd '请输入密码' o admin ngan gon hon ban backend).
    adm = os.path.join(ROOT, 'tools/i18n/locales/admin')
    build('vi', os.path.join(adm, 'vi.json'), 'server/application/admin/lang',
          'admin/vi.json')
    build('zh-cn', os.path.join(adm, 'zh.json'), 'server/application/admin/lang',
          'admin/zh.json')
