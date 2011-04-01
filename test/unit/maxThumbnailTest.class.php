<?php
require_once($max_path.'/maxThumbnail.php');

class maxThumbnailTest extends maxThumbnail
{
  public
    $loadSourcePhoto = array(),
    $savePhoto = array()
  ;

  protected function getDefaultOptions()
  {
    return array_merge(
      parent::getDefaultOptions(),
      array(
        'dealer_id' => 106
      )
    );
  }

  public function getSourcePhotoUrl($_dealerId, $_autoId, $_imageId)
  {
    return parent::getSourcePhotoUrl($_dealerId, $_autoId, $_imageId);
  }

  public function getPhotoFilePath($_dealerId, $_autoId, $_imageId, $_typeId)
  {
    return parent::getPhotoFilePath($_dealerId, $_autoId, $_imageId, $_typeId);
  }

  public function checkSourcePhoto($_dealerId, $_autoId, $_imageId)
  {
    return parent::checkSourcePhoto($_dealerId, $_autoId, $_imageId);
  }

  // Метод полностью перекрыт для целей тестирования
  protected function loadSourcePhotoFromUrl($_path)
  {
    $this->loadSourcePhoto[] = $_path;
    return $this->getOption('loadSourcePhotoFromUrl');
  }

  // Метод полностью перекрыт для целей тестирования
  protected function savePhoto($_filePath, $_photo)
  {
    $this->savePhoto[] = $_filePath;
    return $this->getOption('savePhoto');
  }

  public function getUrlPattern()
  {
    return parent::getUrlPattern();
  }

  public function getRequestParams($_matches)
  {
    return parent::getRequestParams($_matches);
  }
}
