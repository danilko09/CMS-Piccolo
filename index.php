<?php

if(file_exists(__DIR__.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'index.php')){
	include __DIR__.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'index.php';
} else {
	?>
        <a href="utils/depChecker.php">Проверка зависимостей пакетов</a><br/>
	<a href="utils/testRunner.php">Запуск тестов</a></br>
        <a href="cleanup.php">Удалить временные файлы</a></br>
	<?php
}