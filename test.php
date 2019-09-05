<?
require_once __DIR__.'/config.php';
/*$_GET = array(
    'date-m'=>0,
    'date-d'=>1,
    'action'=>'update',
    'cron'=>'y'
);
include $_SERVER["DOCUMENT_ROOT"]."/import/soap.php";
die();*/
$t = microtime(true);

use \Product\Options;

include __DIR__.'/classes/soap.php';
include __DIR__.'/classes/sport.php';
include __DIR__.'/classes/functions.php';
$s = new Soap();
$mag = new Sport($seo_settings);
$prods = $s->client->getProduct(6152/*8885 78584*/);
pre_print($prods);
die("END");
echo "<pre>";
Functions::init($config);
Functions::AddProduct($prods);
die("END");
$i = 0;
while($err = \Product\Images::CheckImages($i)){
    pre_print($err);
    if($i > 200000) break;
    $i += 1000;
};
die('END');
$p = new \Product\Product();
$prod = $p->get("УТ-00007701");
pre_print($prod);
pre_print($prod[0]->GetFilePath());
pre_print($prod[0]->GetImages());
//pre_print(\Product\Features::Get($prod[0]->product_id));
//pre_print($prod->GetFilePath()) ;
die();
$pdo = new PDO("mysql:host=".$config["db_host"].";dbname=".$config["db_name"].";charset=UTF8",$config["db_user"],$config["db_password"]);
$r = $pdo->prepare("SELECT *
FROM `bishop_images_links` JOIN bishop_images ON detailed_id = bishop_images.image_id WHERE object_type='product'
LIMIT 50");//bishop_images
$r->execute();
pre_print($r->fetchAll(PDO::FETCH_ASSOC));
die("STOP");
spl_autoload_register(function ($class_name) {
    echo $class_name;
    include $_SERVER["DOCUMENT_ROOT"]."/import/classes/".str_replace("\\","/",$class_name) . '.php';
});
$prod = new \Product\Product();
$prod_ = $prod->get("УТ-00007216");
pre_print($prod_);
$prod_->price = new \Product\Prices\Price();
$prod_->price = $prod_->price->get($prod_->product_id);
pre_print($prod_);
die("STOP");
$pdo = new PDO("mysql:host=".$config["db_host"].";dbname=".$config["db_name"].";charset=UTF8",$config["db_user"],$config["db_password"]);
$q = "SELECT * FROM bishop_products LEFT JOIN bishop_product_prices as pp ON pp.product_id = bishop_products.product_id WHERE product_code='УТ-00007216'";
$r = $pdo->query($q)->fetchAll(PDO::FETCH_ASSOC);
pre_print($r);//bishop_product_prices
die("STOP");
require $_SERVER["DOCUMENT_ROOT"]."/import/classes/functions.php";
require $_SERVER["DOCUMENT_ROOT"]."/import/classes/sport.php";
require $_SERVER["DOCUMENT_ROOT"]."/import/classes/soap.php";

$seo_settings = array(
    "CAT_TITLE" => "((NAME)) оптом  с доставкой, низкая цена в интернет-магазине СпортШоп24",
    "CAT_DESCRIPTION" => "((NAME)) оптом  с доставкой по низкой цене в интернет-магазине СпортШоп24. Индивидуальные скидки. Доставка по России.",
    "CAT_KEYWORDS" => "((NAME)) оптом",
    "PROD_TITLE" => "((NAME)) оптом  с доставкой, низкая цена в интернет-магазине СпортШоп24",
    "PROD_DESCRIPTION" => "((NAME)) оптом  с доставкой по низкой цене в интернет-магазине СпортШоп24. Индивидуальные скидки. Доставка по России. ",
    "PROD_KEYWORDS" => "((NAME)) оптом",
);
$price_type = ["Золото","Серебро","Бронза","Старт","РРЦ","Прайм"];
define('PRICE', 3);




$mag = new Sport(array());

$_REQUEST["step"] = 1;
$source = new Soap;

Functions::init($config);
/*
pre_print(Functions::GetFeaturesFromName("Цвет"));
pre_print(Functions::GetFeatureVariantFromName(7,"черный/желтый"));
*/

/*$op = $s->get_options(27119);

pre_print($op);

pre_print(Functions::GetOptionsVariantIdFromName(3580,"XS"));
echo "TIME ".(microtime(true)-$t);*/




//$prods = $source->getProductIdAllByChanged(0,1)->productId;//"УТ-00007216" id=78584
$prods = $source->client->getProduct(78584);
pre_print($prods);
$p = $mag->GetProduct("pcode=УТ-00007216");
pre_print($p);

//Functions::UpdateProduct($prods);



$i = 1;
/*foreach ($prods as $id) {
	echo $id."<br>";
	pre_print($source->getProduct($id));
	if($i > 5) break;# code...
	$i++;
}*/


die("STOP");



$ddd = explode("-", date("d-m-y",time()));
echo "<pre>";
print_r($ddd);
    die("STOP");
define("BOOTSTRAP", "value");
include "../config.local.php";
$db = new mysqli($config["db_host"],$config["db_user"],$config["db_password"],$config["db_name"]);
$ar_rez = array();

    $res = $db->query("SELECT
        category_id,
        `level`,
        product_count,
        parent_id 
    FROM
        bishop_categories AS c 
    ORDER BY `level` ASC
    ");
    while ($rez = $res->fetch_assoc()){
        //echo $rez['product_count']." | ".$rez['category_id']."<br>";
        $ar_rez[$rez["level"]][$rez["parent_id"]][]= $rez;
        //getCat($rez['category_id'],1);
    }


echo "<pre>";
print_r($ar_rez);

function getCat($id,$i){
    global $db, $ar_rez;
    $res = $db->query("SELECT product_count, category_id, parent_id FROM bishop_categories WHERE category_id = $id");
    while ($rez = $res->fetch_assoc()){
        if($i>0) echo $i.str_repeat(">",$i);
        echo "C::$id | ".$rez['category_id']." | ".$rez['product_count']."<br>";
        if($rez['product_count'] == 0){
            if($c_id = getParent($rez['category_id'],$rez['parent_id'], ++$i)){
                echo "$i > CID:: ".$c_id["cat_id"]."<br>";
                $ar_rez[] = $c_id;
            }
        }
        //return $rez['product_count'];
    }
    return false;
}
function getParent($id,$parent, $i=0){
    global $db;
    $res = $db->query("SELECT product_count, category_id, parent_id FROM bishop_categories WHERE parent_id = $id");
    if($res->num_rows == 0) return array("cat_id"=>$id,"parent"=>$parent);
    while ($rez = $res->fetch_assoc()){
        if($i>0) echo $i.str_repeat(">",$i);
        echo "P::$id | ".$rez['category_id']." | ".$rez['product_count']."<br>";
        if($rez['product_count'] == 0){
            getCat($rez['category_id'], ++$i);
        }
        //return $rez['product_count'];
    }
    return false;
}
function getProduct($cat_id){

}
exit();
echo "DR:: ".$_SERVER["DOCUMENT_ROOT"]."<br>";
$f = fopen("test.txt","w");
fwrite($f,"111\n");
fwrite($f,"222\n");
fwrite($f,"333\n");
fclose($f);

$t=new Test();
$t2=new Test2();
echo $t->func(4);
echo $t2->func2(4);
class Test{
    function func($num){
        return $num * 2;
    }
}
class Test2 extends Test{
    function func2($num){
        $num++;
        return $this->func($num);
    }
}
exit();
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Cache-Control: post-check=0,pre-check=0", false);
header("Cache-Control: max-age=0", false);
header("Pragma: no-cache");
?>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <style>
        #rez{background-color: red;};
    </style>
</head>
<body>
<div id="rez"></div>
<iframe src="/import/index.php" frameborder="0"></iframe><br>
<button id="next"  data-link="/import/index.php?step=1&clear=1">Продолжить</button>
<button id="stop" >Стоп</button>
<?php


/**
 * Created by PhpStorm.
 * User: Александр
 * Date: 31.01.2018
 * Time: 19:07
 */
/*ignore_user_abort(true);
set_time_limit(0);
start(126);
function start($step){
    ob_start();
    $ch = curl_init("http://yml.loc");

    $opt = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => array("step"=>$step,"clear"=>1),
    );
    curl_setopt_array($ch,$opt);
    $rez = stripslashes(curl_exec($ch));
    //echo $rez;
    //$rez = preg_replace("#\s+#um","",$rez);
    //echo $rez."<br>";
    echo "<pre>";
    $ar_rez = json_decode($rez, true);
    print_r($ar_rez);
    if($ar_rez["STATUS"] == "STOP" && (int)$ar_rez["STEP"] > 0){
        ob_end_flush();
        //start((int)$ar_rez["STEP"]);
    }
    echo json_last_error_msg();
    echo curl_error($ch);
    //print_r(curl_getinfo($ch));

    curl_close($ch);
}*/

/*$text = "Турник распорный сделан из прочной стали с порошковым напылением, устойчивым к истиранию.
 
Занимает мало места и универсален в использовании. Усиленный крепежный механизм на три точки крепления значительно повышает надежность турника. Принадлежности для крепежа в комплекте.
Предназначен для спортивных занятий и тренировок, на нем можно подтягиваться узким, широким, обратным и параллельным хватами. 
Характеристики:
Тип: распорный
Материал: сталь
Диаметр, мм: 28
Ширина, см: 76-84
Максимальная нагрузка, кг: 120
Окраска: порошковая ударопрочная
Производство: Россия
Дополнительно: имеются в комплекте принадлежности для крепежа 

 ";*/
/*
$text="Утяжелители ЛЮКС (пара) - это утяжелители специально предназначенные для занятий фитнесом, благодаря мягкому на ощупь материалу он не натирает и не создает дискомфорта при использовании.
Также используются во время тренировок по гимнастике для увеличения силы, выносливости и техничности спортсменок при отработке различных элементов.
Основные характеристики:
Тип: для рук
Вес, кг: 0,3
Материал: трикотаж
Наполнитель: свинцовая дробь
Застежка: липучка с кольцом
Цвет: в ассортименте
Дополнительные характеристики:
Производство: Россия";
echo "<pre>";



print_r(getFeaturesFromDescription($text));
function getFeaturesFromDescription($text){
    $rez = $text_rez = array();
    if($poz = strpos($text,"Характеристики:")){
        $text_rez[] = substr($text,$poz);
        $desc = substr($text,0, $poz);
    }elseif ($poz_osn = strpos($text,"Основные характеристики:")){
        $poz_dop = strpos($text,"Дополнительные характеристики:");
        $desc = substr($text,0, $poz_osn);
        if($poz_osn && $poz_dop) {
            $text_rez[] = substr($text,$poz_osn,($poz_dop - $poz_osn));
            $text_rez[] = substr($text,$poz_dop);
        }elseif($poz_osn) $text_rez[] = substr($text,$poz_osn);
        elseif($poz_dop) $text_rez[] = substr($text,$poz_dop);
    }
    //print_r($text_rez);
//
    foreach ($text_rez as $ttt){
        preg_match_all("#^.*$#m",$ttt,$rrr);
        foreach ($rrr[0] as $k => $str){
            if($k == 0 || trim($str) == "") continue;
            $ar = explode(":",$str);
            $rez["features"][$ar[0]] = $ar[1];
        }
    }
    $rez["description"] = $desc;
    return $rez;
}
echo "</pre>";*/?>
<script type="text/javascript">
    function getCounter() {
        $.get("/import/counter.txt",function (data) {
            var rez = JSON.parse(data);
            console.log(rez);
            var p = rez.PERCENT;
            if (rez.STATUS == "WORCK"){
                $("#rez").html(rez.STEP).css("width", p + "%");
            }
            else if (rez.STATUS == "STOP"){
                console.log("stop");
                $("#rez").html("END STEP=" + rez.STEP).css("width", "100%");
                $("#next").data("link", "/import/index.php?step=" + (rez.STEP * 1 +1) + "&clear=1");
                //setInterval(getCounter, 600);
            }else if(rez.STATUS == "END"){
                console.log("end");
                $("#rez").html("Парсинг выполнен на шаге " + rez.STEP);
                clearInterval(interval);
            }

        });
    }
    window.interval = setInterval(getCounter, 600);
    $("#next").on("click", function(e){
        e.preventDefault();
        var h = $(this).data("link");
        clearInterval(interval);
        window.interval = setInterval(getCounter, 600);
        $.get(h,function(data){
            console.log("qqq");
        });
        //setTimeout(function(){getCounter();}, 1000);
    });
    $("#stop").on("click", function(e){
        e.preventDefault();
        clearInterval(interval);
    });
</script>
</body>
</html>