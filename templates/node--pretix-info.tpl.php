<?php if (isset($node->pretix['data'])): ?>
  <dl>
    <?php if (isset($node->pretix['data']['pretix_event_url'])): ?>
      <dt>
        <?php echo t('Pretix event url') ?>
      </dt>
      <dd>
        <?php echo l($node->pretix['data']['pretix_event_url'], $node->pretix['data']['pretix_event_url']) ?>
      </dd>
    <?php endif ?>

    <?php if (isset($node->pretix['data']['pretix_event_shop_url'])): ?>
      <dt>
        <?php echo t('Pretix event shop url') ?>
        <?php echo ' ' ?>
        <?php echo isset($node->pretix['data']['event']['live']) ? t('(shop live)') : t('(shop not live') ?>
      </dt>
      <dd>
        <?php echo l($node->pretix['data']['pretix_event_shop_url'], $node->pretix['data']['pretix_event_shop_url']) ?>
      </dd>
    <?php endif ?>
  </dl>
<?php endif ?>
