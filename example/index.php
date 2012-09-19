<?php

require_once('config.php');
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
<?php foreach (glob(__DIR__.'/'.$d.'/*.php') as $file): ?>
<?php $base = basename($file, '.php') ?>

<?php if ($base != 'config'): ?>
<?php /* $metadata = extract_file_metadata($file); */ ?>
<li><a href="show.php/<?php echo htmlentities($d.'/'.$base) ?>"><?php echo e(isset($metadata['title']) ? $metadata['title'] : titleise_slug($base)) ?></a>
<?php /* if ($metadata['description']): ?>
<p><?php echo $metadata['description'] ?></p>
<?php endif; */ ?>
</li>
<?php endif; ?>

<?php endforeach; ?>
</ul>
</li>
<?php endforeach; ?>
</ul>
</body>
</html>
