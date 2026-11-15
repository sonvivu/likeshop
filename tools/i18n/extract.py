#!/usr/bin/env python3
"""LikeShop i18n extractor (nhanh vi, hard fork).

Quet code, trich cac literal tieng Trung (CJK) thanh msgid (kieu gettext:
key = dung chuoi Trung goc), sinh file locale JSON cho tung mat, phan loai
chuoi hien thi / du lieu / noi suy, va (voi --apply) thay code dung key i18n.

Mat ho tro: backend (PHP), admin (template ThinkPHP .html),
uniapp (.vue/.js), pc (.vue/.js). Loai tru: area.js (data dia danh TQ),
uview-ui (lib ben thu ba), vendor, node_modules, .git.

Cach dung:
  python3 tools/i18n/extract.py --scan            # chi sinh locale + bao cao
  python3 tools/i18n/extract.py --apply           # thay code (chi literal sach)
  python3 tools/i18n/extract.py --apply --dry-run # in ra cho thay doi, khong ghi

Quy tac thay the an toan (chi ap dung cho literal "sach"):
  - Khong noi suy: khong {$var}, ${...}, %s, %d, noi chuoi ke ben.
  - Khong phai du lieu: khong nam trong so sanh (==,!=,===), switch/case,
    where(, in_array, khoa array, order/group, ten truong DB.
  - Khong phai SQL/URL/ten file.
  - Literal noi suy hoac du lieu -> ghi vao manual.json de lam tay.
"""
import re
import os
import sys
import json
import argparse
import collections

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
CJK = re.compile(r'[\u4e00-\u9fff]')
SQ = re.compile(r"'((?:\\.|[^'\\])*)'")
DQ = re.compile(r'"((?:\\.|[^"\\])*)"')
BT = re.compile(r'`((?:\\.|[^`\\])*)`')
# Mot pass duy nhat cho moi dong: tranh literal long nhau (vd '...\"...\"...'
# ma DQ rieng se bat nham phan trong). Tim theo thu tu xuat hien.
LIT = re.compile(
    r"'((?:\\.|[^'\\])*)'|\"((?:\\.|[^\"\\])*)\"|`((?:\\.|[^`\\])*)`")
TEXTNODE = re.compile(r'>([^<>]{1,200})<')

# Duong dan loai tru khoi quet (data, lib, build san)
EXCLUDE_DIRS = {'.git', 'node_modules', 'vendor', 'uview-ui', 'runtime',
                'lang'}  # lang: file ngon ngu ThinkPHP sinh tu dong, khong phai source
EXCLUDE_FILES = {'area.js'}

SURFACES = {
    'backend': {
        'root': 'server/application',
        'exts': ('.php',),
        'kinds': ('literal',),
    },
    'admin': {
        'root': 'server/application/admin/view',
        'exts': ('.html',),
        'kinds': ('literal', 'textnode'),
    },
    'uniapp': {
        'root': 'uniapp',
        'exts': ('.vue', '.js'),
        'kinds': ('literal', 'textnode'),
    },
    'pc': {
        'root': 'pc',
        'exts': ('.vue', '.js'),
        'kinds': ('literal', 'textnode'),
    },
}

# Ngu canh bao hieu literal la DU LIEU (gia tri enum/truong DB), khong dich runtime.
# CAN THAN: 'switch' phai la lenh switch (switch-tab cua Layui khong tinh),
# 'select' phai la SQL (the <select> HTML khong tinh).
DATA_CTX = re.compile(
    r"(==|!=|===|!==|switch\s*\(|switch\s+\w+\s*\{|case\s+['\"]|"
    r"in_array|array_key_exists|"
    r"where\s*\(|order\s+by|group\s+by|->where|::where|"
    r"field\s*\(|->field|->select\s*\(|select\s+[\w\*,\s]+\s+from\b|"
    r"Db::|->query|->execute|"
    r"Cache::|Config::|['\"]\s*=>\s*$|=>\s*['\"]?$)"
)
# Ngu canh bao hieu literal HIEN THI (an toan de thay)
DISPLAY_CTX = re.compile(
    r"(_success|_error|_success_|throw new|Exception|msg|message|toast|"
    r"alert|confirm|return\s|echo\s|title|label|placeholder|text)"
)


def strip_blocks(s):
    def repl(m):
        t = m.group(0)
        return '\n' * t.count('\n')
    s = re.sub(r'/\*.*?\*/', repl, s, flags=re.S)
    s = re.sub(r'<!--.*?-->', repl, s, flags=re.S)
    return s


