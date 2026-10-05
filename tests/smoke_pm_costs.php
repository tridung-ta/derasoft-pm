<?php
define('ROOT_PATH',dirname(__DIR__).DIRECTORY_SEPARATOR);
require ROOT_PATH.'classes/template/smarty.class.php';
$s=new Smarty();$s->setTemplateDir(ROOT_PATH.'templates');$s->setCompileDir(sys_get_temp_dir());
$actual=['cost'=>null,'currency'=>null,'hours'=>'10.00','regular_hours'=>'8.00','ot_hours'=>'2.00'];
$data=['valuation_date'=>'2026-10-02','filters'=>['from'=>'2026-10-01','to'=>'2026-10-02','project_id'=>1],'summary'=>$actual,'estimate_total'=>null,'estimate_currency'=>null,'warnings'=>['Khác currency: không cộng chi phí.','<script>bad</script>'],'projects'=>[['id'=>1,'name'=>'<script>bad</script>','budget'=>'2000.00','actual'=>$actual,'lifetime'=>$actual,'estimate'=>['cost'=>null,'currency'=>null]]],'tasks'=>[['id'=>1,'name'=>'Task','deleted_at'=>'2026-10-02','actual'=>$actual,'estimate'=>null]],'groups'=>['user'=>[1=>$actual],'department'=>[],'role'=>[],'date'=>[]],'page'=>1,'total_pages'=>2];
$s->assign(['pageTitle'=>'Chi phí','error'=>'','filters'=>$data['filters'],'costData'=>$data,'costJson'=>json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)]);
$html=$s->fetch('admin/pm-costs.tpl.html');
foreach(['&lt;script&gt;','chartjs-4.5.1','page=2','(đã ẩn)','Không cộng','pm-cost-kpis','cost-tabs','cost-project-panel','cost-group-panel','trend-chart-empty','budget-chart-empty','class="chart" hidden'] as $needle)if(!str_contains($html,$needle))throw new RuntimeException('Cost render missing: '.$needle);
if(str_contains($html,'<script>bad</script>'))throw new RuntimeException('Cost HTML/JSON escaping failed.');
$empty=$data;$empty['projects']=[];$empty['tasks']=[];$empty['groups']=['user'=>[],'department'=>[],'role'=>[],'date'=>[]];
$s->assign(['costData'=>$empty,'costJson'=>json_encode($empty,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)]);
$emptyHtml=$s->fetch('admin/pm-costs.tpl.html');
foreach(['Không có dự án trong phạm vi.','Chưa có công việc trong phạm vi.','Chưa có chấm công trong khoảng lọc.'] as $needle)if(!str_contains($emptyHtml,$needle))throw new RuntimeException('Empty fallback missing: '.$needle);
if(in_array('--preview',$argv,true))file_put_contents(ROOT_PATH.'.local/phase9b-costs-empty.html',str_replace('<head>','<head><base href="/">',$emptyHtml));
$s->assign(['costData'=>null,'costJson'=>'null','error'=>'<script>invalid filter</script>']);
$html=$s->fetch('admin/pm-costs.tpl.html');if(str_contains($html,'id="cost-data"')||str_contains($html,'<script>invalid'))throw new RuntimeException('Error render/escaping failed.');
echo "PASS group 5: cost Smarty charts/table, currency warnings, history labels, pagination/filter, error and HTML/JSON escaping.\n";
if(in_array('--preview',$argv,true)){
    $actual=['cost'=>'1102.75','currency'=>'VND','hours'=>'10.00','regular_hours'=>'8.00','ot_hours'=>'2.00'];
    $data['summary']=$actual;$data['estimate_total']='1002.50';$data['estimate_currency']='VND';
    $data['projects'][0]=['id'=>1,'name'=>'Website nội bộ','budget'=>'2000.00','actual'=>$actual,'lifetime'=>$actual,'estimate'=>['cost'=>'1002.50','currency'=>'VND'],'comparable'=>true];
    $data['groups']['date']=['2026-10-01'=>$actual];$data['warnings']=['Phòng ban/role dùng phân loại hiện tại; chưa có snapshot lịch sử.'];
    $data['tasks'][0]['actual']=$actual;
    $s->assign(['costData'=>$data,'error'=>'','costJson'=>json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)]);
    $preview=str_replace('<head>','<head><base href="/">',$s->fetch('admin/pm-costs.tpl.html'));
    file_put_contents(ROOT_PATH.'.local/phase6-preview.html',$preview);
    file_put_contents(ROOT_PATH.'.local/phase6-preview.json',json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE));
    echo "Preview with synthetic data written to ignored .local directory.\n";
}
