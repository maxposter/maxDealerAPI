<?php
require_once(dirname(__FILE__).'/maxOption.php');

/**
 * Реализация класса для запросов экспортных данных автосалонов от Интернет-сервиса MaxPoster
 *
 * @author Александр Воробьев avorobiev@maxposter.ru
 * @version 1.0
 * @package maxposer_dealer_api
 */

class maxXmlClient extends maxOption
{
  private
    // Название темы для запроса к интернет-сервесу
    $requestThemeName,

    // Данные заголовков ответа "Last-Modified" и "Expires" от запроса к Интернет-сервису
    $responseHeaders = false,

    // Параметры запроса (будут переданы POST-запросом Интернет-сервису)
    $requestParams = array(),

    // Параметры паджинатора
    $getParameters = array(),

    // Название темы, полученной в ответ на запрос
    $responseThemeName
  ;

  protected
    // Ответ в формате DOMDocument либо false
    $xml
  ;

  /**
   * Определение параметров класса по умолчанию
   *
   * @return array()      Параметры класса по умолчанию
   */
  protected function getDefaultOptions()
  {
    return array(
      // Основная точка доступа к сервису
      'mirror1' => 'http://export1.maxposter.ru/',

      // Резервная точка доступа к сервису
      'mirror2' => 'http://export2.maxposter.ru/',

      // Номер версии API Интернет-Сервиса
      'api_version' => '',

      // Код автосалона в системе MaxPoster.ru, либо массив с кодами автосалонов
      'dealer_id' => '',

      // Пароль для http-авторизации
      'password' => '',

      // Список допустимых тем запросов (кроме перечисленных тем допустим запрос с кодом автообъявления)
      'allowed_request_themes' => array('marks', 'search_form', 'vehicles', 'full_vehicles'),

      // Параметры CURL
      'CURL' => array(
        //CURLOPT_TIMEOUT => 30         // Ожидать ответа в течение 30 секунд
      ),

      'max_per_page' => '50',
    );
  }

  /**
   * Установка темы запроса
   *
   * @param mixed $_themeName     Тема, либо код автообъявления
   * @return true
   */
  public function setRequestThemeName($_requestThemeName)
  {
    $allowedRequestThemes = $this->getOption('allowed_request_themes');
    if (  (is_array($allowedRequestThemes) && in_array($_requestThemeName, $allowedRequestThemes))
        || is_numeric($_requestThemeName)
    )
    {
      $this->requestThemeName = $_requestThemeName;
    }
    else
    {
      throw maxException::getException(maxException::ERR_WRONG_REQUEST_THEME, $_requestThemeName);
    }
    return true;
  }

  public function getRequestThemeName()
  {
    if (!$this->requestThemeName)
    {
      throw maxException::getException(maxException::ERR_DOES_NOT_SET_REQUEST_THEME);
    }
    return $this->requestThemeName;
  }


    /**
     * Merges any number of arrays of any dimensions, the later overwriting
     * previous keys, unless the key is numeric, in whitch case, duplicated
     * values will not be added.
     *
     * The arrays to be merged are passed as arguments to the function.
     *
     * @access public
     * @return array Resulting array, once all have been merged
     */
    public function mergeDeep() {
        // Holds all the arrays passed
        $params = func_get_args();

        // First array is used as the base, everything else overwrites on it
        $return = array_shift($params);

        // Merge all arrays on the first array
        foreach ($params as $array) {
            foreach ($array as $key => $value) {
                // Numeric keyed values are added (unless already there)
                if (is_numeric($key) && (!in_array($value, $return))) {
                    if (is_array($value)) {
                        $return[] = $this->mergeDeep($return[$key], $value);
                    } elseif (false !== $value) {
                        $return[] = $value;
                    }

                // String keyed values are replaced
                } else {
                    if (isset($return[$key]) && is_array($value) && is_array($return[$key])) {
                        $return[$key] = $this->mergeDeep($return[$key], $value);
                    } elseif (false === $value) {
                        unset($return[$key]);
                    } else {
                        $return[$key] = $value;
                    }
                }
            }
        }

        return $return;
    }

