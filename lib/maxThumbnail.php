<?php
require_once(dirname(__FILE__).'/maxOption.php');
require_once(dirname(__FILE__).'/maxImageResize.php');

/**
 * Реализация класса для преобразования фотографий под размеры сайтов автосалонов.
 *
 * @author Александр Воробьев avorobiev@maxposter.ru
 * @version 1.0
 * @package maxposer_dealer_api
 */

class maxThumbnail extends maxOption
{
    /**
     * Добавление специфических для класса параметров
     *
     * @return array()      Параметры класса по умолчанию
     */
    protected function getDefaultOptions()
    {
        return array(
            // Шаблон адреса к источнику фото
            'source_photo_url' => 'http://www.maxposter.ru/photo/%s/%s/orig/%s',

            // Путь к каталогу для хранения фотографий
            'photo_dir' => null,

            // Подкаталог внутри photo_dir для хранения исходных фото
            'source_photo_dir' => 'source',

            // Используемые размеры фото
            'allowed_photo_sizes' => array(
            /**
             * Например:
             * '640x480'     => array('width' => 640, 'height' => 480), // Большие фото формата 640x480
             * '180x135'  => array('width' => 180, 'height' => 135),    // Средние фото формата 180x135
             * '120x90'   => array('width' => 120, 'height' =>  90)     // Маленькие фото формата 120x90
             */
            ),

            // Максимальные размеры используемых фото. Относительно них приводятся все фотографии (добавление фона)
            'maximum_photo_size' => array('width' => 640, 'height' => 480),

            // Цвет фоновой заливки, при недостаточном размере фото (R, G, B)
            'fill_color' => array(145, 145, 145),

            // Допустимые расширения файлов с фото
            'photo_extensions' => array('jpg', 'jpeg',),

            // Разделитель кодов автосалонов
            'dealer_separator' => '_'
        );
    }


    /**
     * Формирование URL для получения большой фотографии
     *
     * @param  int    $dealerId    Код автосалона
     * @param  int    $autoId      Код автообъявления
     * @param  string $imageId     Код фотографии
     * @return string URL для фотографии на сайте maxposter.ru
     */
    protected function getSourcePhotoUrl($dealerId, $autoId, $imageId)
    {
        return sprintf($this->getRequiredOption('source_photo_url'), $dealerId, $autoId, $imageId);
    }


    /**
     * Формирование пути к фотографии в локальном кэше
     *
     * @param int     $_dealerId    Код автосалона
     * @param int     $_autoId      Код автообъявления
     * @param string  $_imageId     Код фотографии
     * @param string  $_typeId      Код размера фото (допустимы значения: 'source', либо ключи из массива параметра allowed_photo_sizes)
     * @return string    Путь к фото в локальном кэше
     */
  protected function getPhotoFilePath($_dealerId, $_autoId, $_imageId, $_typeId)
  {
    return $this->getRequiredOption('photo_dir') . DIRECTORY_SEPARATOR
            . ($this->isMultyDealer()
                ? $_dealerId . DIRECTORY_SEPARATOR
                : ''
            ) . $_autoId . DIRECTORY_SEPARATOR
            . $_typeId . DIRECTORY_SEPARATOR
            . $_imageId
        ;
    }


    /**
     * Загрузка фото
     *
     * @param  string $path   Путь к фото (URL либо локальный путь)
     * @return  Фото
     */
    protected function loadSourcePhotoFromUrl($path)
    {
        return file_get_contents($path);
    }


    /**
     * Проверка наличия каталога для сохранения файла.
     * Если каталога/ов нет, то они создаются.
     *
     * @param string $dir
     */
    protected function checkDir($dir)
    {
        if (!is_dir($dir)) {
            if ($this->checkDir(dirname($dir))) {
                mkdir($dir, 0777, false);
                chmod($dir, 0777);
            }
        }

        return true;
    }


    /**
     * Сохранение фото в локальном кэше
     *
     * @param string $_filePath   Путь для сохранения
     * @param string $_photo      Фото
     * @return mixed      Результат записи фото в файл
     */
    protected function savePhoto($_filePath, $_photo)
    {
        $this->checkDir(dirname($_filePath));

        $photo = file_put_contents($_filePath, $_photo);

        chmod($_filePath, 0666);

        return $photo;
    }


