<?php
//- Iniciamos la sesion
if(!isset($_SESSION)){ session_start(); }

//- Incluimos la clase de conexion e instanciamos del objeto principal
include_once("../libs/php/class.connection.php");
$conexion = new Conexion();

//- Si la variable de usuario no viene se detenemos la ejecucion
if(!isset($_POST["user"])){ exit(); }

//- Obtenemos las variables y las decodificamos de utf8, a la 
//- vez que limpiamos de caracteres raros para evitar inyecciones SQL 
$user = $conexion->escape(utf8_decode($_POST["user"]));
$pass = $conexion->escape(utf8_decode($_POST["pass"]));

//- Inicializamos el array que usaremos para responder
$success=array();

//- Escribimos la consulta para buscar al usuario
$selUser = "SELECT count(log_id) AS 'total' FROM login WHERE log_usr='{$user}'";
//- Ejecutamos la consulta y asignamos a la variable $res el resultado del select
$res = $conexion->execSelect($selUser);
//- Parseamos el resultado y lo convertimos en un array asociativo
$iUsers = $conexion->fetchArray($res["result"]);

//- Si el usuario no se encuentra detenemos, la ejecucion
if($iUsers["total"]!="1"){ 
	$success = array("success"=>"true","t"=>"false","msg"=>"El usuario  es incorrecta");
	echo json_encode($success); exit(); 
}

//- Repetimos el proceso anterior pero con el usuario y password a la vez
$selPass = "SELECT count(log_id) AS 'total',log_id AS 'id' FROM login WHERE log_usr='{$user}' AND log_pss='{$pass}' ";
$res = $conexion->execSelect($selPass);
$iUserP = $conexion->fetchArray($res["result"]);

//- Si el usuario y el password no se encuentran, detenemos la ejecucion
if($iUserP["total"]!="1"){ 
	$success = array("success"=>"true","t"=>"false","msg"=>"El usuario o la contrase&ntilde;a es incorrecta");
	echo json_encode($success); exit(); 
}

$selInfo = "SELECT c.car_es_doctor AS 'isdoctor',e.emp_idsuc,e.emp_nom,e.emp_ape FROM empleado AS e INNER JOIN cargo AS c on e.emp_idcar = c.car_id WHERE emp_id='".$iUserP["id"]."'";
$res = $conexion->execSelect($selInfo);
$iEmp = $conexion->fetchArray($res["result"]);
$esDoctor = ($iEmp["isdoctor"]=="true")?true:false;
$nombres = explode(" ",$iEmp["emp_nom"]);
$apellidos = explode(" ",$iEmp["emp_ape"]);

//- Si llego hasta aqui es porque se encontro el usuario, 
//- por lo tanto se asignan las variables a la sesion
$_SESSION["iduser"] = $iUserP["id"];
$_SESSION["user"] = $user;
$_SESSION["password"] = $pass;
$_SESSION["esDoctor"] = $esDoctor;
$_SESSION["idsucursal"] = $iEmp["emp_idsuc"];
$_SESSION["nombre"] = $nombres[0]." ".$apellidos[0];

$conexion->execManto("INSERT INTO acceso(acc_ult,acc_idlog) VALUES(NOW(),'".$iUserP["id"]."') ");

$success = array("success"=>"true","t"=>"true","msg"=>"Correcto");
echo json_encode($success); exit(); 


?>