  /**
   * Установка параметров, которые должны быть переданы Интернет-сервису MaxPoster
   *
   * @param array $params
   */
  public function setRequestParams(array $params)
  {
    $this->requestParams = $this->mergeDeep($this->requestParams, $params);
  }

  /**
   * Возвращает параметры, которые должны быть перенады Интернет-сервису MaxPoster
   *
   * @return array
   */
  public function getRequestParams()
  {
    return $this->requestParams;
  }

  /**
   * Установка параметров, которые должны быть переданы сервису как часть query-string
   *
   * @param  array  $params
   */
  public function setGetParameters($params = array())
  {
    $this->getParameters = $this->mergeDeep($this->getParameters, (array) $params);
  }

  /**
   * Вернуть параметры для отправки сервису методом GET
   *
   * @return array
   */
  public function getGetParameters()
  {
    return $this->getParameters;
  }

  /**
   * Определение кода автосалона по параметру dealer_id
   *
   * @return string   Код автосалона для http-запросов
   */
  protected function getDealerId()
  {
    $dealerId = $this->getOption('dealer_id');
    if (!$dealerId)
    {
      throw new maxException(
        maxException::$err_messages[maxException::ERR_DOES_NOT_SET_DEALERID],
        maxException::ERR_DOES_NOT_SET_DEALERID
      );
    }
    return (string) $dealerId;
  }

  /**
   * Формирование относительного пути для запроса к Интернет-сервису MaxPoster
   *
   * @param string $_requestThemeName    Название темы запроса
   * @return string   Отсносительный путь для запроса к Интернет-сервису MaxPoster
   */
  protected function getRelativePath()
  {

    $apiPrefix = $this->getOption('api_version')
                    ? 'api'.$this->getOption('api_version').'/'
                    : ''
                 ;

    return $apiPrefix.$this->getDealerId().'/'.$this->getRequestThemeName().'.xml';
  }

  /**
   * Формирование абсолютного пути для запроса к Интернет-сервису MaxPoster
   *
   * @param string $_mirror              Допустимые значения mirror1 | mirror2
   * @return string   Путь к Интернет-Сервису
   */
  protected function getAbsolutePath($_mirror)
  {
    $mirror = $this->getOption($_mirror);
    if (!$mirror)
    {
      throw new maxException(
        sprintf(maxException::$err_messages[maxException::ERR_DOES_NOT_SET_MIRROR], $_mirror),
        maxException::ERR_DOES_NOT_SET_MIRROR
      );
    }
    return $mirror.$this->getRelativePath();
  }

  /**
   * Формирование абсолютного пути к основному зеркалу Интернет-сервиса MaxPoster
   *
   * @return string   Путь к Интернет-Сервису
   */
  protected function getPathToFirstMirror()
  {
    return $this->getAbsolutePath('mirror1');
  }

  /**
   * Формирование абсолютного пути к запасному зеркалу Интернет-сервиса MaxPoster
   *
   * @return string   Путь к Интернет-Сервису
   */
  protected function getPathToSecondMirror()
  {
    return $this->getAbsolutePath('mirror2');
  }

  /**
   * Извлечение из заголовков ответа данных о:
   * - времени генерации данных (Last-Modified);
   * - времени актуальности данных (Expires).
   *
   * @param object $ch
   * @param string $header
   */
  protected function setResponseHeaders($_ch, $_header)
  {
    if (false!== strpos($_header, 'Last-Modified: '))
    {
    	$this->responseHeaders[0] = strtotime(substr($_header, strlen('Last-Modified: ')));
    }
    if (false!== strpos($_header, 'Expires: '))
    {
    	$this->responseHeaders[1] = strtotime(substr($_header, strlen('Expires: ')));
    }
    return strlen($_header);
  }

  /**
   * Возвращает массив с двумя элементами:
   * - таймстампом времени генерации данных;
   * - таймстампом времени, до которого данные считаются актуальными.
   *
   * @return array(timestamp генерации, timestamp актуальности)
   */
  protected function getResponseHeaders()
  {
    return $this->responseHeaders;
  }

