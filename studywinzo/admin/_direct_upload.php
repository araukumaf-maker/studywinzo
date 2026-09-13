<?php
$cfg = supabase_config();
?>
<script>
window.SW_SUPABASE = {
    url:    '<?= $cfg['url'] ?>',
    key:    '<?= $cfg['storage_token'] ?>',
    bucket: 'studywinzo'
};
</script>
<script src="_direct_upload.js?v=1"></script>
