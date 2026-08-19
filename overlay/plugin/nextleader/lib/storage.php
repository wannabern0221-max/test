<?php
if (!defined('_GNUBOARD_')) exit;

function nl_storage_path() {
    return G5_DATA_PATH.'/'.NL_STORAGE_DIR_NAME;
}
function nl_storage_prepare() {
    $base = nl_storage_path();
    if (!is_dir($base)) @mkdir($base, G5_DIR_PERMISSION, true);
    if (!is_dir($base.'/.trash')) @mkdir($base.'/.trash', G5_DIR_PERMISSION, true);
    $ht = $base.'/.htaccess';
    if (!is_file($ht)) @file_put_contents($ht, "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    return is_dir($base) && is_writable($base);
}
function nl_storage_bytes() {
    $base = nl_storage_path();
    if (!is_dir($base)) return 0;
    $bytes = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) if ($f->isFile() && $f->getFilename() !== '.htaccess' && $f->getFilename() !== '.cleanup.lock') $bytes += $f->getSize();
    return $bytes;
}
function nl_human_bytes($bytes) {
    $bytes = max(0, (float)$bytes);
    $units = array('B','KB','MB','GB','TB');
    $i = 0;
    while ($bytes >= 1000 && $i < count($units)-1) { $bytes /= 1000; $i++; }
    return number_format($bytes, $i ? 1 : 0).' '.$units[$i];
}
function nl_file_row($id) {
    if (!nl_table_exists(nl_table('file'))) return array();
    $id = (int)$id;
    $row = sql_fetch("SELECT * FROM ".nl_table('file')." WHERE id={$id} LIMIT 1", false);
    return is_array($row) ? $row : array();
}
function nl_file_path($row) {
    $name = basename((string)($row['stored_name'] ?? ''));
    return $name ? nl_storage_path().'/'.$name : '';
}
function nl_file_is_privileged($row, $mb_id = '') {
    global $member, $is_admin;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if (!$mb_id) return false;
    if ($is_admin === 'super') return true;
    return ($row['owner_mb_id'] ?? '') === $mb_id || nl_can('file_manage', $mb_id);
}
function nl_file_access_allowed($row, $mb_id = '') {
    global $member;
    if (!$mb_id) $mb_id = $member['mb_id'] ?? '';
    if (!$mb_id || !nl_is_approved($mb_id) || ($row['status'] ?? '') !== 'active') return false;
    if (nl_file_is_privileged($row, $mb_id)) return true;
    $p = nl_profile($mb_id);
    if (($row['audience'] ?? 'leaders') === 'executives') return nl_is_executive_role($p['role_code'] ?? '');
    return true;
}
function nl_storage_lock() {
    if (!nl_storage_prepare()) return false;
    $lock = @fopen(nl_storage_path().'/.cleanup.lock', 'c');
    if (!$lock) return false;
    if (!flock($lock, LOCK_EX)) { fclose($lock); return false; }
    return $lock;
}
function nl_storage_unlock($lock) {
    if (!is_resource($lock)) return;
    @flock($lock, LOCK_UN);
    @fclose($lock);
}
function nl_delete_file_record($row, $reason = 'manual', $lock_held = false) {
    if (empty($row['id']) || ($row['status'] ?? '') !== 'active') return false;
    $lock = false;
    if (!$lock_held) {
        $lock = nl_storage_lock();
        if (!$lock) {
            nl_audit('file_delete_blocked', 'file', (int)$row['id'], array('reason'=>'storage_lock'));
            return false;
        }
    }
    try {
        $table = nl_table('file');
        $id = (int)$row['id'];
        // Re-read while the shared storage lock is held so a stale page cannot
        // delete a record that another request already transitioned.
        $fresh = sql_fetch("SELECT * FROM {$table} WHERE id={$id} AND status='active' LIMIT 1", false);
        if (empty($fresh['id'])) return false;
        $row = $fresh;
        $src = nl_file_path($row);
        $trash = nl_storage_path().'/.trash/'.$id.'__'.basename((string)$row['stored_name']);
        if (is_file($trash) && !@unlink($trash)) {
            nl_audit('file_delete_blocked', 'file', $id, array('reason'=>'existing_trash_not_removable'));
            return false;
        }
        $now = nl_now();
        $marked = sql_query("UPDATE {$table} SET status='deleting', updated_at='{$now}' WHERE id={$id} AND status='active'", false);
        if (!$marked) return false;
        $check = sql_fetch("SELECT status FROM {$table} WHERE id={$id} LIMIT 1", false);
        if (($check['status'] ?? '') !== 'deleting') return false;
        if (is_file($src) && !@rename($src, $trash)) {
            sql_query("UPDATE {$table} SET status='active', updated_at='{$now}' WHERE id={$id} AND status='deleting'", false);
            nl_audit('file_delete_failed', 'file', $id, array('reason'=>'move_to_trash_failed'));
            return false;
        }
        $reasonEsc = nl_sql_escape(substr($reason,0,100));
        $ok = sql_query("UPDATE {$table} SET status='deleted', delete_reason='{$reasonEsc}', deleted_at='{$now}', updated_at='{$now}' WHERE id={$id} AND status='deleting'", false);
        if (!$ok) {
            if (is_file($trash)) @rename($trash, $src);
            sql_query("UPDATE {$table} SET status='active', updated_at='{$now}' WHERE id={$id} AND status='deleting'", false);
            nl_audit('file_delete_failed', 'file', $id, array('reason'=>'metadata_update_failed'));
            return false;
        }
        if (is_file($trash) && !@unlink($trash)) {
            sql_query("UPDATE {$table} SET status='trash_pending', updated_at='{$now}' WHERE id={$id} AND status='deleted'", false);
            nl_audit('file_delete_pending', 'file', $id, array('reason'=>$reason, 'name'=>$row['original_name'] ?? ''));
            return false;
        }
        nl_audit('file_delete', 'file', $id, array('reason'=>$reason, 'name'=>$row['original_name'] ?? ''));
        return true;
    } finally {
        if ($lock) nl_storage_unlock($lock);
    }
}
function nl_trash_file_count() {
    $dir = nl_storage_path().'/.trash';
    if (!is_dir($dir)) return 0;
    $count = 0;
    foreach (glob($dir.'/*') ?: array() as $path) if (is_file($path)) $count++;
    return $count;
}
function nl_purge_trash($lock_held = false) {
    $lock = false;
    if (!$lock_held) {
        $lock = nl_storage_lock();
        if (!$lock) return array('deleted'=>0, 'remaining'=>nl_trash_file_count(), 'ok'=>false);
    }
    try {
        $dir = nl_storage_path().'/.trash';
        $deleted = 0;
        $remaining = 0;
        $known = array();
        $table = nl_table('file');
        if (nl_table_exists($table)) {
            $res = sql_query("SELECT id,stored_name FROM {$table} WHERE status='trash_pending' ORDER BY id", false);
            if ($res) while ($row = sql_fetch_array($res)) {
                $id = (int)$row['id'];
                $path = $dir.'/'.$id.'__'.basename((string)$row['stored_name']);
                $known[$path] = true;
                $removed = !is_file($path) || @unlink($path);
                if (!$removed) { $remaining++; continue; }
                $ok = sql_query("UPDATE {$table} SET status='deleted', updated_at='".nl_now()."' WHERE id={$id} AND status='trash_pending'", false);
                if ($ok) $deleted++; else $remaining++;
            }
        }
        // Remove orphaned trash files that have no matching trash_pending row.
        foreach (glob($dir.'/*') ?: array() as $path) {
            if (!is_file($path) || isset($known[$path])) continue;
            if (@unlink($path)) $deleted++; else $remaining++;
        }
        if ($deleted) nl_audit('file_trash_purge','file','trash',array('count'=>$deleted));
        return array('deleted'=>$deleted, 'remaining'=>$remaining, 'ok'=>$remaining===0);
    } finally {
        if ($lock) nl_storage_unlock($lock);
    }
}
function nl_cleanup_storage($incoming_bytes = 0, $lock_held = false) {
    $incoming_bytes = max(0, (int)$incoming_bytes);
    $lock = false;
    if (!$lock_held) {
        $lock = nl_storage_lock();
        if (!$lock) {
            $before = nl_storage_bytes();
            return array('triggered'=>($before + $incoming_bytes >= NL_STORAGE_CLEANUP_TRIGGER),'before'=>$before,'after'=>$before,'deleted'=>0,'ok'=>false,'error'=>'cleanup_lock');
        }
    }
    try {
        $before = nl_storage_bytes();
        if ($before + $incoming_bytes < NL_STORAGE_CLEANUP_TRIGGER) return array('triggered'=>false,'before'=>$before,'after'=>$before,'deleted'=>0,'ok'=>true);

        $trash = nl_purge_trash(true);
        $current = nl_storage_bytes();
        // 실제 파일 삭제 또는 metadata 정리가 막힌 상태에서 추가 활성 파일을 연쇄 삭제하지 않는다.
        if (empty($trash['ok']) || !empty($trash['remaining'])) {
            nl_audit('file_cleanup_blocked','file','storage',array('reason'=>'trash_not_clean','remaining'=>(int)($trash['remaining'] ?? 0)));
            return array('triggered'=>true,'before'=>$before,'after'=>$current,'deleted'=>0,'ok'=>false,'error'=>'trash_not_clean');
        }

        if ($current + $incoming_bytes < NL_STORAGE_CLEANUP_TRIGGER) {
            return array('triggered'=>true,'before'=>$before,'after'=>$current,'deleted'=>0,'ok'=>true);
        }

        $target = max(0, NL_STORAGE_CLEANUP_TARGET - $incoming_bytes);
        $deleted = 0;
        $failed = false;
        $table = nl_table('file');
        $result = sql_query("SELECT * FROM {$table} WHERE status='active' ORDER BY created_at ASC, id ASC", false);
        if ($result) {
            while (($row = sql_fetch_array($result)) && nl_storage_bytes() > $target) {
                if (!nl_delete_file_record($row, 'automatic_capacity_cleanup', true)) {
                    $failed = true;
                    break;
                }
                $deleted++;
            }
        }
        $after = nl_storage_bytes();
        if ($failed) {
            nl_audit('file_cleanup_blocked','file','storage',array('reason'=>'delete_failed','deleted'=>$deleted,'after'=>$after));
            return array('triggered'=>true,'before'=>$before,'after'=>$after,'deleted'=>$deleted,'ok'=>false,'error'=>'delete_failed');
        }
        return array('triggered'=>true,'before'=>$before,'after'=>$after,'deleted'=>$deleted,'ok'=>($after <= $target));
    } finally {
        if ($lock) nl_storage_unlock($lock);
    }
}
function nl_upload_allowed_extension($name) {
    $ext = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
    return in_array($ext, array('jpg','jpeg','png','gif','webp','pdf','txt','csv','hwp','hwpx','doc','docx','xls','xlsx','ppt','pptx','zip'), true) ? $ext : '';
}
function nl_store_upload($file, $audience, $download_allowed, $label = '') {
    global $member;
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return array(false, '업로드된 파일을 확인할 수 없습니다.');
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0 || $size > NL_FILE_MAX_BYTES) return array(false, '파일 크기는 50MB 이하만 허용됩니다.');
    $ext = nl_upload_allowed_extension($file['name'] ?? '');
    if (!$ext) return array(false, '허용되지 않은 파일 형식입니다.');
    if (!nl_storage_prepare()) return array(false, '파일 저장소에 쓸 수 없습니다.');
    $lock = nl_storage_lock();
    if (!$lock) return array(false, '파일 저장소 작업 잠금을 확보하지 못했습니다. 잠시 후 다시 시도해 주세요.');
    try {
        // Capacity check, cleanup, physical move and metadata insert are serialized
        // so concurrent uploads cannot both reserve the same remaining capacity.
        $cleanup = nl_cleanup_storage($size, true);
        if (!empty($cleanup['triggered']) && empty($cleanup['ok'])) return array(false, '저장공간 자동 정리를 안전하게 완료하지 못해 업로드를 중단했습니다. 관리자에게 저장소 상태를 확인해 주세요.');
        $stored = bin2hex(random_bytes(20)).'.'.$ext;
        $dest = nl_storage_path().'/'.$stored;
        if (!move_uploaded_file($file['tmp_name'], $dest)) return array(false, '파일 저장에 실패했습니다.');
        @chmod($dest, G5_FILE_PERMISSION);
        $mime = 'application/octet-stream';
        if (class_exists('finfo')) {
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $detected = $fi->file($dest);
            if ($detected) $mime = substr($detected,0,100);
        }
        $audience = $audience === 'executives' ? 'executives' : 'leaders';
        $label = trim($label) ?: ($file['name'] ?? '파일');
        $table = nl_table('file');
        $owner = nl_sql_escape($member['mb_id']);
        $original = nl_sql_escape(substr((string)$file['name'],0,255));
        $storedEsc = nl_sql_escape($stored);
        $labelEsc = nl_sql_escape(substr($label,0,255));
        $mimeEsc = nl_sql_escape($mime);
        $audEsc = nl_sql_escape($audience);
        $dl = $download_allowed ? 1 : 0;
        $now = nl_now();
        $ok = sql_query("INSERT INTO {$table} (owner_mb_id,label,original_name,stored_name,mime_type,size_bytes,audience,download_allowed,status,created_at,updated_at) VALUES ('{$owner}','{$labelEsc}','{$original}','{$storedEsc}','{$mimeEsc}',{$size},'{$audEsc}',{$dl},'active','{$now}','{$now}')", false);
        if (!$ok) { @unlink($dest); return array(false, '파일 메타데이터 저장에 실패했습니다.'); }
        $row = sql_fetch("SELECT LAST_INSERT_ID() id", false);
        nl_audit('file_upload','file',(int)($row['id'] ?? 0),array('name'=>$file['name'] ?? '', 'audience'=>$audience));
        return array(true, (int)($row['id'] ?? 0));
    } finally {
        nl_storage_unlock($lock);
    }
}
