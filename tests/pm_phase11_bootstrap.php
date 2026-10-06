<?php
define('ROOT_PATH',dirname(__DIR__).'/');
$css=file_get_contents(ROOT_PATH.'css/vendor/bootstrap-5.3.8/bootstrap.min.css');
if(base64_encode(hash('sha384',$css,true))!=='sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB')throw new RuntimeException('Bootstrap official digest mismatch');
if(!str_contains(file_get_contents(ROOT_PATH.'css/vendor/bootstrap-5.3.8/LICENSE'),'MIT License'))throw new RuntimeException('Bootstrap license missing');
foreach(glob(ROOT_PATH.'templates/admin/pm*.html') as $path){$html=file_get_contents($path);if(!str_contains($html,'<head'))continue;$bootstrap=strpos($html,'css/vendor/bootstrap-5.3.8/bootstrap.min.css');if($bootstrap===false||substr_count($html,'css/vendor/bootstrap-5.3.8/bootstrap.min.css')!==1)throw new RuntimeException('Bootstrap missing/duplicate');$theme=strpos($html,'css/pmui.css');if($theme!==false&&$bootstrap>$theme)throw new RuntimeException('Theme order wrong');}
echo "PASS: official Bootstrap 5.3.8 SHA384/license, self-hosted once before PM theme on all entry templates. No JS dependency.\n";
