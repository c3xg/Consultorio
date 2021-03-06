<?php
include("sesion.back.php");


//- Incluimos la clase de conexion e instanciamos del objeto principal
include_once("../libs/php/class.connection.php");
include_once("../libs/php/class.objetos.base.php");
$conexion = new Conexion();

$minutos_citas = $conf["duracion"]; //40;
$hora_inicio = $conf["horaInicio"]; //1379854800;
$hora_fin = $conf["horaFin"]; //1379887200;


//- Si la variable action no viene se detenemos la ejecucion
if(!isset($_POST["action"])){ exit(); }

$accion = $_POST["action"];

switch ($accion) {
	case 'ls_pacientes':
		$query = "";
		if(isset($_POST["q"]) && $_POST["q"]!=""){
			$q = $conexion->escape($_POST["q"]);
			$query = " WHERE CONCAT(pac_nom,' ',pac_ape) LIKE '%{$q}%' ";
		}
		$selPacientes = "SELECT pac_id AS 'id',CONCAT(pac_nom,' ',pac_ape) AS 'nombre',pac_correo AS 'mail' FROM paciente {$query} ORDER BY pac_nom,pac_ape";
		$res = $conexion->execSelect($selPacientes);
		
		$registros=array();
		if($res["num"]>0){
			$i=0;
			while($iPaci = $conexion->fetchArray($res["result"])){
				$registros[]=array("id"=>$iPaci["id"],"text"=>utf8_encode($iPaci["nombre"]."<br />".$iPaci["mail"]));
			}
		}

		$results = array("results"=>$registros,"more"=>false);
		echo json_encode($results);
		
	break;

	case 'rt_agenda':

		$fecha_inicial = date('Y/m/d',$_POST["fechainicial"]);
		$fecha_final = date('Y/m/d',strtotime("+7 days",$_POST["fechainicial"]));

		$selCitas = "SELECT c.cit_id AS 'id',CONCAT(p.pac_nom,' ',p.pac_ape) AS 'nombre', DATE_FORMAT(c.cit_fecha_cita,'%Y/%m/%d') AS 'fecha',
						DATE_FORMAT(c.cit_fecha_cita,'%H:%i') AS 'hora'
						FROM cita AS c INNER JOIN paciente AS p ON c.cit_idpac = p.pac_id
						WHERE c.cit_fecha_cita BETWEEN '{$fecha_inicial}' AND '{$fecha_final}' ";
		
		$res = $conexion->execSelect($selCitas);
		$citas = array();

		$registros=array();
		$i=0;
		if($res["num"]>0){
			while($iCita = $conexion->fetchArray($res["result"])){

				$posicion = calcularCuadroAgenda($iCita["fecha"],$iCita["hora"]);

				$registros[]=array(
					"id_cita"=>$iCita["id"],
					"posicion"=>$posicion["id"],
					"offset"=>$posicion["offset"],
					"texto_uno"=>utf8_encode($iCita["nombre"]),
					"texto_dos"=>$iCita["fecha"]." ".$iCita["hora"]
				);
				$i++;
			}
		}

		$results = array("citas"=>$registros,"total"=>$i);
		echo json_encode($results);

	break;

	case 'sv_cita':
		if(!isset($_POST["idpaciente"])||!isset($_POST["hinicio"])||!isset($_POST["idempleado"])) exit();

		$tipo = ($_POST["id"]=="")?'nuevo':'editar';

		$id = (int)$conexion->escape($_POST["id"]);
		$idPaciente = (int)$conexion->escape($_POST["idpaciente"]);
		$idEmpleado = (int)$conexion->escape($_POST["idempleado"]);
		$comentario = utf8_decode((string)$conexion->escape($_POST["comentario"]));
		$hi = ((int)$_POST["hinicio"])*60;
		//$hf = (int)$_POST["hfin"]);
		$fe = (int)$_POST["fecha"];
		$fecha = date("Y-m-d H:i:s",$fe+$hi);
		$idSucursal = $_SESSION["idsucursal"];

		$mantoCita = "";
		if($tipo=='nuevo'){
			$mantoCita = "INSERT INTO cita(cit_idpac,cit_fecha_cita,cit_idemp,cit_com,cit_estado,cit_idsuc,cit_fecha_cre) VALUES('{$idPaciente}','{$fecha}','{$idEmpleado}','{$comentario}','1','{$idSucursal}',NOW()) ";
		}else{
			$mantoCita = "UPDATE cita SET cit_idpac='{$idPaciente}',cit_idemp='{$idEmpleado}',cit_fecha_cita='{$fecha}',cit_com='{$comentario}' WHERE cit_id = {$id} ";
		}
		
		$res = 0;
		$res = $conexion->execManto($mantoCita);

		if($res>0){
			$success = array("success"=>"true","msg"=>"La cita se ha guardado");
		}else{
			$success = array("success"=>"false","msg"=>"Ha ocurrido un error");
		}
		echo json_encode($success);
	break;



	case 'br_cita':
		$result = array("success"=>"false","msg"=>"");

		if(!isset($_POST["id"])){ exit(); }
		$id = json_decode($_POST["id"],true);

		$borrarCita = "DELETE FROM cita WHERE cit_id = {$id} ";
		$res = $conexion->execManto($borrarCita);
		if($res>0){
			$result = array("success"=>"true","msg"=>"La cita se ha borrado");
		}else{
			$result = array("success"=>"false","msg"=>"La cita tiene una consulta relacionada");
		}
		echo json_encode($result);
	break;


}



function calcularCuadroAgenda($fecha,$hora){
	global $minutos_citas,$hora_inicio,$hora_fin;
	$hi = strtotime(date("Y-m-d")." ".date("H:i",$hora_inicio));
	$hf = strtotime(date("Y-m-d")." ".date("H:i",$hora_fin));
	$hc = strtotime(date("Y-m-d")." ".$hora);
	$hMaximo = entero(($hf-$hi)/($minutos_citas*60));

	$diff = $hc-$hi;
	$posh = $offset = 0;
	if($diff<=0){ $posh = 0; }
	elseif($diff>=($hf-$hi)){ $posh=$hMaximo; }
	else{ 
		$tmpN = ($diff/($minutos_citas*60));
		$posh = entero($tmpN);
		$offset = entero(43*($tmpN-$posh));
	}
	

	$posd = date("N",strtotime($fecha));

	$id = "h_{$posh}_d_{$posd}";
	return array("id"=>$id,"offset"=>$offset);
}

function entero($n){
	$nTmp = (string)$n;
	if($nTmp=="") return 0;
	if(strstr($nTmp,".") === false) return (int)$n;
	$nE = explode(".",$n);
	return (int)$nE[0];
}

?>