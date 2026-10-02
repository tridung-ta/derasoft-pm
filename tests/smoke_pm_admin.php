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
	));
	$html = $smarty->fetch('pm.tpl.html');
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

echo "PM admin template smoke test passed.\n";
