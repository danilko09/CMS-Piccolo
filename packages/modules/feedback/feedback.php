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
			
			$config = self::loadConfig();
			
			//Проверяем форму и при необходимости добавляем в БД
			self::validate($message, filter_input_array(INPUT_POST), $config);
			
			$content = PICCOLO_ENGINE::getRTmpl('feedback/form',array('message'=>$message));
			
			return array(
				'title'=>$config['title'],
				'content'=>$content
			);
			
		}
		
		private static function validate(&$msg,$inp,$cfg){
			if(!isset($inp['feedback_message_hidden'])){
				return;//Если не была отправлена пометка формы - отклоняем дальнейшую обработку
			}
			//Подгружаем список обязательных полей
			$must_have = $cfg['must_have'];
			//Проверяем по списку
			foreach($must_have as $field=>$title){
				if(!isset($inp[$field]) || $inp[$field] == null){
					$msg .= PICCOLO_ENGINE::getRTmpl('feedback/form_must_have_message',array('title'=>$title));
				}
			}
			if($msg === ''){
				$uid = self::addMessage($inp);
				$msg = PICCOLO_ENGINE::getRTmpl('feedback/form_send_message',array('uid'=>$uid));
			}else{
				$msg = PICCOLO_ENGINE::getRTmpl('feedback/form_must_have_frame',array('messages'=>$msg));
			}
		}
		
		//Роутер для админки
		public static function bindAdminPage($b,$p){
			if(!isset($p[0])){
				$p[0] = '';
			}
			switch($p[0]){
				case 'settings': return self::settings($b.'/'.array_shift($p));
				case 'msg_list': return self::msg_list($b.'/'.array_shift($p),$p);
				default: return self::index($b);
			}
		}
		
		private static function settings(){
			$config = self::loadConfig();
			$title_msg = '';$add_msg = '';
			if(filter_input(INPUT_POST,'feedback_settings_title') !== null){
				$config['title'] = filter_input(INPUT_POST,'title');
				$title_msg = PICCOLO_ENGINE::getRTmpl('feedback/admin/message',array('message'=>'Название было изменено'));
				PICCOLO_ENGINE::updateConfig('feedback',$config);
			}
			if(filter_input(INPUT_POST,'feedback_settings_list-add') !== null && filter_input(INPUT_POST,'add_name') != ''){
				$config['must_have'][filter_input(INPUT_POST,'add_name')] = filter_input(INPUT_POST,'add_title');
				$add_msg = PICCOLO_ENGINE::getRTmpl('feedback/admin/message',array('message'=>'Обязательный элемент добавлен'));
				PICCOLO_ENGINE::updateConfig('feedback',$config);
			}
			
			if(filter_input(INPUT_GET,'remove') !== null){
				unset($config['must_have'][filter_input(INPUT_GET,'remove')]);
				$add_msg = PICCOLO_ENGINE::getRTmpl('feedback/admin/message',array('message'=>'Обязательный элемент удалён'));
				PICCOLO_ENGINE::updateConfig('feedback',$config);
			}
			
			$fields = PICCOLO_ENGINE::getMTmpl('feedback/admin/settings_list_element', $config['must_have'], array('title'=>''), 'name', 'title');
			return PICCOLO_ENGINE::getRTmpl('feedback/admin/settings_page',array('title'=>$config['title'],'fields'=>$fields,'title_msg'=>$title_msg,'add_msg'=>$add_msg));
		}
		
		private static function index($b){
			return PICCOLO_ENGINE::getRTmpl('feedback/admin/menu',array('base'=>PICCOLO_ENGINE::getIndex().$b));
		}
		
		private static function msg_list($b,$p){
			
			//Загрузка настроек "pagination"
			$config = self::loadConfig();

			$on_page = $config['on_page'];
			
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
		
		private static function loadConfig(){
			
			$config = PICCOLO_ENGINE::loadConfig('feedback');
			
			if(!isset($config['title'])){
				$config['title'] = 'Feedback';
			}
			
			if(!isset($config['on_page'])){
				$config['on_page'] = 10;
			}

			if(!isset($config['must_have'])){
				$config['must_have'] = array('name'=>'Имя','email'=>'E-mail','message'=>'Сообщение');
			}
			
			return $config;
			
		}
		
	}