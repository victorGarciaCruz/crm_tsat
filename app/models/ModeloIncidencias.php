<?php


class ModeloIncidencias{

    private $db;


    public function __construct(){
        $this->db = new Base;
    } 
    
    public function obtenerIncidenciasTablaClass($filas,$orden,$tipoOrden,$filaspagina,$cond){
        $this->db->query("SELECT inc.id as 'Nº', DATE_FORMAT(inc.creacion , '%d/%m/%Y', 'es_ES') AS 'Creación', 
                        CONCAT(usu.nombre, ' ', usu.apellidos)  AS 'Usuario',
                        cli.nombre AS 'Cliente', suc.nombre AS 'Sucursal',
                        equ.nombre as 'Equipo', 
                        IF(inc.estado=1,'pendiente',
                            IF(inc.estado=2,'en curso',
                                IF(inc.estado=3 AND inc.validarcliente =0,'terminadasinvalorar','terminada')										  
                            )
                        ) AS 'Estado',
                        inc.nombrestecnicos as 'Técnicos'
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id                        
                        WHERE inc.activo =1 $cond
                        order by " . $orden . " limit $filaspagina,$filas ");
                                  
        $resultado = $this->db->registros();

        return $resultado;
    }

    public function obtenerIncidenciasTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond){
        $this->db->query("SELECT inc.id as 'Nº', DATE_FORMAT(inc.creacion , '%d/%m/%Y', 'es_ES') AS 'Creación', 
                        CONCAT(usu.nombre, ' ', usu.apellidos)  AS 'Usuario',
                        cli.nombre AS 'Cliente', suc.nombre AS 'Sucursal',
                        equ.nombre AS 'Equipo', 
                        IF(inc.estado=1,'pendiente',
                            IF(inc.estado=2,'en curso',
                                IF(inc.estado=3 AND inc.validarcliente =0,'terminadasinvalorar','terminada')										  
                            )
                        ) AS 'Estado',
                        inc.nombrestecnicos as 'Técnicos',
                        IF(moda.valor>0,'horas','') AS 'verhorascliente'
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id 
                        LEFT JOIN modalidadhoras moda ON MONTH(inc.creacion) = moda.mes AND YEAR(inc.creacion) = moda.anio AND inc.idcliente=moda.idcliente            
                        WHERE inc.activo =1  $cond
                        order by " . $orden . "  limit $filaspagina,$filas ");
       
        $resultado = $this->db->registros();

        return $resultado;
    }

    public function totalRegistrosIncidencias($cond)
    {
        $this->db->query("SELECT count(*) AS contador 
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id   
                        LEFT JOIN modalidadhoras moda ON MONTH(inc.creacion) = moda.mes AND YEAR(inc.creacion) = moda.anio
                        WHERE inc.activo =1 $cond ");
                        
        $fila = $this->db->registro();
        return $fila;
    }

    public function totalRegistrosIncidenciasBuscar($cond)
    {
        $this->db->query("SELECT count(*) AS contador
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id   
                        WHERE inc.activo =1  $cond ");
        $fila = $this->db->registro();
        return $fila;
    }
    
    public function obtenerTodasSucursalesPorCliente($id)
    {
        $this->db->query("SELECT * FROM sucursales WHERE activo =1 AND idcliente= '$id' ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerSucursalesDelClienteDesdeIdUsuario($idUsuario)
    {
        $this->db->query("SELECT suc.id, suc.nombre
                        FROM usuarios usu
                        LEFT JOIN sucursales suc ON usu.idcliente=suc.idcliente
                        WHERE usu.id= '$idUsuario' AND suc.activo= 1 ");
        $resultado = $this->db->registros();
        return $resultado;
    }
    
    public function obtenerEquiposPorSucursal($idSucursal)
    {
        $this->db->query("SELECT eq.id, eq.nombre FROM equipos eq WHERE eq.idsucursal= $idSucursal AND activo=1");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function sucursalDelEquipoPorIdEquipo($idEquipo)
    {
        $this->db->query("SELECT eq.idsucursal, eq.id, eq.nombre  FROM equipos eq WHERE eq.id= $idEquipo ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    

    public function obtenerIdClientePorIdusuario($idUsuario)
    {
        $this->db->query("SELECT idcliente FROM usuarios WHERE id='$idUsuario' ");
        $resultado = $this->db->registro();
        return $resultado->idcliente;
    }

    public function insertarNuevaSolicitud($datos)
    {                       
        $this->db->query("INSERT INTO incidencias (descripcion, idusuario, idcliente, sucursal, creacion, idequipo, estado, activo, tecnicos, nombrestecnicos,ipincidencia,estadofactppto,nomestadofactppto,fechahora) 
                        VALUES (:descripcion, :idusuario, :idcliente, :idsucursal, :creacion, :idequipo, :estado, :activo, :tecnicosAsig, :nombresTecnicos, :ip, :estadofactppto, :nomestadofactppto, :fechahora )");                    
            
        $this->db->bind(':idsucursal', $datos['idsucursal']);
        $this->db->bind(':idequipo', $datos['idequipo']);
        $this->db->bind(':descripcion', $datos['descripcion']);
        $this->db->bind(':idusuario', $datos['idusuario']);
        $this->db->bind(':idcliente', $datos['idcliente']);
        $this->db->bind(':creacion', $datos['creacion']);
        $this->db->bind(':estado', $datos['estado']);
        $this->db->bind(':activo', $datos['activo']);
        $this->db->bind(':tecnicosAsig', $datos['tecnicos']);
        $this->db->bind(':nombresTecnicos', $datos['nombresTecnicos']);  
        $this->db->bind(':ip', $datos['remoteADDR']);
        $this->db->bind(':estadofactppto', $datos['estadofactppto']);
        $this->db->bind(':nomestadofactppto', $datos['nomestadofactppto']);
        $this->db->bind(':fechahora', $datos['fechahora']);
        


        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return false;
        }        
    }
        
    public function crearPresupuestoParaCliente($idIncidencia,$comentario,$idusuario)
    {
        $this->db->query("INSERT INTO incidenciaspresupuestos (idincidencia, comentario, idusuario) 
                        VALUES ('$idIncidencia', '$comentario', '$idusuario')");                        

        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function obtenerTecnicosAsignados($idCliente)
    {
        $this->db->query("SELECT tecnicos FROM clientes WHERE id='$idCliente' ");
        $resultado = $this->db->registro();
        return $resultado->tecnicos;
    }

    public function obtenerIdsTecnicosAsignados($idCliente)
    {
        $this->db->query("SELECT tecnicos FROM clientes WHERE id='$idCliente' ");
        $resultado = $this->db->registro();
        return $resultado->tecnicos;
    }

    public function obtenerTecnicosAsignadosParaIncidencia($idIncidencia)
    {       
        $this->db->query("SELECT tecnicos FROM incidencias WHERE id='$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado->tecnicos;
    }

    public function nombresTecnicosAsignadosParaIncidencia($idIncidencia)
    {       
        $this->db->query("SELECT nombretecnicos FROM incidencias WHERE id='$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado->nombretecnicos;
    }
   
    public function nombreCompletoDeTecnicoPorId($idTecnico)
    {
        $this->db->query("SELECT CONCAT(nombre, ' ', apellidos) as nombretecnico FROM usuarios WHERE id='$idTecnico' ");
        $resultado = $this->db->registro();
        return $resultado->nombretecnico;
    }

    public function obtenerDatosIncidencia($idIncidencia)
    {
        $this->db->query("SELECT inc.*, suc.nombre AS nombresucursal,
                        cli.nombre AS nombrecliente, eq.nombre AS nombreequipo, 
                        eq.serie, eq.marca, eq.ip as ipficha,
                        DATE_FORMAT(inc.creacion,'%d-%m-%Y %H:%i:%s') AS fecha, est.estado AS nombreestado,
                        CONCAT(usu.nombre,' ',usu.apellidos) AS nombreusuario
                        FROM incidencias inc 
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN equipos eq ON inc.idequipo=eq.id
                        LEFT JOIN estadoincidencias est ON inc.estado=est.id
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        WHERE inc.id='$idIncidencia' ");
                        
        $resultado = $this->db->registro();
        return $resultado;
    }
 
    public function existenIncidencias()
    {
        $this->db->query("SELECT COUNT(*) as contador FROM incidencias ");
        $resultado = $this->db->registro();
        return $resultado->contador;
    }

    public function incidenciasTecnicosTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond){

        $this->db->query("SELECT inc.id as 'Nº', DATE_FORMAT(inc.creacion , '%d/%m/%Y', 'es_ES') AS 'Creación', 
                        CONCAT(usu.nombre, ' ', usu.apellidos)  AS 'Usuario',                        
                        CONCAT(cli.nombre, ' ', cli.nombrecomercial) AS 'Cliente',
                        suc.nombre AS 'Sucursal',
                        equ.nombre AS 'Equipo', 
                        IF(inc.estado=1,'pendiente',
                            IF(inc.estado=2,'en curso',
                                IF(inc.estado=3 AND inc.validarcliente =0,'terminadasinvalorar','terminada')										  
                            )
                        ) AS 'Estado',
                        inc.nombrestecnicos as 'Técnicos', inc.play AS 'Atención',
                        IF(inc.fechahora IS NULL, '', DATE_FORMAT(inc.fechahora, '%d-%m-%Y %H:%i')) AS 'Agendado'
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id                        
                        WHERE inc.activo =1  $cond
                        order by " . $orden . "  limit $filaspagina,$filas ");
                        
                        /*print_r($this);
                        die;*/
        $resultado = $this->db->registros();

        return $resultado;
    }

    public function incidenciasAdminTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond){

        $this->db->query("SELECT inc.id as 'Nº', DATE_FORMAT(inc.creacion , '%d/%m/%Y', 'es_ES') AS 'Creación', 
                        CONCAT(usu.nombre, ' ', usu.apellidos)  AS 'Usuario',
                        CONCAT(cli.nombre, ' ', cli.nombrecomercial) AS 'Cliente', suc.nombre AS 'Sucursal',
                        equ.nombre AS 'Equipo', 
                        IF(inc.estado=1,'pendiente',
                            IF(inc.estado=2,'en curso',
                                IF(inc.estado=3 AND inc.validarcliente =0,'terminadasinvalorar','terminada')										  
                            )
                        ) AS 'Estado',
                        inc.nombrestecnicos as 'Técnicos', inc.play AS 'Atención' ,
                        inc.nomestadofactppto AS 'Fact/Ppto',
                        IF(inc.fechahora IS NULL, '', DATE_FORMAT(inc.fechahora, '%d-%m-%Y %H:%i')) AS 'Agendado'
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id                                               
                        WHERE inc.activo =1  $cond
                        order by " . $orden . "  limit $filaspagina,$filas ");
                        
                        
        $resultado = $this->db->registros();

        return $resultado;
    }

   
    public function totalIncidenciasTecnicoBuscar($cond)
    {
        $this->db->query("SELECT count(*) AS contador
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        LEFT JOIN clientes cli ON inc.idcliente=cli.id
                        LEFT JOIN sucursales suc ON inc.sucursal=suc.id
                        LEFT JOIN equipos equ ON inc.idequipo=equ.id     
                        WHERE inc.activo =1  $cond ");
                      
        $fila = $this->db->registro();
        return $fila;
    }
    


    public function listaModalidadesTecnicos()
    {
        $this->db->query("SELECT * FROM modalidadtecnico WHERE activo = 1 ");
        $resultado = $this->db->registros();
        return $resultado;
    }
    
    public function crearInicioAtencionIncidencia($datos)
    {
        $idIncidencia = $datos['idIncidencia'];
        $modalidad = $datos['modalidad'];
        $idTecnico = $datos['idTecnico'];
        $play = $datos['play'];
        $creacion = $datos['creacion'];
        $idCliente = $datos['idCliente'];
        $idEquipo = $datos['idEquipo'];
        
        $this->db->query("INSERT INTO incidenciastiempos (idincidencia,idequipo,modalidadtecnico,idtecnico,play,creacion,idcliente) 
                        VALUES ('$idIncidencia','$idEquipo','$modalidad','$idTecnico','$play','$creacion', '$idCliente')");

        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return false;
        }   
    } 
    
    public function actualizarPlayStopIncidencia($datos,$ins)
    {
   
        $idIncidencia = $datos['idIncidencia'];            

        $this->db->query("UPDATE incidencias 
                        SET play = '$ins'                        
                        WHERE id = '$idIncidencia' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }      
    }

    
    public function obtenerEstadoIncidencia($idIncidencia)
    {
        $this->db->query("SELECT estado FROM incidencias WHERE id= '$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function cambiarEstadoDePendienteAEnCurso($idIncidencia)
    {    
        $this->db->query("UPDATE incidencias 
                        SET estado = 2
                        WHERE id = '$idIncidencia' ");

        $this->db->execute();         
    }

    public function reabrirIncidencia($idIncidencia)
    {    
        $this->db->query("UPDATE incidencias 
                        SET estado = 2
                        WHERE id = '$idIncidencia' ");

        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    
    
    public function detenerAtencionIncidencia($datos)
    {
        $idAtencion = $datos['idAtencion'];        
        $play = $datos['play'];
        $finalizacion = $datos['finalizacion'];
        $tiempoTotal = $datos['tiempoTotal'];

        $this->db->query("UPDATE incidenciastiempos 
                        SET play = '$play', finalizacion = '$finalizacion', tiempototal = '$tiempoTotal'                        
                        WHERE id = '$idAtencion' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }    
    }

    public function obtenerDetallesControlTiemposIncidencia($id)
    {
        $this->db->query("SELECT  mot.modalidad as modalidad, 
                        DATE_FORMAT(tim.creacion , '%d/%m/%Y %H:%i:%s', 'es_ES') AS creacion,
                        tim.idincidencia
                        FROM incidenciastiempos tim 
                        LEFT JOIN modalidadtecnico mot ON tim.modalidadtecnico=mot.id
                        WHERE tim.id= '$id' ");
                       
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function obtenerIdIncidenciaDesdeIdAtencion($idAtencion)
    {
        $this->db->query("SELECT tim.idincidencia
                        FROM incidenciastiempos tim 
                        WHERE tim.id= '$idAtencion' ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function obtnerListadoFicherosImagenes($idIncidencia)
    {
        $this->db->query("SELECT * FROM incidenciasficheros doc WHERE doc.idincidencia= $idIncidencia AND doc.base <>'' ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtnerListadoFicherosImagenesTodas($idIncidencia)
    {
        $this->db->query("SELECT * FROM incidenciasficheros doc WHERE doc.idincidencia= $idIncidencia ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtnerListadoFicherosDocumentos($idIncidencia)
    {
        $this->db->query("SELECT * FROM incidenciasficheros doc WHERE doc.idincidencia= $idIncidencia AND doc.base ='' ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerNombreFicheroPorId($idFichero)
    {
        $this->db->query("SELECT doc.nombre FROM incidenciasficheros doc WHERE doc.id= $idFichero ");
        $resultado = $this->db->registro();
        return $resultado->nombre;
    }

    public function insertarDatosFichero($fichero, $idIncidencia, $base64)
    {
        $nombre = $fichero['nombre'];
        $tipo = $fichero['tipo'];
        $tamanio = $fichero['tamanio'];
         
        $this->db->query("INSERT INTO incidenciasficheros (idincidencia, tipo, nombre, tamanio, base) 
                        VALUES ($idIncidencia, '$tipo', '$nombre', '$tamanio', '$base64')");
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }else{
            return 0;
        }     
    }

    
    public function listaTecnicosActivos()
    {
        $this->db->query("SELECT * FROM usuarios WHERE activo = 1 AND rol=2 ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerCorreoDesdeIdusuario($idUsuario)
    {
        $this->db->query("SELECT correo, recibemails FROM usuarios WHERE id = $idUsuario AND activo=1 ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function finalizarIncidencia($datosUpd)
    {
        $idIncidencia = $datosUpd['idIncidencia'];
        $estado = $datosUpd['estado'];    
        $fecha = $datosUpd['fecha'];

        $this->db->query("UPDATE incidencias 
                        SET estado = '$estado', finalizacion = '$fecha'
                        WHERE id = '$idIncidencia' ");

        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function insertarComentarioFinalizarIncidencia($idIncidencia,$comentario,$idUsuario,$rol,$idEquipo,$tipo,$valoracion)
    {            
        $this->db->query("INSERT INTO incidenciascomentarios (idincidencia, comentario, idusuario, rol, idequipo, tipo, valoracion) 
                        VALUES (:idIncidencia, :comentario, :idUsuario, :rol, :idEquipo, :tipo, :valoracion)");

        $this->db->bind(':idIncidencia', $idIncidencia);
        $this->db->bind(':comentario', $comentario);
        $this->db->bind(':idUsuario', $idUsuario);
        $this->db->bind(':rol', $rol);
        $this->db->bind(':idEquipo', $idEquipo);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':valoracion', $valoracion);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }else{
            return 0;
        }  
    }

    public function insertarComentarioDelClientesIncidencia($idIncidencia,$comentario,$idUsuario,$rol,$idEquipo,$tipo,$valoracion)
    {            
        $this->db->query("INSERT INTO incidenciascomentarios (idincidencia, comentario, idusuario, rol, idequipo, tipo, valoracion) 
                        VALUES ($idIncidencia, '$comentario', '$idUsuario', '$rol', '$idEquipo', '$tipo', '$valoracion')");
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }else{
            return 0;
        }
    }

    public function nombreUsuarioQueRegistroLaIdIncidencia($idIncidencia)
    {       
        $this->db->query("SELECT usu.correo, usu.recibemails
                        FROM incidencias inc
                        LEFT JOIN usuarios usu ON inc.idusuario=usu.id
                        WHERE inc.id='$idIncidencia' AND usu.activo=1 ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function valorarIncidencia($datosUpd)
    {        
        $idIncidencia = $datosUpd['idIncidencia'];        
        $fecha = $datosUpd['fecha'];
        $valoracion = $datosUpd['valoracion'];

        $this->db->query("UPDATE incidencias 
                        SET finalizacion = '$fecha', validarcliente = '$valoracion'
                        WHERE id = '$idIncidencia' ");

        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function usuarioEstaActivo($idUsuario)
    {
        $this->db->query("SELECT codigotecnico,activo FROM usuarios WHERE id= '$idUsuario' ");

        $resultado = $this->db->registro();
        return $resultado;
    }

    public function actulizarTecnicosNuevos($idIncidencia,$tecnicosNuevos,$nombreNuevos)
    {        
        $this->db->query("UPDATE incidencias 
                        SET tecnicos = '$tecnicosNuevos', nombrestecnicos = '$nombreNuevos'
                        WHERE id = '$idIncidencia' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }      
    }


 
    public function obtnerListadoDeAtencionesTecnicos($idIncidencia)
    {
        $this->db->query("SELECT tmp.*, moda.modalidad,
                        (SELECT inc.nombrestecnicos FROM incidencias inc WHERE inc.id='$idIncidencia') AS tecnicos 
                        FROM incidenciastiempos tmp 
                        LEFT JOIN modalidadtecnico moda ON tmp.modalidadtecnico=moda.id
                        WHERE tmp.idincidencia= '$idIncidencia' ");

        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerMarcaPermisoTecnico($idUsuario)
    {
        $this->db->query("SELECT usu.editartiempo FROM usuarios usu WHERE usu.id= '$idUsuario' ");

        $resultado = $this->db->registro();
        return $resultado->editartiempo;
    }

    public function tienePermisoVerTodasLasIncidencias($idUsuario)
    {
        $this->db->query("SELECT vertodas FROM usuarios WHERE id= '$idUsuario' ");
        $resultado = $this->db->registro();
        return $resultado->vertodas;
    }

    public function guadarImagenFirmaincidencia($idIncidencia,$imagenBase64,$guardada)
    {    
        $this->db->query("UPDATE incidencias 
                        SET firma = '$imagenBase64', guardada='$guardada'
                        WHERE id = '$idIncidencia' ");

        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function obtenerSucursalesDeClientesAsignadosATecnicos($idTecnico)
    {
       
        $this->db->query('SELECT cli.id, cli.nombre FROM clientes cli
                        WHERE JSON_SEARCH(cli.tecnicos, "one", "'.$idTecnico.'") IS NOT NULL ORDER BY cli.nombre ASC ');
                         
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerSucursalesPorCliente($idCliente)
    {
        $this->db->query("SELECT id, nombre FROM sucursales WHERE idcliente= $idCliente ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerCorreosUsuariosAsignadosNoSupervisor($idEquipo)
    {
        $this->db->query('SELECT usu.id, usu.correo, usu.recibemails 
                        FROM usuarios usu WHERE 
                        JSON_SEARCH(usu.equipos, "one", "'.$idEquipo.'") IS NOT NULL
                        AND usu.rol=1 
                        AND usu.clientetipo= "usuario" AND usu.activo = 1 ');
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerTipoClientePorid($idUsuario)
    {
        $this->db->query("SELECT clientetipo 
                        FROM usuarios WHERE id= $idUsuario ");
        $resultado = $this->db->registro();
        return $resultado->clientetipo;
    }    

    public function obtenerIdsSucursalesParaUsuarioOSupervisor($idUsuario)
    {
        $this->db->query("SELECT idsucursal 
                        FROM usuarios                        
                        WHERE id= $idUsuario ");
        $resultado = $this->db->registro();
        return $resultado->idsucursal;
    }

    public function obtenerSucursalesParaUsuarioOSupervisor($idSucursal)
    {
        $this->db->query("SELECT id, nombre 
                FROM sucursales
                WHERE id= $idSucursal AND activo=1 ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function obtenerEquiposAsignadosASupervisorOUsuario($idUsuario)
    {
        $this->db->query("SELECT equipos 
                        FROM usuarios                        
                        WHERE id= $idUsuario ");
        $resultado = $this->db->registro();
        return $resultado->equipos;
    }

    public function contarNotificacionesPendientesPorTecnico($idUsuario)
    {
        $this->db->query('SELECT COUNT(*) AS contador FROM incidencias inc 
                        WHERE JSON_SEARCH(inc.tecnicos, "one", "'.$idUsuario.'") IS NOT NULL 
                        AND inc.estado = 1');
        $resultado = $this->db->registro();
        return $resultado->contador;
    }

    public function contarTodasLasNotificacionesPendientes()
    {
        $this->db->query('SELECT COUNT(*) AS contador FROM incidencias inc 
                        WHERE inc.estado = 1');
        $resultado = $this->db->registro();
        return $resultado->contador;
    }

    public function incidenciasPendientesPorTencioAsignado($idUsuario)
    {
        $this->db->query('SELECT inc.id, DATE_FORMAT(inc.creacion,"%d-%m-%Y %H:%i:%s", "es_ES") AS creacion 
                        FROM incidencias inc 
                        WHERE JSON_SEARCH(inc.tecnicos, "one", "'.$idUsuario.'") IS NOT NULL 
                        AND inc.estado = 1');

        $resultado = $this->db->registros();
        return $resultado;
    }

    public function todasLasIncidenciasPendientes()
    {
        $this->db->query("SELECT inc.id, DATE_FORMAT(inc.creacion,'%d-%m-%Y %H:%i:%s', 'es_ES') AS creacion 
                        FROM incidencias inc 
                        WHERE inc.estado = 1
                        ORDER BY inc.creacion DESC");

        $resultado = $this->db->registros();
        return $resultado;
    }

    public function idUsuarioQueRegistroLaIdIncidencia($idIncidencia)
    {
        $this->db->query("SELECT idusuario
                        FROM incidencias                        
                        WHERE id='$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado->idusuario;
    }
 
    public function idTecnicoQueFinalizoLaIdIncidencia($idIncidencia)
    {
        $this->db->query("SELECT idusuario
                        FROM incidenciascomentarios                        
                        WHERE idincidencia='$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado->idusuario;
    }

    public function idClientePorIncidencia($idIncidencia)
    {
        $this->db->query("SELECT idcliente
                        FROM incidencias                        
                        WHERE id='$idIncidencia' ");
                        
        $resultado = $this->db->registro();
        return $resultado->idcliente;
    }
    
    public function clienteTieneContratadoBolsaHoras($idCliente)
    {
        $this->db->query("SELECT COUNT(*) AS contador 
                        FROM modalidadhoras WHERE idcliente='$idCliente' ");
        $resultado = $this->db->registro();
        return $resultado->contador;
    }

    public function horasConsumidasHorasContratadasTablaClass($filas,$orden,$filaspagina,$tipoOrden,$cond){
        /*$this->db->query("SELECT MONTH(tie.creacion) AS 'Mes', YEAR(tie.creacion) AS 'Año',                        
                        ROUND(SUM((TIMESTAMPDIFF(MINUTE, tie.creacion, tie.finalizacion))/60),2) AS  horascons,
                        IF(moda.valor IS NULL,0,moda.valor) AS 'Hr. contrat.'
                        FROM incidenciastiempos tie 
                        LEFT JOIN modalidadhoras moda ON tie.idcliente=moda.idcliente AND MONTH(tie.creacion)=moda.mes AND YEAR(tie.creacion)=moda.anio
                        WHERE $cond AND tie.finalizacion >0
                        GROUP BY YEAR(tie.creacion), MONTH(tie.creacion)  
                        order by " . $orden . " " . $tipoOrden . " limit $filaspagina,$filas ");*/
		
		$this->db->query("SELECT MONTH(tie.creacion) AS 'Mes', YEAR(tie.creacion) AS 'Año',                        
                        ROUND(SUM((TIMESTAMPDIFF(MINUTE, tie.creacion, tie.finalizacion))/60),2) AS  horascons
                        FROM incidenciastiempos tie 
                        LEFT JOIN modalidadhoras moda ON tie.idcliente=moda.idcliente AND MONTH(tie.creacion)=moda.mes AND YEAR(tie.creacion)=moda.anio
                        WHERE $cond AND tie.finalizacion >0
                        GROUP BY YEAR(tie.creacion), MONTH(tie.creacion)  
                        order by " . $orden . " " . $tipoOrden . " limit $filaspagina,$filas ");
       
        $resultado = $this->db->registros();

        return $resultado;
    }

    public function totalRegistrosBolsaHoras($cond)
    {
        $this->db->query("SELECT *
                        FROM incidenciastiempos tie 
                        LEFT JOIN modalidadhoras moda ON tie.idcliente=moda.idcliente AND MONTH(tie.creacion)=moda.mes AND YEAR(tie.creacion)=moda.anio
                        WHERE $cond AND tie.finalizacion >0
                        GROUP BY YEAR(tie.creacion), MONTH(tie.creacion) ");
        $fila = $this->db->registros();
        if (isset($fila) ) {
            $contador = count($fila);
        }
        return $contador;
    }

    public function obtenerIdEquipoPorIncidencia($idIncidencia)
    {
        $this->db->query("SELECT idequipo FROM incidencias WHERE id='$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado->idequipo;
    }

    public function obtenerTodosLosComentariosPorIdIncidencia($idIncidencia)
    {
        $this->db->query("SELECT com.*, CONCAT(usu.nombre,' ',usu.apellidos) AS nombreusuario
                        FROM incidenciascomentarios com 
                        LEFT JOIN usuarios usu ON com.idusuario = usu.id
                        WHERE com.idincidencia= $idIncidencia
                        ORDER BY com.fechacreacion DESC");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerTodosLosComentariosExternosPorIdIncidencia($idIncidencia)
    {
        $this->db->query("SELECT com.*, CONCAT(usu.nombre,' ',usu.apellidos) AS nombreusuario
                        FROM incidenciascomentarios com 
                        LEFT JOIN usuarios usu ON com.idusuario = usu.id
                        WHERE com.idincidencia= $idIncidencia AND tipo = 'externo'
                        ORDER BY com.fechacreacion DESC");

        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerHoraCreacionAtencion($idAtencion)
    {
        $this->db->query("SELECT creacion FROM incidenciastiempos WHERE id='$idAtencion' ");
        $resultado = $this->db->registro();
        return $resultado->creacion;
    }

    public function idTecnicoQueInicioLaAtencion($idAtencion)
    {
        $this->db->query("SELECT idtecnico FROM incidenciastiempos WHERE id='$idAtencion' ");
        $resultado = $this->db->registro();       
        return $resultado->idtecnico;
    }

    
    public function actualizarFechasYHorasDeAtencionTecnico($idAtencion,$creacion,$finalizacion,$tiempoTranscurrido)
    {
        $this->db->query("UPDATE incidenciastiempos 
                        SET creacion = '$creacion', finalizacion = '$finalizacion', tiempototal = '$tiempoTranscurrido'
                        WHERE id = '$idAtencion' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }      
    }

    public function obtenerClientes(){
        $this->db->query("SELECT id, nombre
                        FROM clientes 
                        WHERE activo =1
                        ORDER BY nombre ASC");

        $resultado = $this->db->registros();

        return $resultado;
    } 

    public function obtnerImagenFicheroDesdeIdFichero($idFichero)
    {
        $this->db->query("SELECT doc.base FROM incidenciasficheros doc WHERE doc.id= $idFichero ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function obtenerIdTecnicoDesdeCodigoTecnico($codigoTecnico)
    {
        $this->db->query("SELECT id FROM usuarios WHERE codigotecnico ='$codigoTecnico' ");
        $resultado = $this->db->registro();
        return $resultado->id;
    }

    public function obtenerEstadoPlayStopDeAtencion($idIncidencia)
    {
        $this->db->query("SELECT play FROM incidencias WHERE id='$idIncidencia' ");
        $resultado = $this->db->registro();       
        return $resultado->play;
    }

    public function eliminarAtencion($idAtencion)
    {
        $this->db->query("DELETE FROM incidenciastiempos WHERE id = '$idAtencion' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }   
    }

    public function obtenerEstadoAtencion($idAtencion)
    {
        $this->db->query("SELECT play, idincidencia
                        FROM incidenciastiempos 
                        WHERE id= '$idAtencion' ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function eliminarIncidencia($idIncidencia)
    {
        $this->db->query("DELETE FROM incidencias WHERE id = '$idIncidencia' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }   
    }
    
    public function eliminarComentariosIncidencia($idIncidencia)
    {
        $this->db->query("DELETE FROM incidenciascomentarios WHERE idincidencia = '$idIncidencia' ");

        $this->db->execute();
    }
    
    public function eliminarFicherosIncidencia($idIncidencia)
    {
        $this->db->query("DELETE FROM incidenciasficheros WHERE idincidencia = '$idIncidencia' ");

        $this->db->execute();
    }    

    public function eliminarTiemposIncidencia($idIncidencia)
    {
        $this->db->query("DELETE FROM incidenciastiempos WHERE idincidencia = '$idIncidencia' ");

        $this->db->execute();
    }

    public function obtenerIdsTodosLosUsuariosAdminActivos()
    {
        $this->db->query("SELECT usu.id FROM usuarios usu WHERE usu.rol=0 AND usu.activo=1");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function insertarAlertaComentarioNuevo($idComentario,$idIncidencia,$iddestinatario,$idUsuario)
    {        
        $this->db->query("INSERT INTO comentariosnoleidos (idcomentario, idincidencia, iddestinatario, idusuario) 
                        VALUES ('$idComentario', $idIncidencia, $iddestinatario, $idUsuario)");

        $this->db->execute();
    }

    public function contarComentariosNoLeidosPorUsuario($idUsuario)
    {
        $this->db->query("SELECT COUNT(*) AS contador FROM comentariosnoleidos com WHERE com.iddestinatario='$idUsuario'");
        $resultado = $this->db->registro();
        return $resultado->contador;
    }

    public function comentariosNoLeidosPorUsuario($idUsuario)
    {
        $this->db->query("SELECT com.id AS idalerta, com.idincidencia, 
                        DATE_FORMAT(com.fechacreacion,'%d/%m/%Y %H:%i:%s', 'es_ES') AS creacion, 
                        CONCAT(usu.nombre,' ',usu.apellidos) AS nombreusuario
                        FROM comentariosnoleidos com 
                        LEFT JOIN usuarios usu ON com.idusuario=usu.id
                        WHERE com.iddestinatario='$idUsuario' ");

        $resultado = $this->db->registros();
        return $resultado;
    }

    public function eliminarRegistroComentarioNoLeido($idAlertaComentario)
    {
        $this->db->query("DELETE FROM comentariosnoleidos WHERE id = '$idAlertaComentario' ");

        if($this->db->execute()){
            return 1;
        }else{
            return 0;
        }
    }

    public function obtenerEstadosFacturacionPresupuesto()
    {
        $this->db->query("SELECT * FROM estadosfactppto ");

        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerEstadoPresupuestoFacturacion($idIncidencia)
    {
        $this->db->query("SELECT inc.estadofactppto, inc.nomestadofactppto FROM incidencias inc WHERE inc.id='$idIncidencia' ");            
        $fila = $this->db->registro();
        return $fila;        
    }

    public function obtenerTodosLosEstadoPresupuestoFacturacion()
    {
        $this->db->query("SELECT * FROM estadosfactppto");            
        $filas = $this->db->registros();
        return $filas;
    }

    public function obtenerEstadosParaTecnico()
    {
        $this->db->query("SELECT * FROM estadosfactppto where  id IN (1,2)");            
        $filas = $this->db->registros();
        return $filas;
    }

    public function obtenerNombreEstadoPresupuestoFacturacionPorIdEstado($idEstado)
    {
        $this->db->query("SELECT * FROM estadosfactppto WHERE id = '$idEstado' ");
        $fila = $this->db->registro();
        return $fila->estado;
    }

    public function actualizarEstadoFacturaPresupuesto($idIncidencia, $idEstado, $nombreEstado)
    {   
        $this->db->query("UPDATE incidencias 
                        SET estadofactppto = '$idEstado', nomestadofactppto = '$nombreEstado'
                        WHERE id = '$idIncidencia' ");
        
        $this->db->execute();
    }

    
    public function insertarDatosAHistorialEstadosFacturarPresupuestar($idIncidencia, $idEstado, $idusuario, $comentParaFacturador)
    {
        $this->db->query("INSERT INTO facturarpresupuestar (idincidencia, idestadofactppto, idusuario, comentario) 
                        VALUES (:idincidencia, :idestadofactppto, :idusuario, :comentario)");

        $this->db->bind(':idincidencia', $idIncidencia);                        
        $this->db->bind(':idestadofactppto', $idEstado);    
        $this->db->bind(':idusuario', $idusuario);    
        $this->db->bind(':comentario', $comentParaFacturador);    

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }  
    }

    public function obtenerPresupuestosParaIncidencia($idIncidencia)
    {
        $this->db->query("SELECT pre.*, CONCAT(usu.nombre,' ' ,usu.apellidos) AS nombreusuario, 
                        DATE_FORMAT(pre.fechacreacion,'%d/%m/%Y %H:%i:%s', 'es_ES') AS creacion
                        FROM incidenciaspresupuestos pre 
                        LEFT JOIN usuarios usu ON pre.idusuario=usu.id
                        WHERE pre.idincidencia='$idIncidencia' ");
        $filas = $this->db->registros();
        return $filas;
    }

    public function obtenerComentariosFacturarPresupuestar($idIncidencia)
    {
        $this->db->query("SELECT fp.*, CONCAT(usu.nombre,' ',usu.apellidos) AS remitente,
                        DATE_FORMAT(fp.fechacreacion,'%d/%m/%Y %H:%i:%s', 'es_ES') AS fecha, fac.estado  
                        FROM facturarpresupuestar fp 
                        LEFT JOIN usuarios usu ON fp.idusuario = usu.id
                        LEFT JOIN estadosfactppto fac ON fp.idestadofactppto=fac.id
                        WHERE fp.idincidencia= '$idIncidencia'
                        ORDER BY fp.fechacreacion DESC");            
        $filas = $this->db->registros();
        return $filas;
    }

    public function obtenerFatosIncidencia($idIncidencia)
    {
        $this->db->query("SELECT * FROM incidencias WHERE id='$idIncidencia' ");            
        $fila = $this->db->registro();
        return $fila;        
    }

    public function actualizarCampoIdFacturaEnIncidencia($idIncidencia, $idFactura)
    {      

        $this->db->query("UPDATE incidencias 
                        SET idfactura = '$idFactura'
                        WHERE id = '$idIncidencia' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }      

    }

    public function verificaSiIncidenciaTieneFacturaAsociada($idIncidencia)
    {
        $this->db->query("SELECT idfactura FROM incidencias WHERE id='$idIncidencia' ");
        $fila = $this->db->registro();
        return (isset($fila->idfactura) && $fila->idfactura > 0)? $fila->idfactura: 0; 
    }

    public function idEstadoFacturaPresupuesto($idIncidencia)
    {
        $this->db->query("SELECT estadofactppto FROM incidencias WHERE id='$idIncidencia' ");
        $fila = $this->db->registro();
        return (isset($fila->estadofactppto) && $fila->estadofactppto > 0)? $fila->estadofactppto: 0; 
    }

    public function insertarDatosFicheroTrabajoTerminado($idComentario, $fichero, $idIncidencia, $base64)
    {
        $nombre = $fichero['nombre'];
        $tipo = $fichero['tipo'];
        $tamanio = $fichero['tamanio'];
         
        $this->db->query("INSERT INTO incterminadasficheros (idcomentario, idincidencia, tipo, nombre, tamanio, base) 
                        VALUES ($idComentario, $idIncidencia, '$tipo', '$nombre', '$tamanio', '$base64')");
        
        $this->db->execute();            
    }    

    public function obtenerTodosLosFicherosDeUnComentario($idComentario)
    {
        $this->db->query("SELECT *
                        FROM incterminadasficheros                         
                        WHERE idcomentario = $idComentario");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerTodosLosFicherosDeUnIncidencia($idIncidencia)
    {
        $this->db->query("SELECT *
                        FROM incterminadasficheros                         
                        WHERE idincidencia = $idIncidencia");
        $resultado = $this->db->registros();
        return $resultado;
    }


    public function actualizarCampoIncidencia($idIncidencia, $field, $value)
    {      
        $this->db->query("UPDATE incidencias 
                        SET  $field = '$value'
                        WHERE id = '$idIncidencia' ");                
        return $this->db->execute();
    }

    public function actualizarIdEquipoEnIncidenciasComentarios($idIncidencia, $value)
    {
        $this->db->query("UPDATE incidenciascomentarios
                        SET  idequipo = '$value'
                        WHERE idincidencia = '$idIncidencia' ");                
        return $this->db->execute();
    }

    public function actualizarIdEquipoEnIncidenciasTiempos($idIncidencia, $value)
    {
        $this->db->query("UPDATE incidenciastiempos
                        SET  idequipo = '$value'
                        WHERE idincidencia = '$idIncidencia' ");                
        return $this->db->execute();
    }

    
    public function obtenerEstadoYFirma($idIncidencia)
    {
        $this->db->query("SELECT firma, guardada FROM incidencias WHERE id= '$idIncidencia' ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function eliminarComentarioByIdComentario($idComentario)
    {
        $this->db->query("DELETE FROM incidenciascomentarios WHERE id = '$idComentario' ");
        if(!$this->db->execute()){
            return false;
        }
        return true;
        
    }    
    
    public function eliminarFicheroComentario($id)
    {
        $this->db->query("DELETE FROM incterminadasficheros WHERE id = '$id' ");
        if(!$this->db->execute()){
            return false;
        }
        return true;        
    }

    public function eliminarFicheroIncidencia($idFichero)
    {
        $this->db->query("DELETE FROM incidenciasficheros WHERE id = '$idFichero' ");
        if(!$this->db->execute()){
            return false;
        }
        return true;           
    }   

    public function actualizarIdClienteEnIncidenciasTiempos($idIncidencia, $value)
    {
        $this->db->query("UPDATE incidenciastiempos
                        SET  idcliente = '$value'
                        WHERE idincidencia = '$idIncidencia' ");                
        return $this->db->execute();
    }

    public function actualizarFechaHoraIncidencia($idIncidencia, $value)
    {
        $this->db->query("UPDATE incidencias
                        SET  fechahora = '$value'
                        WHERE id = '$idIncidencia' ");                
        return $this->db->execute();
    }

}