    /**
     * Проверка наличия иходной фотографии в файловом кэше.
     * При отсутствии фото в кэше выполняется попытка загрузки фото с удаленного сервера.
     *
     * @param int $_dealerId        Код автосалона
     * @param int $_autoId          Код автообъявления
     * @param string  $_imageId     Код фотографии
     * @return boolean   Если фото есть в кэше - true, иначе будет выброшен maxException
     */
    protected function checkSourcePhoto($_dealerId, $_autoId, $_imageId)
    {
        $filePath = $this->getPhotoFilePath($_dealerId, $_autoId, $_imageId, $this->getRequiredOption('source_photo_dir'));

        if (!file_exists($filePath)) {
            if (($photo = $this->loadSourcePhotoFromUrl($this->getSourcePhotoUrl($_dealerId, $_autoId, $_imageId)))) {
                if (!$this->savePhoto($filePath, $photo)) {
                    throw new maxException('File save error '.$filePath, maxException::ERR_OTHER);
                }
            } else {
              throw new maxException('Photo file '.$this->getSourcePhotoUrl($_dealerId, $_autoId, $_imageId).' does not exist.', maxException::ERR_OTHER);
            }
        }

        return true;
    }

    protected function show404page()
    {
        header("HTTP/1.0 404 Not Found");
        die('<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL ' . htmlspecialchars($_SERVER['REQUEST_URI']) . ' was not found on this server.</p>
</body></html>');
    }


    /**
     * Проверка на атвосалоном с несколькими учетными записями в системе
     *
     * @return boolean True если у автосалона несколько учетных записей
     */
    public function isMultyDealer()
    {
        return false !== strpos($this->getRequiredOption('dealer_id'), $this->getOption('dealer_separator'));
    }


    /**
     * Получение шаблона для парсинга URL
     *
     * @return string Регулярное выражение
     */
    protected function getUrlPattern()
    {
        $urlPattern = '\/([0-9]*)\/('.implode('|', array_merge(array_keys($this->getRequiredOption('allowed_photo_sizes'))
        , array($this->getRequiredOption('source_photo_dir')))).')\/([0-9a-z]*.('.implode('|', $this->getRequiredOption('photo_extensions')).'))$';

        // Проверка на несколько автосалонов (тогда надо код автосалона получать из URL)
        if($this->isMultyDealer()) {
            $urlPattern = '\/('.str_replace($this->getOption('dealer_separator'), '|', $this->getRequiredOption('dealer_id')).')' . $urlPattern;
        }

        return $urlPattern;
    }


    protected function getRequestParams($_matches)
    {
        if ($this->isMultyDealer()) {
            // Код автосалона должен браться из URL запроса
            $params = array(
            $_matches[1][0],   // код автосалона
            $_matches[2][0],   // код объявления
            $_matches[3][0],   // размер изображения
            $_matches[4][0],   // название файла с расширением
            );
        } else {
          // Код автосалона должен браться из параметров
          $params = array(
            $this->getRequiredOption('dealer_id'),    // код автосалона
            $_matches[1][0],                          // код объявления
            $_matches[2][0],                          // размер изображения
            $_matches[3][0],                          // название файла с расширением
            );
        }
        return $params;
    }


    public function getPhoto($_url)
    {
        try {
            if (preg_match_all('/'.$this->getUrlPattern().'/', $_url, $matches)) {
                list($dealerId, $autoId, $sizeId, $fileName) = $this->getRequestParams($matches);

                if ($this->checkSourcePhoto($dealerId, $autoId, $fileName)) {
                    if ($sizeId != $this->getRequiredOption('source_photo_dir')) {
                        $sizes = $this->getRequiredOption('allowed_photo_sizes');
                        $size = $sizes[$sizeId];
                        $maxSize = $this->getRequiredOption('maximum_photo_size');
                        $imageResize = new maxImageResize($maxSize['width'], $maxSize['height']);
                        $imageResize->loadFile($this->getPhotoFilePath($dealerId, $autoId, $fileName, $this->getRequiredOption('source_photo_dir')));
                        $thumbPath = $this->getPhotoFilePath($dealerId, $autoId, $fileName, $sizeId);
                        $this->checkDir(dirname($thumbPath));
                        $imageResize->resize($size['width'], $size['height']);
                        $imageResize->save($thumbPath);
                        chmod($thumbPath, 0666);

                        header('Content-type: '.$imageResize->getMime());
                        echo $imageResize->toString();
                    } else {
                        header('Content-type: image/jpeg');
                        echo file_get_contents($this->getPhotoFilePath($dealerId, $autoId, $fileName, $this->getRequiredOption('source_photo_dir')));
                    }
                }
            } else {
                throw new maxException('Wrong request!', maxException::ERR_OTHER);
            }
        // При возникновении любого сбоя возвращаем посетителю 404 страницу
        } catch (Exception $e) {
            $this->show404page();
        }
    }

}
