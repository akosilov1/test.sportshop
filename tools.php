<?php
require_once __DIR__.'/config.php';
define('STEP_WIDTH', 5);
$t = microtime(true);
use \Product\Product;
require __DIR__."/classes/soap.php";
$soap = new Soap();
$db = new \Db;
?>
    <form action="" method="post">
        <button name="action" value="check_images">Проверка картинок</button>
        <button name="action" value="check_cron">Крон</button>
    </form>
<?
$log = file_get_contents(__DIR__.'/update.log');
echo 'Крон: ';pre_print($log);
switch ($_REQUEST['action']){
    case 'check_cron':
        $log = array('STATUS' => 'END', 'TIME' => time());
        file_put_contents(__DIR__.'/update.log', json_encode($log));
        break;
    case 'check_images':

        $offset = ((int)$_GET['offset'])?(int)$_GET['offset']:0;
        $q = 'SELECT product_id, amount, product_code FROM '.$config['table_prefix'].'products LIMIT '.$offset.','.STEP_WIDTH;
        $r = $db->query($q);
        foreach ($r as $prod){
            echo $prod['product_code'].' >>><br>';
            $p_id = Product::GetIdByCode($prod['product_code']);
            echo 'ID = '.$p_id.' <br>';
            $source_prod = $soap->getProduct($p_id);//16308 78584
            if($source_prod === false){
                echo 'Товар не найден<br>';
                pre_print($soap->erroors);
            }else{
                $p = new Product();
                $pr = $p->Get('product_id='.$prod['product_id']);
                $pr[0]->images = $pr[0]->GetImages();
                //pre_print($pr);
                if (!$pr[0]->images){
                    pre_print($source_prod);
                    addImages($prod['product_id'],$source_prod->picture);
                    /*$i = 1;
                    foreach ($images as $img){
                        if($img->source === 'detail' || $img->source === 'more'){
                            $img_type = ($img->source === 'detail')?'M':'A';
                            echo '#'.$i.' ADD IMG >>> '.$img->_.'<br>';
                            if($new_i_id = \Product\Images::AddImage($img->_, 'product',$prod['product_id'],$img_type))
                                echo 'REZ OK'.$new_i_id.'<br>';
                            else
                                echo 'REZ ERR'.$db->error.'<br>';
                            $i++;
                        }
                    }*/
                }else{
                    pre_print($pr[0]->images);
                    foreach ($pr[0]->images as $img){
                        if(file_exists($img->image_path)){
                            echo "ok";
                        }else{
                            echo 'Нет такого файла '.$img->image_path.'<br>';
                            delAllImg($prod['product_id']);
                            addImages($prod['product_id'],$source_prod->picture);
                            break;
                        }
                    }

                }
            }
        }
        if(count($r) > 0):
            ?>
            <a id="next" href="?offset=<?=$offset + STEP_WIDTH?>&action=check_images#next">Next >>></a>
            <script>
                document.getElementById('next').click();
            </script>
        <?
        endif;
        echo "-= Конец =-";
    break;
}
//delAllImg(13898 );
function delAllImg($prod_id){
    $db = new \Db();
    if(!$prod_id) return;
    $q = 'SELECT image_id FROM '.$config['table_prefix'].'images_links WHERE object_type="product" AND object_id='.$prod_id;
    $rez = $db->query($q);
    if(!$rez) return;
    foreach ($rez as $img_id){
        $q = 'DELETE FROM '.$config['table_prefix'].'images WHERE image_id='.$img_id;
        $db->query($q);
    }
    $q = 'DELETE FROM '.$config['table_prefix'].'images_links WHERE object_type="product" AND object_id='.$prod_id;
    $db->query($q);
    pre_print($rez);

}
function addImages($prod_id, $images){
    $i = 1;$db = new \DB;
    foreach ($images as $img){
        if($img->source === 'detail' || $img->source === 'more'){
            $img_type = ($img->source === 'detail')?'M':'A';
            echo '#'.$i.' ADD IMG >>> '.$img->_.'<br>';
            if($new_i_id = \Product\Images::AddImage($img->_, 'product',$prod_id,$img_type))
                echo 'REZ OK'.$new_i_id.'<br>';
            else
                echo 'REZ ERR'.$db->error.'<br>';
            $i++;
        }
    }
}
