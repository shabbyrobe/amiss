<?php

$amissPath = __DIR__.'/../src';
require_once($amissPath.'/Loader.php');
spl_autoload_register(array(new Amiss\Loader, 'load'));

function e($val) {
	return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

function source($code)
{
	ob_start();
	
	$lines = substr_count($code, "\n");
	echo '<table><tr><td class="lines">';
	for ($i=1; $i<=$lines; $i++) {
		echo '<span id="line-'.$i.'">'.$i.'</span><br />';
	}
	echo '</td><td class="code">';
	echo highlight_string($code, true);
	echo '</td></tr></table>';
	
	return ob_get_clean();
}

function dump($obj, $depth=10, $highlight=true)
{
	$trace = debug_backtrace();
	$line = $trace[0]['line'];
	
	echo '<div class="dump">';
	echo '<div class="file">Dump at <a href="#line-'.$line.'">Line '.$line.'</a>:</div>';
	echo '<div class="code">';
	VarDumper::dump($obj, $depth, $highlight);
	echo "</div>";
	echo '</div';
}

function extract_file_metadata($file)
{
	$tokens = token_get_all(file_get_contents($file));
	$doc = null;
	foreach ($tokens as $token) {
		if ($token[0] == T_DOC_COMMENT) {
			$doc = $token[1];
			break;
		}
	}
	
	$meta = array('title'=>'', 'description'=>'', 'notes'=>array());
	if ($doc) {
		$lines = preg_split("/(\r\n|\n)/", trim(trim($doc, '/*')));
		foreach ($lines as $k=>$line) $lines[$k] = preg_replace('/^[\t ]*\* /', '', $line);
		$meta['title'] = $lines[0];
		
		$notes = false;
		foreach (array_slice($lines, 1) as $line) {
			$test = trim($line);
			if ($test && $test[0] == '@') {
				$notes = true;
			}
			
			if ($notes) {
				$x = explode(' ', ltrim($line, ' @'));
				if ($x[0]) {
					$meta['notes'][$x[0]] = isset($x[1]) ? $x[1] : true;
				} 
			}
			else {
				$meta['description'] .= $line;
			}	
		}
	}
	return $meta;
}

function titleise_slug($slug)
{
	return ucfirst(preg_replace('/[_-]/', ' ', $slug));
}

/**
 * This was borrowed from the Yii framework (yiiframework.com), which
 * is released under the following BSD license:
 * 
 * Copyright Copyright 2008-2011 by Yii Software LLC
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
 * 
 * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
 * Neither the name of Yii Software LLC nor the names of its contributors may be used to endorse or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class VarDumper
{
	private static $_objects;
	private static $_output;
	private static $_depth;

	/**
	 * Displays a variable.
	 * This method achieves the similar functionality as var_dump and print_r
	 * but is more robust when handling complex objects such as Yii controllers.
	 * @param mixed $var variable to be dumped
	 * @param integer $depth maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param boolean $highlight whether the result should be syntax-highlighted
	 */
	public static function dump($var,$depth=10,$highlight=false)
	{
		echo self::dumpAsString($var,$depth,$highlight);
	}

	/**
	 * Dumps a variable in terms of a string.
	 * This method achieves the similar functionality as var_dump and print_r
	 * but is more robust when handling complex objects such as Yii controllers.
	 * @param mixed $var variable to be dumped
	 * @param integer $depth maximum depth that the dumper should go into the variable. Defaults to 10.
	 * @param boolean $highlight whether the result should be syntax-highlighted
	 * @return string the string representation of the variable
	 */
	public static function dumpAsString($var,$depth=10,$highlight=false)
	{
		self::$_output='';
		self::$_objects=array();
		self::$_depth=$depth;
		self::dumpInternal($var,0);
		if($highlight)
		{
			$result=highlight_string("<?php\n".self::$_output,true);
			self::$_output=preg_replace('/&lt;\\?php<br \\/>/','',$result,1);
		}
		return self::$_output;
	}

	/*
	 * @param mixed $var variable to be dumped
	 * @param integer $level depth level
	 */
	private static function dumpInternal($var,$level)
	{
		switch(gettype($var))
		{
			case 'boolean':
				self::$_output.=$var?'true':'false';
				break;
			case 'integer':
				self::$_output.="$var";
				break;
			case 'double':
				self::$_output.="$var";
				break;
			case 'string':
				self::$_output.="'".addslashes($var)."'";
				break;
			case 'resource':
				self::$_output.='{resource}';
				break;
			case 'NULL':
				self::$_output.="null";
				break;
			case 'unknown type':
				self::$_output.='{unknown}';
				break;
			case 'array':
				if(self::$_depth<=$level)
					self::$_output.='array(...)';
				else if(empty($var))
					self::$_output.='array()';
				else
				{
					$keys=array_keys($var);
					$spaces=str_repeat(' ',$level*4);
					self::$_output.="array\n".$spaces.'(';
					foreach($keys as $key)
					{
						$key2=str_replace("'","\\'",$key);
						self::$_output.="\n".$spaces."    '$key2' => ";
						self::$_output.=self::dumpInternal($var[$key],$level+1);
					}
					self::$_output.="\n".$spaces.')';
				}
				break;
			case 'object':
				if(($id=array_search($var,self::$_objects,true))!==false)
					self::$_output.=get_class($var).'#'.($id+1).'(...)';
				else if(self::$_depth<=$level)
					self::$_output.=get_class($var).'(...)';
				else
				{
					$id=array_push(self::$_objects,$var);
					$className=get_class($var);
					$members=(array)$var;
					$spaces=str_repeat(' ',$level*4);
					self::$_output.="$className#$id\n".$spaces.'(';
					foreach($members as $key=>$value)
					{
						$keyDisplay=strtr(trim($key),array("\0"=>':'));
						self::$_output.="\n".$spaces."    [$keyDisplay] => ";
						self::$_output.=self::dumpInternal($value,$level+1);
					}
					self::$_output.="\n".$spaces.')';
				}
				break;
		}
	}
}
