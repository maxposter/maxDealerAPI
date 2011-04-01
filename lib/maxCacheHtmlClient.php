<?php
require_once(dirname(__FILE__).'/maxCacheXmlClient.php');

/**
 * К возможностям класса maxCacheXmlClient добавлено:
 * - преобразование XML в HTML посредством XSLT;
 * - кэширование полученного HTML.
 *
 * @author Александр Воробьев avorobiev@maxposter.ru
 * @version 1.0
 * @package maxposer_dealer_api
 */

class maxCacheHtmlClient extends maxCacheXmlClient
{
  private
    // название специального XSLT-шаблона, применяемого для преобразования XML в HTML
    $xsltTemplate
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
        // Путь к каталогу, содержащему XSLT. Путь должен заканчиваться слэшем.
        'xslt_dir' => 'source/xsl/',

        // Название каталога для хранения xml-кэша. Каталог будет создан внутри каталога cache_dir
        'cache_html_dir' => 'html/',

        // Название тем, которые должны кэшироваться с виде HTML
        'cached_html_themes' => array('marks', 'vehicles', 'vehicle')
      )
    );
  }

  /**
   * Установка специфического XSLT шаблона, для преобразования XML, взамен шаблона по умолчанию
   *
   * @param string $_xslt   Название XSLT-шаблона
   */
  public function setXsltTemplate($_xslt)
  {
    $this->xsltTemplate = $_xslt;
    return true;
  }

  /**
   * Получение шаблона для преобразования данных по настоящему запросу
   *
   * @return string    Название шаблона
   */
  public function getXsltTemplate()
  {
    return $this->xsltTemplate;
  }

  /**
   * Добавление к спросу xml-кэша сброса html-кэша
   *
   */
  protected function clearCache()
  {
    parent::clearCache();
    $this->delTree($this->getCacheDir('html'));
  }

  /**
   * Формирование пути к XSLT-шаблону
   *
   * @param string $_xslName - имя шаблона
   * @return string     Путь к XSLT-шаблону
   */
  protected function getPath2XSLT($_xslName)
  {
    return $this->getOption('xslt_dir').$_xslName.'.xsl';
  }

  /**
   * Составление строки для формирования хэш-ключа для поика данных в HTML-кэше
   *
   * @param string $_themeName    Название темы
   * @return strung   Строка для формирования хэша
   */
  protected function getHtmlCacheHashKey($_themeName)
  {
    return $this->getCacheHashKey($_themeName);
  }

  /**
   * Кэширование HTML
   *
   * @param string $_xsltName         Название Xslt-шаблона, примененного для получения HTML
   * @param string $_html             Html для кэширования
   * @return boolean      Результат кэширования
   */
  protected function cacheHtml($_xsltName, $_html)
  {
    if (in_array($_xsltName, $this->getOption('cached_html_themes')))
    {
      // Исключение для темы vehicle, поскольку в request код объявления, а в response "vehicle"
      $themeName = 'vehicle' == $_xsltName ? $this->getRequestThemeName() : $_xsltName;

    	$ret = $this->saveCacheToFile($this->getCachePath('html', $themeName), $_html);
    }
    else
    {
      $ret = false;
    }
    return $ret;
  }

  /**
   * Получение XSLT-шаблона в формате DOMDocument
   *
   * @param string $_xstlName   Название Xslt-шаблона
   * @return mixed    DOMDocument либо false в случае отсутствия шаблона
   */
  protected function getXslDom($_xstlName)
  {
    $ret = new DOMDocument();
    $ret->load($this->getPath2XSLT($_xstlName));
    return $ret;
  }

  /**
   * Преобразование Xml в соответствии с Xslt-шаблонов
   *
   * @param string $_xstlName   Название Xslt-шаблона
   * @return html
   */
  protected function xsltTransform($_xstlName)
  {
    $xsl = new XSLTProcessor();
    $xsl->importStyleSheet($this->getXslDom($_xstlName));

    return trim($xsl->transformToXML($this->getXml()));
  }

  /**
   * Преобразование XML в соответствии с шаблоном XSLT
   * Чтобы в результате преобразования XML с помощью XSLT получить кодировку,
   * отличную от UTF-8, надо в XSLT-шаблоне явно задать кодировку ответа в теге
   * <xsl:output>, например так: <xsl:output encoding="windows-1251"/>
   *
   * @return string - html полученный в результате преобразования
   */
  protected function transformXml2Html()
  {
    $responseTheme = $this->getResponseThemeName();

    // Возможность перекрыть xslt-шаблон по умолчанию, на заданный в maxCacheHtmlRequest для ответа, не содержащего ошибку
    $xsltName = (($this->getOption('error_theme') != $responseTheme) && $this->getXsltTemplate())
                    ? $this->getXsltTemplate()
                    : $responseTheme;

    $html = $this->xsltTransform($xsltName);

    $this->cacheHtml($xsltName, $html);

    return $html;
  }

  /**
   * Получение HTML из кэша
   *
   * @return mixed    Строка с данными либо false
   */
  protected function loadHtmlFromCache()
  {
    $themeName = $this->getXsltTemplate()
                    ? $this->getXsltTemplate()
                    : $this->getRequestThemeName()
                 ;
    return  $this->loadFromCache('html', $themeName);
  }


  /**
   * Получение HTML
   *
   * @param string $_xsltTemplate   Название XSLT-шаблона для преобразования XML в HTML
   * @return html
   */
  public function getHtml($_xsltTemplate = null)
  {
    if (!is_null($_xsltTemplate))
    {
      $this->setXsltTemplate($_xsltTemplate);
    }

    try
    {
      if (!($html = $this->loadHtmlFromCache()))
      {
        $this->getXml();
  	    $html = $this->transformXml2Html();
      }
    }
    catch (maxException $e)
    {
      $this->setErrorXml($e);
      $html = $this->xsltTransform($this->getResponseThemeName());
    }

    return $html;
  }
}
