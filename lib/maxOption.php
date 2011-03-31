<?php
require_once(dirname(__FILE__).'/maxException.php');

/**
 * Абстрактный класс обеспечивающий работу с опциями
 *
 * @author Александр Воробьев avorobiev@maxposter.ru
 * @version 1.0
 * @package maxposer_dealer_api
 * @abstract
 */

abstract class maxOption
{
  // Параметры по умолчанию задаются в методе getDefaultOptions() на наследниках
  private $options = array();

  /**
   * Конструктор
   *
   * @param array $_options   Параметры класса, перекрывающие параметры заданные по умолчанию
   */
  public function __construct(array $_options = array())
  {
    $this->setOptions(array_merge($this->getDefaultOptions(), $_options));
  }

  /**
   * Метод должен возвращать массив с параметрами по умолчанию
   *
   * @return array Массив с параметрами по умолчанию
   * @abstract
   */
  abstract protected function getDefaultOptions();

  /**
   * Получение значения параметра по названию
   *
   * @param string $_name   Название параметра
   * @return mixed    Значение параметра
   */
  public function getOption($_name)
  {
    return isset($this->options[$_name]) ? $this->options[$_name] : null;
  }

  public function getRequiredOption($_name)
  {
    if (!isset($this->options[$_name]) || !$this->options[$_name])
    {
      throw maxException::getException(maxException::ERR_DOES_NOT_SET_REQUIRED_OPTION, $_name);
    }

    return $this->options[$_name];
  }

  /**
   * Получение массив со всеми параметрами класса
   *
   * @return array    Параметры класса
   */
  public function getOptions()
  {
    return $this->options;
  }

  /**
   * Установка параметра класcа
   *
   * @param string $_name         Название параметра
   * @param mixed $_value         Значение параметра
   * @return true
   */
  public function setOption($_name, $_value)
  {
    $this->options[$_name] = $_value;
    return true;
  }

  /**
   * Добавление/установка нескольких параметров класса
   *
   * @param array $_options     Массив с добавляемыми параметрами
   * @return true
   */
  public function setOptions(array $_options)
  {
    foreach ($_options as $key => $value)
    {
      $this->setOption($key, $value);
    }
    return true;
  }
}