def is_interpolated(v):
    return ('{$' in v or '${' in v or '%s' in v or '%d' in v
            or re.search(r'\{\w+\}', v) is not None)


def is_sql_or_url(v):
    lv = v.lower()
    return ('http://' in lv or 'https://' in lv or 'www.' in lv
            or v.strip().upper().startswith(
                ('SELECT ', 'INSERT ', 'UPDATE ', 'DELETE ', 'FROM ',
                 'CREATE ', 'DROP ', 'ALTER '))
            or re.search(r'\.(php|js|vue|html|css|png|jpg|jpeg|gif|svg)\b', v)
            or 'FROM_UNIXTIME' in v or 'DATE_FORMAT' in v)


def classify(path, line_no, line, lit_start, lit_end, value):
    """Tra ve (kind, reason): kind = display|data|interp|sql|other."""
    if is_sql_or_url(value):
        return ('sql', 'sql/url/filename')
    if is_interpolated(value):
        return ('interp', 'co noi suy/placeholder')
    ctx = line[max(0, lit_start - 90):lit_end + 10]
    if DATA_CTX.search(ctx):
        return ('data', 'ngu canh du lieu: ' + ctx.strip()[:60])
    return ('display', '')

def extract_file(path, kinds):
    """Tra ve list dict {line, col, value, kind, reason, raw, quote, textnode}."""
    try:
        text = open(path, encoding='utf-8', errors='ignore').read()
    except OSError:
        return []
    clean = strip_blocks(text)
    lines = clean.split('\n')
    out = []
    for i, line in enumerate(lines):
        ls = line.strip()
        if ls.startswith('//') or ls.startswith('*') or ls.startswith('#'):
            continue
        if 'literal' in kinds:
            # Mot pass: LIT an theo thu tu, literal trong literal khong bat lai
            for m in LIT.finditer(line):
                v = m.group(1) if m.group(1) is not None else (
                    m.group(2) if m.group(2) is not None else m.group(3))
                q = "'" if m.group(1) is not None else (
                    '"' if m.group(2) is not None else '`')
                # Bo qua literal da la call i18n (scan phai idempotent sau apply):
                # :placeholder="$t('x')" match DQ co value "$t('x')"; con
                # $t('x') / lang('x') thi match SQ ben trong van lay dung msgid.
                # Chu Y: scan KHONG idempotent hoan toan tren code Vue da apply
                # (ternary trong attribute, classify doi display->data khi dong
                # co $t()...) — dung scan truoc apply; sau apply lay part file
                # lam goc, dung quet lai de sinh zh/vi.json.
                if any(t in v for t in ('$t(', 'this.$t(', 'i18n.t(', '{:lang(')):
                    continue
                if not CJK.search(v):
                    continue
                kind, reason = classify(path, i + 1, line, m.start(), m.end(), v)
                out.append({'line': i + 1, 'col': m.start(), 'value': v,
                            'kind': kind, 'reason': reason, 'quote': q,
                            'textnode': False, 'raw': m.group(0)})
        if 'textnode' in kinds:
            # Chi xet text node trong template (bo <script>/<style> o buoc loc sau)
            for m in TEXTNODE.finditer(line):
                v = m.group(1).strip()
                if not v or not CJK.search(v):
                    continue
                if v.startswith('{{') or v.endswith('}}'):
                    continue
                if re.search(r'[<>{}$]', v):
                    continue
                out.append({'line': i + 1, 'col': m.start(1), 'value': v,
                            'kind': 'display', 'reason': 'text node',
                            'quote': '', 'textnode': True, 'raw': v})
    return out


def scan_surface(name, cfg):
    root = os.path.join(ROOT, cfg['root'])
    found = []  # (relpath, entry)
    for dp, dn, fn in os.walk(root):
        dn[:] = [d for d in dn if d not in EXCLUDE_DIRS]
        for f in fn:
            if not any(f.endswith(e) for e in cfg['exts']):
                continue
            if f in EXCLUDE_FILES:
                continue
            p = os.path.join(dp, f)
            rel = os.path.relpath(p, ROOT)
            for e in extract_file(p, cfg['kinds']):
                found.append((rel, e))
    return found


