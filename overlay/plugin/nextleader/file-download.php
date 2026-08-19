<?php
include_once './_common.php';

nl_require_approved();

$id = (int)nl_get_str('id', '0');
$mode = nl_get_str('mode', 'view') === 'download' ? 'download' : 'view';
$row = nl_file_row($id);

if (!$row || !nl_file_access_allowed($row)) {
    alert('파일에 접근할 수 없습니다.');
}
if ($mode === 'download' && !$row['download_allowed'] && !nl_file_is_privileged($row)) {
    alert('이 파일은 다운로드가 허용되지 않았습니다.');
}

$path = nl_file_path($row);
if (!$path || !is_file($path)) {
    nl_audit('file_missing', 'file', $id);
    alert('실제 파일을 찾을 수 없습니다. 관리자에게 문의해 주세요.');
}

$mime = $row['mime_type'] ?: 'application/octet-stream';
$inlinePreviewAllowed = strpos($mime, 'image/') === 0 || $mime === 'application/pdf';
if ($mode === 'view' && !$inlinePreviewAllowed) {
    if (!$row['download_allowed'] && !nl_file_is_privileged($row)) {
        alert('이 형식은 브라우저 미리보기를 지원하지 않습니다.');
    }
    $mode = 'download';
}

header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store, max-age=0');
header('Pragma: no-cache');
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));

$disposition = $mode === 'download' ? 'attachment' : 'inline';
$asciiName = preg_replace('/[^A-Za-z0-9._-]+/', '_', basename($row['original_name']));
if (!$asciiName) $asciiName = 'file';

header(
    "Content-Disposition: {$disposition}; filename=\"{$asciiName}\"; filename*=UTF-8''"
    .rawurlencode($row['original_name'])
);

nl_audit('file_access', 'file', $id, array('mode' => $mode));
readfile($path);
exit;
