<?php
define('SOAP_LOGIN', 'sale@sportshop24.ru');
define('SOAP_PASS', '610985');

define("BOOTSTRAP", "value");
define("HOST", $_SERVER["HTTP_HOST"]);
define('DEVELOPMENT', false);

define("API_MAIL","sale%40sportshop24.ru");
define("API_KEY","234Q3koD6aC49829xV8kjVD123067NH2");

require_once __DIR__."/../config.local.php";

$seo_settings = array(
    "CAT_TITLE" => "((NAME)) оптом  с доставкой, низкая цена в интернет-магазине СпортШоп24",
    "CAT_DESCRIPTION" => "((NAME)) оптом  с доставкой по низкой цене в интернет-магазине СпортШоп24. Индивидуальные скидки. Доставка по России.",
    "CAT_KEYWORDS" => "((NAME)) оптом",
    "PROD_TITLE" => "((NAME)) оптом  с доставкой, низкая цена в интернет-магазине СпортШоп24",
    "PROD_DESCRIPTION" => "((NAME)) оптом  с доставкой по низкой цене в интернет-магазине СпортШоп24. Индивидуальные скидки. Доставка по России. ",
    "PROD_KEYWORDS" => "((NAME)) оптом",
);
$price_type = array("Золото","Серебро","Бронза","Старт","РРЦ","Прайм");
define('PRICE', 3);

spl_autoload_register(function ($class_name) {
    //echo $class_name;
    include $_SERVER["DOCUMENT_ROOT"]."/import/classes/App/".str_replace("\\","/",$class_name) . '.php';
});
function pre_print($data){
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}