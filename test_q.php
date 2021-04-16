<?php

/**
 * TODO Не работает если скобок нет. Нужно сделать проверку
 * Далее при обходе строки необходимо создать массив $layer_check = array() для подсчета слов в уровне
 * Будет выглядеть примерно так:
 * if ($word != '') {
 *      $layer_check[$layer] += 1;
 * Далее нужно обойти данный массив и проверить, есть ли в нем ключи (обозначения уровней 1,2 и тд), у которых значение < 3. Если есть, завершить скрипт с ошибкой.
 


$char = $_POST['string'];

#Проверка на количество открывающих и закрывающих скобок

$count_chars =count_chars($char . '[](){}',1);
if (($count_chars['40'] != $count_chars['41']) OR ($count_chars['123'] != $count_chars['125']) OR ($count_chars['91'] != $count_chars['93'])) {
    exit ('Проблема со скобками');
}

#Проверка на вложенность


$open_now = array();
for ($i = 0; $i < strlen($char); $i++) {
    if (stristr('{[(', $char[$i])) {
        switch (ord($char[$i])) {
            case (40):
                $open_now[] = ')';
                break;
            case (123):
                $open_now[] = '}';
                break;
            case (91):
                $open_now[] = ']';
                break;
        }
    } elseif (stristr(']})', $char[$i])) {
        if ($char[$i] != $open_now[count($open_now)-1]) {
            exit('bad char');
        } else {
            array_pop($open_now);
        }
    }
}
$layer = 0;
$i = 0;
# Обход всей строки
while ($i < strlen($char)) {
    $word = '';

    while (!stristr(' ,.[{()}]?!', $char[$i]) AND $i < strlen($char)-1) { #Выделение отдельных слов
        $word .= $char[$i];
        $i++;
    }

    if (stristr('{[(', $char[$i])) {
        $layer++;
    } elseif (stristr(')}]', $char[$i])) {
        $layer--;
    }
    $i++;
    try {
        $db_pdo = new PDO('mysql:dbname=char_db;host=172.16.0.1;port=34112', $user, $password);
    } catch (PDOException $e) {
        die('Ошибка подключения к char_db: ' . $e->getMessage());
    }
    #Запись слова в БД, если слово не пустая строка
        try {
            $db_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db_pdo->beginTransaction();
            $id_count = $db_pdo->prepare("SELECT count(id), char_count FROM chars_db WHERE layer = {$layer} AND word = {$word}");
            $id_count->execute();
            $arr_id_count = $id_count->fetchAll();
            if ($arr_id_count[0][0] != 0 and $word != '') {
                $arr_id_count[1][0]++;
                $db_pdo->exec("UPDATE chars_db SET count_char = {$arr_id_count[1][0]} WHERE layer = {$layer} AND word = {$word}");
            } elseif ($word != '') {
                $db_pdo->exec("INSERT INTO chars_db (word, layer, count) VALUES ({$word}, {$layer}, 1)");
            }
            $db_pdo->commit();
        } catch (Exception $e) {
            $db_pdo->rollBack();
            die($e->getMessage());
        }
    }
}

