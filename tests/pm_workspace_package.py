"""Build a local, immutable-code update with a manual UAT kit. Never uploads or opens DB."""
from pathlib import Path
import argparse,hashlib,json,subprocess,zipfile
from pm_deploy_manifest import classify
root=Path(__file__).resolve().parents[1]
git=['git','-c',f'safe.directory={root.as_posix()}']
baseline='3df33493da81f53e2fdc67d826998850812dbd2f'
def run(*args):return subprocess.check_output(git+list(args),cwd=root)
def digest(data):return hashlib.sha256(data).hexdigest()
def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('--date',default='20261009');parser.add_argument('--ref',default='HEAD');parser.add_argument('--package',action='store_true')
    args=parser.parse_args();
    if len(args.date)!=8 or not args.date.isdigit():raise ValueError('Invalid date')
    ref=run('rev-parse',args.ref).decode().strip()
    changes=run('diff','--name-status','--no-renames',baseline,ref).decode().strip().splitlines()
    rows=[];files={}
    for line in changes:
        status,path=line.split('\t',1);action=classify(status,path)
        row={'status':status,'path':path,'action':action}
        if action=='upload_runtime':
            content=run('show',ref+':'+path)
            # Refuse a package that accidentally omits current runtime changes.
            if (root/path).read_bytes().replace(b'\r\n',b'\n')!=content.replace(b'\r\n',b'\n'):
                raise RuntimeError('Runtime differs from source commit: '+path)
            row['sha256']=digest(content);files['runtime/'+path]=content
        rows.append(row)
    runtime={r['path'] for r in rows if r['action']=='upload_runtime'}
    cron_release='cron/pm-reminders.php' in runtime
    if cron_release and args.date<'20261014':raise RuntimeError('Cron release requires date>=20261014 and manual migration 011 handoff')
    required={'classes/dao/pmdashboard.class.php','classes/services/pmdashboardservice.class.php',
              'classes/services/pmshiftlabelservice.class.php','css/pmworkspace.css','js/pmkanban.js','pm_ajax.php','assets/fonts/inter/InterVariable.woff2'}
    if not required.issubset(runtime):raise RuntimeError('Missing runtime dependency')
    if cron_release and not {'cron/pm-reminders.php','includes/pm_reminders.inc.php','classes/dao/pmnotifications.class.php','classes/services/pmnotificationservice.class.php','classes/services/pmreminderservice.class.php','templates/admin/pm-notification-menu.tpl.html','templates/admin/pm-notifications.tpl.html','modules/admin/pmnotifications.module.php'}.issubset(runtime):raise RuntimeError('Missing cron runtime dependency')
    throttle_release='classes/services/pmreminderoptions.class.php' in runtime
    untracked=run('ls-files','--others','--exclude-standard').decode().splitlines()
    if any(classify('A',p)=='upload_runtime' for p in untracked):raise RuntimeError('Untracked runtime not packaged')
    docs={
       'review/README_DEPLOY.md':'docs/WORKSPACE_RELEASE_HANDOFF_20261009.md',
       'review/PREFLIGHT_SCHEMA.sql':'docs/WORKSPACE_PREFLIGHT_SCHEMA.sql',
       'review/LOCAL_VERIFY.md':'docs/WORKSPACE_INTEGRATION_VERIFY_20261009.md',
       'review/TEST_REPORT_FULL.md':'docs/TEST_REPORT_FULL.md',
       'review/UAT_DATA_SOURCE.json':'tests/fixtures/pm_manual_uat_data.json'
    }
    for destination,path in docs.items():files[destination]=run('show',ref+':'+path)
    uat=root/'outputs/pm-host-uat-20261009'
    # Canonical sample-data specification must match the source commit.
    source_path='tests/fixtures/pm_manual_uat_data.json'
    if (root/source_path).read_bytes().replace(b'\r\n',b'\n')!=files['review/UAT_DATA_SOURCE.json'].replace(b'\r\n',b'\n'):
        raise RuntimeError('UAT source changed after commit')
    for name in ['BO_DU_LIEU_UAT.xlsx','01_import_valid.xlsx','02_import_invalid.xlsx','03_import_collision.xlsx']:
        files['manual-test/'+name]=(uat/name).read_bytes()
    if args.date>='20261012':
        files['optional/maintenance.html']=run('show',ref+':maintenance.html')
        files['review/NOTIFICATION_VERIFY.md']=run('show',ref+':docs/VI_NOTIFICATIONS_VERIFY_20261012.md')
    if args.date>='20261013':
        files['review/TOAST_VERIFY.md']=run('show',ref+':docs/TOAST_VERIFY_20261013.md')
        files['review/CENTERED_NAV_VERIFY.md']=run('show',ref+':docs/CENTERED_NOTIFICATION_NAV_VERIFY_20261013.md')
        files['review/TOP_TOAST_VERIFY.md']=run('show',ref+':docs/TOP_TOAST_10S_VERIFY_20261013.md')
        files['review/TOAST_VISUAL_VERIFY.md']=run('show',ref+':docs/TOAST_VISUAL_VERIFY_20261013.md')
        files['review/NAV_SHIFT_VERIFY.md']=run('show',ref+':docs/NAV_SHIFT_VERIFY_20261013.md')
        files['review/HEADER_NAV_VERIFY.md']=run('show',ref+':docs/HEADER_NAV_VERIFY_20261013.md')
    if cron_release:
        files['review/CRON_VERIFY.md']=run('show',ref+':docs/PM_CRON_VERIFY_20261014.md')
        files['review/CRON_HOST_UAT.md']=run('show',ref+':docs/PM_CRON_HOST_UAT_20261014.md')
        files['manual-migrations/011_create_pm_notifications.sql']=run('show',ref+':database/migrations/011_create_pm_notifications.sql')
        if subprocess.run(git+['cat-file','-e',ref+':docs/PM_CRON_DB_VERIFY_20261014.md'],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL).returncode==0:
            files['review/CRON_DB_VERIFY.md']=run('show',ref+':docs/PM_CRON_DB_VERIFY_20261014.md')
            files['review/CRON_THROTTLE_PLAN.md']=run('show',ref+':docs/PLAN_PM_CRON_THROTTLE_20261014.md')
    if throttle_release:
        files['manual-migrations/012_add_pm_notification_due_date.sql']=run('show',ref+':database/migrations/012_add_pm_notification_due_date.sql')
        files['review/THROTTLE_BUILD_VERIFY.md']=run('show',ref+':docs/PM_CRON_THROTTLE_BUILD_VERIFY_20261014.md')
        for name in ['PM_CRON_012_MANUAL_TEST.md','PM_CRON_012_MANUAL_CONTRACT.sql','PM_CRON_012_CHECKSUM.sql','PM_CRON_012_PREFLIGHT.sql','PM_CRON_012_BAD_COLUMN_PROBE.sql']:
            files['review/'+name]=run('show',ref+':docs/'+name)
        for name in ['pm_cron_throttle_db_smoke.php','pm_cron_throttle_worker.php','pm_cron_throttle_http_router.php','pm_cron_db_fixture.php']:
            files['operator-only/tests/'+name]=run('show',ref+':tests/'+name)
    manifest={'date':args.date,'source_commit':ref,'branch':run('branch','--show-current').decode().strip(),
       'baseline':baseline,'package_type':'incremental existing-PM update; manual host UAT',
       'state':'local prepared; not uploaded; DB/session/host UAT NOT RUN',
       'new_migrations':(['database/migrations/011_create_pm_notifications.sql']+(['database/migrations/012_add_pm_notification_due_date.sql'] if throttle_release else [])) if cron_release else [],'uat_data_source_sha256':digest(files['review/UAT_DATA_SOURCE.json']),'files':rows,
       'manual_files':{p:digest(data) for p,data in files.items() if not p.startswith('runtime/')}}
    files['review/WORKSPACE_MANIFEST.json']=(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n').encode()
    files['README_FIRST.txt']=(
        'DeraSoft PM workspace integration - '+args.date+'\nSource commit: '+ref+
        '\nRead review/README_DEPLOY.md BEFORE upload.\n'
        'Upload CONTENTS of runtime/ into the EXISTING PM web root; preserve config/license/uploads and legacy.\n'
        'manual-test/ is data for manual UI/import testing; DO NOT upload it to web root.\n'
        'BO_DU_LIEU_UAT.xlsx is a reference workbook, NOT an Import file. Only the three numbered files use import format.\n'
        'Package generation never creates a database or executes SQL. Migration 011 was tested locally only; host application requires separate approval. PREFLIGHT_SCHEMA.sql is read-only.\n'
        'Use your own passwords; none are packaged. Data is synthetic and host tests are NOT RUN.\n'
        'Schema/backup/maintenance checks precede upload. Do not rerun historical migrations blindly.\n'
    ).encode()
    files['SHA256SUMS.txt']=''.join(digest(data)+'  '+p+'\n' for p,data in sorted(files.items())).encode()
    for path in files:
        if '..' in Path(path).parts or path.startswith('/') or '\\' in path:raise RuntimeError('Unsafe archive path')
        if path.startswith('runtime/') and (path.endswith('.sql') or any(x in path for x in ['config.inc.php','.local/','.tools/','license/','upload/','templates_c/'])):
            raise RuntimeError('Protected runtime file: '+path)
    output=root/'.local/releases'/('DeraSoft_PM_workspace_'+args.date+'_'+ref[:7]+'.zip')
    output.parent.mkdir(parents=True,exist_ok=True)
    if not args.package:
        print('PASS: immutable code inventory and UAT inputs verified; no ZIP/upload/DB.')
        return
    with zipfile.ZipFile(output,'w',zipfile.ZIP_DEFLATED) as archive:
        for path,data in sorted(files.items()):
            info=zipfile.ZipInfo(path,(int(args.date[:4]),int(args.date[4:6]),int(args.date[6:8]),0,0,0));info.compress_type=zipfile.ZIP_DEFLATED;archive.writestr(info,data)
    with zipfile.ZipFile(output) as archive:
        if set(archive.namelist())!=set(files) or archive.testzip() is not None:raise RuntimeError('ZIP inventory/CRC')
        for path,data in files.items():
            if digest(archive.read(path))!=digest(data):raise RuntimeError('ZIP entry hash: '+path)
    output.with_suffix('.zip.sha256').write_text(digest(output.read_bytes())+'  '+output.name+'\n')
    output.with_suffix('.manifest.json').write_text(json.dumps(manifest,ensure_ascii=False,indent=2)+'\n',encoding='utf-8')
    print('PASS: '+str(len(runtime))+' immutable runtime files, 4 UAT XLSX files, '+str(len(files))+' ZIP entries; exact inventory/CRC/all SHA256 verified.')
    print(str(output));print('ZIP SHA256='+digest(output.read_bytes()))
if __name__=='__main__':main()
