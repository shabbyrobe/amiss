<?php
require_once 'config.php';
$dirs = [
    'active',
    'note',
    'array',
];
?>
<html>
<body>
<ul>
<?php foreach ($dirs as $d): ?>
  <li><?php echo e(titleise_slug($d)) ?>
    <ul>
    <?php foreach (glob(__DIR__.'/'.$d.'/*.php') as $file): ?>
    <?php $base = basename($file, '.php') ?>

    <?php if ($base != 'config'): ?>
      <li>
        <a href="show.php/<?php echo htmlentities($d.'/'.$base) ?>">
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
