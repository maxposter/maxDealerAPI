<?php
require_once($max_path.'/maxOption.php');

class maxOptionTest extends maxOption
{
  protected function getDefaultOptions()
  {
    return array('first' => 'first_value');
  }
}