  /**
   * Подготовка параметров CURL
   *
   * @return array      Параметры CURL
   */
  protected function getCurlOptions()
  {
    $options = array(
      CURLOPT_HEADER => 0,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_HEADERFUNCTION => array($this, 'setResponseHeaders'),
      CURLOPT_TIMEOUT => 5,
      CURLOPT_USERPWD => $this->getDealerId().':'.$this->getOption('password')
    );
    $userOptions = $this->getOption('CURL');
    if (is_array($userOptions))
    {
    	foreach ($userOptions as $id => $value)
    	{
    	  $options[$id] = $value;
    	}
    }
    return $options;
  }

  /**
   * Инициализация CURL. Установка параметров запроса.
   *
   * @param string    $_path         Путь для запроса XML (может быть как локальным так и URL)
   * @param array     $_postParams   POST-параметры запроса
   * @return resource   CURL
   */
  protected function initCurl($_path, array $_postParams = null)
  {
    $ch = curl_init();

    foreach ($this->getCurlOptions() as $id => $value) {
      curl_setopt($ch, $id, $value);
    }

    if (is_array($_postParams) && count($_postParams)) {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_postParams));
    }

    $path = $_path;
    if (false !== $getParams = $this->getGetParameters()) {
      $path .= '?' . http_build_query($getParams);
    }

    curl_setopt($ch, CURLOPT_URL, $path);

    return $ch;
  }

  /**
   * Загрузка XML с адреса $_path. В случае ошибки позвращает false.
   * Ошибка получения XML от сервиса может быть из-за отсутствия ответа от сервера
   * либо из-за передачи некорректного XML
   *
   * @param string    $_path         Путь для запроса XML (может быть как локальным так и URL)
   * @return DOMDocument    Xml как DOM-объект, либо false в случае неудачи
   */
  protected function loadXmlFromMirror($_path)
  {
    // Сброс заголовков о времени генерации данных и сроке годности
    $this->responseHeaders = array(0, 0);

    try
    {
      $ch = $this->initCurl($_path, $this->getRequestParams());
      $xml = curl_exec($ch);
      curl_close($ch);
    }
    catch (maxException $e)
    {
      $xml = false;
    }

    if(false != $xml)
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
   * Полчение названия темы из XML
   *
   * @param DOMDocument $_xml
   */
  public function getResponseThemeName()
  {
    if (is_null($this->responseThemeName))
    {
      if ($this->xml instanceof DOMDocument)
      {
      	$this->responseThemeName =  $this->xml->getElementsByTagName('response')->item(0)->getAttribute('id');
      }
    	if (!$this->responseThemeName)
    	{
    		throw maxException::getException(maxException::ERR_DOES_NOT_SET_RESPONSE_THEME);
    	}
    }

    return $this->responseThemeName;
  }

  protected function setErrorXml(maxException $_e)
  {
    $this->responseThemeName = null;
    $this->xml = new DOMDocument();
    $this->xml->loadXML('<?xml version="1.0" encoding="utf-8"?><response id="error"><error error_id="'.$_e->getCode().'">'.$_e->getMessage().'</error></response>');
  }

  /**
   * Загрузка XML из Интернет-сервиса
   *
   */
  protected function loadXml()
  {
    // Получение ответа от первого зеркала
    $this->xml = $this->loadXmlFromMirror($this->getPathToFirstMirror());

    // Если от первого зеркала получена ошибка, направляем запрос ко второму зеркалу
    if (false === $this->xml)
    {
      $this->xml = $this->loadXmlFromMirror($this->getPathToSecondMirror());
    }

    if (false == $this->xml)
  	{
  	  throw maxException::getException(maxException::ERR_NO_RESPONSE);
  	}
  }

  /**
   * Полчение XML из Интернет-Сервиса
   *
   * @return DOMDocument  XML как DOM-объект
   */
  public function getXml()
  {
    if (!($this->xml instanceof DOMDocument))
    {
      try
      {
        $this->loadXml();
      }
      catch (maxException $e)
      {
        $this->setErrorXml($e);
      }
    }
    return $this->xml;
  }
}
