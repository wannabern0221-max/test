<?php
if (!defined('_GNUBOARD_')) exit;
if (($write['wr_10'] ?? '')==='published' && $is_admin!=='super' && !nl_can('content_approve')) {
    alert('게시된 콘텐츠는 승인 권한자가 삭제해야 합니다. 수정 시에는 다시 승인 대기로 전환할 수 있습니다.');
}
