"""Release allowlist regression; no DB, Git mutation, upload or packaging."""
import unittest
from pm_deploy_manifest import classify,FONT_ASSETS

class ManifestAllowlistTest(unittest.TestCase):
    def test_font_and_license_are_runtime(self):
        for path in FONT_ASSETS:self.assertEqual(classify('A',path),'upload_runtime')
    def test_other_assets_not_implicitly_allowed(self):
        for path in ['assets/fonts/inter/private.key','assets/fonts/inter/extra.woff2','assets/unreviewed.php']:
            self.assertEqual(classify('A',path),'exclude_nonruntime')
    def test_sensitive_paths_never_upload(self):
        for path in ['includes/config.inc.php','license/license.inc.php','upload/person.xlsx','templates_c/cache.php','classes/.env','classes/test.sql','.local/release.zip','logs/app.log','includes/config.bak-secret']:
            self.assertEqual(classify('M',path),'exclude_protected_or_sql')
    def test_deleted_paths_keep_remote(self):
        for path in ['modules/admin/legacy.module.php','includes/config.inc.php',*FONT_ASSETS]:
            self.assertEqual(classify('D',path),'review_deleted_keep_remote')
    def test_existing_runtime_and_docs(self):
        self.assertEqual(classify('M','css/pmui.css'),'upload_runtime')
        self.assertEqual(classify('M','pm_ajax.php'),'upload_runtime')
        self.assertEqual(classify('A','docs/UAT_PHASE9B.md'),'exclude_nonruntime')

if __name__=='__main__':unittest.main()
