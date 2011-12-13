<?php

/*
 * This file is part of Amiss.
 * 
 * Amiss is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Amiss is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with Amiss.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Copyright 2011 Blake Williams
 * http://k3jw.com 
 */

require_once('config.php');
$dirs = array(
	'active',
	'basic',
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
