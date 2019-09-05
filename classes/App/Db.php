<?php
/**
 * Created by PhpStorm.
 * User: Aleksandr
 * Date: 25.02.2019
 * Time: 19:36
 */

class Db
{
    public $pdo;
    public $error;
    function __construct()
    {
        global $config;
        $this->pdo = new PDO("mysql:host=".$config["db_host"].";dbname=".$config["db_name"].";charset=UTF8",$config["db_user"],$config["db_password"]);
    }
    function query($q,$class_name='',array $data=array()){
        $thd = $this->pdo->prepare($q);
        $thd->execute($data);
        if($this->pdo->errorCode() != 00000){
            echo $this->pdo->errorInfo();
            return false;
        }
        if($class_name){
            $rez = array();
            while($o = $thd->fetchObject($class_name))
                $rez[] = $o;
            return $rez;
        }
        //echo "RC".$thd->rowCount();
        return $thd->fetchAll(PDO::FETCH_ASSOC);
    }
    function update($q,$data){
        $thd = $this->pdo->prepare($q);
        $thd->execute($data);
        $this->error = $thd->errorInfo();
        return $thd->errorCode();
    }
    function insert($q,$data){
        $thd = $this->pdo->prepare($q);
        $thd->execute($data);
        if($thd->errorCode() == '00000'){
            return $this->pdo->lastInsertId();
        }
        $this->error = $thd->errorInfo();
        return false;
    }

    function GetError(){
        return $this->error;
    }
}