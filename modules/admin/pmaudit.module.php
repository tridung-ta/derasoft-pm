<?php
include_once(ROOT_PATH.'classes/dao/pmauditlogs.class.php');
$pmAccess = new PmAccess($db, (int)$storeId, (int)$userInfo->getId());
requirePermission('pm.audit.view');
if (!($pmAccess->hasRole('ADMIN') || $pmAccess->hasRole('PM') || $pmAccess->hasRole('HR'))) { http_response_code(403); exit('Forbidden'); }
$templateFile = 'pm-audit.tpl.html';
$filters = ['entity_type' => (string)$request->element('entity_type'), 'action' => (string)$request->element('action')];
$result = ['rows' => [], 'total' => 0, 'page' => 1]; $error = '';
try {
    $result = (new PmAuditLogs($db, (int)$storeId, (int)$userInfo->getId()))->list($filters, max(1, (int)$request->element('page', 1)));
} catch (InvalidArgumentException $e) {
    http_response_code(400); $error = $e->getMessage();
} catch (Throwable $e) {
    error_log('PM audit read failed.'); $error = 'Không thể tải nhật ký. Vui lòng thử lại.';
}
$template->assign(['pageTitle' => 'Nhật ký kiểm toán — DeraSoft PM', 'auditRows' => $result['rows'], 'filters' => $filters, 'page' => $result['page'], 'totalPages' => max(1, (int)ceil($result['total'] / 20)), 'error' => $error]);
