<?php require_once __DIR__ . '/header.php'; ?>

<body>

<?php require_once __DIR__ . '/navbar.php'; ?>
<?php require_once __DIR__ . '/sidebar.php'; ?>

<!-- [ Main Content ] start -->
<main class="main-content">
    <?php //require_once __DIR__ . '/../dashboard/index.php'; ?>
</main>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/footer.php'; ?>

<!-- Core JS -->
<script src="../assets/js/admin.js"></script>
<script src="../assets/js/plugins/popper.min.js"></script>
<script src="../assets/js/plugins/simplebar.min.js"></script>
<script src="../assets/js/plugins/bootstrap.min.js"></script>
<script src="../assets/js/icon/custom-font.js"></script>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/theme.js"></script>
<script src="../assets/js/plugins/feather.min.js"></script>

<!-- Layout Settings -->
<script>
    layout_change('light');
    font_change('Roboto');
    change_box_container('false');
    layout_caption_change('true');
    layout_rtl_change('false');
    preset_change('preset-1');
</script>

<!-- Page Specific JS -->
<?php if (isset($extraJs)) echo $extraJs; ?>

</body>
</html>
