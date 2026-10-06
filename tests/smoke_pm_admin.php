<?php
define('ROOT_PATH', dirname(__DIR__).'/');
require ROOT_PATH.'classes/template/smarty.class.php';

class MockPmUser
{
	public function getFullName() { return 'Dũng'; }
	public function getUsername() { return 'dung'; }
	public function getEmail() { return 'dung@example.com'; }
	public function getTel() { return ''; }
	public function getAddress() { return ''; }
	public function getId() { return 111; }
	public function getStatus() { return 1; }
}

$smarty = new Smarty();
$smarty->setTemplateDir(ROOT_PATH.'templates/admin');
$smarty->setCompileDir(sys_get_temp_dir());
$user = new MockPmUser();

foreach (array('dashboard', 'profile', 'password', 'team') as $section) {
	$smarty->assign(array(
		'pageTitle' => 'DeraSoft PM',
		'section' => $section,
		'csrfToken' => 'smoke-test-token',
		'notice' => '',
		'error' => '',
		'teamMembers' => array($user),
		'teamCount' => 1,
		'canViewTeam' => true,
		'pmRoleCodes' => array('ADMIN'),
		'profileUser' => $user,
		'displayName' => 'Dũng',
		'op' => 'pm',
		'pmNavigation' => [['url'=>'?op=pm&act=profile','label'=>'Hồ sơ','current'=>false],['url'=>'?op=pm&act=password','label'=>'Đổi mật khẩu','current'=>false],['url'=>'?op=pmprojects','label'=>'Dự án & công việc','current'=>false],['url'=>'?op=pmtimesheets','label'=>'Chấm công','current'=>false]],
	));
	$html = $smarty->fetch('pm.tpl.html');if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9-'.$section.'.html',str_replace('<head>','<head><base href="/">',$html));
	if($section==='dashboard'&&(!str_contains($html,'pm-overview-kpis')||!str_contains($html,'pm-overview-shortcuts')||substr_count($html,'class="metric"')!==3))throw new RuntimeException('Overview strip/shortcuts missing.');
	if (strpos($html, 'DeraSoft PM') === false) {
		fwrite(STDERR, "PM template failed for section: {$section}\n");
		exit(1);
	}
	if ($section === 'profile' && (strpos($html, 'name="op" value="pm"') === false
		|| strpos($html, 'name="act" value="profile"') === false)) {
		fwrite(STDERR, "PM profile form is missing POST route fields.\n");
		exit(1);
	}
	if ($section === 'password' && (strpos($html, 'name="op" value="pm"') === false
		|| strpos($html, 'name="act" value="password"') === false)) {
		fwrite(STDERR, "PM password form is missing POST route fields.\n");
		exit(1);
	}
}
$smarty->assign(['section'=>'dashboard','canViewTeam'=>false,'teamCount'=>987654321,'pmNavigation'=>[['url'=>'?op=pm&act=profile','label'=>'Hồ sơ','current'=>false]]]);
$html=$smarty->fetch('pm.tpl.html');
if(str_contains($html,'987654321')||str_contains($html,'?op=pmprojects')||str_contains($html,'?op=pmtimesheets'))throw new RuntimeException('Restricted overview leaks count/navigation.');
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9b-dashboard-restricted.html',str_replace('<head>','<head><base href="/">',$html));

echo "PM admin template smoke test passed.\n";
