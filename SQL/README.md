# SQL 안내

기본 설치에서는 이 폴더의 SQL을 직접 실행할 필요가 없습니다.

`/plugin/nextleader/install.php`가 현재 Gnuboard `G5_TABLE_PREFIX`를 읽어 custom table을 안전하게 생성합니다. `schema_reference.sql`은 운영 검토/DBA 확인용 참고본이며 `{{PREFIX}}` placeholder가 있으므로 그대로 실행하면 안 됩니다.

기존 Supabase 데이터 migration SQL은 실제 운영 DB schema/export가 확인되기 전에는 제공하지 않습니다. 임의 추측 migration은 데이터 손실 위험이 있습니다.
