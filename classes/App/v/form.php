<form action="" method="get">
    <fieldset title="Добавить категории">
        <legend>Добавить категории</legend>
        <button class="btn" name="action" value="add_category">Добавить категории</button>
    </fieldset>
    <fieldset title="Импорт">
        <legend>Импорт</legend>
        <label>Шаг: <input type="text" name="step" value="<?=($_REQUEST["step"])?$_REQUEST["step"] + 1:0;?>" /></label>
        <button class="btn" name="action" value="import">Старт импорта</button>
    </fieldset>
    <fieldset title="Обновить от даты">
        <legend>Обновить от даты</legend>
        <label>на <input type="number" name="date-m" value="0"/> Месяцев назад.</label>
        <label>на <input type="number" name="date-d" value="0"/> Дней: назад.</label>
        <button class="btn" name="action" value="update">Обновить</button>
    </fieldset>
</form>
<br>
<a class="btn" target="_blank" href="/import/categoryes.php">Редактировать коэффициенты для цен</a>
<br>