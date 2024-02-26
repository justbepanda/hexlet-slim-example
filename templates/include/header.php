<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<div style="margin: 20px auto; max-width: 300px">
    <a href="/users">Пользователи</a>
    <a href="/users/new">Добавить пользователя</a>
    <div style="color:red; font-weight: bold">
        <?php
        if (isset($flash)) {
            foreach ($flash as $result):
                foreach ($result as $message):
                    echo $message;
                endforeach;
            endforeach;
        }


        ?>
    </div>
        