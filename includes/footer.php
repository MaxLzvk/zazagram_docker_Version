
</main><!-- /.main-content -->

<footer class="site-footer">
    <p>© 2026 Zazagram — Created by Norihy</p>
</footer>

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<script src="<?= BASE_URL ?>/assets/js/search.js"></script>
<?php if (isset($extra_js)): ?>
    <?php foreach ($extra_js as $js): ?>
        <script src="<?= BASE_URL ?>/assets/js/<?= $js ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
