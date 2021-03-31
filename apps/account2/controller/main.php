<?php
/**
 * User Controller
 *
 * @author Serhii Shkrabak
 * @global object $CORE
 * @package Controller\Main
 */
namespace Controller;
class Main
{
	use \Library\Shared;

	private $model;

	public function exec():?array {
		$result = null;
		$url = $this->getVar('REQUEST_URI', 'e');

		//path получает массив из пути url и разделяет в массив
		//od.ua/form/submitAmbassador => path ['form', 'submitAmbassador]
		$path = explode('/', $url);

		if (isset($path[2]) && !strpos($path[1], '.')) { // Disallow directory changing
			//подключаем форму запроса
			$file = ROOT . 'model/config/methods/' . $path[1] . '.php';
			if (file_exists($file)) {
				include $file;
			}
			else {
				throw new \Exception("REQUEST_UNKNOWN");
			}

			//NEW
			//подключаем паттерны для запроса
			$file = ROOT . 'model/config/patterns/patterns.php';
			if (file_exists($file)) {
				include $file;
			}
			else {
				throw new \Exception("REQUEST_UNKNOWN");
			}
			//END NEW

			if (isset($methods[$path[2]])) {
				$details = $methods[$path[2]];
				$request = [];

				foreach ($details['params'] as $param) {
					$var = $this->getVar($param['name'], $param['source']);
					
					//NEW
					//проверка присутствия запроса
					if ($param['required'] === true) {

						//проверка обязательного поля
						if (isset($var)) {

							//проверка на соответствие шаблону
							if (preg_match($patterns["{$param['name']}"], $var) == 0) {
								throw new \Exception("REQUEST_INCORRECT, {$param['name']}");
							}

							//махинации с номером телефона: приводим к одинаковому виду (+380*)
							if ($param['name'] == 'phone') {
								$var = '+380'.substr($var, strlen($var) - 9);
							}
						}
						else {
							throw new \Exception("REQUEST_INCOMPLETE, {$param['name']}");
						}

					}
					//END NEW

					//заполняем массив запроса
					if ($var) {
						$request[$param['name']] = $var;
					}
				}

				//formsubmitAmbassador - новая заявка
				if (method_exists($this->model, $path[1] . $path[2])) {
					$method = [$this->model, $path[1] . $path[2]];
					$result = $method($request);
				}
				else {
					throw new \Exception("REQUEST_UNKNOWN");
				}
			}
			else {
				throw new \Exception("REQUEST_UNKNOWN");
			}
		}
		else {
			throw new \Exception("REQUEST_UNKNOWN");
		}
		return $result;
	}

	public function __construct() {
		// CORS configuration
		$origin = $this -> getVar('HTTP_ORIGIN', 'e');
		$front = $this -> getVar('FRONT', 'e');

		foreach ( [$front] as $allowed )
			if ( $origin == "https://$allowed") {
				header( "Access-Control-Allow-Origin: $origin" );
				header( 'Access-Control-Allow-Credentials: true' );
			}
		$this->model = new \Model\Main;
	}
}