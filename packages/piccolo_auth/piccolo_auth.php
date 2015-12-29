<?php
	
	final class piccolo_auth {
		
		public static function bindAdminPage(){
			self::logout();
			return self::showForm();
			
		}
		
		private static $last_err = null;
		private static $last_answer = null;
		
		/*
		 * Возвращает форму для авторизации.
		 * Попутно проводит попытку авторизации.
		 */
		
		public static function showForm(){
			$msg = "";
			if(filter_input(INPUT_POST, 'piccolo_auth_secret') != null && !self::checkInput()){
				$msg = self::$last_err;
			}
            //$captcha = self::getCaptcha();
            return self::isAuthorised() ? WSE_ENGINE::getRTmpl("piccolo_auth/form", /*array('captcha' => $captcha*/ array('message'=>$msg))
                        : WSE_ENGINE::translate('ALREADY_LOGGED_IN', 'piccolo_auth');
		}
		
		public static function isAuthorised(){
			return !isset($_SESSION['piccolo_auth']['status']) || $_SESSION['piccolo_auth']['status'] !== 'SUCCESS';
		}
		
		/*
		 * Позволяет заранее проверить удалась ли авторизация по форме.
		 */
		
		public static function checkInput(){
			
			if(filter_input(INPUT_POST,'handler') == null){
				self::$last_err = PICCOLO_ENGINE::translate('NO_HANDLER','piccolo_auth');
				return false;
			}
			
			if(filter_input(INPUT_POST,'password') == null){
				self::$last_err = PICCOLO_ENGINE::translate('NO_PASSWORD','piccolo_auth');
				return false;
			}
			
			$result = self::sendAuthRequest(filter_input(INPUT_POST,'handler'), filter_input(INPUT_POST,'password'));
			$_SESSION['piccolo_auth'] = self::$last_answer;
					
			return $result;
			
		}

		/*
		 * Удаляет данные об авторизации из сессии.
		 */
		
		public static function logout(){
			unset($_SESSION['piccolo_auth']);			
		}
		
		/*
		 * Посылает запрос авторизации на сервер auth.piccolo.tk
		 */
		
		public static function sendAuthRequest($handler, $password){
			try{
				self::$last_answer = json_decode(file_get_contents('http://auth.piccolo.tk/auth/handle/'.$handler.'/password/'.$password),true);
				if(isset(self::$last_answer['status']) && self::$last_answer['status'] !== 'SUCCESS'){
					self::$last_err = PICCOLO_ENGINE::translate('ANSWER_'.self::$last_answer['status'],'piccolo_auth');
					return false;
				}elseif(isset(self::$last_answer['status']) && self::$last_answer['status'] !== 'SUCCESS'){
					self::$last_err = 'OK';
					return true;
				}elseif(!isset(self::$last_answer['status'])){
					self::$last_err = PICCOLO_ENGINE::translate('BAD_ANSWER','piccolo_auth');
					return false;
				}
			}catch(Exception $e){
				if(PICCOLO_SYSTEM_DEBUG){
					self::$last_err = $e->getTraceAsString();
				}else{
					self::$last_err = $e->getMessage();
				}
				return false;
			}
		}

		/*
		 * Возвращает последний ответ сервера auth.piccolo.tk после вызова метода sendAuthRequest.
		 */
		
		public static function getLastAnswer(){
			return self::$last_answer;
		}
		
		/*
		 * Возвращает последнюю ошибку авторизации
		 */
		
		public static function getLastError(){
			return self::$last_err;
		}
		
	}
