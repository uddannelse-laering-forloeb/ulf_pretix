<?php if (isset($pretix_urls)): ?>
  <dl>
  <?php foreach ($pretix_urls as $name => $url): ?>
    <dt><?php echo $name; ?></dt>
    <dd><?php echo l($url, $url); ?></dd>
  <?php endforeach ?>
  </dl>
<?php endif ?>
