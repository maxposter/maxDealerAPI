<?php
require_once(dirname(__FILE__).'/maxXmlClient.php');

/**
 * К возможностям класса maxXmlClient добавлено кэширование данных на стороне Клиента.
 * Перед запросом к Интернет-сервису проверяется наличие актуальных данных в кэше. Если в кэше данные есть,
 * то данные берутся из кэша и запрос к Интернет-сервису не производится. Если актуальных данных в кэше нет,
 * то после получения данных они кэшируются для повторных обращений.
 *
 * @author Александр Воробьев avorobiev@maxposter.ru
 * @version 1.0
 * @package maxposer_dealer_api
 */

class maxCacheXmlClient extends maxXmlClient
{
  private
    /**
     * Массив с таймстампами, характеризиующими актуальность данных в кэше:
     * [0] - время генерации данных,
     * [1] - срок актуальности данных.
     */
    $cacheActualPoint
  ;

  /**
   * Добавление специфических для класса параметров
   *
   * @return array()      Параметры класса по умолчанию
   */
  protected function getDefaultOptions()
  {
    return array_merge(
      parent::getDefaultOptions(),
      array(
        /* Путь к каталогу для кэширования. У процесса, выполняющего скрипт должны быть права
           rwx на каталог и его файлы. Путь должен заканчиваться слэшем */
        'cache_dir' => 'cache/',

        // Название файла, в котором хранятся данные об актуальности кэша
        'cache_actual_file' => 'actual_point.txt',

        // Название каталога для хранения xml-кэша. Каталог будет создан внутри каталога cache_dir
        'cache_xml_dir' => 'xml/',

        /* Название тем, которые должны кэшироваться в виде XML (т.е. до XSLT-преобразования).
           Кэшировать XML обосновано для XML, на основании которых формируется несколько страниц,
           например vehicles */
        'cached_xml_themes' => array('marks', 'vehicles', 'vehicle'),

        // Название темы с описанием возникшей ошибки
        'error_theme' => 'error'
      )
    );
  }

  /**
   * Рекурсивная функция. Создает строку для формирвоания ключа кэша.
   * Ключ формируется из параметров запроса, имеющих значения, и упорядоченных по возрастанию.
   *
   * @param mixed $_param     Параметр (или массив с параметрами)
   * @param string $_key      Название ключа
   * @return string   Строка для формирования ключа кэша
   */
  protected function getRequestParamsAsString($_param, $_key = null)
  {
    $ret = '';
    if (is_array($_param) && ksort($_param))
    {
      foreach ($_param as $key => $value)
      {
        $key = $_key ? $_key.'['.$key.']' : $key;
      	$ret .= $this->getRequestParamsAsString($value, $key);
      }
    }
    else if (('' !== $_key) && ('' !== $_param))  // Важно чтобы в хэше оказались только значащие для результата значения
    {
      $ret = '&'.$_key.'='.$_param;
    }

    return $ret;
  }

  /**
   * Составление строки для формирования хэш-ключа для поика данных в кэше
   *
   * @param string $_themeName    Название темы
   * @return string   Строка для формирования хэша
   */
  protected function getCacheHashKey($_themeName)
  {
    return $_themeName.$this->getRequestParamsAsString($this->getRequestParams()).$this->getRequestParamsAsString($this->getGetParameters());
  }

  /**
   * Составление строки для формирования хэш-ключа для поика данных в кэше
   *
   * @param string $_themeName    Название темы
   * @return string   Строка для формирования хэша
   */
  protected function getXmlCacheHashKey($_themeName)
  {
    return $this->getCacheHashKey($_themeName);
  }

  /**
   * Формирование пути к каталогу с кэшем
   *
   * @param string $_type           Тип кэша. Ожидаемые значения xml|html
   * @return string Путь к каталогу с кэшем
   */
  protected function getCacheDir($_type)
  {
    return $this->getOption('cache_dir').$this->getOption('cache_'.$_type.'_dir');
  }

  /**
   * Формирование пути к данным в кэше
   *
   * @param string $_type         Тип кэша. Допустимые значения xml|html
   * @param string $_themeName    Название темы
   * @return string(32)     Путь к кэшу
   */
  protected function getCachePath($_type, $_themeName)
  {
    $fCacheHash = 'get'.ucfirst($_type).'CacheHashKey';
    return $this->getCacheDir($_type).md5($this->$fCacheHash($_themeName)).'.'.$_type;
  }

  /**
   * Определение пути к файлу с данными об актуально кэша
   * @return string Путь к файлу с данными об актуальности кэша
   */
  protected function getCacheActualPointPath()
  {
    return $this->getOption('cache_dir').$this->getOption('cache_actual_file');
  }

  /**
   * Получение строки с данными об актуальности кэша из файла
   *
   * @return string   Строка с данными об актуальности кэша
   */
  protected function getCacheActualPointFromFile()
  {
    return @file_get_contents($this->getCacheActualPointPath());
  }

  /**
   * Получение данных об актуальности кэша
   *
   * @return array
   */
  protected function getCacheActualPoint()
  {
    if (is_null($this->cacheActualPoint))
    {
      $actualPoint = explode(' ', $this->getCacheActualPointFromFile());

      $this->cacheActualPoint = count($actualPoint) > 1 ? $actualPoint : array(0, 0);
    }
    return $this->cacheActualPoint;
  }


