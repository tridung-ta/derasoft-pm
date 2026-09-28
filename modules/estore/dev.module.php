<?php
$slug = $request->element('slug');
if ($slug == 'trienlam') {
    $templateFile = 'trienlam.tpl.html';
} else if ($slug == 'sankhau') {
    $templateFile = 'sankhau.tpl.html';
} else {
    header('location:' . '/');
}