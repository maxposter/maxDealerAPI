<?php

class maxGDAdapter
{
  protected
    $sourceMime,          // MIME исходного изображения
    $quality,             // Качество преобраования
    $source,              // Исходное изображение
    $sourceWidth,         // Ширина исходного изображения
    $sourceHeight,        // Высота исходного изображения
    $thumb;               // Преобразованное изображение

  /**
   * List of accepted image types based on MIME
   * descriptions that this adapter supports
   */
  protected $imgTypes = array(
    'image/jpeg',
    'image/pjpeg',
    'image/png',
    'image/gif',
  );

  /**
   * Stores function names for each image type
   */
  protected $imgLoaders = array(
    'image/jpeg'  => 'imagecreatefromjpeg',
    'image/pjpeg' => 'imagecreatefromjpeg',
    'image/png'   => 'imagecreatefrompng',
    'image/gif'   => 'imagecreatefromgif',
  );

  /**
   * Stores function names for each image type
   */
  protected $imgCreators = array(
    'image/jpeg'  => 'imagejpeg',
    'image/pjpeg' => 'imagejpeg',
    'image/png'   => 'imagepng',
    'image/gif'   => 'imagegif',
  );

  /**
   * Конструктор
   *
   * @param float $quality          // Качество, с которым будет преобразовываться изображение
   * @param array $options          // Параметры адаптера
   */
  public function __construct($quality, $options)
  {
    if (!extension_loaded('gd'))
    {
      throw new Exception ('GD not enabled. Check your php.ini file.');
    }
    $this->quality = $quality;
    $this->options = $options;
  }

  /**
   * Загрузка исходного изображения из файла
   *
   * @param string $image   Путь к исходному изображению
   * @return boolean        True при успешной загрузке, либо exception
   */
  public function loadFile($image)
  {
    $imgData = @GetImageSize($image);

    if (!$imgData)
    {
      throw new Exception(sprintf('Could not load image %s', $image));
    }

    if (in_array($imgData['mime'], $this->imgTypes))
    {
      $loader = $this->imgLoaders[$imgData['mime']];
      if(!function_exists($loader))
      {
        throw new Exception(sprintf('Function %s not available. Please enable the GD extension.', $loader));
      }

      $this->source = $loader($image);
      $this->sourceMime = $imgData['mime'];
      $this->sourceWidth = $imgData[0];
      $this->sourceHeight = $imgData[1];

      return true;
    }
    else
    {
      throw new Exception(sprintf('Image MIME type %s not supported', $imgData['mime']));
    }
  }

  /**
   * Загрузка изображения из переменной
   *
   * @param data $image           // Изображени
   * @param string $mime          // MIME-type
   * @return boolean              True при успешной загрузке, либо exception
   */
  public function loadData($image, $mime)
  {
    if (in_array($mime, $this->imgTypes))
    {
      $this->source = imagecreatefromstring($image);
      $this->sourceMime = $mime;
      $this->sourceWidth = imagesx($this->source);
      $this->sourceHeight = imagesy($this->source);

      return true;
    }
    else
    {
      throw new Exception(sprintf('Image MIME type %s not supported', $mime));
    }
  }

  /**
   * Возвращает ширину исходного изображения, либо выбрасывает exception если исходное изображение не загружено
   *
   * @return int        Ширина
   */
  public function getSourceWidth()
  {
    if (is_null($this->sourceWidth))
    {
    	throw new Exception('Source image has not loaded');
    }
    return $this->sourceWidth;
  }

  /**
   * Возвращает высоту исходного изображения, либо выбрасывает exception если исходное изображение не загружено
   *
   * @return int        Высота
   */
  public function getSourceHeight()
  {
    if (is_null($this->sourceHeight))
    {
    	throw new Exception('Source image has not loaded');
    }
    return $this->sourceHeight;
  }

  /**
   * Преобразование исходного изображения
   *
   * @param maxImageResize $imageResize   // Отвечает за координаты преобразования и фоновую заливку
   * @param int $width                    // Ширина получаемого изображения
   * @param int $height                   // Высота получаемого изображения
   * @return boolean
   */
  public function resize(maxImageResize $imageResize, $width, $height)
  {
    if ($this->getSourceWidth() == $width && $this->getSourceHeight() == $height)
    {
      // Преобразование не требуется
      $this->thumb = $this->source;
    }
    else
    {
      $size = $imageResize->calcResizeParams($width, $height, $this->getSourceWidth(), $this->getSourceHeight());

      $this->thumb = imagecreatetruecolor($width, $height);
      if ($size[0] || $size[1]) // Если требуется заливка
      {
        $fillColor = $imageResize->getFillColor();
      	imagefill($this->thumb, 0, 0, imagecolorallocate($this->thumb, $fillColor[0], $fillColor[1], $fillColor[2]));
      }
      imagecopyresampled($this->thumb, $this->source, $size[0], $size[1], $size[2], $size[3], $size[4], $size[5], $size[6], $size[7]);
    }
    return true;
  }

  public function save($imageResize, $thumbDest, $targetMime = null)
  {
    if($targetMime !== null)
    {
      $creator = $this->imgCreators[$targetMime];
    }
    else
    {
      $creator = $this->imgCreators[$imageResize->getMime()];
    }

    if ($creator == 'imagejpeg')
    {
      imagejpeg($this->thumb, $thumbDest, $this->quality);
    }
    else
    {
      $creator($this->thumb, $thumbDest);
    }
  }

  public function toString($imageResize, $targetMime = null)
  {
    if ($targetMime !== null)
    {
      $creator = $this->imgCreators[$targetMime];
    }
    else
    {
      $creator = $this->imgCreators[$imageResize->getMime()];
    }

    ob_start();
    $creator($this->thumb);

    return ob_get_clean();
  }

  public function toResource()
  {
    return $this->thumb;
  }

  public function freeSource()
  {
    if (is_resource($this->source))
    {
      imagedestroy($this->source);
    }
  }

  public function freeThumb()
  {
    if (is_resource($this->thumb))
    {
      imagedestroy($this->thumb);
    }
  }

  public function getSourceMime()
  {
    return $this->sourceMime;
  }

}