    /**
     * Рекурсивное удаление каталога и вложенных в него подкаталогов и файлов.
     * Используется для удаления кэша, потерявшего актуальность.
     *
     * @param string $dir   Путь к каталогу, который должен быть удален
     */
    protected function delTree($dir)
    {
        $list = glob($dir . '*', GLOB_MARK);
        // в случае когда нет файлов в кеше
        if (!$list && !is_array($list)) {
            return false;
        }

        foreach($list as $file) {
            if (DIRECTORY_SEPARATOR == substr($file, -1)) {
                $this->delTree($file);
                @rmdir($file);
            } else {
                @unlink($file);
            }
        }

        return true;
    }


  /**
   * Сброс кэша
   *
   */
  protected function clearCache()
  {
    $this->delTree($this->getCacheDir('xml'));
  }

  /**
   * Сохранение новой точки актуальности кэша
   *
   * @param array $_cacheActualPoint   Массив формата (timestap - время обновления данных, timestamp - время актуальности)
   * @return    Количество записанных байт, либо false в случае сбоя записи
   */
  protected function saveCacheActualPoint(array $_cacheActualPoint)
  {
    $this->cacheActualPoint = $_cacheActualPoint;
    return @file_put_contents($this->getCacheActualPointPath(), implode(' ', $this->cacheActualPoint));
  }

  /**
   * Обновление данных точки актуальности кэша
   *
   * @param array $_cacheActualPoint   Массив формата (timestap - время обновления данных, timestamp - время актуальности)
   */
  protected function updateCacheActualPoint(array $_cacheActualPoint)
  {
    $cacheActualPoint = $this->getCacheActualPoint();

    // Если изменилась дата генерации данных, то обнуляем кэш
    if ($_cacheActualPoint[0] != $cacheActualPoint[0])
    {
      $this->clearCache();
    }

    // Если изменились данные точки актуальности, то обновляем точку актуальности
    if ($cacheActualPoint != $_cacheActualPoint)
    {
      $this->saveCacheActualPoint($_cacheActualPoint);
    }
  }

  /**
   * Проверка актуальности кэша
   *
   * @return boolean    true если кэш актуальне, либо false
   */
  protected function checkCacheExpiresAt()
  {
    list($cacheActualAt, $cacheExpiresAt) = $this->getCacheActualPoint();

    return $cacheExpiresAt >= time();
  }

  /**
   * Получение данных из кэша
   *
   * @param string $_type   Тип кэша. Допустимые значения xml|html
   * @param string $_themeName    Название темы
   * @return string   Строка, содержащая данные из кэша
   */
  protected function loadFromCache($_type, $_themeName)
  {
    $ret = false;
    if ($this->checkCacheExpiresAt())
    {
    	$cachePath = $this->getCachePath($_type, $_themeName);
    	if (is_file($cachePath))
    	{
    		$ret = @file_get_contents($cachePath);
    	}
    }
    return $ret;
  }

  /**
   * Получение XML из кэша
   *
   * @return mixed    XML в формате DOMDocument либо false
   */
  protected function loadXmlFromCache()
  {
    $xml = $this->loadFromCache('xml', $this->getRequestThemeName());

    if (false !== $xml)
    {
      $ret = new DOMDocument();
      $ret->loadXML($xml);
    }
    else
    {
      $ret = false;
    }


    return $ret;
  }

  /**
   * Сохранение текста в кэше
   *
   * @param string $_path       Путь к файлу для сохранения кэша
   * @param string $_string     Текст для сохранения в кэше
   * @return int      Количество сохраненных в файл байт, либо false
   */
  protected function saveCacheToFile($_path, $_string)
  {
    $ret = false;
    /**
     * Запись в кэш выполняется только если есть информация о:
     * - времени генерации данных;
     * - времени актуальности кэша.
     * Иначе кэшировать данные бессмысленно.
     */
    if ($this->cacheActualPoint[0] && $this->cacheActualPoint[1] && $_string)
    {
    	$dir = dirname($_path);
      if (!is_dir($dir))
      {
        @mkdir($dir, 0775, true);
      }
      $ret = @file_put_contents($_path, $_string);
    }

    return $ret;
  }

  /**
   * Кэширование XML
   *
   * @return int - число байт при сохранении в кэш или false
   */
  protected function cacheXML()
  {
    $ret = false;

    // В кэше сохраняются только темы, заданные в опции cached_xml_themes
    if (($this->xml instanceof DOMDocument) && (in_array($this->getResponseThemeName(), $this->getOption('cached_xml_themes'))))
    {
      $this->updateCacheActualPoint($this->getResponseHeaders());

      /**
       * Для формирвоания хэша кэша используется getRequestThemeName, поскольку для темы vehicle
       * request будет код объявления, а в response 'vehicle'. Чтобы при запросе данных в кэша они
       * находились, используем для кэширования всегда данные из getRequestThemeName.
       */
      $ret = $this->saveCacheToFile($this->getCachePath('xml', $this->getRequestThemeName()), $this->xml->saveXML());
    }

    return $ret;
  }

  /**
   * Загрузка XML из кэша, либо, при отсутствии в кэше, из Интернет-Сервиса
   *
   * @return DOMDocument  XML как DOM-объект
   */
  protected function loadXml()
  {
    try
    {
    	if (!($this->xml = $this->loadXmlFromCache()))
    	{
    	  // Загрузка из Интернет-сервиса
    		parent::loadXml();

    		// Кэширование полученного Xml
    		$this->cacheXML();
    	}
    }
    catch (maxException $e)
    {
      $this->setErrorXml($e);
    }
  }
}
