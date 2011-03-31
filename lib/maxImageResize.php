<?php
require_once(dirname(__FILE__).'/maxGDAdapter.php');

class maxImageResize
{
  protected
    $adapter,       // Используемый адаптер для преобразование изображений
    $minWidth,      // Минимально-возможная ширина исходного изображения
    $minHeight,     // Минимально-возможная высота исходного изображения
    $fillColor      // Цвет заливки
  ;

  /**
   * Constructor
   *
   * @param int $_width         Требуемая ширина изображения
   * @param int $_height        Требуемах высота изображения
   * @param int $_minWidth      Минимальная ширина оригинала
   * @param int $_minHeight     Минимальная высота оригинала
   * @param int $_quality       Качестно
   * @param array $_adapterOptions  Параметра используемого адаптера
   * @param array $_fillColor   Цвет заливки
   */
  public function __construct($_minWidth = null, $_minHeight = null, $_quality = 75, $_adapterOptions = array(), $_fillColor = array(145, 145, 145))
  {
    $this->minWidth = $_minWidth;
    $this->minHeight = $_minHeight;
    $this->fillColor = $_fillColor;
    $this->adapter = new maxGDAdapter($_quality, $_adapterOptions);
  }

  /**
   * Loads an image from a file and creates an internal thumbnail out of it
   *
   * @param string filename (with absolute path) of the image to load.
   *
   * @return boolean True if the image was properly loaded
   * @throws Exception If the image cannot be loaded, or if its mime type is not supported
   */
  public function loadFile($image)
  {
    if (!is_readable($image))
    {
      throw new Exception(sprintf('The file "%s" is not readable.', $image));
    }

    $this->adapter->loadFile($image);
  }

  /**
  * Loads an image from a string (e.g. database) and creates an internal thumbnail out of it
  *
  * @param string the image string (must be a format accepted by imagecreatefromstring())
  * @param string mime type of the image
  *
  * @return boolean True if the image was properly loaded
  * @access public
  * @throws Exception If image mime type is not supported
  */
  public function loadData($image, $mime)
  {
    $this->adapter->loadData($image, $mime);
  }

  public function resize($width, $height)
  {
    return $this->adapter->resize($this, $width, $height);
  }

  /**
   * Saves the thumbnail to the filesystem
   * If no target mime type is specified, the thumbnail is created with the same mime type as the source file.
   *
   * @param string the image thumbnail file destination (with absolute path)
   * @param string The mime-type of the thumbnail (possible values are 'image/jpeg', 'image/png', and 'image/gif')
   *
   * @access public
   * @return void
   */
  public function save($thumbDest, $targetMime = null)
  {
    $this->adapter->save($this, $thumbDest, $targetMime);
  }

  /**
   * Returns the thumbnail as a string
   * If no target mime type is specified, the thumbnail is created with the same mime type as the source file.
   *
   *
   * @param string The mime-type of the thumbnail (possible values are adapter dependent)
   *
   * @access public
   * @return string
   */
  public function toString($targetMime = null)
  {
    return $this->adapter->toString($this, $targetMime);
  }

  public function toResource()
  {
    return $this->adapter->toResource($this);
  }

  public function freeSource()
  {
    $this->adapter->freeSource();
  }

  public function freeThumb()
  {
    $this->adapter->freeThumb();
  }

  public function freeAll()
  {
    $this->adapter->freeSource();
    $this->adapter->freeThumb();
  }

  /**
   * Возвращает mime type исходного изображения
   */
  public function getMime()
  {
    return $this->adapter->getSourceMime();
  }

  /**
   * Возвращает цвета фоновой заливки
   *
   * @return array(R, G, B)
   */
  public function getFillColor()
  {
    return $this->fillColor;
  }

  /**
   * Расчет параметров преобразования
   * Используется в адаптере
   */
  public function calcResizeParams($_width, $_height, $_sourceWidth, $_sourceHeight)
  {
    $thumbX = $thumbY = $sourceX = $sourceY = $thumbWidth = $thumbHeight = $sourceWidth = $sourceHeight = 0;

    // Определяем максимальную кратность для фото
    $division = (($_sourceWidth/4) > ($_sourceHeight/3))
                  ? floor($_sourceHeight/3)    // ширина больше высоты
                  : floor($_sourceWidth/4)     // высота больше ширины
              ;
    // Определяем размеры оригинала, используемые для формирования копии
  	$sourceWidth  = $division*4;
	  $sourceHeight = $division*3;

	  if (($sourceWidth >= $this->minWidth) && ($sourceHeight >= $this->minHeight))
	  {
	    // Приведенные к размеру 4х3
	    $sourceX = floor(($_sourceWidth - $sourceWidth) / 2);
	  	$sourceY = floor(($_sourceHeight - $sourceHeight) / 2);
	  	$thumbX = 0;
	    $thumbY = 0;
	    $thumbWidth = $_width;
	    $thumbHeight = $_height;
	  }
	  else
	  {
	    // Масштабирование с заливкой без обрезания
	    $sourceX = 0;
	  	$sourceY = 0;
	  	$sourceWidth  = $_sourceWidth;
  	  $sourceHeight = $_sourceHeight;

  	  if (($sourceWidth < $this->minWidth) && ($sourceHeight < $this->minHeight))
  	  {
  	    $thumbWidth = $sourceWidth >= $this->minWidth
    	                   ? $_width
    	                   : floor($_width * $sourceWidth / $this->minWidth)
    	                ;
  	    $thumbHeight = $sourceHeight >= $this->minHeight
    	                   ? $_height
    	                   : floor($_height * $sourceHeight / $this->minHeight)
    	                ;
  	  	$thumbX = floor(($_width - $thumbWidth) / 2);
  	    $thumbY = floor(($_height - $thumbHeight) / 2);
  	  }
  	  elseif(($sourceWidth >= $this->minWidth) && ($sourceHeight < $this->minHeight))
  	  {
  	    $ratio = $this->minWidth / $sourceWidth;
  	    $thumbWidth = $_width;
  	    $thumbX = 0;
  	    $thumbHeight = floor($_height * ($sourceHeight * $ratio / $this->minHeight));
  	    $thumbY = floor(($_height - $thumbHeight) / 2);
  	  }
  	  elseif(($sourceWidth < $this->minWidth) && ($sourceHeight >= $this->minHeight))
  	  {
  	    $ratio = $this->minHeight / $sourceHeight;
  	    $thumbHeight = $_height;
  	    $thumbY = 0;
  	    $thumbWidth = floor($_width * ($sourceWidth * $ratio / $this->minWidth));
  	    $thumbX = floor(($_width - $thumbWidth) / 2);
  	  }
  	  else
  	  {
  	    $ratio = ($sourceWidth / $this->minWidth) > ($sourceHeight / $this->minHeight)
  	               ? $sourceHeight / $this->minHeight
  	               : $sourceWidth / $this->minWidth
	               ;
        $thumbWidth = floor($_width * $ratio);
        $thumbHeight = floor($_height * $ratio);
        $thumbX = floor(($_width - $thumbWidth) / 2);
        $thumbY = floor(($_height - $thumbHeight) / 2);
  	  }
	  }
    return array($thumbX, $thumbY, $sourceX, $sourceY, $thumbWidth, $thumbHeight, $sourceWidth, $sourceHeight);
  }

  public function __destruct()
  {
    $this->freeAll();
  }
}
