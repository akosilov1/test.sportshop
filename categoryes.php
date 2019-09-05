<?php
/**
 * Created by PhpStorm.
 * User: Александр
 * Date: 13.03.2018
 * Time: 20:40
 */
define("BOOTSTRAP", "value");
define('DEVELOPMENT', false);
include "../config.local.php";
$db = new mysqli($config["db_host"],$config["db_user"],$config["db_password"],$config["db_name"]);
//echo "<pre>";print_r($_SERVER);
if($_SERVER["REQUEST_METHOD"] == "POST"){
    //echo"<pre>";print_r($_REQUEST["cat"]);
    if(isset($_REQUEST["cat"]) && is_array($_REQUEST["cat"])){
        $i;
        foreach ($_REQUEST["cat"] as $id=>$val){
            if($val > 1 && (int)$id > 0){
                $q = "UPDATE category SET coeff=$val WHERE id=$id";
                if(!$db->query($q))
                    echo $db->error;
                $i++;
            }
        }
        //echo "$i записи обновлены";
        header("Location: ".$_SERVER['SCRIPT_NAME']."?rez=OK&count=$i");
    }
}?>
<script src="https://yastatic.net/jquery/3.1.1/jquery.min.js"></script>
<style>
#categories{
border-collapse:collapse;
}
#categories td, #categories th{
padding:5px; 
border: 1px solid #333;
}
#categories td:first-of-type{
	cursor: pointer;
}
.clear-filter{
	cursor: pointer;
}

</style>
<?if($_REQUEST["rez"]=="OK") echo "<h3>".$_REQUEST["count"]." записей обновлены</h3>";
?><h1>Коэффициенты для цен</h1>
    <a href="/import/soap.php"><< Назад</a><br><br>
    <p>Для фильтра по родителю, щелкни по его ID </p>
    <p>Для сброса фильтра, щелкни сюда >> <span class="clear-filter">[Сброс фильтра]</span></p>
    <p>Для сохранения нажми ENTER</p>
    <form action="" method="post">
        <button>Отправить</button>
    <table id="categories" >
    	<thead>
        <tr>
            <th>ID прайс</th>
            <th>ID маг.</th>
            <th>Название</th>
            <th>Родитель</th>
            <th>Коэффициент</th>
        </tr>
        </thead>
        <tbody>
<?
$res = $db->query("SELECT * FROM category  ORDER BY parent, cat_id");
while($rez = $res->fetch_assoc()){
    ?>

        <tr data-id="<?=$rez["cat_id"]?>" data-parent="<?=$rez["parent"]?>">
            <td><?=$rez["cat_id"]?></td>
            <td><?=$rez["add_id"]?></td>
            <td><?=$rez["name"]?></td>
            <td><?=$rez["parent"]?></td>
            <td><input type="text" name="cat[<?=$rez["id"]?>]" value="<?=$rez["coeff"]?>"/></td>
        </tr>

<?}?>
</tbody>
</table>
        <button>Отправить</button>
    </form>
    <script>
    	$("#categories tbody tr td:first-of-type").on("click", function(e){
    		var parent = $(this).parent().data("id");
    		$("#categories tbody tr").hide(600);
    		$("#categories tbody tr[data-parent="+parent+"]").show(600);
    	});
    	$(".clear-filter").on("click", function(e){
    		$("#categories tbody tr").show();
    	});
    </script>
<?