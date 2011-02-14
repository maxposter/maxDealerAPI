<?php
/**
 * Исключения, используемые при обработке запросов к Интернет-сервису MaxPoster
 *
 * @author Александр Воробьев avorobiev@maxposter.ru
 * @version 1.0
 * @package maxposer_dealer_api
 */

class maxException extends Exception
{
  // Коды ошибок
  const
    ERR_404 = 404,
    ERR_WRONG_REQUEST_THEME = 1010,
    ERR_DOES_NOT_SET_REQUEST_THEME = 1020,
    ERR_DOES_NOT_SET_DEALERID = 1030,
    ERR_DOES_NOT_SET_MIRROR = 1040,
    ERR_DOES_NOT_SET_RESPONSE_THEME = 1050,
    ERR_NO_RESPONSE = 1060,
    ERR_DOES_NOT_SET_REQUIRED_OPTION = 1070,
    ERR_OTHER = 1100
  ;

  // Сообщения об ошибках
  static public
    $err_messages = array(
      self::ERR_404 => 'Запрашиваемые данные не существуют.',
      self::ERR_WRONG_REQUEST_THEME => 'Интернет-сервис не поддерживает запрос темы "%s".',
      self::ERR_DOES_NOT_SET_REQUEST_THEME => 'Не задана тема для запроса к Интернет-сервису.',
      self::ERR_DOES_NOT_SET_DEALERID => 'Не задан код автосалона.',
      self::ERR_DOES_NOT_SET_MIRROR => 'Не задан путь до зеркалa "%s" Интернет-Сервиса.',
      self::ERR_DOES_NOT_SET_RESPONSE_THEME => 'Не удалось определить тему ответа.',
      self::ERR_NO_RESPONSE => 'Интернет-сервис не отвечает. Выполните запрос через некоторое время.',
      self::ERR_DOES_NOT_SET_REQUIRED_OPTION => 'Не задан обязательный параметр "%s".',
      self::ERR_OTHER  => 'Произошел сбой в работе.'
    )
  ;

  /**
   * Генерация исключения
   *
   * @param int $_code      Код исключения (см. константы класса)
   * @param mixed $_param   Параметры сообщения об исключении
   * @return maxException   Исключение
   */
  static public function getException($_code, $_param = null)
  {
    return new maxException(
      sprintf(
        isset(self::$err_messages[$_code]) ? self::$err_messages[$_code] : self::$err_messages[self::ERR_OTHER],
        $_param
      ),
      $_code
    );
  }
}