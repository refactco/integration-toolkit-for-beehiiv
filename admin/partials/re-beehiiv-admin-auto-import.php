<?php
if (!defined('WPINC')) die;
?>

<div class="wrap">
    <h1>Re Beehiiv - Auto Import</h1>
    <div class="re-beehiiv-wrapper">
        <div class="re-beehiiv-tabs">
            <nav class="nav-tab-wrapper">
                <a class="nav-tab" data-tab="re-beehiiv-import" id="re-beehiiv-import-tab" href="<?= admin_url('admin.php?page=re-beehiiv-import') ?>">Manual Import</a>
                <a class="nav-tab nav-tab-active" data-tab="re-beehiiv-auto-import" id="re-beehiiv-auto-import-tab" href="<?= admin_url('admin.php?page=re-beehiiv-import&tab=auto-import') ?>">Auto Import</a>
            </nav>
        </div>
    </div>
</div>