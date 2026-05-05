<?php
setcookie('gesdoc_token', '', time() - 3600, "/");
?>
<script>
    try {
        localStorage.removeItem('gesdoc_jwt');
    } catch(e) {}
    window.location.href = 'login.php';
</script>
