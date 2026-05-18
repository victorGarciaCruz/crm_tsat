<?php


class ModeloClientes{

    private $db;


    public function __construct(){
        $this->db = new Base;
    }


    public function obtenerClientes(){
        $this->db->query("SELECT id, nombre as 'Razón Social', cif as 'CIF', IF(activo=1,'Activo','') as Estado
                        FROM clientes 
                        WHERE activo =1
                        ORDER BY id DESC");

        $resultado = $this->db->registros();

        return $resultado;
    } 

    
    public function obtenerClientesTablaClass($filas,$orden,$tipoOrden,$filaspagina){
        $this->db->query("SELECT id as 'Nº', nombre as 'Razón Social', nombrecomercial as 'Nom.Comercial', cif as 'CIF',
                        poblacion as 'Población', provincia as 'Provincia'
                        FROM clientes 
                        WHERE activo =1
                        order by " . $orden . " " . $tipoOrden . " limit $filaspagina,$filas ");

        $resultado = $this->db->registros();

        return $resultado;
    }

    public function obtenerClientesTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond){
        $this->db->query("SELECT id as 'Nº', nombre as 'Razón Social', nombrecomercial as 'Nom.Comercial', cif as 'CIF',
                        poblacion as 'Población', provincia as 'Provincia'
                        FROM clientes 
                        WHERE activo =1  $cond
                        order by " . $orden . " " . $tipoOrden . " limit $filaspagina,$filas ");

        $resultado = $this->db->registros();

        return $resultado;
    }

    public function totalRegistrosClientes()
    {
        $this->db->query("SELECT count(id) AS contador 
                        FROM clientes 
                        WHERE activo =1 ");
        $fila = $this->db->registro();
        return $fila;
    }

    public function totalRegistrosClientesBuscar($cond)
    {
        $this->db->query("SELECT count(id) AS contador
                        FROM clientes 
                        WHERE activo =1  $cond ");

        /* echo"<br><br>this<br>";
        print_r($this);
        die; */
        $fila = $this->db->registro();
        return $fila;
    }
    

    public function insertarDatosClienteNuevo($datos)
    {
        //$nombre = strtoupper($datos['nombre']);
        $nombre = mb_strtoupper($datos['nombre'], 'UTF-8');
        $nombrecomercial = mb_strtoupper($datos['nombrecomercial'], 'UTF-8');
        $cif = $datos['cif'];
        $direccion = $datos['direccion'];
        $poblacion = $datos['poblacion'];
        $provincia = $datos['provincia'];
        $codigopostal = $datos['codigopostal'];
        $activo = 1;
        $creacion = date('Y-m-d');
        $tecnicos = json_encode($datos['idstecnicos']);
        //$contactos = json_encode($datos['contactos']);
        $contactos = json_encode($datos['contactos'], JSON_UNESCAPED_UNICODE);
        $observaciones = $datos['observaciones'];
        $cuentasBancarias = json_encode($datos['cuentasBancarias']);

        $this->db->query("INSERT INTO clientes (nombre,nombrecomercial,cif,direccion,poblacion,provincia,codigopostal,activo,creacion, tecnicos,contactos,observaciones,cuentas) 
                        VALUES ('$nombre','$nombrecomercial','$cif','$direccion','$poblacion','$provincia','$codigopostal','$activo','$creacion', '$tecnicos','$contactos', '$observaciones','$cuentasBancarias')");


        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }

    public function detalleClientePorId($id){

        $this->db->query("SELECT * FROM clientes WHERE id = $id ");

        $resultado = $this->db->registro();

        return $resultado;
    }

    public function eliminarCliente($id)
    {
        $this->db->query("UPDATE clientes SET activo = -1 WHERE id = $id ");
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function actualizarDatosClienteNuevo($datos)
    {         
        //$nombre = strtoupper($datos['nombre']);
        $nombre = mb_strtoupper($datos['nombre'], 'UTF-8');
        $nombrecomercial = mb_strtoupper($datos['nombrecomercial'], 'UTF-8');
        $cif = $datos['cif'];
        $direccion = $datos['direccion'];
        $poblacion = $datos['poblacion'];
        $provincia = $datos['provincia'];
        $codigopostal = $datos['codigopostal'];        
        $id = $datos['id'];
        $tecnicos = json_encode($datos['idstecnicos']);
        //$contactos = json_encode($datos['contactos']);
        $contactos = json_encode($datos['contactos'], JSON_UNESCAPED_UNICODE);
        $observaciones = $datos['observaciones'];
        $cuentasBancarias = json_encode($datos['cuentasBancarias']);

        $this->db->query("UPDATE clientes 
                        SET nombre = '$nombre', cif = '$cif',direccion = '$direccion',poblacion = '$poblacion',
                        provincia = '$provincia',codigopostal = '$codigopostal', tecnicos = '$tecnicos', contactos = '$contactos',  observaciones = '$observaciones',  cuentas = '$cuentasBancarias', nombrecomercial = '$nombrecomercial' 
                        WHERE id = $id ");
        
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function nombreTecnicoPorId($idTecnico)
    {        
        $this->db->query("SELECT * FROM usuarios WHERE id = $idTecnico ");        
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function obtenerListaTecnicos()
    {        
        $this->db->query("SELECT * FROM usuarios WHERE rol=2 AND activo = 1 ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function obtenerSucursalesPorCliente($id)
    {
        $this->db->query("SELECT * FROM sucursales WHERE activo =1 AND idcliente= '$id' ");
        $resultado = $this->db->registros();
        return $resultado;
    }
    public function obtenerSucursalesActivasPorCliente($id)
    {
        $this->db->query("SELECT * FROM sucursales WHERE activo =1 AND idcliente= '$id' ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    
    public function detalleSucursalPorId($id){

        $this->db->query("SELECT * FROM sucursales WHERE id = $id ");
        $resultado = $this->db->registro();
        return $resultado;
    }
    
    public function insertarDatosSucursalNueva($datos)
    {      
       
        $nombre = $datos['nombreSucursal'];        
        $direccion = $datos['direccionSucursal'];
        $poblacion = $datos['poblacionSucursal'];
        $provincia = $datos['provinciaSucursal'];
        $codigopostal = $datos['codigopostalSucursal'];
        $activo = 1;
        $creacion = date('Y-m-d');        
        //$contactos = json_encode($datos['contactos']);
        $contactos = json_encode($datos['contactos'], JSON_UNESCAPED_UNICODE);
        $idCliente = $datos['idCliente'];

        $this->db->query("INSERT INTO sucursales (idcliente,nombre,direccion,poblacion,provincia,codigopostal,activo,creacion, contactos) 
                        VALUES ('$idCliente','$nombre','$direccion','$poblacion','$provincia','$codigopostal','$activo','$creacion', '$contactos')");
        
        
        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }

    public function actualizarDatosSucursalNueva($datos)
    {        
        $nombre = $datos['nombreSucursal'];  
        $direccion = $datos['direccionSucursal'];
        $poblacion = $datos['poblacionSucursal'];
        $provincia = $datos['provinciaSucursal'];
        $codigopostal = $datos['codigopostalSucursal'];              
        $idSucursal = $datos['idSucursal'];    
        //$contactos = json_encode($datos['contactos']);
        $contactos = json_encode($datos['contactos'], JSON_UNESCAPED_UNICODE);

        $this->db->query("UPDATE sucursales 
                        SET nombre = '$nombre', direccion = '$direccion',poblacion = '$poblacion',
                        provincia = '$provincia',codigopostal = '$codigopostal', contactos = '$contactos' 
                        WHERE id = $idSucursal ");
        
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function eliminarSucursal($idSucursal)
    {
        $this->db->query("UPDATE sucursales SET activo = -1 WHERE id = $idSucursal ");
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function listadoEquiposPorSucursal($idSucursal)
    {
        $this->db->query("SELECT * FROM equipos eq WHERE eq.idsucursal= $idSucursal AND activo=1  ");
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function insertarDatosEquipoNuevo($datos)
    {        
        $idcliente = $datos['idCliente'];
        $idsucursal = $datos['idSucursal'];
        $nombre = $datos['nombreEquipo'];
        $descripcion = $datos['descripcionEquipo'];
        $serie = $datos['serie'];
        $marca = $datos['marca'];
        $ip = trim($datos['ip']);

        $sistemaop = $datos['sistemaop'];
        $antivirus = $datos['antivirus'];
        $versionoffice = $datos['versionoffice'];

        $activo = 1;
        $creacion = date('Y-m-d');                

        $this->db->query("INSERT INTO equipos (idcliente,idsucursal,nombre,descripcion,serie,marca,ip,activo,creacion, sistemaop,antivirus,versionoffice) 
                        VALUES ('$idcliente','$idsucursal','$nombre','$descripcion','$serie','$marca','$ip','$activo','$creacion','$sistemaop','$antivirus','$versionoffice')");
        
        
        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return false;
        }
    }

    public function detalleEquipoPorId($idEquipo)
    {
        $this->db->query("SELECT eq.*, suc.nombre AS nombresucursal
                        FROM equipos eq
                        LEFT JOIN sucursales suc ON eq.idsucursal=suc.id
                        WHERE eq.id = $idEquipo ");
        $resultado = $this->db->registro();
        return $resultado;        
    }

    public function obtenerEquiposTablaClass($filas,$orden,$tipoOrden,$filaspagina,$cond)
    {
        $this->db->query("SELECT eq.id AS 'Nº', eq.nombre AS 'Nombre equipo',                
                        eq.valor AS 'Coste actual',  
                        suc.nombre AS 'Sucursal'
                        FROM equipos eq
                        LEFT JOIN sucursales suc ON eq.idsucursal=suc.id
                        WHERE 1 $cond
                        order by " . $orden . " " . $tipoOrden . " limit $filaspagina,$filas ");
       
        $resultado = $this->db->registros();

        return $resultado;
    }

    public function totalEquiposClientes($cond)
    {
        $this->db->query("SELECT COUNT(*) AS contador
        FROM equipos eq
        LEFT JOIN sucursales suc ON eq.idsucursal=suc.id
        WHERE eq.activo=1 $cond ");
        $fila = $this->db->registro();
        return $fila;
    }

    public function modalidadDePagoPorEquipos($id)
    {
        $this->db->query("SELECT modalidad, valor
                        FROM equipos 
                        WHERE id = '$id' ");
        $fila = $this->db->registro();
        return $fila;
    }
    
    public function buscarMesAnioContratadoPorEquipo($post)
    {        
        $idEquipo = $post['idEquipo'];
        $mes = $post['mes'];
        $anio = $post['anio'];

        $this->db->query("SELECT id FROM modalidadcostefijo 
                        WHERE idequipo='$idEquipo' AND mes='$mes' AND anio='$anio' ");
        $fila = $this->db->registro();
        return $fila;
    }
    
    public function actualizarModalidadPagoEquipo($post, $pk)
    {
        $modalidad = $post['modalidad'];
        $contratado = $post['contratado'];
        $mes = $post['mes'];
        $anio = $post['anio'];

        $this->db->query("UPDATE modalidadcostefijo 
                        SET modalidad = '$modalidad', 
                        valor = '$contratado', mes = '$mes', anio ='$anio'
                        WHERE id = $pk ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }       
    }
    public function insertarModalidadpagoEquipo($post)
    {
        $modalidad = $post['modalidad'];
        $contratado = $post['contratado'];
        $mes = $post['mes'];
        $anio = $post['anio'];
        $idEquipo = $post['idEquipo'];
        $creacion = date('Y-m-d');
        $idCliente = $post['idCliente'];

        $this->db->query("INSERT INTO modalidadcostefijo (idequipo,idcliente,modalidad,valor,mes,anio,creacion) 
                        VALUES ('$idEquipo','$idCliente','$modalidad','$contratado','$mes','$anio','$creacion')");

        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return 0;
        }
    }

    public function actualizarModalidadPagoDefault($post)
    {  
        $modalidad = $post['modalidad'];
        $contratado = $post['contratado'];
        $idEquipo = $post['idEquipo'];

        $this->db->query("UPDATE equipos 
                        SET modalidad = '$modalidad', 
                        valor = '$contratado'
                        WHERE id = '$idEquipo' ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }       
    }

    public function obtenerHistorialModalidadPorEquipo($idEquipo)
    {        
        $this->db->query("SELECT * FROM modalidadcostefijo 
                        WHERE idequipo= '$idEquipo'
                        ORDER BY anio DESC, mes DESC  ");

        $fila = $this->db->registros();
        return $fila;
    }   
    
    public function actualizarDatosEquipo($datos)
    {                
        $idEquipo = $datos['idEquipo'];
        $nombre = $datos['nombreEquipo'];
        $descripcion = $datos['descripcionEquipo'];
        $serie = $datos['serie'];
        $marca = $datos['marca'];
        $ip = $datos['ip'];
        $sistemaop = $datos['sistemaop'];
        $antivirus = $datos['antivirus'];
        $versionoffice = $datos['versionoffice'];
        $usuarios = json_encode($datos['usuarios']);        
        
        $this->db->query("UPDATE equipos 
                        SET nombre='$nombre',descripcion='$descripcion',serie='$serie',marca='$marca',ip='$ip',usuarios='$usuarios',sistemaop='$sistemaop',antivirus='$antivirus',versionoffice='$versionoffice'
                        WHERE id = $idEquipo ");

        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }       
    }

    public function eliminarEquipo($idEquipo)
    {
        $this->db->query("UPDATE equipos SET activo = -1 WHERE id = $idEquipo ");
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function eliminarEquipoDefinitivamente($idEquipo)
    {
        $this->db->query("DELETE FROM equipos WHERE id = $idEquipo ");
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function validarSiEquipoTieneIncidenciasRegistradas($idEquipo)
    {
        $this->db->query("SELECT COUNT(*) AS contador FROM incidencias inc WHERE inc.idequipo='$idEquipo' ");
        $resultado = $this->db->registro();
        return $resultado->contador;
    }

    public function eliminarEquipoAsignadoAUsuarios($idEquipo)
    {
        $this->db->query('UPDATE usuarios usu
                        SET usu.equipos = JSON_REMOVE(usu.equipos, JSON_UNQUOTE(JSON_SEARCH(usu.equipos, "one", '.$idEquipo.')))
                        WHERE JSON_SEARCH(usu.equipos, "one", '.$idEquipo.') IS NOT NULL');

        $this->db->execute();
    }

    public function eliminarEquipoDeTablaMantenimientoEquipos($idEquipo)
    {
        $this->db->query("DELETE FROM modalidadcostefijo WHERE idequipo = '$idEquipo' ");

        $this->db->execute();
    }

    public function obtenerBolsasHorasTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond)
    {
        $this->db->query("SELECT moda.id AS 'Nº', moda.valor AS 'Contratado', moda.preciototal AS 'Euros',
                        IF(moda.mes = 1,'enero', IF(moda.mes = 2,'febrero',IF(moda.mes = 3,'marzo',IF(moda.mes = 4,'abril',IF(moda.mes = 5,'mayo',IF(moda.mes = 6,'junio',IF(moda.mes = 7,'julio',IF(moda.mes = 8,'agosto',IF(moda.mes = 9,'setiembre',IF(moda.mes = 10,'octubre',IF(moda.mes = 11,'noviembre','diciembre')))))))))) ) AS 'Mes', 
                        moda.anio AS 'Año', DATE_FORMAT(moda.creacion, '%d/%m/%Y', 'es_ES') AS 'Creación' 
                        FROM modalidadhoras moda 
                        WHERE 1 $cond
                        order by " . $orden . " " . $tipoOrden . " ,moda.mes DESC limit $filaspagina,$filas ");
   
        $resultado = $this->db->registros();

        return $resultado;
    }
    
    public function totalRegistrosModalidadHorasBuscar($cond)
    {
        $this->db->query("SELECT count(*) AS contador
                        FROM modalidadhoras moda  
                        WHERE 1 $cond ");

        $fila = $this->db->registro();
        return $fila;

    }

    public function insertarBolsaHorasNueva($post,$precioHora)
    {
        $modalidad = $post['modalidad'];
        $contratado = $post['contratado'];
        $contratadoPrecio = $post['contratadoPrecio'];
        $mes = $post['mes'];
        $anio = $post['anio'];
        $idCliente = $post['idCliente'];
        $creacion = date('Y-m-d');

        $this->db->query("INSERT INTO modalidadhoras (idCliente,modalidad,valor,mes,anio,creacion,preciototal,preciohora) 
                        VALUES ('$idCliente','$modalidad','$contratado','$mes','$anio','$creacion','$contratadoPrecio','$precioHora')");

        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return 0;
        }
    }

    public function obtenerDetalleBolsaHorasMes($id)
    {
        $this->db->query("SELECT *
                        FROM modalidadhoras 
                        WHERE id = '$id' ");
        $fila = $this->db->registro();
        return $fila;
    }

    public function actualizarBolsaHorasNueva($post,$precioHora)
    {        
        $valor = $post['contratado'];
        $contratadoEuros = $post['contratadoEuros'];
        $mes= $post['mes'];
        $anio= $post['anio'];
        $idBolsa = $post['idBolsaMes'];
        
        $this->db->query("UPDATE modalidadhoras
                        SET valor='$valor',mes='$mes',anio='$anio', preciototal='$contratadoEuros', preciohora ='$precioHora'
                        WHERE id = $idBolsa ");
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function usuariosActivosTipoSupervisorYUsuarioPorIdCliente($idCliente)
    {
        $this->db->query("SELECT usu.id, usu.nombre, usu.apellidos 
                         FROM usuarios usu WHERE usu.idcliente= '$idCliente' AND usu.activo=1 AND (usu.clientetipo= 'usuario' OR usu.clientetipo='supervisor') ");
                      
        $filas = $this->db->registros();
        return $filas;
    }

    public function datosUsuarioPorId($id)
    {        
        $this->db->query("SELECT usu.id, usu.nombre, usu.apellidos, usu.correo, usu.clientetipo
                        FROM usuarios usu
                        WHERE usu.id = $id ");
        $resultado = $this->db->registro();
        return $resultado;
    }
   
    public function crearUsuarioNuevos($key,$jsonEquipo,$idCliente,$jsonSucursal){                                  

        $this->db->query("INSERT INTO usuarios (nombre,apellidos,rol,correo,contra,estado, cambiar,idcliente,clientetipo, equipos,idsucursal) 
                        VALUES (:nombre, :apellidos, :rol,:correo,:contra,:estado,:cambiar,:idcliente,:clientetipo,:equipos, :sucursales )");

        $this->db->bind(':nombre', $key['nombre']);
        $this->db->bind(':apellidos', $key['apellido']);
        $this->db->bind(':rol', 1); //es cliente por defecto
        $this->db->bind(':correo', $key['email']);
        $this->db->bind(':contra', 'user'); //password por defecto
        $this->db->bind(':estado', 1); //activo
        $this->db->bind(':cambiar', 1); //por defecto debe cambiar la contraseña
        $this->db->bind(':idcliente', $idCliente);
        $this->db->bind(':clientetipo', $key['tipo']);        
        $this->db->bind(':equipos', $jsonEquipo);
        $this->db->bind(':sucursales', $jsonSucursal);
        
        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return 0;
        }
    }

    public function agregarEquipoEnTablaUsuario($idUsuario,$idEquipo)
    {
        $this->db->query('UPDATE usuarios usu
                        SET usu.equipos = JSON_INSERT(usu.equipos,"$[1000]", "'.$idEquipo.'")  
                        WHERE usu.id ='. $idUsuario);                        
        $this->db->execute();        
    }

    public function agregarSucursalEnTablaUsuario($idUsuario,$idSucursal)
    {
        $this->db->query('UPDATE usuarios usu
                        SET usu.idsucursal = JSON_INSERT(usu.idsucursal,"$[1000]", "'.$idSucursal.'")  
                        WHERE usu.id ='. $idUsuario);                        
        $this->db->execute();        
    }

    public function verificarSiUsuarioTieneLaSucursalAsignada($idUsuario,$idSucursal)
    {
        $this->db->query('SELECT count(*) as contador FROM usuarios usu 
                        WHERE JSON_SEARCH(usu.idsucursal, "one", "'.$idSucursal.'") IS NOT NULL  AND usu.id='.$idUsuario);
                    
        $resultado = $this->db->registro();
        return $resultado->contador;
    }
 
    public function obtenerUsuariosAsignadosAEquipoPorIdEquipo($idEquipo)
    {        
        $this->db->query('SELECT * FROM usuarios usu 
                        WHERE JSON_SEARCH(usu.equipos, "one", "'.$idEquipo.'") IS NOT NULL  ');
                    
        $resultado = $this->db->registros();
        return $resultado;
    }


    public function obtenerIdTecnicoDesdeCodigoTecnico($codigoTecnico)
    {
        $this->db->query("SELECT id FROM usuarios WHERE codigotecnico ='$codigoTecnico' ");
        $resultado = $this->db->registro();
        return $resultado->id;
    }

    public function aniosConIncidencias()
    {
        $this->db->query("SELECT DISTINCT(YEAR(creacion)) as anio FROM incidencias ");
        $filas = $this->db->registros();
        return $filas;
    }

    public function borraPreciosAsignadoEquipo($datos)
    {
        $idequipo = $datos['idEquipo'];
        $mes = $datos['mes'];
        $anio = $datos['anio'];

        $this->db->query("DELETE FROM modalidadcostefijo WHERE idequipo = '$idequipo' AND mes = '$mes' AND anio = '$anio' ");
        $this->db->execute();  
    }

    public function borrarBolsaHorasMes($datos)
    {
        $idcliente = $datos['idCliente'];
        $mes = $datos['mes'];
        $anio = $datos['anio'];

        $this->db->query("DELETE FROM modalidadhoras WHERE idcliente = '$idcliente' AND mes = '$mes' AND anio = '$anio' ");
        $this->db->execute(); 

    }

    
    public function tienePermisoParaEditarClientes($idUsuario)
    {
        $this->db->query("SELECT editarclientes
        FROM usuarios
        WHERE rol =2 AND id = '$idUsuario' ");

        $resultado = $this->db->registro();
        return $resultado->editarclientes;
    }

    public function permisosTecnicosClientes($idUsuario)
    {
        $this->db->query("SELECT editarclientes, verclientes
        FROM usuarios
        WHERE rol =2 AND id = '$idUsuario' ");

        $resultado = $this->db->registro();
        return $resultado;
    }

    public function eliminarBolsaHoras($idBolsaHoras)
    {       
        $this->db->query("DELETE FROM modalidadhoras WHERE id = '$idBolsaHoras' ");
        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function obtenerClientesSelect()
    {
        
        $this->db->query("SELECT id, nombre FROM clientes WHERE activo =1 ORDER BY nombre ASC ");

        $resultados = $this->db->registros();

        return $resultados;
    }

    
    public function obtenerNombreClientePorId($id)
    {        
        $this->db->query("SELECT nombre FROM clientes WHERE id = '$id' ");
        $resultado = $this->db->registro();
        return $resultado->nombre;
    }

    
    public function obtenerDatosClientePorId($id)
    {        
        $this->db->query("SELECT * FROM clientes WHERE id = '$id' ");
        $resultado = $this->db->registro();
        return $resultado;
    }

    public function obtenerContactosCliente($idCliente)
    {
        $this->db->query("SELECT contactos FROM clientes WHERE id = '$idCliente' ");
        $resultado = $this->db->registro();
        return (isset($resultado->contactos))? $resultado->contactos: false;
    }

    public function actualizarCifCliente($idCliente, $cif)
    {                 
        $this->db->query("UPDATE clientes 
                        SET cif = '$cif'
                        WHERE id = $idCliente ");
        
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function buscarClientesConLike($like)
    {
        $this->db->query("SELECT id, nombre, cif, cuentas FROM clientes WHERE activo=1 AND nombre LIKE". $like);
       
        $resultados = $this->db->registros();
        return $resultados;        
    }

    
    public function buscarClientesConLikeYNombreComercial($like)
    {
        $this->db->query("SELECT id, CONCAT(nombre, ' ', nombrecomercial) AS nombre, cif, cuentas 
                        FROM clientes 
                        WHERE activo=1 
                        AND (nombre LIKE $like OR nombrecomercial LIKE $like)
                        ");
       
        $resultados = $this->db->registros();
        return $resultados;        
    }

    public function obtenerPrimeros100Clientes()
    {
        $this->db->query("SELECT id, nombre, cif FROM clientes WHERE activo=1 ORDER BY nombre LIMIT 100");
        return $this->db->registros();
    }

    public function obtenerPrimeros100ClientesConNombreComercial()
    {
        $this->db->query("SELECT id, CONCAT(nombre, ' ', nombrecomercial) AS nombre,  cif FROM clientes WHERE activo=1 ORDER BY nombre LIMIT 100");
        return $this->db->registros();
    }

    
    public function insertarDatosFicheroEquipo($fichero, $idEquipo)
    {
        $nombre = $fichero['nombre'];
        $tipo = $fichero['tipo'];
        $tamanio = $fichero['tamanio'];
         
        $this->db->query("INSERT INTO equiposficheros (idequipo, tipo, nombre, tamanio) 
                        VALUES ($idEquipo, '$tipo', '$nombre', '$tamanio')");
        
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }           
    }

    public function obtenerImagenesEquipo($idEquipo)
    {
        $this->db->query("SELECT * FROM equiposficheros WHERE idequipo = '$idEquipo' ");
       
        $resultados = $this->db->registros();
        return $resultados; 
    }

    
    public function obtenerDatosImagenEquipo($idImagen)
    {
        $this->db->query("SELECT * FROM equiposficheros WHERE id = '$idImagen' ");
       
        $resultado = $this->db->registro();
        return $resultado; 
    }

    public function eliminarFilaImagenEquipo($idimagen)
    {
        $this->db->query("DELETE FROM equiposficheros WHERE id = '$idimagen' ");
        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function obtnerImagenEquiposDesdeIdFichero($idFichero)
    {
        $this->db->query("SELECT nombre FROM equiposficheros WHERE id = '$idFichero' ");
       
        $resultado = $this->db->registro();
        return (isset($resultado->nombre) && $resultado->nombre != null)? $resultado->nombre: ''; 
    }

    //nuevos
    public function modalidadesMantenimientoEquipos()
    {
        $this->db->query("SELECT * FROM modalidadesmantto WHERE activo = '1' ");
       
        $resultado = $this->db->registros();
        return $resultado;
    }
    
    public function crearModalidadMntto($post)
    {
        date_default_timezone_set("Europe/Madrid");
        $modalidad = $post['modalidad'];
        $contratado = $post['contratado'];
        $fechaInicio = $post['fechaInicio'];       
        $idEquipo = $post['idEquipo'];
        $comentarios = $post['comentarios'];
        $creacion = date('Y-m-d');
        $idCliente = $post['idCliente'];

        $this->db->query("INSERT INTO modalidadesmanttoequipo (idequipo,idcliente,modalidad,contratado,fechainicio,comentarios,creacion) 
                        VALUES ('$idEquipo','$idCliente','$modalidad','$contratado','$fechaInicio','$comentarios','$creacion')");

        if($this->db->execute()){
            return $this->db->lastInsertId();
        } else {
            return 0;
        }
    }

    public function obtenerHistoriaContratosMnttoEquipos($idEquipo)
    {
        $this->db->query("SELECT m.*, (SELECT mm.modalidad FROM modalidadesmantto mm WHERE mm.id=m.modalidad) AS nommodalidad FROM modalidadesmanttoequipo m
        WHERE m.idequipo = '$idEquipo' ORDER BY m.fechainicio DESC ");       
        $resultado = $this->db->registros();
        return $resultado;
    }

    public function eliminarContratoMnttoEquipo($idMod)
    {
        $this->db->query("DELETE FROM modalidadesmanttoequipo WHERE id = '$idMod' ");
        if ($this->db->execute()) {
            return 1;
        }else{
            return 0;
        }
    }

    public function comentarioContratoMantenimientoEquipo($idEquipo)
    {
        $this->db->query("SELECT comentarios FROM equipos WHERE id = '$idEquipo' ");
        $resultado = $this->db->registro();
        return $resultado;
    }
    public function actualizarComentariosEquipo($idEquipo, $comentarios)
    {
        $this->db->query("UPDATE equipos SET comentarios = '$comentarios' WHERE id = '$idEquipo' ");
       
        if($this->db->execute()){
            return 1;
        }else {
            return 0;
        }
    }

    public function buscarEquipoSucursalPorTexto($idSucursal, $searchTerm)
    {        
        $this->db->query("SELECT id, nombre
                        FROM equipos                        
                        WHERE idsucursal = :idsucursal AND activo=1 AND nombre LIKE :searchTerm"); 

        $this->db->bind(':idsucursal', $idSucursal);
        $this->db->bind(':searchTerm', '%' . $searchTerm . '%');
        $resultado = $this->db->registros();
        return $resultado ? $resultado : [];
    }

    public function obtenerPrimeros20EquiposSucursal($idSucursal)
    {
        
        $this->db->query("SELECT id, nombre
                        FROM equipos                        
                        WHERE idsucursal = :idsucursal AND activo=1 
                        ORDER BY nombre 
                        LIMIT 20"); 
        $this->db->bind(':idsucursal', $idSucursal);
        $resultado = $this->db->registros();
        return $resultado ? $resultado : [];
    }



    //nuevo    
    public function buscarCuentasBancariasCliente($idCliente)
    {
        $this->db->query("SELECT cuentas FROM clientes WHERE id=".$idCliente);
       
        $resultado = $this->db->registro();
        if (isset($resultado->cuentas)) {
            return json_decode($resultado->cuentas);
        }
        
        return [];
    }

}