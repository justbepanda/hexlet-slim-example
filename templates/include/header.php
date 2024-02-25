<div style="margin: 20px auto; max-width: 300px">
<a href="/users">Пользователи</a>
<a href="/users/new">Добавить пользователя</a>
    <div style="color:red; font-weight: bold">
<?php 
foreach ($flash as $result):
    foreach ($result as $message):
        echo $message;
    endforeach;
endforeach;

?>
        </div>
        