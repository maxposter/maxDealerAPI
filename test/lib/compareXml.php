<?php
function compareXML($_one, $_two, $_from = 'UTF-8', $_to = 'CP866//TRANSLIT')
{
  $one = str_replace(array("\r", "\n", "\t", '  '), '', iconv($_from, $_to, $_one));
  $two = str_replace(array("\r", "\n", "\t", '  '), '', iconv($_from, $_to, $_two));
  $length = strlen($one) >= strlen($two) ? strlen($one) : strlen($two);
  $ok = true;
  for ($i=0; $i<$length; $i++)
  {
    if (($one[$i]!=$two[$i]) || !$ok)
    {
      $ok = false;
      echo '#...'.substr($one, $i, 50).PHP_EOL;
      echo '#...'.substr($two, $i, 50).PHP_EOL;
      break;
    }
  }
  return $ok && strlen($one) == strlen($two);
}