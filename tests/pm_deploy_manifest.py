"""Read-only Git/FileZilla manifest; never uploads or executes SQL."""
from pathlib import Path
import subprocess,hashlib,json,argparse
root=Path(__file__).resolve().parents[1]
baseline='3df33493da81f53e2fdc67d826998850812dbd2f'
git=['git','-c',f'safe.directory={root.as_posix()}']
def run(*args):return subprocess.check_output(git+list(args),cwd=root).decode('utf-8').strip()
def main():
    parser=argparse.ArgumentParser();parser.add_argument('--check',action='store_true');args=parser.parse_args()
    # Includes tracked Phase 10 runtime fixes. Untracked tests/docs aren't web payload.
    names=run('diff','--name-only','--no-renames',baseline).splitlines()
    changes=run('diff','--name-status','--no-renames',baseline).splitlines()
    rows=[]
    for line in changes:
        status,path=line.split('\t',1);p=Path(path)
        protected=(path.startswith(('license/','upload/','uploads/','templates_c/','.local/','.tools/','logs/','debug/','cache/','caches/')) or p.name.startswith('.env') or path=='includes/config.inc.php' or '.bak-' in p.name or p.suffix.lower() in ('.sql','.bak','.dump','.log','.zip'))
        runtime=(path.startswith(('classes/','modules/','includes/','templates/','css/','js/','languages/')) or (len(p.parts)==1 and p.suffix=='.php') or path=='.htaccess')
        action='review_deleted_keep_remote' if status=='D' else ('exclude_protected_or_sql' if protected else ('upload_runtime' if runtime else 'exclude_nonruntime'))
        row={'status':status,'path':path,'action':action}
        if action=='upload_runtime':row['sha256']=hashlib.sha256((root/path).read_bytes()).hexdigest()
        rows.append(row)
    assert sorted(names)==sorted(r['path'] for r in rows)
    payload={'version':'0.10.0-local-rc1','date':'2026-10-05','baseline':baseline,'application_base_commit':'df76268','release_ref':'feature/pm-phase10-test-release','source':'git diff --name-only --no-renames BASELINE (tracked working tree, including Phase 10 fixes)','files':rows}
    docs=root/'docs';json_text=json.dumps(payload,ensure_ascii=False,indent=2)+'\n'
    text='\n'.join(r['path'] for r in rows if r['action']=='upload_runtime')+'\n'
    raw='\n'.join(names)+'\n'
    outputs={'DEPLOY_MANIFEST.json':json_text,'DEPLOY_FILES.txt':text,'DEPLOY_DIFF_ALL.txt':raw}
    if args.check:
        for name,value in outputs.items():
            current=(docs/name).read_text(encoding='utf-8');assert current==value,'Stale manifest: '+name
    else:
        for name,value in outputs.items():(docs/name).write_text(value,encoding='utf-8',newline='\n')
    print(f"PASS: {len(rows)} tracked changed/new/deleted paths; {sum(r['action']=='upload_runtime' for r in rows)} runtime uploads; complete classification and SHA256. No upload/SQL execution.")
if __name__=='__main__':main()
