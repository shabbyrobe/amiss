<?php
require_once __DIR__.'/config.php';
$dirs = array(
    'active',
    'note',
    'array',
);
?>
<html>
<body>
<ul>
<?php foreach ($dirs as $d): ?>
  <li><?php echo e(titleise_slug($d)) ?>
    <ul>
    <?php foreach (glob(__DIR__.'/'.$d.'/*.phps') as $file): ?>
    <?php $base = basename($file, '.phps') ?>

    <?php if ($base != 'config'): ?>
      <li>
        <a href="show.php/<?php echo e($d.'/'.$base) ?>">
          <?php echo e(titleise_slug($base)) ?>
        </a>
      </li>
    <?php endif; ?>
    <?php endforeach; ?>
    </ul>
  </li>
<?php endforeach; ?>
</ul>
</body>
</html>
