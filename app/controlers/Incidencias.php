<?php

class Incidencias extends Controlador {

    private $fetch;

    public function __construct() {
        
        session_start();
        $this->controlPermisos();
        $this->ModelIncidencias = $this->modelo('ModeloIncidencias');
        $this->ModelFacturasDetalleCliente = $this->modelo('ModeloFacturasDetalleCliente');
        $this->ModelProductos = $this->modelo('ModeloProductos');        
        $this->ModelTiposIva = $this->modelo('ModeloTiposIva');   
        $this->ModelFacturasCliente = $this->modelo('ModeloFacturasCliente');
        $this->ModelClientes = $this->modelo('ModeloClientes');
        $this->modeloBase = $this->modelo('ModeloBase');
        $this->arrFieldsEmailSent = ['iddoc','fecha','tipodoc','nomfichero','destinatarios','asunto','mensaje','correoremitente','nomremitente']; 
        
        if(file_get_contents("php://input")){
            $payload = file_get_contents("php://input");    
            $this->fetch = json_decode($payload, true);
        } 
    
    }

    public function index() {
        $datos = [];
        $modalidadestecnicos = $this->ModelIncidencias->listaModalidadesTecnicos();
        $tecnicos = $this->ModelIncidencias->listaTecnicosActivos();
        $idCliente = $this->ModelIncidencias->obtenerIdClientePorIdusuario($_SESSION['idusuario']);
        $contratadoBolsaHoras = $this->ModelIncidencias->clienteTieneContratadoBolsaHoras($idCliente);
        $clienteTipo = $this->ModelIncidencias->obtenerTipoClientePorid($_SESSION['idusuario']);        

        if ($_SESSION['nombrerol'] == 'cliente' ) {  
            $datos = [ 
                'contratadoBolsaHoras' => $contratadoBolsaHoras,
                'clienteTipo' => $clienteTipo
            ];             
            $this->vista('incidencias/listadoIncidencias',$datos);

        }else if($_SESSION['nombrerol'] == 'tecnico'){      

            $permiso = $this->ModelIncidencias->tienePermisoVerTodasLasIncidencias($_SESSION['idusuario']);
            $datos = [ 
                'modalidadestecnicos' => $modalidadestecnicos,
                'permiso' => $permiso,
                'tecnicos' => $tecnicos               
            ];
            $this->vista('incidencias/listadoIncidenciasTecnico',$datos);

        }else if($_SESSION['nombrerol'] == 'admin'){
            $datos = [ 
                'modalidadestecnicos' => $modalidadestecnicos,
                'tecnicos' => $tecnicos
            ];
            $this->vista('incidencias/listadoIncidenciasAdmin',$datos);
        }

    }

    
    public function crearTablaIncidencias()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];                
            $ordenMultiple = isset($_POST['ordenMultiple']) ? $_POST['ordenMultiple'] : '';
        }

        $idUsuario = $_SESSION['idusuario'];
        $rol = $_SESSION['nombrerol'];        
        
        $cond = '';
        $idCliente = $this->ModelIncidencias->obtenerIdClientePorIdusuario($idUsuario);
        $tipo = $this->ModelIncidencias->obtenerTipoClientePorid($idUsuario);

        if ($rol == 'cliente' && $tipo == 'administrador') {            
            $cond = " AND inc.idcliente = '$idCliente' ";
        }else if ($rol == 'cliente' && ($tipo == 'supervisor' || $tipo == 'usuario')){
            $equiposAsignados = $this->construirCadenaEquiposAsignados($idUsuario);
            $cond = " AND inc.idcliente = '$idCliente' AND inc.idequipo $equiposAsignados ";
        }

        $filaspagina = $filas * $pagina;
    
        if ($buscar != "") {            
            $datos = json_decode($buscar);            
            $cond .= $this->construirCondicionesBuscar($datos);   
        }
        // Si se recibió ordenMultiple (JSON codificado), delegar al nuevo método que aplica ORDER BY con múltiples criterios
        if (!empty($ordenMultiple)) {
            // $ordenMultiple viene URL-encoded desde JS; intentar decodificar si es necesario
            $ordenMultipleRaw = urldecode($ordenMultiple);
            $incidencias = $this->ModelIncidencias->obtenerIncidenciasConOrdenMultiple($filas, $ordenMultipleRaw, $filaspagina, $cond);
        } else {
            $incidencias = $this->ModelIncidencias->obtenerIncidenciasTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);
        }
        print(json_encode($incidencias));  
    }    

    public function construirCadenaEquiposAsignados($idUsuario)
    {
        $jsonEquiposAsignados = $this->ModelIncidencias->obtenerEquiposAsignadosASupervisorOUsuario($idUsuario);
        $arrEquipos = json_decode($jsonEquiposAsignados);
        $cadena = ' IN ( ';
        for ($i=0; $i <count($arrEquipos) ; $i++) {
            if ($i != (count($arrEquipos)-1)) {
                $cadena .=  $arrEquipos[$i] . ",";
            } else {
                $cadena .=  $arrEquipos[$i] . " ) ";
            }
        }
       return $cadena;
    }

    public function totalRegistrosIncidencias()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            
        }
        $idUsuario = $_SESSION['idusuario'];
        $rol = $_SESSION['nombrerol'];

        $cond = '';
        if ($rol == 'cliente') {
            $idCliente = $this->ModelIncidencias->obtenerIdClientePorIdusuario($idUsuario);
            $cond = " AND inc.idcliente = '$idCliente' ";
        }  
    
        if ($buscar != "") {            
            $datos = json_decode($buscar);
            $cond .= $this->construirCondicionesBuscar($datos);           
        }
        $contador = $this->ModelIncidencias->totalRegistrosIncidenciasBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }  

    private function mapearCampoOrdenTecnicoMultiple($campoVisible) {
        $mapa = [
            'Nº'       => 'inc.id',
            'Creación' => "DATE_FORMAT(inc.creacion, '%Y/%m/%d')",      //'se formatea la fecha 
            'Usuario'  => "CONCAT(usu.nombre, ' ', usu.apellidos)",
            'Cliente'  => "CONCAT(cli.nombre, ' ', cli.nombrecomercial)",
            'Sucursal' => 'suc.nombre',
            'Equipo'   => 'equ.nombre',
            'Estado'   => 'inc.estado',                        
            'Técnicos' => 'inc.nombrestecnicos',
            'Atención' => 'inc.play',                           
            'Agendado' => "DATE_FORMAT(inc.fechahora, '%Y/%m/%d')"     //'se formatea la fecha                   
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByTecnicos($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    // Usamos el mapeo especial para orden múltiple
                    $campoSQL = $this->mapearCampoOrdenTecnicoMultiple($campoVisible);
                    $sentencias[] = "$campoSQL $direccion";
                }
                return implode(", ", $sentencias);
            }
        }

        // 2. Si no hay orden múltiple válido, usar el orden simple (el tradicional)
        if (!empty($ordenSimple)) {
            return $ordenSimple;
        }

        return "";
    }



    public function crearTablaIncidenciasTecnicos()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            //$orden = $_POST['orden'];
            //$tipoOrden = $_POST['tipoOrden'];                
            // Nuevos parámetros para orden múltiple
            $ordenMultiple = isset($_POST['ordenMultiple']) ? urldecode($_POST['ordenMultiple']) : '';
            $ordenSimple   = isset($_POST['orden']) ? $_POST['orden'] : '';
            $tipoSimple    = isset($_POST['tipoOrden']) ? $_POST['tipoOrden'] : '';
        }

        $idTecnico = $_SESSION['idusuario'];
        $rol = $_SESSION['nombrerol'];

        $cond = '';
        if ($rol == 'tecnico') {           
            $cond = " AND JSON_SEARCH(inc.tecnicos, 'one', '". $idTecnico ."') IS NOT NULL ";
        }

        $filaspagina = $filas * $pagina;
    
        if ($buscar != "") {                           
            $datos = json_decode($buscar);            
            $cond .= $this->construirCondicionesBuscar($datos);           
        }

        // Construir la cláusula ORDER BY
        $clausulaOrder = $this->construirClausulaOrderByTecnicos($ordenMultiple, $ordenSimple, $tipoSimple);
         // Llamar al modelo pasando la cláusula completa
        $incidencias = $this->ModelIncidencias->incidenciasTecnicosTablaClassBuscar(
            $filas,
            $clausulaOrder,
            $filaspagina,
            $tipoSimple,
            $cond
        );     
        print(json_encode($incidencias));  
    }    

    public function construirCondicionesBuscar($datos)
    {
        $tamanio = count((array) $datos);
        $cond = '';
        if ($tamanio > 0) {                                
            $cont = 0;
            $cond = " AND  (";
            foreach ($datos as $key => $value) {

                $cont++;                   
                
                if ($cont < ($tamanio) ) {                    
                    $y =  " LIKE " . "'%$value%'" . " AND ";
                } else {                    
                    $y =  " LIKE " . "'%$value%'" . ") ";
                }
                if ($key == 'Nº') {
                    $cond .= "inc.id" . $y;
                }
                if ($key == 'Creación') {
                        
                    $fechaEstandar = " DATE_FORMAT( inc.creacion, '%d/%m/%Y' ) LIKE '%".$value."%' ";
                    
                    if ($cont < ($tamanio) ) {                    
                        $m =  " AND ";
                    } else {                    
                        $m =  " ) ";
                    }

                    $cond .= $fechaEstandar . $m;
                }
                if ($key == 'Usuario') {
                    $cond .= " CONCAT(usu.nombre, ' ', usu.apellidos) " . $y;
                } 
                if ($key == 'Cliente') {
                    //$cond .= "cli.nombre" . $y;                    
                    $cond .= " CONCAT(cli.nombre, ' ', cli.nombrecomercial) " . $y;
                }
                if ($key == 'Sucursal') {
                    $cond .= "suc.nombre" . $y;
                }
                if ($key == 'Equipo') {
                    $cond .= "equ.nombre" . $y;
                }                    
                if ($key == 'Estado') {
                    
                    $estados = ["pendiente" => " inc.estado=1 ", 
                                "en curso" => " inc.estado=2 " ,
                                "terminada" => " inc.estado=3 "];
                                            
                    $condEstado = ' ';
                    $numEstados = 0;
                    $arrEstados = [];

                    foreach ($estados as $estado => $parte) {                       

                        $pos = stripos($estado, $value);
                        if ($pos !== false) {                        
                            $numEstados++;
                            $arrEstados[] = $parte;
                        }
                    }     

                    if ($arrEstados && count($arrEstados) >0 ) {

                        $b = 0;
                        foreach ($arrEstados as $est) {
                            $b++;  
                            if ($b < $numEstados) {
                                $condEstado .=  $est . " OR ";
                            } else {
                                $condEstado .=  $est . " ";
                            }   
                        }
                    }

                    if ($cont < ($tamanio) ) {                    
                        $z =  " AND ";
                    } else {                    
                        $z =  " ) ";
                    }

                    $cond .= $condEstado . $z;
                }

                if ($key == 'Técnicos') {
                    $cond .= "inc.nombrestecnicos" . $y;
                }

                if ($key == 'Fact/Ppto') {
                    $cond .= "inc.nomestadofactppto" . $y;
                }
                if ($key == 'Agendado') {
                    $cond .= " DATE_FORMAT(inc.fechahora, '%d-%m-%Y %H:%i') " . $y;
                    
                }
            }                                        
        }

        return $cond;
    }
    
    public function totalRegistrosIncidenciasTecnicos()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];            
        }
 
        $idTecnico = $_SESSION['idusuario'];
        $rol = $_SESSION['nombrerol'];

        $cond = '';
        if ($rol == 'tecnico') {           
            $cond = " AND JSON_SEARCH(inc.tecnicos, 'one', '". $idTecnico ."') IS NOT NULL ";
        }
 
    
        if ($buscar != "") {                           
            $datos = json_decode($buscar);
            $cond .= $this->construirCondicionesBuscar($datos); 
        }
        $contador = $this->ModelIncidencias->totalIncidenciasTecnicoBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }  

    private function mapearCampoOrdenMultiple($campoVisible) {
        $mapa = [
            'Nº'          => 'inc.id',
            'Creación'    => "DATE_FORMAT(inc.creacion, '%Y/%m/%d', 'es_ES')",  
            'Usuario'     => "CONCAT(usu.nombre, ' ', usu.apellidos)",
            'Cliente'     => "CONCAT(cli.nombre, ' ', cli.nombrecomercial)",
            'Sucursal'    => 'suc.nombre',
            'Equipo'      => 'equ.nombre',
            'Estado'      => 'inc.estado',
            'Técnicos'    => 'inc.nombrestecnicos',
            'Fact/Ppto'   => 'inc.nomestadofactppto',
            'Agendado'    => "DATE_FORMAT(inc.fechahora, '%Y/%m/%d', 'es_ES')",
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    public function crearTablaIncidenciasAdmin() {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $ordenMultiple = isset($_POST['ordenMultiple']) ? urldecode($_POST['ordenMultiple']) : '';
            $ordenSimple = isset($_POST['orden']) ? $_POST['orden'] : '';
            $tipoSimple = isset($_POST['tipoOrden']) ? $_POST['tipoOrden'] : '';

            // Construir la cláusula ORDER BY
            $clausulaOrder = $this->construirClausulaOrderBy($ordenMultiple, $ordenSimple, $tipoSimple);

            $cond = '';
            $filaspagina = $filas * $pagina;

            if ($buscar != "") {
                $datos = json_decode($buscar);
                $cond .= $this->construirCondicionesBuscar($datos);
            }

            $incidencias = $this->ModelIncidencias->obtenerIncidenciasConOrdenMultiple($filas, $clausulaOrder, $filaspagina, $tipoSimple, $cond);
            print(json_encode($incidencias));
        }
    } 

    private function construirClausulaOrderBy($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con al menos un criterio), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    // Usamos el mapeo especial para orden múltiple
                    $campoSQL = $this->mapearCampoOrdenMultiple($campoVisible);
                    $sentencias[] = "$campoSQL $direccion";
                }
                return implode(", ", $sentencias);
            }
        }

        // 2. Si no hay orden múltiple, usar el orden simple (puede ser múltiple en el string)
        if (!empty($ordenSimple)) {
            return $ordenSimple;
        }

        return "";
    }

    public function totalRegistrosIncidenciasAdmin()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];            
        }

        $cond = '';
    
        if ($buscar != "") {                           
            $datos = json_decode($buscar);            
            $cond .= $this->construirCondicionesBuscar($datos); 
        }
        $contador = $this->ModelIncidencias->totalIncidenciasTecnicoBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }  
    
    public function crearIncidencia()
    {
        //session_start();

        $datos = [];      
    
        $idUsuario = $_SESSION['idusuario'];
        $tipo = $this->ModelIncidencias->obtenerTipoClientePorid($idUsuario);

        if ($_SESSION['nombrerol'] == 'cliente' && $tipo == 'administrador' ) {        
            $sucursales = $this->ModelIncidencias->obtenerSucursalesDelClienteDesdeIdUsuario($_SESSION['idusuario']);
        }else if($_SESSION['nombrerol'] == 'cliente' && ($tipo == 'usuario' || $tipo == 'supervisor')){
            $sucursales = $this->listarSucursalesParaUsuarioOSupervisor($_SESSION['idusuario']);
          
        }
            
        $datos = [
            'sucursales' => $sucursales
        ];
       
        $this->vista('incidencias/crearNuevaIncidencia', $datos);
    }

    public function listarSucursalesParaUsuarioOSupervisor($idUsuario)
    {
        $sucursalesAsignados = $this->ModelIncidencias->obtenerIdsSucursalesParaUsuarioOSupervisor($idUsuario);
        $arr = json_decode($sucursalesAsignados);
       
        $sucursales = [];
        if ($arr && count($arr)>0 ) {
            foreach ($arr as $key) {
                $sucursal = $this->ModelIncidencias->obtenerSucursalesParaUsuarioOSupervisor($key);
                if ($sucursal) {
                    $sucursales[] = $sucursal;
                    
                }
            }
        }       
        return $sucursales;


    }

    public function llenarSelectorEquiposPorSucursal()
    {
        $retorno = ['options' => ''];

        if (isset($_POST['idSucursal']) && $_POST['idSucursal']>0) {
            
            $idSucursal = $_POST['idSucursal'];
            $equipos = [];
            $clienteTipo = $this->ModelIncidencias->obtenerTipoClientePorid($_SESSION['idusuario']);
                    
            $iconEdit = '';
            if(isset($_POST['edit'])){          
                
                if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) { 
                    $iconEdit = '<i class="ml-2 fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm cursor-pointer edit_field" data-field="equiposTecnico"></i>';    
                }

                $options = '<div class="inline-flex">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Equipo implicado</label>'.$iconEdit.'
                            </div>
                            <select id="equiposTecnico" name="equiposTecnico" class="todos py-2 px-3 rounded-lg border border-gray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">
                                <option disabled selected>Seleccionar</option>';
            }else{
                $options = '<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Equipo(s) implicado(s)</label>
            <select id="equiposTecnico" name="equiposTecnico" class="todos py-2 px-3 rounded-lg border border-gray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" >
                <option disabled selected>Seleccionar</option> ';
            }            

            if ($clienteTipo == 'administrador' || $_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {
                $equipos = $this->ModelIncidencias->obtenerEquiposPorSucursal($_POST['idSucursal']);
                
                if (count($equipos) >0 ) {
                    
                    foreach ($equipos as $equipo) {
                        $options .= "<option value='".$equipo->id."'>".$equipo->nombre."</option>";
                    }
            
                }
            }else if ($clienteTipo == 'supervisor' || $clienteTipo == 'usuario'){
                $jsonEquiposUsuario = $this->ModelIncidencias->obtenerEquiposAsignadosASupervisorOUsuario($_SESSION['idusuario']);
                $arrEquipos = json_decode($jsonEquiposUsuario);
                if (isset($arrEquipos) && count($arrEquipos)>0 ) {                   
                    foreach ($arrEquipos as $key) {
                        $datosEquipo = $this->ModelIncidencias->sucursalDelEquipoPorIdEquipo($key);
                        if ($datosEquipo->idsucursal == $idSucursal) {
                            $options .= "<option value='".$datosEquipo->id."'>".$datosEquipo->nombre."</option>";
                        }
                    }
                }                
            }

            $options .= '</select>';
            
            $retorno = [
                'options' => $options
            ];


        }
        print json_encode($retorno);
    }

    public function registrarIncidencia()
    {  
       
        if ($_POST['sucursal'] && $_POST['equipo'] && $_POST['descripcion'] ) {
            
            $idCliente = $this->ModelIncidencias->obtenerIdClientePorIdusuario($_SESSION['idusuario']);
            $idsTecnicosAsig = $this->ModelIncidencias->obtenerIdsTecnicosAsignados($idCliente);
            $nombresTecnicos = $this->obtenerStringNombresTecnicosAsignados(json_decode($idsTecnicosAsig));
            
            date_default_timezone_set("Europe/Madrid");

            $estadofactppto = 0;
            $nomestadofactppto = 'sin estado';
            if (isset($_POST['presupuestarEnCreacion']) && $_POST['presupuestarEnCreacion'] == 1) {
                $estadofactppto = 2;
                $nomestadofactppto = 'presupuestar';
            }

            $datos = [
                'idsucursal' => $_POST['sucursal'],
                'idequipo' => $_POST['equipo'],
                'descripcion' => $_POST['descripcion'],
                'idusuario' => $_SESSION['idusuario'],
                'idcliente' => $idCliente,
                'creacion' => date("Y-m-d H:i:s"),
                'estado' => 1, //por defecto 1-Pendiente
                'activo' => 1,
                'tecnicos' => $idsTecnicosAsig,
                'nombresTecnicos' => $nombresTecnicos,
                'nombreUsuario' => $_SESSION['usuario'],
                "remoteADDR" => $_SERVER['REMOTE_ADDR'],
                'estadofactppto' => $estadofactppto,
                'nomestadofactppto' => $nomestadofactppto,
                'fechahora' => empty($_POST['fechahora'])? $_POST['fechahora']:null
            ];

            $ins = $this->ModelIncidencias->insertarNuevaSolicitud($datos);
            
            if ($ins >0) {
                if (isset($_FILES['ficheroCrearIncidencia']) && count($_FILES['ficheroCrearIncidencia']['size']) >0) {                
                    
                    $this->construirDatosFicherosAInsertar($_FILES,$ins);
                }
                
                //si viene solicitud de ppto.
                if (isset($_POST['presupuestarEnCreacion']) && $_POST['presupuestarEnCreacion'] == 1) {
                    
                    $datosSolicitudCrear['idIncidencia'] = $ins;  
                    $datosSolicitudCrear['comentario'] = $_POST['comentarioParaPresupuestoCrear']; 
                    $datosSolicitudCrear['creacion'] = date("Y-m-d H:i:s");

                   $solPpto =  $this->ModelIncidencias->crearPresupuestoParaCliente($ins,$_POST['comentarioParaPresupuestoCrear'],$_SESSION['idusuario']);
                   if ($solPpto == 1 && EMPRESA == 'INFOMALAGA') {
                        $this->enviarEmailSolicitudDePresupuestoALosAdministradores($datosSolicitudCrear);
                   }
                }
                
                $this->enviarEmailAcuseDeReciboCliente($ins,$datos);
                $this->enviarEmailALosTecnicosAsignadosAlCliente($ins,$datos);                
                
                $_SESSION['message'] = 'Se ha registrado la solicitud corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar la solicitud porque falta completar datos en el formulario.';
            }

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar la solicitud porque falta completar datos en el formulario.';
        }
        redireccionar('/Incidencias');

    }

    public function enviarEmailSolicitudDePresupuestoALosAdministradores($datos)
    {          
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Solicitud de presupuesto";
        $idIncidencia = $datos['idIncidencia'];
        $nombreUsuario =  $_SESSION['usuario'];                              
            
        $idsAdministradores = [2=>CUENTA_CORREOADMINISTRACION1, 4=>CUENTA_CORREOADMINISTRACION2];        
        
            foreach ($idsAdministradores as $idUsuario => $cuenta) {
                      
                $emailsDestino = [];
                $emailsDestino[] = $cuenta;
    
                //construyo cuerpo de mensaje    
                $fecha = date('d-m-Y H:i',strtotime($datos['creacion']));
                $enlace = 'Haz click en el enlace para ir a la plataforma Infomalaga.';
                $contenido = 'El usuario '.$nombreUsuario.' ha solicitado un presupuesto en la solicitud Nº '.$idIncidencia.' con fecha '.$fecha.' y le ha dejado el siguiente comentario: '.$datos['comentario'];
                $info = $idIncidencia.'/'.$idUsuario;
    
                $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                $cambio = [$enlace, $contenido, $info];
                $mensaje = str_replace($cambiar,$cambio,$plantilla);

                $message = html_entity_decode($mensaje);
                        
                $tipoDoc = '';
                $attachment = '';

                enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
            }                                   
    }

    public function enviarEmailCambioEstadoPresupuestoALosAdministradores($datos)
    {          
            if(EMPRESA == 'INFOMALAGA'){

                $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

                $nombreRemitente = 'InfoMalaga';
                $emailRemitente = CUENTA_CORREO;
                $cambioEstado = $this->ModelIncidencias->obtenerNombreEstadoPresupuestoFacturacionPorIdEstado($datos['idEstado']);
                $asunto = "Notificación de cambio de estado a ".$cambioEstado;
                $idIncidencia = $datos['idIncidencia'];
                $nombreUsuario =  $_SESSION['usuario'];                              
                    
                $idsAdministradores = [2=>CUENTA_CORREOADMINISTRACION1, 4=>CUENTA_CORREOADMINISTRACION2];        
            
                foreach ($idsAdministradores as $idUsuario => $cuenta) {
                          
                    $emailsDestino = [];
                    $emailsDestino[] = $cuenta;
        
                    //construyo cuerpo de mensaje    
                    $fecha = date('d-m-Y H:i',strtotime($datos['creacion']));
                    $enlace = 'Haz click en el enlace para ir a la plataforma Infomalaga.';
                    $contenido = 'El usuario '.$nombreUsuario.' ha cambiado el estado a '.$cambioEstado.' para la solicitud Nº '.$idIncidencia.' con fecha '.$fecha.' y le ha dejado el siguiente comentario: '.$datos['comentario'];
                    $info = $idIncidencia.'/'.$idUsuario;
        
                    $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                    $cambio = [$enlace, $contenido, $info];
                    $mensaje = str_replace($cambiar,$cambio,$plantilla);
    
                    $message = html_entity_decode($mensaje);
                            
                    $tipoDoc = '';
                    $attachment = '';
    
                    enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                }                

            }

    }


    public function enviarEmailAcuseDeReciboCliente($idIncidencia,$datos)
    {        
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");
        
        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Nueva solicitud de servicio";
       
            $usuariosAsignadosAlEquipo = $this->ModelIncidencias->obtenerCorreosUsuariosAsignadosNoSupervisor($datos['idequipo']);
            $emailsDestino = [];
            if (count($usuariosAsignadosAlEquipo)>0) {
                
                foreach ($usuariosAsignadosAlEquipo as $key) {
                    if (isset($key->correo) && $key->correo!='' && $key->recibemails ) {
                        $emailsDestino[] = $key->correo;

                        $fecha = date('d-m-Y H:i:s',strtotime($datos['creacion']));
                        $enlace = 'Haz click en el enlace para ver la solicitud.';
                        $contenido = 'Hemos registrado tu solicitud de servicio con fecha '.$fecha.'. El número de solicitud es '.$idIncidencia.'.';
                        $info = $idIncidencia.'/'.$key->id;
    
                        $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                        $cambio = [$enlace, $contenido, $info];
                        $mensaje = str_replace($cambiar,$cambio,$plantilla);
                        
                        $message = html_entity_decode($mensaje);
                    
                        $tipoDoc = '';
                        $attachment = '';
                    
                        enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                        
                    }
 
                }
            }
        
    }

    public function enviarEmailALosTecnicosAsignadosAlCliente($idIncidencia,$datos)
    {                
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Nueva solicitud de servicio";
        $user = $datos['nombreUsuario'];                     
        
            $idsTecnicos = json_decode($datos['tecnicos']);
            $emailsDestino = [];
            if ($idsTecnicos && count($idsTecnicos)>0) {
                foreach ($idsTecnicos as $key) {
                    
                    $correoTecnico = $this->ModelIncidencias->obtenerCorreoDesdeIdusuario($key);
                    if (isset($correoTecnico->correo) && $correoTecnico->correo !='' && $correoTecnico->recibemails == 1) {
                        $emailsDestino[] = $correoTecnico->correo;

                        //construyo cuerpo de mensaje    
                        $fecha = date('d-m-Y H:i:s',strtotime($datos['creacion']));
                        $enlace = 'Haz click en el enlace para ver la solicitud.';
                        $contenido = 'El usuario '.$user.' ha registrado la solicitud de servicio Nº '.$idIncidencia.' con fecha '.$fecha.' .Se te ha asignado esta solicitud.';
                        $info = $idIncidencia.'/'.$key;

                        $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                        $cambio = [$enlace, $contenido, $info];
                        $mensaje = str_replace($cambiar,$cambio,$plantilla);
                        
                        $message = html_entity_decode($mensaje);
                    
                        $tipoDoc = '';
                        $attachment = '';
                                  
                        enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                        
                    }                   
                }
            }            
        
  
    }


    public function obtenerStringNombresTecnicosAsignados($idsTecnicos)
    {        
        $nom = '';

        if ($idsTecnicos && count($idsTecnicos) >0) {        
            
                foreach ($idsTecnicos as $key) {              
                    $nombreTecnico = $this->ModelIncidencias->nombreCompletoDeTecnicoPorId($key);
                    $nom .=  $nombreTecnico;
                    $nom .= "/";               
                }            
        }         
        return $nom;
    }
   
    public function construirDatosFicherosAInsertar($files,$idIncidencia)
    {
        $nombres = $files['ficheroCrearIncidencia']['name'];
        $tipos = $files['ficheroCrearIncidencia']['type'];
        $tamanios = $files['ficheroCrearIncidencia']['size'];
        $temporales = $files['ficheroCrearIncidencia']['tmp_name'];
        $errores = $files['ficheroCrearIncidencia']['error'];

        $documentos = [];
        $imagenes = [];

        for ($i=0; $i < count($nombres); $i++) { 
            if ($tamanios[$i] >0 && $tamanios[$i] <= 6000000 && $errores[$i] == 0 ) {
                
                //para obtener la extension de los ficheros
                $extInst = new SplFileInfo($nombres[$i]);                                
                $extension = strtolower($extInst->getExtension());
                
                
                $extensionesImg = ["jpeg", "jpg", "png", "gif", "bmp", "svg"]; 
                $extensionesDoc = ["doc", "docx", "docm", "xlsx", "xlsm", "pptx", "pptm", "csv", "pdf", "xls", "mp3", "wav", "ogg", "mp4"]; 
                    
                if (in_array($extension, $extensionesImg)) {
                    //echo"es imagen";                        
                    $tmp = [];
                    $tmp['nombre'] = $idIncidencia."_".$nombres[$i];
                    $tmp['tipo'] = $tipos[$i];
                    $tmp['tamanio'] = $tamanios[$i];
                    $tmp['tmp'] = $temporales[$i];
                    $imagenes[] = $tmp;

                    $path = $temporales[$i];
               
                    // Extensión de la imagen
                    //$type =  $tipos[$i];
                    
                    // Cargando la imagen
                    $directorio = DOCS_INCIDENCIAS;
                    $subir_archivo = $directorio . basename($idIncidencia."_".$nombres[$i]);
                    if (move_uploaded_file($path, $subir_archivo)) {                        
                        //$data = $this->doCurl(RUTA_URL.'/public/documentos/Incidencias/'.basename($idIncidencia."_".$nombres[$i]));

                        //$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);                        
                        $base64 = '';
                        $this->ModelIncidencias->insertarDatosFichero($tmp, $idIncidencia, $base64);
                    } 

                }else if (in_array($extension, $extensionesDoc)) {
                    //echo"es doc";                         
                    $tmp2 = [];
                    $tmp2['nombre'] = $idIncidencia."_".$nombres[$i];
                    $tmp2['tipo'] = $tipos[$i];
                    $tmp2['tamanio'] = $tamanios[$i];
                    $tmp2['tmp'] = $temporales[$i];
                    $documentos[] = $tmp2;

                    $directorio = DOCS_INCIDENCIAS;

                    $subir_archivo = $directorio . basename($idIncidencia."_".$nombres[$i]);
                
                    if (move_uploaded_file($temporales[$i], $subir_archivo)) {
                        $this->ModelIncidencias->insertarDatosFichero($tmp2, $idIncidencia, '');                       
                    }                                                        
                }
                
            }
        }
       
    }     

    public function editarIncidencia()
    {     
        $info = [];      

        if (isset($_POST['id']) && $_POST['id'] >0 ) {                                  

                $detalles = $this->ModelIncidencias->obtenerDatosIncidencia($_POST['id']);   
                $imagenes = $this->ModelIncidencias->obtnerListadoFicherosImagenes($_POST['id']);
                $documentos = $this->ModelIncidencias->obtnerListadoFicherosDocumentos($_POST['id']);                

                $comentarios = $this->ModelIncidencias->obtenerTodosLosComentariosPorIdIncidencia($_POST['id']);
                $comentariosSearch = $this->ModelIncidencias->obtenerTodosLosComentariosPorIdIncidencia($_POST['id']);
                //agregar fichero a comentarios:
                $comentarios = $this->agregarFicherosTrabajoTerminadoComentarioCliente($comentariosSearch);

                $optionEstados = '';            
                if ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {
                    $apartadoFactPpto = $this->montarOptionesSelectEstadoParaEditIncidencia($_POST['id']);
                    $optionEstados = $apartadoFactPpto;              
                }                

                $presupuestos = [];
                $tienePptos = 0;
                $obtPptos = $this->ModelIncidencias->obtenerPresupuestosParaIncidencia($_POST['id']);
                if (isset($obtPptos) && count($obtPptos)>0) {
                    $presupuestos = $obtPptos;
                    $tienePptos = count($obtPptos);
                }
                
                $consultHaistorialEstados = $this->ModelIncidencias->obtenerComentariosFacturarPresupuestar($_POST['id']);
                $historialEstados = [];
                if (isset($consultHaistorialEstados) && count($consultHaistorialEstados)>0) {
                    $historialEstados = $consultHaistorialEstados;
                }

                $emailsEnviados = $this->montarHtmlEmailsEnviados($_POST['id']);

                $info = [
                    'detalles' => $detalles,
                    'equipos' => $this->ModelClientes->listadoEquiposPorSucursal($detalles->sucursal),
                    'numfactura' => ($detalles->idfactura > 0)? $this->ModelFacturasCliente->nuFacturaPorIdFactura($detalles->idfactura):'',
                    'imagenes' => $imagenes,
                    'documentos' => $documentos,
                    'comentarios' => $comentarios,
                    'optionEstados' => $optionEstados,
                    //'verFacPpto' => $verFacPpto,
                    'presupuestos' => $presupuestos,
                    'tienePptos' => $tienePptos,
                    'historialEstados' => $historialEstados,
                    'botonesFacturaIncidencia' => TemplateHelperDocumento::buildButtonsActionInvoicesFromRequest($detalles->idfactura),
                    'htmlPrefacturaDetalle' => $this->obtenerHtmlPrefacturaDetalle($_POST['id']),
                    'sucursales' => $this->ModelClientes->obtenerSucursalesActivasPorCliente($detalles->idcliente),
                    'emailsEnviados' => $emailsEnviados
                ];                                            
            
        }else if(isset($_SESSION['idIncidenciaAlerta']) && $_SESSION['idIncidenciaAlerta'] >0){

            $detalles = $this->ModelIncidencias->obtenerDatosIncidencia($_SESSION['idIncidenciaAlerta']);   
            $imagenes = $this->ModelIncidencias->obtnerListadoFicherosImagenes($_SESSION['idIncidenciaAlerta']);
            $documentos = $this->ModelIncidencias->obtnerListadoFicherosDocumentos($_SESSION['idIncidenciaAlerta']);                

            $comentariosSearch = $this->ModelIncidencias->obtenerTodosLosComentariosPorIdIncidencia($_SESSION['idIncidenciaAlerta']);
            //agregar fichero a comentarios:
            $comentarios = $this->agregarFicherosTrabajoTerminadoComentarioCliente($comentariosSearch);

            $optionEstados = '';            
            if ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {
                $apartadoFactPpto = $this->montarOptionesSelectEstadoParaEditIncidencia($_POST['id']);
                $optionEstados = $apartadoFactPpto;              
            }

            $presupuestos = [];
            $tienePptos = 0;
            $obtPptos = $this->ModelIncidencias->obtenerPresupuestosParaIncidencia($_POST['id']);
            if (isset($obtPptos) && count($obtPptos)>0) {
                $presupuestos = $obtPptos;
                $tienePptos = count($obtPptos);
            }
            $consultHaistorialEstados = $this->ModelIncidencias->obtenerComentariosFacturarPresupuestar($_POST['id']);
            $historialEstados = [];
            if (isset($consultHaistorialEstados) && count($consultHaistorialEstados)>0) {
                $historialEstados = $consultHaistorialEstados;
            }

            $emailsEnviados = $this->montarHtmlEmailsEnviados($_SESSION['idIncidenciaAlerta']);

            $info = [
                'detalles' => $detalles,
                'imagenes' => $imagenes,
                'documentos' => $documentos,
                'comentarios' => $comentarios,
                'optionEstados' => $optionEstados,
                //'verFacPpto' => $verFacPpto,
                'presupuestos' => $presupuestos,
                'tienePptos' => $tienePptos,
                'historialEstados' => $historialEstados,
                'emailsEnviados' => $emailsEnviados
            ];
            unset($_SESSION['idIncidenciaAlerta']);
        }
        
        $this->vista('incidencias/verIncidencia', $info);
    }

    private function montarHtmlEmailsEnviados($idIncidencia)
    {
        $envios = $this->modeloBase->getAllFieldsTablaByFieldsFilters(
            'emails_clientes_facturas', 
            ['iddoc' => $idIncidencia, 'tipodoc' => 'parte'], 
            'fecha', 
            'DESC'
        );        
        if(!empty($envios)){
            return TemplateHelperDocumento::buildHTMLListSentEmailsDocumento($envios, 'parte');
        }else{
            return '<div class="container_emails">No se ha enviado emails de esta incidencia.</div>';
        }
        
    }

    private function agregarFicherosTrabajoTerminadoComentarioCliente($comentariosSearch)
    {        
        if(!empty($comentariosSearch) && count($comentariosSearch) > 0){

            foreach ($comentariosSearch as $comentario) {
                $ficheros = $this->ModelIncidencias->obtenerTodosLosFicherosDeUnComentario($comentario->id);
                if(!empty($ficheros)){
                    $comentario->ficheros = $ficheros;                                        
                }else {
                    $comentario->ficheros = []; // Asignar un array vacío si no hay ficheros
                }
            }
        }
        return $comentariosSearch; 
    }
 
    private function obtenerHtmlPrefacturaDetalle($idIncidencia)
    {
        $rows = $this->ModelFacturasDetalleCliente->obtenerFilasPrefactura($idIncidencia);

        $datos = [                        
            'productos' => $this->ModelProductos->buscarProdutosActivos(),
            'tiposIva' => $this->ModelTiposIva->obtenerTipoIvaActivos()
        ];                         
        $respuesta = TemplateHelperDocumento::buildRowGridRequestWithData($rows, $datos);
        return $respuesta;
    }

    public function montarOptionesSelectEstadoFactPptoParaAdminYTecnico($idIncidencia)
    {            
            $optionEstados = '';
            $verApartadoFactPpto = '';

            if ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {

                $html = "<option disabled selected>Seleccionar</option>";
                $estado = $this->ModelIncidencias->obtenerEstadoPresupuestoFacturacion($idIncidencia);
                $todosLosEstados = $this->ModelIncidencias->obtenerTodosLosEstadoPresupuestoFacturacion();            
                $idEstado = $estado->estadofactppto;              
 
                foreach ($todosLosEstados as $key) {
                    if ($key->id != $idEstado) {
                        $html .= "<option value='".$key->id."'>".$key->estado."</option>";
                    }
                }
                $optionEstados = $html;
                $verApartadoFactPpto = 1;

            }/* else if($_SESSION['nombrerol'] == 'tecnico') {                
                              
                $verFactPresup = $this->validaSiEstadoPermiteVisualizarApartadoFactPresup($idIncidencia);                
                if ($verFactPresup == 1) {
                    $optionEstados = $this->construirOpcionesEstadoFactPpto($idIncidencia);
                    $verApartadoFactPpto = 1;   
                }
            
            } */
           
            return array($optionEstados,$verApartadoFactPpto);               
    }

    public function montarOptionesSelectEstadoParaEditIncidencia($idIncidencia)
    {            
            $optionEstados = '';            

            if ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {

                $html = "<option disabled selected>Seleccionar</option>";
                $estado = $this->ModelIncidencias->obtenerEstadoPresupuestoFacturacion($idIncidencia);
                $todosLosEstados = $this->ModelIncidencias->obtenerTodosLosEstadoPresupuestoFacturacion();            
                $idEstado = $estado->estadofactppto;              
 
                foreach ($todosLosEstados as $key) {
                    if ($key->id != $idEstado) {
                        $html .= "<option value='".$key->id."'>".$key->estado."</option>";
                    }
                }
                $optionEstados = $html;                

            }/* else if($_SESSION['nombrerol'] == 'tecnico') {                                
                $optionEstados = $this->construirOpcionesEstadoFactPpto($idIncidencia);                
            } */
           
            return $optionEstados;
    }

    public function listarIncidenciasTecnico()
    {
        $salida = '';        
        $rol = $_SESSION['nombrerol'];
        
        if ($rol == 'tecnico' || $rol == 'admin') {                
    
            $hayIncidencias = $this->ModelIncidencias->existenIncidencias();
    
            if ($hayIncidencias  && $hayIncidencias >0 ) {
            
                $salida .= '                                    
                <div class="my-2 flex sm:flex-row flex-col">
                    <div class="flex flex-row mb-1 sm:mb-0">
                        <div class="relative flex" id="buscador">                            
                        </div>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                        <div id="destinoincidenciastecnicosajax"></div>
                    </div>
                </div>        
                <div id="paginador"></div>
                <script  type="module">
                  import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClass.js" 
                  arrancar("tablaincidencias","Incidencias/crearTablaIncidenciasTecnicos", "destinoincidenciastecnicosajax", "inc.estado ASC, inc.creacion DESC", "DESC", 0, "buscador","Incidencias/totalRegistrosIncidenciasTecnicos", [10, 20, 30],"min-w-full leading-normal","paginador",["estadoatencion","ver","reasignar","terminar","historial"],"'.RUTA_URL.'/Incidencias/editarIncidencia","");
                </script>                                
                ';        
                
            }                           
        }

        echo json_encode($salida);                
    }

    public function listarTodasLasIncidencias()
    {
        $salida = '';        
        $rol = $_SESSION['nombrerol'];
                
        if ($rol == 'tecnico' || $rol == 'admin') {                
            
            $hayIncidencias = $this->ModelIncidencias->existenIncidencias();
    
            if ($hayIncidencias  && $hayIncidencias >0 ) {
            
                $salida .= '                                    
                <div class="my-2 flex sm:flex-row flex-col">
                    <div class="flex flex-row mb-1 sm:mb-0">
                        <div class="relative flex" id="buscador2">                            
                        </div>
                        
                    </div>                    
                </div>
                <div class="overflow-x-auto">
                    <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                        <div id="destinoincidenciastodasajax"></div>
                    </div>
                </div>
                <div id="paginador2"></div>                
                <script  type="module">

                  import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClass.js" 
                  arrancar("tablaincidencias2","Incidencias/crearTablaIncidenciasAdmin", "destinoincidenciastodasajax", "inc.estado ASC, inc.creacion DESC", "DESC", 0, "buscador2","Incidencias/totalRegistrosIncidenciasAdmin", [10, 20, 30],"min-w-full leading-normal","paginador2",["estadoatencion","ver","terminar","historial","reasignar","reabrir","eliminar"],"'.RUTA_URL.'/Incidencias/editarIncidencia","");

                </script>                
                
                
                ';        
                
            }                           
        }

        echo json_encode($salida);                
    }

    public function iniciarAtencionIncidencia()
    {
        $retorno = ['respuesta' => 0];      

        if (isset($_SESSION['idusuario']) && isset($_SESSION['nombrerol'])) {

            if ($_SESSION['nombrerol'] == 'tecnico' || $_SESSION['nombrerol'] == 'admin') {
                
                if (isset($_POST['idIncidencia']) && $_POST['idIncidencia'] >0 & isset($_POST['modalidad']) && $_POST['modalidad'] >0) {
                    date_default_timezone_set("Europe/Madrid");

                    $idCliente = $this->ModelIncidencias->idClientePorIncidencia($_POST['idIncidencia']);
                    $idEquipo = $this->ModelIncidencias->obtenerIdEquipoPorIncidencia($_POST['idIncidencia']);

                    $datos = [
                        'idIncidencia' => $_POST['idIncidencia'],
                        'modalidad' => $_POST['modalidad'],
                        'idTecnico' => $_SESSION['idusuario'],
                        'play' => 1,
                        'creacion' => date('Y-m-d H:i:s'),
                        'idCliente' => $idCliente,
                        'idEquipo' => $idEquipo
                    ];

                    $ins = $this->ModelIncidencias->crearInicioAtencionIncidencia($datos);
        
                    if ($ins) {                
                        $this->ModelIncidencias->actualizarPlayStopIncidencia($datos,$ins);
                        $estado = $this->ModelIncidencias->obtenerEstadoIncidencia($_POST['idIncidencia']);
                        if (isset($estado) && $estado->estado == 1) {
                            $this->ModelIncidencias->cambiarEstadoDePendienteAEnCurso($_POST['idIncidencia']);
                        }
                        $retorno = ['respuesta'=>1];
                    }
                }

            }
        
        }                 
        echo json_encode($retorno);
    }

    public function detenerAtencionIncidencia()
    {    
        $retorno = ['respuesta' => 0];
        $tienePermisoDetener = 0;

        if ($_SESSION['nombrerol'] == 'tecnico') {                                
            $idTecnicoIniciador = $this->ModelIncidencias->idTecnicoQueInicioLaAtencion($_POST['idAtencion']);
            if ($_SESSION['idusuario']== $idTecnicoIniciador){
                $tienePermisoDetener = 1;    
            }          
        }else if($_SESSION['nombrerol'] == 'admin'){
            $tienePermisoDetener = 1;            
        }

        if ($tienePermisoDetener ==1) {
            if (isset($_POST['idAtencion']) && $_POST['idAtencion'] >0 ) {

                date_default_timezone_set("Europe/Madrid");
                $fechaFinalizacion = date('Y-m-d H:i:s');

                $idIncidencia = $this->ModelIncidencias->obtenerIdIncidenciaDesdeIdAtencion($_POST['idAtencion']);
               
                $tiempoTotal = $this->calculoTiempoTototalAtencion($_POST['idAtencion'],$fechaFinalizacion);

                $datos = [
                    'idAtencion' => $_POST['idAtencion'],
                    'idTecnico' => $_SESSION['idusuario'],
                    'play' => 0,
                    'finalizacion' => $fechaFinalizacion,
                    'idIncidencia' => $idIncidencia->idincidencia,
                    'tiempoTotal' => $tiempoTotal
                ];
                
                $upd = $this->ModelIncidencias->detenerAtencionIncidencia($datos);

                
                if ($upd) {                
                    $this->ModelIncidencias->actualizarPlayStopIncidencia($datos,0);

                    if (isset($_POST['comentario']) && $_POST['comentario'] !='') {
                        $idEquipo = $this->ModelIncidencias->obtenerIdEquipoPorIncidencia($idIncidencia->idincidencia);
                        $idComentCli = $this->ModelIncidencias->insertarComentarioFinalizarIncidencia($idIncidencia->idincidencia,$_POST['comentario'],$_SESSION['idusuario'],$_SESSION['nombrerol'],$idEquipo,'externo',0);
                        
                        if ($idComentCli >0) {                     
                            if (!empty($_FILES['ficheroDetenerAtencion']['name'][0])) {
                                $this->construirDatosFicherosAInsertarDetenerAtencion($_FILES, $idIncidencia->idincidencia, $idComentCli);
                            }                       
                        }
                    }

                    $idEstadoFP = $this->ModelIncidencias->idEstadoFacturaPresupuesto($idIncidencia->idincidencia);

                    if (isset($_POST['facturarPresupuestar']) && ($_POST['facturarPresupuestar'] >= 1 && $_POST['facturarPresupuestar'] <= 6 )) {
                        $this->actualizarEstadoFacturaPresupuesto($idIncidencia->idincidencia, $_POST['facturarPresupuestar'], $_POST['comentParaFacturador']);
                        $idEstadoFP = $_POST['facturarPresupuestar'];                   
                    }                    

                    if(!empty($_POST['comentParaFacturador'])){                      

                       $this->ModelIncidencias->insertarDatosAHistorialEstadosFacturarPresupuestar($idIncidencia->idincidencia, $idEstadoFP, $_SESSION['idusuario'], $_POST['comentParaFacturador']);                      

                    }

                    $retorno = ['respuesta'=>1]; // si está detenido
                }else{
                    $retorno = ['respuesta'=>0]; // si da error al detener
                }
            }
        }else{
            $retorno = ['respuesta' => 2]; // si no tiene permiso para detener
        }
        
        echo json_encode($retorno);
    }

    public function actualizarEstadoFacturaPresupuesto($idIncidencia, $idEstado, $comentParaFacturador)
    {     
        $nombreEstado = $this->ModelIncidencias->obtenerNombreEstadoPresupuestoFacturacionPorIdEstado($idEstado);
        $this->ModelIncidencias->actualizarEstadoFacturaPresupuesto($idIncidencia, $idEstado, $nombreEstado);

        $this->ModelIncidencias->insertarDatosAHistorialEstadosFacturarPresupuestar($idIncidencia, $idEstado, $_SESSION['idusuario'], $comentParaFacturador);
    }
    
    public function calculoTiempoTototalAtencion($idAtencion,$fechaFinalizacion)
    {
        date_default_timezone_set("Europe/Madrid");
        $fechaInicio = $this->ModelIncidencias->obtenerHoraCreacionAtencion($idAtencion);
        //$fechaFinalizacion = date('Y-m-d H:i:s');
              
        $firstDate  = new DateTime($fechaInicio);
        $secondDate = new DateTime($fechaFinalizacion);
        $intvl = $firstDate->diff($secondDate);

        $dias = ($intvl->d) *24 ;   
        $horas = ($intvl->h);
        $minutos =  ($intvl->i) / 60;
        $segundos =  ($intvl->s) / 3600;
        $totalHoras = $dias + $horas + $minutos + $segundos;        
        
        return round($totalHoras,2);
    }

    public function detallesTiemposIncidencia()
    {       
        $retorno = [
            'respuesta' =>0,
            'modalidad' =>'',
            'creacion' =>'',
            'verFactPresup' => 0,
            'optionEstados' => ''
        ];
        if ($_POST['idAtencion'] && $_POST['idAtencion']) {
            $sel = $this->ModelIncidencias->obtenerDetallesControlTiemposIncidencia($_POST['idAtencion']);
           
            if (isset($sel)) {                            
                $retorno = [
                    'respuesta' => 1,
                    'modalidad' => $sel->modalidad,
                    'creacion' => $sel->creacion
                ];
                                
                $verFactPresup = $this->validaSiEstadoPermiteVisualizarApartadoFactPresup($sel->idincidencia);
               
                $retorno['verFactPresup'] = $verFactPresup;
                $optionEstados = '';
                if ($verFactPresup == 1) {
                    $optionEstados .= $this->construirOpcionesEstadoFactPpto($sel->idincidencia);
                    $retorno['optionEstados'] = $optionEstados;
                }               
            }
           
        }
        echo json_encode($retorno);
    }

    public function validaSiEstadoPermiteVisualizarApartadoFactPresup($idincidencia){
        $verApartado = 0;
        $estado = $this->ModelIncidencias->obtenerEstadoPresupuestoFacturacion($idincidencia);            
        $idEstado = $estado->estadofactppto;              
        
        if ($idEstado == 0 || $idEstado == 2) {
            $verApartado = 1;
        }      

        return $verApartado;
    }

    public function construirOpcionesEstadoFactPpto($idincidencia) 
    {
        $estado = $this->ModelIncidencias->obtenerEstadoPresupuestoFacturacion($idincidencia);
        $estados = $this->ModelIncidencias->obtenerEstadosParaTecnico();
        
        $idEstado = $estado->estadofactppto;              
        $html = "<option disabled selected>Seleccionar</option>";
        if ($idEstado == 2) {
            foreach ($estados as $key) {
                if ($key->id != $idEstado) {
                    $html .= "<option value='".$key->id."'>".$key->estado."</option>";
                }
            }
        }else if ($idEstado == 0){
            foreach ($estados as $key) {               
                $html .= "<option value='".$key->id."'>".$key->estado."</option>";                
            }        
        }
        return $html;
    }

    public function contruirListadoTecnicosPorIncidencia()
    {
        $retorno = [
            'respuesta' =>0,
            'html' =>''            
        ];

        if ($_POST['idIncidencia'] && $_POST['idIncidencia'] >0) {
            $tecnicosAsignados = $this->ModelIncidencias->obtenerTecnicosAsignadosParaIncidencia($_POST['idIncidencia']);
            
            $arridsTecnicos = json_decode($tecnicosAsignados);            
            
            $html = '';
            if ($arridsTecnicos && count($arridsTecnicos)>0) {
                foreach ($arridsTecnicos as $key) {

                    $tecnicoActivo = $this->ModelIncidencias->usuarioEstaActivo($key);
                    if ($tecnicoActivo->activo == 1) {

                        $nombreTecnico = $this->ModelIncidencias->nombreCompletoDeTecnicoPorId($key);
                        $html .= "<tr class='hover:bg-grey-lighter'>
                            <td style='width: 20%;' class='p-2 border-b border-grey-light'><input style='width: 70%;' value='".$tecnicoActivo->codigotecnico."' name='idsTecSel[]' class='inputKeyTecnico'></td>
                            <td class='p-2 border-b border-grey-light'>".$nombreTecnico."</td>
                            <td style='width: 30%;' class='p-2 border-b border-grey-light'><a href='' class='eliminarTecnico'><i class='fas fa-user-minus' style='color:red;'></i></a></td>
                            </tr>";
                    }
    
                }
            }
            $retorno = [
                'respuesta' =>1,
                'html' => $html        
            ];
        }
        
        echo json_encode($retorno);
    }
    
    public function finalizarIncidencia()
    {    
        $retorno = [
            'respuesta' =>0  
        ];     

        if (isset($_SESSION['idusuario']) && isset($_SESSION['nombrerol']) && isset($_POST['idIncidencia']) && $_POST['idIncidencia'] >0 ) {

            $erroresFicheros = $this->validarFicherosAdjuntos($_FILES);

            if (!empty($erroresFicheros)) {
                echo json_encode([
                    'respuesta' => 3,
                    'mensaje' => implode(" | ", $erroresFicheros)
                ]);
                exit;
            }            

            if ($_POST['comentario'] && $_POST['comentario']!='') {
            
                date_default_timezone_set("Europe/Madrid");
                $fechaFinalizacion = date('Y-m-d H:i:s');
    
                $datosUpd = [
                    'idIncidencia' => $_POST['idIncidencia'],
                    'estado' => 3,
                    'comentario' => $_POST['comentario'],
                    'fecha' => $fechaFinalizacion
                ];
    
                $idTecnico = $_SESSION['idusuario'];
                $nombreRol = $_SESSION['nombrerol'];
                
                $idEquipo = $this->ModelIncidencias->obtenerIdEquipoPorIncidencia($_POST['idIncidencia']);
                $upd = $this->ModelIncidencias->finalizarIncidencia($datosUpd);
                
                $upd = true;
                if ($upd) {

                    //asignar fechahora actual al campo 
                    $this->ModelIncidencias->actualizarFechaHoraIncidencia($_POST['idIncidencia'], date("Y-m-d H:i:s"));
                   
                    //valido si play está detenido o no
                    $play = $this->ModelIncidencias->obtenerEstadoPlayStopDeAtencion($_POST['idIncidencia']);   
                    if ($play != '' && $play >0) { //si no está detenido, lo detengo, actualizo play a cero y calculo el tiempo
                        
                        $tiempoTotal = $this->calculoTiempoTototalAtencion($play,$fechaFinalizacion);
    
                        $datosDetener = [
                            'idAtencion' => $play,
                            'idTecnico' => $_SESSION['idusuario'],
                            'play' => 0,
                            'finalizacion' => $fechaFinalizacion,
                            'idIncidencia' => $_POST['idIncidencia'],
                            'tiempoTotal' => $tiempoTotal
                        ];
                        
                        $this->ModelIncidencias->detenerAtencionIncidencia($datosDetener);                                                    
                        $this->ModelIncidencias->actualizarPlayStopIncidencia($datosDetener,0);              
        
                    }
    
                    //inserto los comentarios
                    $tipoE = 'externo';
                    $idComentCli = $this->ModelIncidencias->insertarComentarioFinalizarIncidencia($_POST['idIncidencia'],$_POST['comentario'],$idTecnico,$nombreRol,$idEquipo,$tipoE,0);

                    if ($_POST['comentarioInterno'] !='') {
                        $tipo = 'interno';
                        $this->ModelIncidencias->insertarComentarioFinalizarIncidencia($_POST['idIncidencia'],$_POST['comentarioInterno'],$idTecnico,$nombreRol,$idEquipo,$tipo,0);
                    }

                    $idEstadoFP = $this->ModelIncidencias->idEstadoFacturaPresupuesto($_POST['idIncidencia']);

                    if (isset($_POST['facturarPresupuestar']) && $_POST['facturarPresupuestar'] != '' ) {
                        $this->actualizarEstadoFacturaPresupuesto($_POST['idIncidencia'], $_POST['facturarPresupuestar'], $_POST['comentParaFacturador']);
                        
                        if ($_POST['facturarPresupuestar'] >= 1 && $_POST['facturarPresupuestar'] <= 6) {
                            
                            $idEstadoFP = $_POST['facturarPresupuestar'];                           
                        }                        
                    }

                    if(!empty($_POST['comentParaFacturador'])){                      

                        $this->ModelIncidencias->insertarDatosAHistorialEstadosFacturarPresupuestar($_POST['idIncidencia'], $idEstadoFP, $_SESSION['idusuario'], $_POST['comentParaFacturador']);                      
 
                    }

                    if ($idComentCli >0) {                                                

                        if (!empty($_FILES['ficheroCrearIncidencia']['name'][0])) {
                            $this->construirDatosFicherosAInsertarTrabajoTerminado($_FILES, $_POST['idIncidencia'], $idComentCli);                            
                        }                       
                    }
                    
                    if(!empty($_POST['firma'])){
                        $upd = $this->ModelIncidencias->guadarImagenFirmaincidencia($_POST['idIncidencia'], $_POST['firma'], 1);
                    }

                    $retorno = ['respuesta' =>1];
                }
               
            }else{
                $retorno = ['respuesta' =>2];  //en caso haya error y no viene comentario para el cliente                
            }
        }
        echo json_encode($retorno);
    }    

    public function validarFicherosAdjuntos($files)
    {
        $errores = [];

        $MAX_TAMANIO_POR_ARCHIVO = 6000000; // 6 MB
        $MAX_TOTAL_TAMANIO = 30000000; // 30 MB
        $MAX_NUMERO_FICHEROS = 5;

        $extensionesImg = ["jpeg", "jpg", "png", "gif", "bmp", "svg"];
        $extensionesDoc = ["doc", "docx", "docm", "xlsx", "xlsm", "pptx", "pptm", "csv", "pdf", "xls", "mp3", "wav", "ogg", "mp4"];
        $extensionesPermitidas = array_merge($extensionesImg, $extensionesDoc);

        if (!isset($files['ficheroCrearIncidencia'])) {
            return []; // No hay archivos, no hay error
        }

        $nombres = $files['ficheroCrearIncidencia']['name'];
        $tamanios = $files['ficheroCrearIncidencia']['size'];
        $erroresArchivos = $files['ficheroCrearIncidencia']['error'];

        if (!is_array($nombres)) {
            $nombres = [$nombres];
            $tamanios = [$tamanios];
            $erroresArchivos = [$erroresArchivos];
        }

        // Número de archivos
        if (count($nombres) > $MAX_NUMERO_FICHEROS) {
            $errores[] = "No puedes subir más de $MAX_NUMERO_FICHEROS archivos.";
        }

        // Validar cada archivo
        $totalTamanio = 0;
        for ($i = 0; $i < count($nombres); $i++) {
            $nombre = $nombres[$i];
            $tamanio = $tamanios[$i];
            $error = $erroresArchivos[$i];

            if ($error !== 0) {
                $errores[] = "El archivo $nombre tiene un error de carga.";
                continue;
            }

            if ($tamanio > $MAX_TAMANIO_POR_ARCHIVO) {
                $errores[] = "El archivo $nombre supera el tamaño máximo permitido de 6MB.";
            }

            $totalTamanio += $tamanio;

            $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (!in_array($extension, $extensionesPermitidas)) {
                $errores[] = "El archivo $nombre tiene una extensión no permitida ($extension).";
            }
        }

        if ($totalTamanio > $MAX_TOTAL_TAMANIO) {
            $errores[] = "La suma total de los archivos supera el límite de 30MB.";
        }

        return $errores;
    }    
    
    public function construirDatosFicherosAInsertarTrabajoTerminado($files, $idIncidencia, $idComentario)
    {
        $directorio = DOCS_TRABAJOS_TERMINADOS;
        $nombres = $files['ficheroCrearIncidencia']['name'];
        $tipos = $files['ficheroCrearIncidencia']['type'];
        $tamanios = $files['ficheroCrearIncidencia']['size'];
        $temporales = $files['ficheroCrearIncidencia']['tmp_name'];
        $errores = $files['ficheroCrearIncidencia']['error'];

        $documentos = [];
        $imagenes = [];

        for ($i=0; $i < count($nombres); $i++) { 
            if ($tamanios[$i] >0 && $tamanios[$i] <= 6000000 && $errores[$i] == 0 ) {
                
                //para obtener la extension de los ficheros
                $extInst = new SplFileInfo($nombres[$i]);                                
                $extension = strtolower($extInst->getExtension());                
                
                $extensionesImg = ["jpeg", "jpg", "png", "gif", "bmp", "svg"]; 
                $extensionesDoc = ["doc", "docx", "docm", "xlsx", "xlsm", "pptx", "pptm", "csv", "pdf", "xls", "mp3", "wav", "ogg", "mp4"]; 
                    
                if (in_array($extension, $extensionesImg)) {                   
                                               
                    $tmp = [];
                    $tmp['nombre'] = $idIncidencia."_".$idComentario."_".$nombres[$i];
                    $tmp['tipo'] = $tipos[$i];
                    $tmp['tamanio'] = $tamanios[$i];
                    $tmp['tmp'] = $temporales[$i];
                    $imagenes[] = $tmp;

                    $path = $temporales[$i];                                  

                    // Cargando la imagen                    
                    $subir_archivo = $directorio . basename($idIncidencia."_".$idComentario."_".$nombres[$i]);

                    if (move_uploaded_file($path, $subir_archivo)) {

                        $base64 = '';
                        $this->ModelIncidencias->insertarDatosFicheroTrabajoTerminado($idComentario, $tmp, $idIncidencia, $base64);
                    }                   

                }else if (in_array($extension, $extensionesDoc)) {
                    $tmp2 = [];
                    $tmp2['nombre'] = $idIncidencia."_".$idComentario."_".$nombres[$i];
                    $tmp2['tipo'] = $tipos[$i];
                    $tmp2['tamanio'] = $tamanios[$i];
                    $tmp2['tmp'] = $temporales[$i];
                    $documentos[] = $tmp2;                    

                    $subir_archivo = $directorio . basename($idIncidencia."_".$idComentario."_".$nombres[$i]);
                
                    if (move_uploaded_file($temporales[$i], $subir_archivo)) {
                        $this->ModelIncidencias->insertarDatosFicheroTrabajoTerminado($idComentario, $tmp2, $idIncidencia, '');                       
                    }                                                        
                }                
            }
        }       
    }      

    public function construirDatosFicherosAInsertarDetenerAtencion($files, $idIncidencia, $idComentario)
    {
        $directorio = DOCS_TRABAJOS_TERMINADOS;
        $nombres = $files['ficheroDetenerAtencion']['name'];
        $tipos = $files['ficheroDetenerAtencion']['type'];
        $tamanios = $files['ficheroDetenerAtencion']['size'];
        $temporales = $files['ficheroDetenerAtencion']['tmp_name'];
        $errores = $files['ficheroDetenerAtencion']['error'];

        $documentos = [];
        $imagenes = [];

        for ($i=0; $i < count($nombres); $i++) { 
            if ($tamanios[$i] >0 && $tamanios[$i] <= 6000000 && $errores[$i] == 0 ) {
                
                //para obtener la extension de los ficheros
                $extInst = new SplFileInfo($nombres[$i]);                                
                $extension = strtolower($extInst->getExtension());
                
                
                $extensionesImg = ["jpeg", "jpg", "png", "gif", "bmp", "svg"]; 
                $extensionesDoc = ["doc", "docx", "docm", "xlsx", "xlsm", "pptx", "pptm", "csv", "pdf", "xls", "mp3", "wav", "ogg", "mp4"]; 
                    
                if (in_array($extension, $extensionesImg)) {
                    //echo"es imagen";                        
                    $tmp = [];
                    $tmp['nombre'] = $idIncidencia."_".$idComentario."_".$nombres[$i];
                    $tmp['tipo'] = $tipos[$i];
                    $tmp['tamanio'] = $tamanios[$i];
                    $tmp['tmp'] = $temporales[$i];
                    $imagenes[] = $tmp;

                    $path = $temporales[$i];                                 
                    
                    // Cargando la imagen
                    
                    $subir_archivo = $directorio . basename($idIncidencia."_".$idComentario."_".$nombres[$i]);
                    if (move_uploaded_file($path, $subir_archivo)) {  
                        $base64 = '';
                        $this->ModelIncidencias->insertarDatosFicheroTrabajoTerminado($idComentario, $tmp, $idIncidencia, $base64);
                    } 

                }else if (in_array($extension, $extensionesDoc)) {
                    //echo"es doc";                         
                    $tmp2 = [];
                    $tmp2['nombre'] = $idIncidencia."_".$idComentario."_".$nombres[$i];
                    $tmp2['tipo'] = $tipos[$i];
                    $tmp2['tamanio'] = $tamanios[$i];
                    $tmp2['tmp'] = $temporales[$i];
                    $documentos[] = $tmp2;                    

                    $subir_archivo = $directorio . basename($idIncidencia."_".$idComentario."_".$nombres[$i]);
                
                    if (move_uploaded_file($temporales[$i], $subir_archivo)) {
                        $this->ModelIncidencias->insertarDatosFicheroTrabajoTerminado($idComentario, $tmp2, $idIncidencia, '');                       
                    }                                                        
                }                
            }
        }       
    }  

    public function enviarEmailClienteConFinalizacionIncidencia($datos)
    {
        $idIncidencia = $datos['idIncidencia'];
        $idEquipo = $this->ModelIncidencias->obtenerIdEquipoPorIncidencia($idIncidencia);
        $emailsDestino = [];

        //$emailUsuario = $this->ModelIncidencias->nombreUsuarioQueRegistroLaIdIncidencia($idIncidencia);
        $usuariosDelEquipo = $this->ModelIncidencias->obtenerCorreosUsuariosAsignadosNoSupervisor($idEquipo);

        if ($usuariosDelEquipo && count($usuariosDelEquipo)>0) {
            foreach ($usuariosDelEquipo as $usuario) {
                if (isset($usuario->correo) && $usuario->correo !='' && $usuario->recibemails == 1) {
                    $emailsDestino[] = $usuario->correo;
                    
                    $nombreRemitente = 'Telesat';        
                    $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");
                                    
                    $emailRemitente = CUENTA_CORREO;
                    $asunto = "Finalización de solicitud de servicio";
                    
                    $user = $usuario->id;
                
                    //construyo cuerpo de mensaje    
                    $fecha = date('d-m-Y H:i',strtotime($datos['fecha']));
                    $enlace = 'Haz click en el enlace para ver la solicitud.';
                    $contenido = 'Estimado usuario, le comunicamos que se ha finalizado la solicitud de servicio Nº '.$idIncidencia.' con fecha '. $fecha.'. Le hemos dejado un comentario. Por favor, ingrese al siguiente enlace para verlo y dejarnos una valoración. Muchas gracias';
                
                    $info = $idIncidencia.'/'.$user;

                    $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                    $cambio = [$enlace, $contenido, $info];
                    $mensaje = str_replace($cambiar,$cambio,$plantilla);
                    
                    $message = html_entity_decode($mensaje);
                
                    $tipoDoc = '';
                    $attachment = '';
                    
                    enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                

                }

            }
        }                             
    }

    public function valorarIncidencia()
    {        
 
        $retorno = [
            'respuesta' =>0        //en caso haya error en variable de session
        ];  
        
        if (isset($_SESSION['idusuario']) && isset($_SESSION['nombrerol'])) {

            if ($_POST['idIncidencia'] && $_POST['comentario'] && $_POST['comentario']!='' && $_POST['valoracion']>0 && $_POST['valoracion']!='') {
                
                date_default_timezone_set("Europe/Madrid");
                
                $datosUpd = [
                    'idIncidencia' => $_POST['idIncidencia'],                
                    'comentario' => $_POST['comentario'],
                    'valoracion' => $_POST['valoracion'],
                    'fecha' => date('Y-m-d H:i:s')                
                ];

                $idUsuario = $_SESSION['idusuario'];
                $nombreRol = $_SESSION['nombrerol'];

                $upd = $this->ModelIncidencias->valorarIncidencia($datosUpd);
                $idEquipo = $this->ModelIncidencias->obtenerIdEquipoPorIncidencia($_POST['idIncidencia']);
                $tipo = 'externo';

                if ($upd) {
                    
                    $this->ModelIncidencias->insertarComentarioFinalizarIncidencia($_POST['idIncidencia'],$_POST['comentario'],$idUsuario,$nombreRol,$idEquipo,$tipo,$_POST['valoracion']);               

                    $this->enviarEmailAlTecnicoConValoracionDelCliente($datosUpd);
                    $retorno = [
                        'respuesta' =>1  //ok      
                    ];
                }
                
            }else{
                $retorno = [
                    'respuesta' =>2  //en caso haya error y no vienen los comentarios y valoración del cliente
                ];
            }

        }
        echo json_encode($retorno);        
    }

    public function enviarEmailAlTecnicoConValoracionDelCliente($datos)
    {          
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Valoración del cliente";
        $idIncidencia = $datos['idIncidencia'];
        $nombreUsuario =  $_SESSION['usuario'];
        $user = $this->ModelIncidencias->idTecnicoQueFinalizoLaIdIncidencia($idIncidencia);   
                       
        $emailsDestino = [];        
        $emailTecnico = $this->ModelIncidencias->obtenerCorreoDesdeIdusuario($user);
        if (isset($emailTecnico->correo) && $emailTecnico->correo !='' && $emailTecnico->recibemails ==1) {         
            $emailsDestino[] = $emailTecnico->correo;
                    
            //construyo cuerpo de mensaje    
            $fecha = date('d-m-Y H:i',strtotime($datos['fecha']));
            $enlace = 'Haz click en el enlace para ver la solicitud.';
            $contenido = 'El usuario '.$nombreUsuario.' ha realizado la valoración de la solicitud Nº '.$idIncidencia.' con fecha '.$fecha.' y le ha dejado un comentario. Para verlo ingrese en el siguiente enlace.';

            $info = $idIncidencia.'/'.$user;

            $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
            $cambio = [$enlace, $contenido, $info];
            $mensaje = str_replace($cambiar,$cambio,$plantilla);
            
            $message = html_entity_decode($mensaje);
        
            $tipoDoc = '';
            $attachment = '';
             
            enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
            
        }

    }

    public function reasignarTecnicos()
    {        
       
        $retorno = [
            'respuesta' =>0        
        ];     

        if (isset($_SESSION['idusuario']) && isset($_SESSION['nombrerol'])) {

            if ($_POST['idIncidencia'] && $_POST['idIncidencia']>0 && isset($_POST['nuevos']) && count($_POST['nuevos'])>0) {
                $idIncidencia = $_POST['idIncidencia'];
                $tecnicosNuevos = $_POST['nuevos'];

                $tecnicosActuales = $this->ModelIncidencias->obtenerTecnicosAsignadosParaIncidencia($idIncidencia);

                $arrTecActuales = json_decode($tecnicosActuales);

                $informarTecnicos = [];
                $idsTecnicosNuevos = [];

                foreach ($tecnicosNuevos as $codigoTecnico) {       
                    $idTecnico = $this->ModelIncidencias->obtenerIdTecnicoDesdeCodigoTecnico($codigoTecnico);
                    if (!in_array($idTecnico, $arrTecActuales)){
                        $informarTecnicos[] = $idTecnico;                    
                    }
                    $idsTecnicosNuevos[] = $idTecnico;
                }

                $jsonTecNuevos = json_encode($idsTecnicosNuevos);   
                $nombreNuevos = $this->obtenerStringNombresTecnicosAsignados($idsTecnicosNuevos);
                $upd = $this->ModelIncidencias->actulizarTecnicosNuevos($idIncidencia,$jsonTecNuevos,$nombreNuevos);
            
            
                if ($upd) {                
                                       
                    $retorno = [
                        'respuesta' =>1       
                    ]; 
                }
            
            }
        
        }
        echo json_encode($retorno);      
    }

    public function enviarEmailTecnicosAsignadosNuevos($idsTecnicos,$idIncidencia)
    {
       
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Reasignación de solicitud";
        $nombreUsuario =  $_SESSION['usuario'];        

        if ($idsTecnicos && count($idsTecnicos)>0) {
            
            foreach ($idsTecnicos as $key) {
             
                    $emailTecnico = $this->ModelIncidencias->obtenerCorreoDesdeIdusuario($key);
                    $emailsDestino = [];
                    if (isset($emailTecnico->correo) && $emailTecnico->correo !='' && $emailTecnico->recibemails==1) {
                        $emailsDestino[] = $emailTecnico->correo;
                      
                        //construyo cuerpo de mensaje         
                        $enlace = 'Haz click en el enlace para ver la solicitud.';
                        $contenido = 'El usuario '.$nombreUsuario.' te ha asignado la solicitud Nº '.$idIncidencia.'. Ingrese al siguiente enlace para verla.';

                        $info = $idIncidencia.'/'.$key;

                        $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                        $cambio = [$enlace, $contenido, $info];
                        $mensaje = str_replace($cambiar,$cambio,$plantilla);
                        
                        $message = html_entity_decode($mensaje);
                    
                        $tipoDoc = '';
                        $attachment = '';

                        enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                    }
                    
                
            }
        }          
    }
    
    public function enviarEmailPruebaAlexis()
    {
        $plantilla=$this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Reasignación de solicitud";
        $nombreUsuario =  $_SESSION['usuario'];        

        
             
                    $emailTecnico = 'alexisdiaz@solbyte.com';
                    $emailsDestino = [];
                    
                        $emailsDestino[] = 'alexisdiaz@solbyte.com';
                        $idIncidencia=1;
                        //construyo cuerpo de mensaje         
                        $enlace = 'Haz click en el enlace para ver la solicitud.';
                        $contenido = 'El usuario '.$nombreUsuario.' te ha asignado la solicitud Nº '.$idIncidencia.'. Ingrese al siguiente enlace para verla.';

                        $info = $idIncidencia.'/'.$key;

                        $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                        $cambio = [$enlace, $contenido, $info];
                        $mensaje = str_replace($cambiar,$cambio,$plantilla);
                        
                        $message = html_entity_decode($mensaje);
                    
                        $tipoDoc = '';
                        $attachment = '';

                    enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);

                    
                   
    }
    

    public function contruirHistorialTiempoPorIncidencia()
    {
        $retorno = [
            'respuesta' =>0,
            'html' =>''            
        ];

        if ($_POST['idIncidencia'] && $_POST['idIncidencia'] >0) {

            $atenciones = $this->ModelIncidencias->obtnerListadoDeAtencionesTecnicos($_POST['idIncidencia']);
            $verBotones = $this->validarQuienPuedeEditarLaAtencion();
            
            $html = '';
            if (isset($atenciones) && count($atenciones)>0) {
                $num = count($atenciones);
                
                //construir el <thead>
                $html .= $this->construirTheadTablaHistorialTiempos($num);

                $html .= '<tbody class="flex-1 sm:flex-none">';
                foreach ($atenciones as $key) {
                    
                    $creacion = $key->creacion;
                    $partes = explode(" ",$creacion);
                    $fechaCreacion = $partes[0];
                    $horaCreacionSeg = $partes[1];
                    $completoIni = explode(":",$horaCreacionSeg);
                    $horaCreacion = $completoIni[0].":".$completoIni[1];

                    $finalizacion = '';                    
                    $fechaFinalDato = '';
                    $horaFinalDato = '';
                    $tiempoHoras = 0;

                    if ($key->finalizacion !='' && $key->finalizacion>0 && $key->finalizacion !=null) {
                        $finalizacion = $key->finalizacion;
                        $partes = explode(" ",$finalizacion);
                        $fechaFinal = $partes[0];
                        $horaFinalSeg = $partes[1];
                        $completoFin = explode(":",$horaFinalSeg);
                        $horaFinal = $completoFin[0].":".$completoFin[1];

                        $fechaFinalDato = "<input type='date' value='".$fechaFinal."' id='fechaFin_".$key->id."'>";
                        $horaFinalDato = "<input type='time' value='".$horaFinal."' id='horaFin_".$key->id."'>";

                        //cálculo horas
                        $tiempoHoras = $this->tiempoTranscurridoFechasHorasFormatoMinutos($creacion,$finalizacion);                      
                    }
                    
                    $html .= "
                            <tr class='flex flex-col flex-no wrap sm:table-row mb-2 sm:mb-0'>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->id."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->modalidad."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'><input type='date' value='".$fechaCreacion."' id='fechaIni_".$key->id."'></td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'><input type='time' value='".$horaCreacion."' id='horaIni_".$key->id."'></td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$fechaFinalDato."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$horaFinalDato."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$tiempoHoras."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->tecnicos."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>";
                                if ($verBotones==1) {        
                                                                                               
                                $html .="<div clas='flex'>
                                    <a href='' class='actualizarTiempo' data-keyupd='".$key->id."'><i class='fas fa-edit mr-2 fill-current text-yellow-500 text-base'></i></a>
                                    <a href='' class='eliminarTiempo' data-keydel='".$key->id."'><i class='fas fa-trash-alt mr-2 fill-current text-red-600 text-base'></i></a></div>";
                                }
                                $html .="</td>
                            </tr>
                    ";                        

                }

                $html .= '</tbody>';
            }

            $retorno = [
                'respuesta' =>1,
                'html' => $html        
            ];
        }
        echo json_encode($retorno);    
    }

    public function construirTheadTablaHistorialTiempos($num)
    {
        $html = '<thead class="text-white">';
        for ($i=0; $i < $num ; $i++) {

            $html .= '<tr class="bg-pink-400 flex flex-col flex-no wrap sm:table-row rounded-l-lg sm:rounded-none mb-2 sm:mb-0">
                        <th class="p-1 text-left">Nº</th>
                        <th class="p-1 text-left">Modalidad</th>
                        <th class="p-1 text-left">F. Ini.</th>
                        <th class="p-1 text-left">Hora Ini.</th>
                        <th class="p-1 text-left">F. Fin</th>
                        <th class="p-1 text-left">Hora Fin</th>
                        <th class="p-1 text-left">Horas totales</th>
                        <th class="p-1 text-left">Técnicos</th>
                        <th class="p-1 text-left" width="110px">Acciones</th>
                    </tr>';


        }
        $html .= '</thead>';
        return $html;
    }

    public function mostrarListadoIncidenciasPendientes()
    {
        $retorno = [
            'respuesta' =>0,
            'html' =>''            
        ];

        if ($_POST['id'] && $_POST['id'] >0) {
            
            $pendientes = [];
            if ($_SESSION['nombrerol'] == 'tecnico') {
                $pendientes = $this->ModelIncidencias->incidenciasPendientesPorTencioAsignado($_POST['id']);
            }else if($_SESSION['nombrerol'] == 'admin'){
                $pendientes = $this->ModelIncidencias->todasLasIncidenciasPendientes();
            }
            $html = '';            
            if (isset($pendientes) && count($pendientes)>0) {                
                foreach ($pendientes as $key) {                    
                    $html .= "
                            <tr class='mb-2 sm:mb-0'>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->id."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->creacion."</td>                                
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>                                    
                                    <div class='flex-1'><form action='".RUTA_URL."/Incidencias/editarIncidencia' method='POST' title='ver'>
                                    <input type='number' class='hidden' name='id' value='".$key->id."'>
                                    <button type='submit' class='btnActualizar'><i class='fas fa-eye mr-2 fill-current text-yellow-500 text-2xl'></i></button>
                                    </form></div>
                                </td>
                            </tr>
                    ";
                }
            }

            $retorno = [
                'respuesta' =>1,
                'html' => $html        
            ];
        }
        echo json_encode($retorno);    
    }


    
    public function validarQuienPuedeEditarLaAtencion()
    {
        $permiso = 0;
        $rol = $_SESSION['nombrerol'];
        if ($rol == 'cliente') {
            $permiso = 0;
        }else{
            if ($rol == 'admin') {
                $permiso = 1;
            }else if( $rol == 'tecnico'){
                $tecnicoConPermiso = $this->tecnicoTienePermisoParaEditarEliminarAtencion($_SESSION['idusuario']);
                
                if ($tecnicoConPermiso==1) {
                    
                    $permiso = 1;
                }
            }
        }
       
        return $permiso;
    }

    public function tecnicoTienePermisoParaEditarEliminarAtencion($idusuario)
    {
        $permisoTecnico = $this->ModelIncidencias->obtenerMarcaPermisoTecnico($idusuario);
        return $permisoTecnico;
    }

    public function eliminarAtencion()
    {
        $retorno = ['respuesta' => 1 ];

        if (isset($_POST['idAtencion']) && $_POST['idAtencion'] >0) {

            $detalleAtencion = $this->ModelIncidencias->obtenerEstadoAtencion($_POST['idAtencion']);

            $del = $this->ModelIncidencias->eliminarAtencion($_POST['idAtencion']);

            if ($del == 1) {
                if ($detalleAtencion->play == 1) {

                    $datos = ['idIncidencia' => $detalleAtencion->idincidencia];
                    $this->ModelIncidencias->actualizarPlayStopIncidencia($datos,0);                    
                    
                }
                $retorno = ['respuesta' => 1];
            }
        }
        echo json_encode($retorno);

    }

    function tiempoTranscurridoFechas($fechaInicio,$fechaFin)
    {
        $tiempo = (strtotime($fechaFin) - strtotime($fechaInicio)) / 3600;        
        $horas = number_format($tiempo,2,",",".");
        return $horas;
    }

    public function tiempoTranscurridoFechasDesglosado($fechaInicio,$fechaFin)
    {
        $firstDate  = new DateTime($fechaInicio);
        $secondDate = new DateTime($fechaFin);
        $intvl = $firstDate->diff($secondDate);

        $dias = ($intvl->d) *24 ;   
        $horas = ($intvl->h);
        $minutos =  ($intvl->i) / 60;
        $segundos =  round((($intvl->s) / 3600),2); //aqui me qued
        $totalHoras = $dias + $horas;
        $totalMinutos = $minutos + $segundos;

        $tiempoTranscurrido = $totalHoras." h ".$totalMinutos." min";
        return $tiempoTranscurrido;            
    }

    function tiempoTranscurridoFechasHorasFormatoMinutos($fechaInicio,$fechaFin)
    {
        $tiempo = (strtotime($fechaFin) - strtotime($fechaInicio)) / 3600;        
        $horas = $tiempo;
                
        $partes = explode(".",$horas);
        $entero = $partes[0];
        $decimales =  $horas - $partes[0];
        $minutos = 0;
        if ($decimales >0) {
            $minutos = round($decimales*60);
        }
        $res = $entero." h ".$minutos." min";
        return $res;
    }
    

    public function crearIncidenciaTecnico()
    {
        $datos = [];      
        $clientes = '';

        if ($_SESSION['nombrerol'] == 'tecnico' ) {        
            $clientes = $this->ModelIncidencias->obtenerSucursalesDeClientesAsignadosATecnicos($_SESSION['idusuario']);
        }else{
            $clientes = $this->ModelIncidencias->obtenerClientes();
        }        
            
        $datos = [
            'clientes' => $clientes
        ];
        
      
        $this->vista('incidencias/crearNuevaIncidenciaTecnico', $datos);
    }

    
    public function llenarSelectorSucursalesParaTecnico()
    {
        $retorno = [];

        if (isset($_POST['idCliente']) && $_POST['idCliente']>0) {
            
            $sucursales = $this->ModelIncidencias->obtenerSucursalesPorCliente($_POST['idCliente']);

            if (count($sucursales) >0 ) {
                $options = '<option disabled selected>Seleccionar</option>';
                foreach ($sucursales as $sucursal) {
                    $options .= "<option value='".$sucursal->id."'>".$sucursal->nombre."</option>";
                }
                $retorno = [
                    'options' => $options
                ];
            }


        }
        print json_encode($retorno);        
    }

    public function registrarIncidenciaTecnico()
    {                                 
        $msgError = 'Ha ocurrido un error. No se puede registrar la solicitud porque falta completar datos en el formulario.';
        
        if ($_POST['cliente'] && $_POST['sucursalesTecnico'] && isset($_POST['equiposTecnico'])) {
                       
                $idsTecnicosAsig = $this->ModelIncidencias->obtenerIdsTecnicosAsignados($_POST['cliente']);
                $nombresTecnicos = $this->obtenerStringNombresTecnicosAsignados(json_decode($idsTecnicosAsig));
                
                date_default_timezone_set("Europe/Madrid");                      
                                
                $estadofactppto = 0;
                $nomestadofactppto = 'sin estado';
                if (isset($_POST['presupuestarEnCreacion']) && $_POST['presupuestarEnCreacion'] == 1) {
                    $estadofactppto = 2;
                    $nomestadofactppto = 'presupuestar';
                }

                $datos = [
                    'idsucursal' => $_POST['sucursalesTecnico'],
                    'idequipo' => $_POST['equiposTecnico'],
                    'descripcion' => $_POST['descripcion'],
                    'idusuario' => $_SESSION['idusuario'],
                    'idcliente' => $_POST['cliente'],
                    'creacion' => date("Y-m-d H:i:s"),
                    'estado' => 1, //por defecto 1-Pendiente
                    'activo' => 1,
                    'tecnicos' => $idsTecnicosAsig,
                    'nombresTecnicos' => $nombresTecnicos,
                    'nombreUsuario' => $_SESSION['usuario'],
                    'remoteADDR' => $_SERVER['REMOTE_ADDR'],
                    'estadofactppto' => $estadofactppto,
                    'nomestadofactppto' => $nomestadofactppto,
                    'fechahora' => !empty($_POST['fechahora'])? $_POST['fechahora']:null
                ];                             

                $ins = $this->ModelIncidencias->insertarNuevaSolicitud($datos);
                if ($ins >0) {
                    $_SESSION['message'] = 'Se ha registrado la solicitud corréctamente.';
                    if (isset($_FILES['ficheroCrearIncidencia']) && count($_FILES['ficheroCrearIncidencia']['size']) >0) {
                        $this->construirDatosFicherosAInsertar($_FILES,$ins);
                    }                                        
                    $this->enviarEmailAcuseDeReciboCliente($ins,$datos);
                    $this->enviarEmailALosTecnicosAsignadosAlCliente($ins,$datos);
                }else{
                    $_SESSION['message'] = $msgError;
                }
            
        }else{
            $_SESSION['message'] = $msgError;
        }
        redireccionar('/Incidencias');

    }

    public function numeroNotificacionesIncidencias()
    {
        $pendientes = 0;
        if ($_POST['id'] && $_POST['id'] >0) {
            if ($_SESSION['nombrerol'] == 'tecnico') {                
                $pendientes = $this->ModelIncidencias->contarNotificacionesPendientesPorTecnico($_POST['id']);
            }else if($_SESSION['nombrerol'] == 'admin'){                
                $pendientes = $this->ModelIncidencias->contarTodasLasNotificacionesPendientes();
            }         
            echo $pendientes;
        }

    }

    public function verHistorialHorasContratadas()
    {        
        $retorno = [
            'respuesta' => 1,
            'tabla' => $this->contruirTablaHistorialBolsaHoras()
        ];
        print json_encode($retorno);  

    }
    
    public function contruirTablaHistorialBolsaHoras() 
    {               
        $html = '
        <div class="my-2 flex sm:flex-row flex-col">
            <div class="flex flex-row mb-1 sm:mb-0">
                <div class="relative flex" id="buscadorHoras">
                </div>                
            </div>            
        </div>        
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <div id="destinoequiposhorasbolsaajax"></div>
            </div>
        </div>                
        <div id="paginadorHoras"></div>
        <script  type="module">
            import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClassSinBuscador.js" 
            arrancar("tablabolsahoras","Incidencias/crearTablaBolsaHoras", "destinoequiposhorasbolsaajax", "tie.creacion", "DESC", 0, "buscadorHoras","Incidencias/totalRegistrosBolsaHoras", [10, 20, 30],"min-w-full leading-normal","paginadorHoras",[""],"","");
        </script>
        ';

        return $html;
    }

    public function crearTablaBolsaHoras()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];                
        }

        $idUsuario = $_SESSION['idusuario'];
        $idCliente = $this->ModelIncidencias->obtenerIdClientePorIdusuario($idUsuario);
                     
        $cond = " tie.idcliente = '$idCliente' ";        

        $filaspagina = $filas * $pagina;
        
        
        $incidencias = $this->ModelIncidencias->horasConsumidasHorasContratadasTablaClass($filas,$orden,$filaspagina,$tipoOrden,$cond);
       
        //=======
        if (count($incidencias) >0) {
            foreach ($incidencias as $key) {
                if (isset($key->horascons) && $key->horascons >0) {
                    $horasCons = $key->horascons;
                    $partes = explode(".",$horasCons);
                    $entero = $partes[0];
                    $decimales =  $horasCons - $partes[0];
                    $minutos = 0;
                    if ($decimales >0) {
                        $minutos = round($decimales*60);
                    }
                    $res = $entero." h ".$minutos." min";
                    $key->horascons = $res;
                }
            }
                
        }

        //=======
        print(json_encode($incidencias));  
    } 

    public function totalRegistrosBolsaHoras()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];            
        }
        $idUsuario = $_SESSION['idusuario'];
        $idCliente = $this->ModelIncidencias->obtenerIdClientePorIdusuario($idUsuario);
                     
        $cond = " tie.idcliente = '$idCliente' ";
    
        /*
        if ($buscar != "") {            
            $datos = json_decode($buscar);
            $cond .= $this->construirCondicionesBuscar($datos);           
        }*/
        $contador = $this->ModelIncidencias->totalRegistrosBolsaHoras($cond);

        //$cont = $contador->contador;        
        print_r($contador);
    } 

    public function actualizarFechasYHorasAtencion()
    {  
        $retorno = [
            'respuesta' =>0,
            'html' =>''            
        ];
         
        if ($_POST['idAtencion'] && $_POST['idAtencion'] >0 && isset($_POST['fechaInicio']) && isset($_POST['horaInicio']) ) {

            $incidencia = $this->ModelIncidencias->obtenerIdIncidenciaDesdeIdAtencion($_POST['idAtencion']);
            
            $permisoActualizar = 0;
            $idTecnicoIniciador = $this->ModelIncidencias->idTecnicoQueInicioLaAtencion($_POST['idAtencion']);
            $verBotones = $this->validarQuienPuedeEditarLaAtencion();

            if ($_SESSION['nombrerol']=='admin') {
                $permisoActualizar =1;
            }else if($_SESSION['nombrerol']=='tecnico' && $_SESSION['idusuario']== $idTecnicoIniciador){
                $permisoActualizar = 1;
            }

            if ($permisoActualizar == 1) {
                $upd = $this->construirDatosParaActualizarFechasYHorasDeAtencionTecnico($_POST);

                $atenciones = $this->ModelIncidencias->obtnerListadoDeAtencionesTecnicos($incidencia->idincidencia);
                $html = '';
                if (isset($atenciones) && count($atenciones)>0) {
                    $num = count($atenciones);
                    
                    //construir el <thead>
                    $html .= $this->construirTheadTablaHistorialTiempos($num);

                    $html .= '<tbody class="flex-1 sm:flex-none">';
                    foreach ($atenciones as $key) {
                        
                        $creacion = $key->creacion;
                        $partes = explode(" ",$creacion);
                        $fechaCreacion = $partes[0];
                        $horaCreacionSeg = $partes[1];
                        $completoIni = explode(":",$horaCreacionSeg);
                        $horaCreacion = $completoIni[0].":".$completoIni[1];

                        $finalizacion = '';                    
                        $fechaFinalDato = '';
                        $horaFinalDato = '';
                        $tiempoHoras = 0;

                        if ($key->finalizacion !='' && $key->finalizacion>0 && $key->finalizacion !=null) {
                            $finalizacion = $key->finalizacion;
                            $partes = explode(" ",$finalizacion);
                            $fechaFinal = $partes[0];
                            $horaFinalSeg = $partes[1];
                            $completoFin = explode(":",$horaFinalSeg);
                            $horaFinal = $completoFin[0].":".$completoFin[1];

                            $fechaFinalDato = "<input type='date' value='".$fechaFinal."' id='fechaFin_".$key->id."'>";
                            $horaFinalDato = "<input type='time' value='".$horaFinal."' id='horaFin_".$key->id."'>";

                            //cálculo horas
                            $tiempoHoras = $this->tiempoTranscurridoFechasHorasFormatoMinutos($creacion,$finalizacion);                      
                        }
                        
                        $html .= "
                                <tr class='flex flex-col flex-no wrap sm:table-row mb-2 sm:mb-0'>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->id."</td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->modalidad."</td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'><input type='date' value='".$fechaCreacion."' id='fechaIni_".$key->id."'></td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'><input type='time' value='".$horaCreacion."' id='horaIni_".$key->id."'></td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'>".$fechaFinalDato."</td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'>".$horaFinalDato."</td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'>".$tiempoHoras."</td>
                                    <td class='border-grey-light border hover:bg-gray-100 p-1'>";
                                    if ($verBotones==1) {        
                                                                                                
                                    $html .="<div clas='flex'>
                                        <a href='' class='actualizarTiempo' data-keyupd='".$key->id."'><i class='fas fa-edit mr-2 fill-current text-yellow-500 text-base'></i></a>
                                        <a href='' class='eliminarTiempo' data-keydel='".$key->id."'><i class='fas fa-trash-alt mr-2 fill-current text-red-600 text-base'></i></a></div>";
                                    }
                                    $html .="</td>
                                </tr>
                        ";                        

                    }

                    $html .= '</tbody>';
                }


                if ($upd) {
                    $retorno = [
                        'respuesta' =>1,
                        'html' => $html        
                    ];        
                }else{
                    $retorno = [
                        'respuesta' =>0,
                        'html' => $html        
                    ];
                }
            }else{
                $retorno = [
                    'respuesta' =>2,
                    'html' => ''        
                ];
            }            
        }
        echo json_encode($retorno);    
    }

    public function construirDatosParaActualizarFechasYHorasDeAtencionTecnico($post)
    {        
        $idAtencion = $post['idAtencion'];
        $fechaInicio = $post['fechaInicio'];
        $horaInicio = $post['horaInicio'];

        $horaFin = '00:00:00';
        if (isset($post['fechaFin'])) {
            $horaFin = $post['horaFin'];
        }  

        $fechaFin = '000-00-00';
        $tiempoTranscurrido = 0;
        
        if (isset($post['fechaFin'])) {
            $fechaFin = $post['fechaFin'];

            //=======
            $firstDate  = new DateTime($fechaInicio." ".$horaInicio);
            $secondDate = new DateTime($fechaFin." ".$horaFin);
            $intvl = $firstDate->diff($secondDate);
    
            $dias = ($intvl->d) *24 ;   
            $horas = ($intvl->h);
            $minutos =  ($intvl->i) / 60;
            $segundos =  ($intvl->s) / 3600;
            $totalHoras = $dias + $horas + $minutos + $segundos;            
            $tiempoTranscurrido = round($totalHoras,2);
            //=======
        }
      

        $creacion = $fechaInicio." ".$horaInicio;
        $finalizacion = $fechaFin." ".$horaFin;     
               
        $upd = $this->ModelIncidencias->actualizarFechasYHorasDeAtencionTecnico($idAtencion,$creacion,$finalizacion,$tiempoTranscurrido);
        if ($upd) {
            return 1;
        }else{
            return 0;
        }

    }

    public function buscarImagenIncidencia()
    {
        $retorno = [                    
            'base' => ''
        ];
        if (isset($_POST['idFichero']) && $_POST['idFichero'] >0) {
            $sel = $this->ModelIncidencias->obtnerImagenFicheroDesdeIdFichero($_POST['idFichero']);
            if (isset($sel->base) && $sel->base !='') {
                $retorno = [                    
                    'base' => $sel->base            
                ];
            }
        }
        echo json_encode($retorno);    
    }

    public function guardarComentarioInterno()
    {
        $retorno = [
            'respuesta' => 0,
            'html' => ''
        ];

        if ($_POST['comentario'] !='' && $_POST['idIncidencia'] >0 && $_POST['idEquipo'] >0) {

            $idIncidencia = $_POST['idIncidencia'];
            $comentario = $_POST['comentario'];
            $idUsuario = $_SESSION['idusuario'];
            $rol = $_SESSION['nombrerol'];
            $idEquipo = $_POST['idEquipo'];
            $tipo = $_POST['tipoComentario'];
            $valoracion = 0;

            $idComentario = $this->ModelIncidencias->insertarComentarioFinalizarIncidencia($idIncidencia,$comentario,$idUsuario,$rol,$idEquipo,$tipo,$valoracion);

            $class= "bg-red-400";
            if($tipo=='externo'){
                $class = 'bg-gray-comentario';
            }

            $html = '                      
                    <div class="flex md:contents" id="comentario_id_'.$idComentario.'">
                        <div class="col-start-2 col-end-4 mr-10 md:mx-auto relative">
                            <div class="h-full w-6 flex items-center justify-center">
                            <div class="h-full w-1 '.$class.' pointer-events-none"></div>
                            </div>
                            <div class="w-6 h-6 absolute top-1/2 -mt-3 rounded-full '.$class.' shadow text-center">
                            <i class="fas fa-check-circle text-white"></i>
                            </div>
                        </div>
                        <div class="'.$class.' col-start-4 col-end-12 p-4 rounded-xl my-4 mr-auto shadow-md w-full">
                         
                            <button class="right-2 text-red-500 hover:text-red-700 focus:outline-none eliminarComentario float-right" title="Eliminar comentario" data-idcomentario="'.$idComentario.'"> <i class="fas fa-trash-alt"></i></button>

                            <h3 class="font-semibold text-lg mb-1">'.$comentario.'</h3>
                            <p class="leading-tight text-justify w-full">
                            '.date('d/m/Y H:i').'
                            </p>
                            <p class="leading-tight text-justify w-full">
                            '.$_SESSION['usuario'].'
                            </p> 
                        </div>
                    </div>';

            $retorno = [
                'respuesta' => 1,
                'html' => $html
            ]; 
        }
        echo json_encode($retorno);

    }

    public function guardarComentarioDelCliente()
    {
        $retorno = [
            'respuesta' => 0,
            'html' => ''
        ];

        if ($_POST['comentario'] !='' && $_POST['idIncidencia'] >0 && $_POST['idEquipo'] >0) {

            $idIncidencia = $_POST['idIncidencia'];
            $comentario = $_POST['comentario'];
            $idUsuario = $_SESSION['idusuario'];
            $rol = $_SESSION['nombrerol'];
            $idEquipo = $_POST['idEquipo'];
            $tipo = 'externo';
            $valoracion = 0;
            date_default_timezone_set("Europe/Madrid");
            $fecha = date('d/m/Y H:i');

            $ins = $this->ModelIncidencias->insertarComentarioDelClientesIncidencia($idIncidencia,$comentario,$idUsuario,$rol,$idEquipo,$tipo,$valoracion);

            if ($ins >0) {
                    
                $this->gestionarNotificacionesDeComentariosNuevosATecnicosYAdministradores($ins,$idIncidencia);

                $html = '                      
                        <div class="flex md:contents">
                            <div class="col-start-2 col-end-4 mr-10 md:mx-auto relative">
                                <div class="h-full w-6 flex items-center justify-center">
                                <div class="h-full w-1 bg-tex-lila pointer-events-none"></div>
                                </div>
                                <div class="w-6 h-6 absolute top-1/2 -mt-3 rounded-full bg-tex-lila shadow text-center">
                                <i class="fas fa-check-circle text-white"></i>
                                </div>
                            </div>
                            <div class="bg-tex-lila col-start-4 col-end-12 p-4 rounded-xl my-4 mr-auto shadow-md w-full">
                                <h3 class="font-semibold text-lg mb-1">'.$comentario.'</h3>
                                <p class="leading-tight text-justify w-full">
                                '.$fecha.'
                                </p>
                                <p class="leading-tight text-justify w-full">
                                '.$_SESSION['usuario'].'
                                </p> 
                            </div>
                        </div>';

                $retorno = [
                    'respuesta' => 1,
                    'html' => $html
                ]; 
            }
            
        }
        echo json_encode($retorno);

    }

    public function gestionarNotificacionesDeComentariosNuevosATecnicosYAdministradores($idComentario,$idIncidencia)
    {
        $idUsuario = $_SESSION['idusuario'];

        $jsonTecnicos = $this->ModelIncidencias->obtenerTecnicosAsignadosParaIncidencia($idIncidencia);

        $usuariosAdmin = $this->ModelIncidencias->obtenerIdsTodosLosUsuariosAdminActivos();
        
            $arrTecnicos = [];
            if (isset($jsonTecnicos)) {
                $arrTecnicos = json_decode($jsonTecnicos);
            }

            $admins = [];
            if (isset($usuariosAdmin) && count($usuariosAdmin)>0) {                
                if (isset($usuariosAdmin) && count($usuariosAdmin)>0) {
                    foreach ($usuariosAdmin as $key) {
                        $admins[] = $key->id;                    
                    }
                }
            }                  
            
            $todos = array_merge($arrTecnicos,$admins);
            if (count($todos) > 0) {
                foreach ($todos as $iddestinatario) {
                    $this->ModelIncidencias->insertarAlertaComentarioNuevo($idComentario,$idIncidencia,$iddestinatario,$idUsuario);
                }  
            }
                                  

    }

    public function eliminarIncidencias()
    {
        $retorno = 0;

        if (isset($_POST['idIncidencia']) && $_POST['idIncidencia'] > 0) {
            $del = $this->ModelIncidencias->eliminarIncidencia($_POST['idIncidencia']);

            if ($del == 1) {
                $this->ModelIncidencias->eliminarComentariosIncidencia($_POST['idIncidencia']);
                $this->ModelIncidencias->eliminarFicherosIncidencia($_POST['idIncidencia']);
                $this->ModelIncidencias->eliminarTiemposIncidencia($_POST['idIncidencia']);
                $retorno = 1;
            }
        }
        echo json_encode($retorno);
    }

    public function reabrirIncidencia()
    {
        $retorno = 0;
        
        if (isset($_POST['idIncidencia']) && $_POST['idIncidencia'] > 0) {
            $upd = $this->ModelIncidencias->reabrirIncidencia($_POST['idIncidencia']);
            $idEquipo = $this->ModelIncidencias->obtenerIdEquipoPorIncidencia($_POST['idIncidencia']);

            if ($upd == 1) {
                $comentario = "Se ha reabierto la solicitud";
                $this->ModelIncidencias->insertarComentarioFinalizarIncidencia($_POST['idIncidencia'],$comentario,$_SESSION['idusuario'],$_SESSION['nombrerol'],$idEquipo,'externo',0);           
                $retorno = 1;
            }
        }
        
        echo json_encode($retorno);
    }

    
    public function numeroComentariosNuevosSinLeer()
    {    
        $pendientes = 0;

        if(isset($_SESSION['idusuario']) && $_SESSION['idusuario'] >0){
            $idUsuario = $_SESSION['idusuario'];
            $pendientes = $this->ModelIncidencias->contarComentariosNoLeidosPorUsuario($idUsuario);
        }                     
        echo $pendientes;
    }

    public function mostrarListadoComentariosNoLeidos()
    {
        $retorno = [
            'respuesta' =>0,
            'html' =>''            
        ];

        if(isset($_SESSION['idusuario']) && $_SESSION['idusuario'] >0){
            $idUsuario = $_SESSION['idusuario'];           
            
            $comentarios = $this->ModelIncidencias->comentariosNoLeidosPorUsuario($idUsuario);

            $html = '';            
            if (isset($comentarios) && count($comentarios)>0) {
                foreach ($comentarios as $key) {                    
                    $html .= "
                            <tr class='mb-2 sm:mb-0'>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->idincidencia."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->creacion."</td>
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>".$key->nombreusuario."</td>                            
                                <td class='border-grey-light border hover:bg-gray-100 p-1'>                                    
                                    <div class='flex-1'><form action='".RUTA_URL."/Incidencias/verIncidenciaDesdeAlertaNavBar' method='POST' title='ver'>
                                    <input type='number' class='hidden' name='idIncVerComentario' value='".$key->idincidencia."'>
                                    <input type='number' class='hidden' name='idAlertaComentario' value='".$key->idalerta."'>
                                    <button type='submit' class='btnVerComentarioInc'><i class='fas fa-eye mr-2 fill-current text-yellow-500 text-sm'></i></button>
                                    </form></div>
                                </td>
                            </tr>
                    ";
                }
                    
                $retorno = [
                    'respuesta' =>1,
                    'html' => $html        
                ];
            }         
        }
        echo json_encode($retorno);    
    }

    public function verIncidenciaDesdeAlertaNavBar()
    {
        if (isset($_POST['idIncVerComentario']) && $_POST['idIncVerComentario'] > 0 &&  isset($_POST['idAlertaComentario']) && $_POST['idAlertaComentario'] > 0 ) {
            
            $del = $this->ModelIncidencias->eliminarRegistroComentarioNoLeido($_POST['idAlertaComentario']);
            
            if ($del) {            
                $_SESSION['idIncidenciaAlerta'] = $_POST['idIncVerComentario'];
                redireccionar('/Incidencias/editarIncidencia');

            }else{
                redireccionar('/Incidencias');
            }

        }else{
            redireccionar('/Incidencias');
        }
    }

    public function contruirSelectTecnicosDisponibles()
    {
        $retorno = [
            'respuesta' =>0,
            'html' =>''            
        ];

        if ($_POST['idIncidencia'] && $_POST['idIncidencia'] >0) {
            $tecnicosAsignados = $this->ModelIncidencias->obtenerTecnicosAsignadosParaIncidencia($_POST['idIncidencia']);
            $idesTecnicos = [];
            if (isset($tecnicosAsignados) && $tecnicosAsignados != '' && count(json_decode($tecnicosAsignados)) >0 ) {                
                $idesTecnicos = json_decode($tecnicosAsignados);                
            }            

            $todos = $this->ModelIncidencias->listaTecnicosActivos();            
            $options = '<option disabled selected value="0">Seleccionar técnico</option>';
            foreach ($todos as $tecnico) {
                if (!in_array($tecnico->id,$idesTecnicos)) {                    
                    $options .= "<option value='".$tecnico->id."'>".$tecnico->nombre." ".$tecnico->apellidos."</option>";
                }
            }
            $retorno = [
                'respuesta' =>1,
                'html' => $options
            ];
        }        
        echo json_encode($retorno);
    }

    public function tecnicoRechazaIncidenciaYReasigna()
    {       
        $retorno = ['respuesta' =>0];

        if (isset($_POST['idIncidencia']) && $_POST['idIncidencia'] != '') {
            
            $idIncidencia = $_POST['idIncidencia'];

            $tecnicosAsignados = $this->ModelIncidencias->obtenerTecnicosAsignadosParaIncidencia($idIncidencia);
            
            $arridsTecnicos = [];
            if (isset($tecnicosAsignados) && $tecnicosAsignados != ''){
                $arrIds = json_decode($tecnicosAsignados);

                if (isset($arrIds) && count($arrIds) >0) {
                    foreach ($arrIds as $key) {
                        if ($key != $_SESSION['idusuario']) {
                            $arridsTecnicos[] = $key;
                        }                                                        
                    }
                }
            }         

            $nuevo = 0;
            if (isset($_POST['nuevoTecnico']) && $_POST['nuevoTecnico'] != '' && !in_array($_POST['nuevoTecnico'],$arridsTecnicos)) {         
                $nuevo = $_POST['nuevoTecnico'];
                $arridsTecnicos[] = $_POST['nuevoTecnico'];               
            }

            $nombresNuevos = '';
            if (count($arridsTecnicos) > 0) {
                $nombresNuevos = $this->obtenerStringNombresTecnicosAsignados($arridsTecnicos);
            }            
            
            $upd = $this->ModelIncidencias->actulizarTecnicosNuevos($idIncidencia,json_encode($arridsTecnicos),$nombresNuevos);
			
            if ($upd == 1) {                
                if ($nuevo > 0) {
                    $this->enviarEmailTecnicosAsignadosNuevos(Array($_POST['nuevoTecnico']),$idIncidencia);
                }                                         
                $this->enviarEmailPorRechazoTecnico(Array(2),$idIncidencia,$nuevo);                              
                $retorno = ['respuesta' =>1];
            }               
        }        
        echo json_encode($retorno);
    }

    public function enviarEmailPorRechazoTecnico($idsTecnicos,$idIncidencia,$nombreNuevo)
    {
       
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaboton.php");

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Rechazo de solicitud";
        $nombreUsuario =  $_SESSION['usuario'];        

        if ($idsTecnicos && count($idsTecnicos)>0) {
            
            foreach ($idsTecnicos as $key) {             		

                    $emailTecnico = $this->ModelIncidencias->obtenerCorreoDesdeIdusuario($key);
                    $emailsDestino = [];
					
					

                    if (isset($emailTecnico->correo) && $emailTecnico->correo !='' && $emailTecnico->recibemails==1) {
                        $emailsDestino[] = $emailTecnico->correo;
                      
						
                        //construyo cuerpo de mensaje
                        $tecnicoAsignado = '';
					
						
                        if ($nombreNuevo > 0) {
                            $nombreTecnico = $this->obtenerStringNombresTecnicosAsignados(Array($nombreNuevo));
							
							
                            $tecnicoAsignado = ' y la ha asignado al tecnico '.substr($nombreTecnico, 0, -1);
							
						
							
                        }
                        $enlace = 'Haz click en el enlace para ver la solicitud.';
                        $contenido = 'El usuario '.$nombreUsuario.' ha rechazado la solicitud Nº '.$idIncidencia.' '.$tecnicoAsignado.'. Ingrese al siguiente enlace para verla.';
						
						
						
						

                        $info = $idIncidencia.'/'.$key;

                        $cambiar = ['{ENLACE}','{CONTENIDO}','{INCIDENCIA}'];
                        $cambio = [$enlace, $contenido, $info];
                        $mensaje = str_replace($cambiar,$cambio,$plantilla);
                        
                        $message = html_entity_decode($mensaje);
                    
                        $tipoDoc = '';
                        $attachment = '';

                        enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);
                    }
                    
                
            }
        }  
		
		
    }

    public function guardarFirma()
    {
        $retorno = [
            'respuesta' => 0,
            'html' => ''
        ];       

        if (!empty($this->fetch['imagen']) && $this->fetch['idIncidencia'] >0) {

            $idIncidencia = $this->fetch['idIncidencia'];
            $imagenBase64 = $this->fetch['imagen'];
            $guardada = 1;           

            $upd = $this->ModelIncidencias->guadarImagenFirmaincidencia($idIncidencia,$imagenBase64,$guardada);

            $html = '';
            if($upd){
                $html = '<div class="text-gray-500" id="contenedorFirma1"><img id="imagenFirma" class="border-2 border-coolGray-300" src="'.$imagenBase64.'" /></div>';
            }            

            $retorno = [
                'respuesta' => $upd,
                'html' => $html
            ]; 
        }
        echo json_encode($retorno);

    }

    
   public function enviarIdIncidenciaGenerarPdf()
    {
        $respuesta['error'] = true;
        $respuesta['mensaje'] = 'No se puede generar el pdf de la factura.';   
        if(isset($this->fetch['id']) && $this->fetch['id'] > 0){
            $_SESSION['idIncidenciaSendPdf'] = $this->fetch['id'];
            $respuesta['error'] = false;
            $respuesta['mensaje'] = '';   
        }
        print_r(json_encode($respuesta));   
    }    

    public function exportarPdfIncidencia()
    {      
       
        if(isset($_SESSION['idIncidenciaSendPdf']) && $_SESSION['idIncidenciaSendPdf'] >0){

            $idIncidencia = $_SESSION['idIncidenciaSendPdf'];
                        
            $detalles = $this->ModelIncidencias->obtenerDatosIncidencia($idIncidencia);               
            $imagenes = $this->ModelIncidencias->obtnerListadoFicherosImagenesTodas($idIncidencia);        

            $documentos = $this->ModelIncidencias->obtnerListadoFicherosDocumentos($idIncidencia);                

            $comentarios = $this->ModelIncidencias->obtenerTodosLosComentariosExternosPorIdIncidencia($idIncidencia);
            $ficherosComentarios =$this->ModelIncidencias->obtenerTodosLosFicherosDeUnIncidencia($idIncidencia);
       

            $optionEstados = '';            
            if ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {
                $apartadoFactPpto = $this->montarOptionesSelectEstadoParaEditIncidencia($idIncidencia);
                $optionEstados = $apartadoFactPpto;              
            }                
            
            $consultHaistorialEstados = $this->ModelIncidencias->obtenerComentariosFacturarPresupuestar($idIncidencia);
            $historialEstados = [];
            if (isset($consultHaistorialEstados) && count($consultHaistorialEstados)>0) {
                $historialEstados = $consultHaistorialEstados;
            }

            $datos = [
                'detalles' => $detalles,                
                'imagenes' => $imagenes,
                'documentos' => $documentos,
                'comentarios' => $comentarios,
                'ficherosComentarios' => $ficherosComentarios,
                'optionEstados' => $optionEstados,                
                'historialEstados' => $historialEstados                
            ]; 
                    
            generarPdf::documentoPDFExportar('P', 'A4', 'es', true, 'UTF-8', array(0, 5, 0, 10), true, 'documentos', 'parte.php', $datos);                    

        }else{
            echo"<br>error en la generación del pdf de la factura<br>";
            die;
        }

    }

    public function modificarCampoIncidencia()
    {
        $retorno = false;
        $errores = [];

        if (isset($_SESSION['idusuario']) && isset($_SESSION['nombrerol'])) {

            if ($_POST['idIncidencia'] && $_POST['idIncidencia']>0) {

                $idIncidencia = $_POST['idIncidencia'];
                $fields = isset($_POST['fields']) ? $_POST['fields'] : [];

                // Actualizar cliente y técnicos, capturando los nombres de técnicos si el cliente cambia
                $nombresTecnicos = $this->actualizarClienteYTecnicos($idIncidencia, $fields);

                foreach ($fields as $field => $value) {
                                        
                    $upd = $this->ModelIncidencias->actualizarCampoIncidencia($idIncidencia, $field, $value);
                    if (!$upd) {                        
                        $errores[] = $field;
                    }
                    if($field=='idequipo'){                                      
                        $this->ModelIncidencias->actualizarIdEquipoEnIncidenciasComentarios($idIncidencia, $value);
                        $this->ModelIncidencias->actualizarIdEquipoEnIncidenciasTiempos($idIncidencia, $value);
                    }                       
                    
                }
                    
                if (empty($errores)) {
                    $retorno = true; 
                }
            }
        
        }
        
        if ($retorno) {            
            $response = ['success' => true];            
            if ($nombresTecnicos !== null) {
                $response['tecnicos'] = $nombresTecnicos;
            }
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudieron actualizar: ' . implode(', ', $errores)]);
        }
    }

    private function actualizarClienteYTecnicos($idIncidencia, &$fields)
    {
        $nombresTecnicos = null;
    
        if (isset($fields['idcliente'])) {
            $idClienteNuevo = $fields['idcliente'];
    
            // Obtener idcliente actual en la incidencia
            $idClienteActual = $this->ModelIncidencias->idClientePorIncidencia($idIncidencia);
    
            // Si el nuevo idcliente es diferente al actual
            if ($idClienteNuevo != $idClienteActual) {
                // Actualizar idcliente en las tablas relacionadas
                $actualizado = $this->ModelIncidencias->actualizarIdClienteEnIncidenciasTiempos($idIncidencia, $idClienteNuevo);
    
                if ($actualizado) {
                    // Obtener IDs de técnicos asignados y nombres
                    $idsTecnicosAsig = $this->ModelIncidencias->obtenerIdsTecnicosAsignados($idClienteNuevo);
                    $nombresTecnicos = $this->obtenerStringNombresTecnicosAsignados(json_decode($idsTecnicosAsig));
    
                    // Actualizar los campos de técnicos en la incidencia
                    $fields['tecnicos'] = $idsTecnicosAsig;
                    $fields['nombrestecnicos'] = $nombresTecnicos;

                    // Actualizar cliente en factura
                    $this->actualizarClienteEnFacturaAsociadaAIncidencia($idIncidencia, $fields);

                }
            }
        }
    
        return $nombresTecnicos; // Retornamos los nombres de los técnicos si cambiaron
    }

    private function actualizarClienteEnFacturaAsociadaAIncidencia($idIncidencia, $fields)
    {
        // Verificar si la incidencia tiene factura asociada
        $idFactura = $this->ModelIncidencias->verificaSiIncidenciaTieneFacturaAsociada($idIncidencia);
        
        if ($idFactura > 0) {
            // Obtener nuevo idcliente y cliente de los datos de la incidencia
            $idClienteNuevo = isset($fields['idcliente']) ? $fields['idcliente'] : null;        
            $clienteNuevo = $this->ModelClientes->obtenerNombreClientePorId($idClienteNuevo);

            if ($idClienteNuevo && $clienteNuevo) {
                // Actualizar los campos en la tabla facturasclientes
                $this->ModelFacturasCliente->actualizarClienteFactura($idFactura, $idClienteNuevo, $clienteNuevo);
            }
        }
    }


    

    public function obtenerEquipoPorSucursal()
    {                
        $sel = [];  
        if (!empty($_POST['idSucursal'])) {
            $idSucursal = $_POST['idSucursal'];
            
            // Si es carga inicial o no hay término de búsqueda
            if (isset($_POST['cargarIniciales']) && $_POST['cargarIniciales'] === 'true') {
                $sel = $this->ModelClientes->obtenerPrimeros20EquiposSucursal($idSucursal);
            } 
            // Si hay término de búsqueda
            else if (!empty($_POST['q'])) {
                $searchTerm = $_POST['q'];
                $sel = $this->ModelClientes->buscarEquipoSucursalPorTexto($idSucursal, $searchTerm);
            }
        }
        echo json_encode($sel);
    }
    

    
    public function eliminarComentario()
    {
            $retorno = false;        
        
            if (!empty($_POST['idcomentario']) && !empty($_POST['idincidencia'])) {

                $idComentario = $_POST['idcomentario'];

                $del = $this->ModelIncidencias->eliminarComentarioByIdComentario($idComentario);              

                if($del){
                    $retorno=true;                  

                    $allFiles = $this->ModelIncidencias->obtenerTodosLosFicherosDeUnComentario($idComentario);
                   
                    if(!empty($allFiles)){                                            
                    
                        foreach ($allFiles as $file) {                            
                            $path = DOCS_TRABAJOS_TERMINADOS . $file->nombre;
                            $delFile = $this->ModelIncidencias->eliminarFicheroComentario($file->id);
                            if($delFile){
                                if (file_exists($path)) {
                                    unlink($path);
                                }
                            }
                        }
                    }                    
                }

            }
               
            echo json_encode($retorno);
    }


    public function eliminarFicheroIncidencia()
    {
            $retorno = false;        
        
            if (!empty($_POST['idfichero']) && !empty($_POST['idincidencia'])) {

                    $idFichero = $_POST['idfichero'];                                               

                    $ficheroNombre = $this->ModelIncidencias->obtenerNombreFicheroPorId($idFichero);
                    if(!empty($ficheroNombre)){
                        
                        $delFile = $this->ModelIncidencias->eliminarFicheroIncidencia($idFichero);
                        if($delFile){
                            $retorno=true;       
                            $path = DOCS_INCIDENCIAS . $ficheroNombre;
                            if (file_exists($path)) {
                                unlink($path);
                            }
                        }
                    }
                    
            }
               
            echo json_encode($retorno);
    }

    public function agregarFicheroIncidenciaEditar(){

        $retorno["html"]="";
        
        if (!empty($_POST['idIncidencia']) ) {

            if (!empty($_FILES['formularioSubirFicheroIncidencia']['name'][0])) {
                $html = $this->construirDatosFicherosIncidenciaEditar($_FILES['formularioSubirFicheroIncidencia'], $_POST['idIncidencia']);      
                $retorno["html"]=$html;
            }                       
            
        }
        echo json_encode($retorno);

    }

    public function construirDatosFicherosIncidenciaEditar($files,$idIncidencia)
    {
        $nombres = $files['name'];
        $tipos = $files['type'];
        $tamanios = $files['size'];
        $temporales = $files['tmp_name'];
        $errores = $files['error'];

        $documentos = [];
        $imagenes = [];
        $htmlFicheros = '';

        for ($i=0; $i < count($nombres); $i++) { 
            if ($tamanios[$i] >0 && $tamanios[$i] <= 6000000 && $errores[$i] == 0 ) {
                                
                $extInst = new SplFileInfo($nombres[$i]);                                
                $extension = strtolower($extInst->getExtension());                
                
                $extensionesImg = ["jpeg", "jpg", "png", "gif", "bmp", "svg"]; 
                $extensionesDoc = ["doc", "docx", "docm", "xlsx", "xlsm", "pptx", "pptm", "csv", "pdf", "xls", "mp3", "wav", "ogg", "mp4"]; 
                    
                if (in_array($extension, $extensionesImg)) {
                    
                    $tmp = [];
                    $tmp['nombre'] = $idIncidencia."_".$nombres[$i];
                    $tmp['tipo'] = $tipos[$i];
                    $tmp['tamanio'] = $tamanios[$i];
                    $tmp['tmp'] = $temporales[$i];
                    $imagenes[] = $tmp;

                    $path = $temporales[$i];                                                                           
                    
                    $directorio = DOCS_INCIDENCIAS;
                    $subir_archivo = $directorio . basename($idIncidencia."_".$nombres[$i]);

                    if (move_uploaded_file($path, $subir_archivo)) {                                                
                        $base64 = '';
                        $ins = $this->ModelIncidencias->insertarDatosFichero($tmp, $idIncidencia, $base64);
                    } 

                }else if (in_array($extension, $extensionesDoc)) {
                    
                    $tmp2 = [];
                    $tmp2['nombre'] = $idIncidencia."_".$nombres[$i];
                    $tmp2['tipo'] = $tipos[$i];
                    $tmp2['tamanio'] = $tamanios[$i];
                    $tmp2['tmp'] = $temporales[$i];
                    $documentos[] = $tmp2;

                    $directorio = DOCS_INCIDENCIAS;

                    $subir_archivo = $directorio . basename($idIncidencia."_".$nombres[$i]);
                
                    if (move_uploaded_file($temporales[$i], $subir_archivo)) {
                        $ins = $this->ModelIncidencias->insertarDatosFichero($tmp2, $idIncidencia, '');                       
                    }                                                        
                }

                if($ins){
                    $htmlFicheros .= $this->construirHtmlFichero($ins, $idIncidencia."_".$nombres[$i]);
                }
                
            }
        }
        return $htmlFicheros;
    }     

    private function construirHtmlFichero($idFichero, $nombreFichero)
    {
        return '<p id="contenedor_fichero_'.$idFichero.'">
                    <a href="'.RUTA_URL.'/public/documentos/Incidencias/'.$nombreFichero.'" target="_blank" class="texto-violeta-oscuro text-sm xl:text-base">
                        <span class="font-semibold">'.$nombreFichero.'</span>
                        <i class="fas fa-download ml-2 "></i>
                    </a>

                    <button class="ml-1 right-2 text-red-500 hover:text-red-700 focus:outline-none eliminarFicheroInc" title="Eliminar fichero" data-idfichero="'.$idFichero.'">
                        <i class="fas fa-trash-alt"></i>
                    </button>
            </p>';
    }

    //envío parte de incidencia
    public function enviarEmailParteIncidencia() 
    {

        $respuesta['error'] = true;
        $respuesta['mensaje'] = "Se ha producido un error y no se ha enviado el email.";      

        if(trim($_POST['emailAsunto']) != '' && trim($_POST['emailMensaje']) != '' && isset($_POST['inputEmailSelected']) && count($_POST['inputEmailSelected']) > 0 && $_POST['idIncidenciaEnviar'] > 0){
                    
            $enviar = $this->enviarEmailDocumentoPdf($_POST);           

            if($enviar){
                $respuesta['error'] = false;
                $respuesta['mensaje'] = 'Correo enviado';               
                $envios = $this->modeloBase->getAllFieldsTablaByFieldsFilters(
                    'emails_clientes_facturas', 
                    ['iddoc' => $_POST['idIncidenciaEnviar'], 'tipodoc' => 'parte'], 
                    'fecha', 
                    'DESC'
                );

                if(!empty($envios)){
                    $respuesta['html'] = TemplateHelperDocumento::buildHTMLListSentEmailsDocumento($envios, 'parte');
                }else{
                    return '<div class="container_emails">No se ha enviado emails de esta incidencia.</div>';
                }                
            }

        }else{
            $respuesta['error'] = true;
            $respuesta['mensaje'] = "Faltan datos para enviar el email";
        }

        echo json_encode($respuesta);

    }

    private function enviarEmailDocumentoPdf($post)
    {
        $retorno = false;
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillaEmail.php");     

        $nombreRemitente = 'Telesat';
        $emailRemitente = CUENTA_CORREO;
        $asunto = $post['emailAsunto'];
    

        $destinatarios = $post['inputEmailSelected'];
        $idIncidencia = $post['idIncidenciaEnviar'];
       
        $attachment = $this->generarPdfParteParaEmail($idIncidencia);
        
        $nombreFichero = "Incidencia_".$idIncidencia."_".strtotime("now").".pdf";
        $tmp['documento'] = $attachment;
        $tmp['nombreDocumento'] = $nombreFichero;
        $attachmentArray[] = $tmp;
                          
        $contenido = $post['emailMensaje'];
    
        $cambiar = ['{CONTENIDO}'];
        $cambio = [$contenido];
        $mensaje = str_replace($cambiar,$cambio,$plantilla);
        
        $message = html_entity_decode($mensaje);
            
        $tipoDoc = '';
            
        $envio = enviarEmail::enviarEmailDocumento($nombreRemitente, $emailRemitente, $destinatarios, $asunto, $message, $attachmentArray, $tipoDoc, $datos='');
        

        if ($envio) {
            $retorno = true;
            $tipoDocumento = 'parte';
            $this->guardarDatosEnvioParte($idIncidencia, $tipoDocumento, $nombreFichero, $destinatarios, $asunto, $contenido, $nombreRemitente, $emailRemitente);
        }   

        return $retorno;    

    }    
    
    private function guardarDatosEnvioParte($idDocumento, $tipoDocumento, $nombreFichero, $emailsDestino, $asunto, $message, $nombreRemitente, $emailRemitente)
    {
        date_default_timezone_set('Europe/Madrid');
        $arrValues['iddoc'] = $idDocumento;        
        $arrValues['fecha'] = date("Y-m-d H:i:s");
        $arrValues['tipodoc'] = $tipoDocumento;
        $arrValues['nomfichero'] = $nombreFichero;
        $arrValues['destinatarios'] = json_encode($emailsDestino);
        $arrValues['asunto'] = $asunto;
        $arrValues['mensaje'] = $message;
        $arrValues['correoremitente'] = $emailRemitente;
        $arrValues['nomremitente'] = $nombreRemitente;        

        $stringQueries = UtilsHelper::buildStringsInsertQuery($arrValues, $this->arrFieldsEmailSent);
            $ok = $stringQueries['ok'];
            $strFields = $stringQueries['strFields'];
            $strValues = $stringQueries['strValues'];
                       
            if($ok){
                $this->modeloBase->insertRow('emails_clientes_facturas', $strFields, $strValues);
            }    
    }

    private function generarPdfParteParaEmail($idIncidencia)
    {                                
        $detalles = $this->ModelIncidencias->obtenerDatosIncidencia($idIncidencia);               
        $imagenes = $this->ModelIncidencias->obtnerListadoFicherosImagenesTodas($idIncidencia);        

        $documentos = $this->ModelIncidencias->obtnerListadoFicherosDocumentos($idIncidencia);                

        $comentarios = $this->ModelIncidencias->obtenerTodosLosComentariosExternosPorIdIncidencia($idIncidencia);
        $ficherosComentarios =$this->ModelIncidencias->obtenerTodosLosFicherosDeUnIncidencia($idIncidencia);
   

        $optionEstados = '';            
        if ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') {
            $apartadoFactPpto = $this->montarOptionesSelectEstadoParaEditIncidencia($idIncidencia);
            $optionEstados = $apartadoFactPpto;              
        }                
        
        $consultHaistorialEstados = $this->ModelIncidencias->obtenerComentariosFacturarPresupuestar($idIncidencia);
        $historialEstados = [];
        if (isset($consultHaistorialEstados) && count($consultHaistorialEstados)>0) {
            $historialEstados = $consultHaistorialEstados;
        }

        $datos = [
            'detalles' => $detalles,                
            'imagenes' => $imagenes,
            'documentos' => $documentos,
            'comentarios' => $comentarios,
            'ficherosComentarios' => $ficherosComentarios,
            'optionEstados' => $optionEstados,                
            'historialEstados' => $historialEstados                
        ]; 
        
        $pdf = generarPdf::documentoPDFParaEmail('P', 'A4', 'es', true, 'UTF-8', array(0, 5, 0, 10), true, 'documentos', 'parte.php', $datos);            
        return $pdf;
    }


}
