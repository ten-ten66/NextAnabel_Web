<footer class="l-footer">
  <address>
    <?= e(site('name')) ?><br>
    〒<?= e(site('address.postal_code')) ?> <?= e(site('address.region') . site('address.locality') . site('address.street')) ?><br>
    TEL <a href="tel:<?= e(str_replace('-', '', (string) site('tel'))) ?>"><?= e(site('tel')) ?></a>
  </address>
  <p><small>&copy; <?= e(site('name')) ?>（架空）</small></p>
</footer>
</body>
</html>