def build_locales(surfaces):
    """Sinh dict: surface -> {zh:{msgid:msgid}, vi:{msgid:''}, usage, manual}."""
    result = {}
    for name in surfaces:
        found = scan_surface(name, SURFACES[name])
        zh, vi, usage, manual = {}, {}, collections.defaultdict(list), {}
        for rel, e in found:
            v = e['value']
            usage[v].append(rel + ':' + str(e['line']))
            if e['kind'] == 'display':
                zh[v] = v
                vi.setdefault(v, '')
            else:
                manual.setdefault(v, {'kind': e['kind'],
                                      'reason': e['reason'],
                                      'files': []})
                if rel not in manual[v]['files']:
                    manual[v]['files'].append(rel)
        result[name] = {
            'zh': dict(sorted(zh.items())),
            'vi': dict(sorted(vi.items())),
            'usage': {k: sorted(set(v)) for k, v in sorted(usage.items())},
            'manual': dict(sorted(manual.items())),
            'stats': {
                'files': len({r for r, _ in found}),
                'occurrences': len(found),
                'display_distinct': len(zh),
                'manual_distinct': len(manual),
            },
        }
    return result


def write_locales(result, outdir):
    for name, data in result.items():
        d = os.path.join(outdir, name)
        os.makedirs(d, exist_ok=True)
        for fn in ('zh', 'vi', 'usage', 'manual'):
            with open(os.path.join(d, fn + '.json'), 'w', encoding='utf-8') as f:
                json.dump(data[fn], f, ensure_ascii=False, indent=2)
                f.write('\n')
        print(f"[{name}] files={data['stats']['files']} "
              f"occurrences={data['stats']['occurrences']} "
              f"display_distinct={data['stats']['display_distinct']} "
              f"manual_distinct={data['stats']['manual_distinct']}")

def php_call(value):
    v = value.replace("\\'", "'").replace("'", "\\'")
    return "lang('" + v + "')"


def vue_call(value, in_script):
    v = value.replace("'", "\\'")
    return ("this.$t('" + v + "')") if in_script else ("$t('" + v + "')")


def tpl_call(value):
    """Call i18n trong template ThinkPHP: {:lang('...')} hoac {:lang("...")}
    (dung \" neu key co dau ', vi template parser khong hieu \\')."""
    if "'" in value and '"' not in value:
        return '{:lang("' + value + '")}'
    return "{:lang('" + value.replace("'", "\\'") + "')}"


def php_no_replace_lines(lines):
    """Dong PHP khong duoc thay literal bang lang(): gia tri mac dinh cua
    thuoc tinh class (protected $message = [...]), hang const, va gia tri
    mac dinh cua tham so ham — tat ca phai la bieu thuc hang, goi ham la fatal.
    Tra ve tap so dong (1-based) nam trong cac vung do."""
    skip = set()
    prop_re = re.compile(
        r'^\s*(public|protected|private|static|var)\s+(?!function\b).*=\s*')
    const_re = re.compile(r'^\s*const\s+\w+\s*=')
    func_re = re.compile(r'\bfunction\s*\w*\s*\(')
    i, n = 0, len(lines)
    while i < n:
        line = lines[i]
        if func_re.search(line):
            depth, started, j = 0, False, i
            while j < n:
                for ch in lines[j]:
                    if ch == '(':
                        depth += 1
                        started = True
                    elif ch == ')':
                        depth -= 1
                skip.add(j + 1)
                if started and depth <= 0:
                    break
                j += 1
            i = j + 1
            continue
        if prop_re.match(line) or const_re.match(line):
            depth, j = 0, i
            while j < n:
                for ch in lines[j]:
                    if ch in '[({':
                        depth += 1
                    elif ch in ']})':
                        depth -= 1
                skip.add(j + 1)
                if depth <= 0 and ';' in lines[j]:
                    break
                j += 1
            i = j + 1
            continue
        i += 1
    return skip


