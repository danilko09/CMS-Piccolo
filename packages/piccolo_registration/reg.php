<?php


    final class piccolo_registration {
        
        public static function showRegForm(){
            $msg = '';
            if(filter_input(INPUT_POST,'piccolo_registration_secret') !== null){$msg = self::regAction();}//Если отправлена форма, то обработаем её
        
            return PICCOLO_ENGINE::getRTmpl('registration',array(//выдача формы
                'msg'=>$msg,
                'login'=>filter_input(INPUT_POST,'piccolo_registration_login') !== null ? filter_input(INPUT_POST,'piccolo_registration_login') : '',
                'password'=>filter_input(INPUT_POST,'piccolo_registration_password') !== null ? filter_input(INPUT_POST,'piccolo_registration_password') : '',
                'email'=>filter_input(INPUT_POST,'piccolo_registration_email') !== null ? filter_input(INPUT_POST,'piccolo_registration_email') : ''
                ));
        }
        
        private static function regAction(){
            if(filter_input(INPUT_POST,'piccolo_registration_secret') === null){return;}
            if(filter_input(INPUT_POST,'piccolo_registration_login') === null){return 'Вы не ввели логин!';}
            if(filter_input(INPUT_POST,'piccolo_registration_password') === null){return 'Вы не ввели пароль!';}
            if(filter_input(INPUT_POST,'piccolo_registration_email') === null){return 'Вы не ввели E-mail!';}
            if(users_base::getIdByField('login',filter_input(INPUT_POST,'piccolo_registration_login')) !== null){return 'Пользователь с таким логином уже зарегистрирован.';}
            if(users_base::getIdByField('E-mail',filter_input(INPUT_POST,'piccolo_registration_email')) !== null){return 'Пользователь с таким E-mail уже зарегистрирован.';}
            users_base::register(array(
                'login'=>filter_input(INPUT_POST,'piccolo_registration_login'),
                'password'=>filter_input(INPUT_POST,'piccolo_registration_password'),
                'E-mail'=>filter_input(INPUT_POST,'piccolo_registration_email'
                        )));
            return 'Регистрация успешно пройдена!';
        }
        
    }