<?php

if(!isset($_SESSION)){ session_start(); }
if($_SESSION["iduser"]==""){ header("Location: login.php"); exit(); }

$botones_menu = array("citas"=>false,"consultas"=>false,"pacientes"=>false,"facturas"=>false,"reportes"=>false,"limpio"=>false);
$botones_configuracion = array("configuracion"=>false,"cargos"=>false,"roles"=>false,"empleados"=>false,"productos"=>false,"proveedores"=>false);
$botones_herramientas = array("paises"=>false,"departamentos"=>false,"municipios"=>false,"sucursales"=>false,"documentos"=>false,
								"movimientos"=>false,"tiposangre"=>false,"cargos"=>false,"roles"=>false,"configuraciones"=>false);

include_once('libs/php/constantes.php');
include_once('libs/php/class.objetos.base.php'); 
$data = new Configuracion();
$conf = $data->obtenerConfiguracion();
$titulo_sistema = $conf["nombreEmpresa"];

?>