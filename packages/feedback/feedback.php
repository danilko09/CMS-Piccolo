<?php


	/**
	 * Скрипт "обратная связь".
	 * Включает в себя и пользовательскую и административную часть.
	 */
	class feedback {
		
		/**
		 * Входная точка пользовательской части
		 * @param string $b база страницы
		 * @param array $p параметры, переданные в URL
		 */
		public static function bindPage($b,$p){
			
			$message = '';//переменная для сообщения пользователю
			
			//Проверяем отправку формы, если отправлена - записываем в БД и выдаем сообщение
//			if(filter_input(INPUT_POST,'feedback_message_hidden') !== null){
//				$uid = self::addMessage(filter_input_array(INPUT_POST));
//				$message = PICCOLO_ENGINE::getRTmpl('feedback/form_send_message',array('uid'=>$uid));
//			}
			
			$config = PICCOLO_ENGINE::loadConfig('feedback');
			
			//Проверяем форму и при необходимости добавляем в БД
			self::validate($message, filter_input_array(INPUT_POST), $config);
			
			$title = isset($config['title']) ? $config['title'] : 'Feedback';
			
			$content = PICCOLO_ENGINE::getRTmpl('feedback/form',array('message'=>$message));
			
			return array(
				'title'=>$title,
				'content'=>$content
			);
			
		}
		
		private static function validate(&$msg,$inp,$cfg){
			if(!isset($inp['feedback_message_hidden'])){
				return;//Если не была отправлена пометка формы - отклоняем дальнейшую обработку
			}
			//Подгружаем список обязательных полей
			$must_have = isset($cfg['must_have']) ? $cfg['must_have'] : array('name'=>'Имя','email'=>'E-mail','message'=>'Сообщение');
			//Проверяем по списку
			foreach($must_have as $field=>$title){
				if(!isset($inp[$field]) || $inp[$field] == null){
					$msg .= PICCOLO_ENGINE::getRTmpl('feedback/form_must_have_message',array('title'=>$title));
				}
			}
			if($msg === ''){
				$uid = self::addMessage($inp);
				$msg = PICCOLO_ENGINE::getRTmpl('feedback/form_send_message',array('uid'=>$uid));
			}
		}
		
		public static function bindAdminPage($b,$p){
			
			//Загрузка настроек "pagination"
			$config = PICCOLO_ENGINE::loadConfig('feedback');

			$on_page = isset($config['on_page']) ? $config['on_page'] : 10;
			
			//Определение страницы
			$index = PICCOLO_ENGINE::loadData('feedback/index');
			
			$messages_count = count($index);
			
			$pages_count = ceil($messages_count/$on_page);
			
			$page = (count($p) >= 1) && (intval($p[0]) <= $pages_count) ? $p[0] : 1;
			
			//Выборка сообщений
			$messages = array_slice($index, ($page-1)*$on_page, $on_page);
			
			$messages_html = '';
			
			foreach($messages as $uid){
				$element = self::htmlspecarray(PICCOLO_ENGINE::loadData('feedback/'.$uid));
				$messages_html .= PICCOLO_ENGINE::getRTmpl('feedback/admin/list_page_element',$element + array('uid'=>$uid));
			}

			if($messages_html === ''){
				$messages_html = PICCOLO_ENGINE::translate('NO_POSTED_MESSAGES','feedback');
			}
			
			return PICCOLO_ENGINE::getRTmpl('feedback/admin/list_page_frame',array('elements'=>$messages_html,'page'=>$page,'pages_count'=>$pages_count,'messages_count'=>$messages_count,'base'=>$b));
			
		}
		
		/**
		 * Добавляет сообщение в БД
		 * @param type $fields поля переданные через форму
		 * @return string Возвращет уникальный идентификатор сообщения в базе данных
		 */
		private static function addMessage($fields){
			
			//Генерируем уникальный id для сообщения
			$uid = uniqid('', true);
			
			while(PICCOLO_ENGINE::isData('feedback/'.$uid)){
				$uid = uniqid('', true);
			}
			
			//сохраняем сообщение в файл
			PICCOLO_ENGINE::updateData('feedback/'.$uid,$fields);
			
			//Добавляем информацию о файле в реестр
			$index_data = PICCOLO_ENGINE::loadData('feedback/index');//загрузка реестра
			
			$index = is_array($index_data) ? $index_data : array();//если у нас тут случайно оказался не массив - создаем массив
			
			array_unshift($index, $uid);//вставка в начало массива
			
			PICCOLO_ENGINE::updateData('feedback/index',$index);//перезапись реестра

			return $uid;
			
		}
		
		private static function htmlspecarray($array){
			foreach($array as $id => $data){
				if(is_array($data)){
					$array[$id] = self::htmlspecarray($data);
				}else{
					$array[$id] = htmlspecialchars($data);
				}
			}
			return $array;
		}
		
	}