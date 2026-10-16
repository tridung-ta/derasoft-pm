"""Check/sync manually curated project lessons. No hooks, network or app execution."""
import argparse
import hashlib
import json
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[2]
REMOTE = 'https://github.com/tridung-ta/derasoft-pm.git'
LABELS = ['Loại', 'Khi gặp', 'Nguyên nhân', 'Hành động', 'Kiểm tra', 'Bằng chứng']


def build():
    source = (ROOT / 'docs/BUG_LESSONS.md').read_text(encoding='utf-8')
    lessons = []
    for match in re.finditer(r'^## (PM-L\d{3})[^\n]*\n(.*?)(?=^## |\Z)', source, re.M | re.S):
        ident, body = match.groups()
        if f'<a id="{ident.lower()}"></a>' not in source:
            raise ValueError('Missing lesson link anchor: ' + ident)
        title = match[0].splitlines()[0].split(' — ', 1)[1]
        fields = {}
        for line in body.splitlines():
            if line.startswith('- ') and ':' in line:
                key, value = line[2:].split(':', 1)
                fields[key] = value.strip()
        if not all(fields.get(key) for key in LABELS):
            raise ValueError('Missing trigger/cause/action/check/evidence: ' + ident)
        unresolved = ident == 'PM-L028'
        lessons.append({
            'id': ident.lower(), 'title': title,
            'trigger': fields[LABELS[1]], 'action': fields[LABELS[3]],
            'classification': fields[LABELS[0]], 'cause': fields[LABELS[2]],
            'verification': fields[LABELS[4]], 'evidence': fields[LABELS[5]],
            'scope': 'project', 'project_id': hashlib.sha256(REMOTE.encode()).hexdigest()[:12],
            'project_name': 'DeraSoft PM', 'confidence': 0.5 if unresolved else 0.9,
            'confidence_basis': 'Human-curated rule, not statistical probability; uncertainty retained in cause/evidence.',
            'source': 'manual-session-and-project-evidence-review',
            'status': 'unresolved-cause' if unresolved else 'documented',
            'updated_at': '2026-10-16',
            'lesson_document': 'docs/BUG_LESSONS.md#' + ident.lower()
        })
    if not lessons or len({item['id'] for item in lessons}) != len(lessons):
        raise ValueError('Missing or duplicate lesson IDs')
    refs = set(re.findall(r'(?:docs|tests)/[A-Za-z0-9_./-]+\.(?:md|php|js|py|ps1)', source))
    missing = sorted(path for path in refs if not (ROOT / path).is_file())
    if missing:
        raise ValueError('Missing evidence/test files: ' + ', '.join(missing))
    if 'docs/BUG_LESSONS.md' not in (ROOT / 'AGENTS.md').read_text(encoding='utf-8'):
        raise ValueError('AGENTS.md must link the project memory')
    payload = {
        'schema_version': 1,
        'adaptation': 'ECC continuous-learning-v2 manual project-scoped memory for Codex; no observer/hooks/import configured',
        'remote': REMOTE, 'updated_at': '2026-10-16', 'instincts': lessons
    }
    return payload, len(refs)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    mode = parser.add_mutually_exclusive_group(required=True)
    mode.add_argument('--sync', action='store_true', help='Regenerate only docs/learning/instincts.json')
    mode.add_argument('--check', action='store_true', help='Read-only consistency/evidence check')
    args = parser.parse_args()
    payload, ref_count = build()
    target = ROOT / 'docs/learning/instincts.json'
    if args.sync:
        target.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    elif json.loads(target.read_text(encoding='utf-8')) != payload:
        raise ValueError('Memory drift: review Markdown then run --sync')
    print(f'PASS: {len(payload["instincts"])} project lessons; {ref_count} existing evidence/test references; AGENTS link and JSON consistency.')