def apply_surface(name, cfg, dry_run=False):
    """Thay literal display sach bang call i18n. Tra ve so file/literal doi.
    Bo qua file .js (khong co $t/this.$t) — de lam tay o buoc manual."""
    root = os.path.join(ROOT, cfg['root'])
    changed_files, changed_lits, skipped, skipped_js = 0, 0, 0, 0
    skipped_prop = 0
    for dp, dn, fn in os.walk(root):
        dn[:] = [d for d in dn if d not in EXCLUDE_DIRS]
        for f in fn:
            if not any(f.endswith(e) for e in cfg['exts']):
                continue
            if f in EXCLUDE_FILES:
                continue
            p = os.path.join(dp, f)
            rel = os.path.relpath(p, ROOT)
            if p.endswith('.js'):
                n = len([e for e in extract_file(p, cfg['kinds'])
                         if e['kind'] == 'display'])
                if n:
                    skipped_js += n
                    print(f'  js-skip {rel}: {n} literals (lam tay)')
                continue
            entries = [e for e in extract_file(p, cfg['kinds'])
                       if e['kind'] == 'display']
            if not entries:
                continue
            raw_text = open(p, encoding='utf-8', errors='ignore').read()
            lines = raw_text.split('\n')
            # Vung PHP cam thay: gia tri mac dinh thuoc tinh/const/tham so ham
            no_replace = php_no_replace_lines(lines) if name == 'backend' else set()
            # Vung <script>...</script> cua file .vue (de chon $t hay this.$t)
            script_ranges = []
            if p.endswith('.vue'):
                for m in re.finditer(r'<script[^>]*>', raw_text):
                    start = raw_text[:m.start()].count('\n') + 1
                    end_m = re.search(r'</script>', raw_text[m.end():])
                    end = (raw_text[:m.end() + end_m.start()].count('\n') + 1
                           if end_m else len(lines))
                    script_ranges.append((start, end))
            by_line = collections.defaultdict(list)
            for e in entries:
                by_line[e['line']].append(e)
            file_changed = False
            for ln, es in sorted(by_line.items()):
                if ln in no_replace:
                    # Gia tri mac dinh thuoc tinh/const/tham so ham: goi ham la fatal
                    skipped_prop += len(es)
                    continue
                line = lines[ln - 1]
                # Thay tu phai sang trai de giu nguyen offset
                for e in sorted(es, key=lambda x: -x['col']):
                    v = e['value']
                    if e['textnode']:
                        old = '>' + v + '<'
                        if name == 'admin':
                            new = '>' + tpl_call(v) + '<'
                        else:
                            new = '>{{ $t(\'' + v.replace("'", "\\'") + '\') }}<'
                        if old in line:
                            line = line.replace(old, new, 1)
                            changed_lits += 1
                            file_changed = True
                        continue
                    raw = e['raw']
                    if 'lang(' in line and name in ('backend', 'admin'):
                        if raw in line and 'lang(' not in line.split(raw)[0][-40:]:
                            pass
                    if name == 'backend':
                        new = php_call(v)
                    elif name == 'admin':
                        # GiU NGUYEN dau ngoac goc: gia tri thuoc tinh HTML va
                        # chuoi JS can ngoac de trinh duyet/JS doc dung
                        # (placeholder="{:lang('...')}", var x = '{:lang('...')}')
                        new = e['quote'] + tpl_call(v) + e['quote']
                    else:
                        in_script = any(s <= ln <= t for s, t in script_ranges)
                        new = vue_call(v, in_script)
                    idx = line.rfind(raw)
                    if idx >= 0:
                        line = line[:idx] + new + line[idx + len(raw):]
                        changed_lits += 1
                        file_changed = True
                lines[ln - 1] = line
            if file_changed:
                changed_files += 1
                if not dry_run:
                    with open(p, 'w', encoding='utf-8') as fh:
                        fh.write('\n'.join(lines))
                print(('~ ' if dry_run else '+ ') + rel)
    print(f"[{name}] files_changed={changed_files} "
          f"literals_replaced={changed_lits} skipped={skipped} "
          f"skipped_prop_default={skipped_prop} "
          f"js_skipped_literals={skipped_js}")
    return changed_files, changed_lits


def main():
    ap = argparse.ArgumentParser(description='LikeShop i18n extractor')
    ap.add_argument('--scan', action='store_true', help='chi sinh locale + bao cao')
    ap.add_argument('--apply', action='store_true', help='thay code dung key i18n')
    ap.add_argument('--dry-run', action='store_true')
    ap.add_argument('--surface', nargs='*', default=list(SURFACES),
                    choices=list(SURFACES))
    ap.add_argument('--out', default=os.path.join(ROOT, 'tools/i18n/locales'))
    args = ap.parse_args()
    if args.scan or not args.apply:
        result = build_locales(args.surface)
        write_locales(result, args.out)
        total_d = sum(r['stats']['display_distinct'] for r in result.values())
        total_m = sum(r['stats']['manual_distinct'] for r in result.values())
        print(f"TOTAL display_distinct={total_d} manual_distinct={total_m}")
    if args.apply:
        for name in args.surface:
            apply_surface(name, SURFACES[name], dry_run=args.dry_run)


if __name__ == '__main__':
    main()
