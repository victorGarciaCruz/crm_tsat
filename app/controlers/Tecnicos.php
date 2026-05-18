<?php

class Tecnicos extends Controlador {

    public function __construct() {
        session_start();
        $this->controlPermisos();
        $this->ModelTecnicos = $this->modelo('ModeloTecnicos');    
    }

    public function index() {
        $datos = [
            //"tecnicos" => $tecnicos
        ];

        $this->vista('tecnicos/tecnicos', $datos);
    }

    private function mapearCampoOrdenTecnico($campoVisible) {
        $mapa = [
            'Nº'        => 'codigotecnico',   
            'Nombre'    => 'nombre',
            'Apellidos' => 'apellidos',
            'Email'     => 'correo',
            'Teléfono'  => 'telefono'
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
                    $campoSQL = $this->mapearCampoOrdenTecnico($campoVisible);
                    $sentencias[] = "$campoSQL $direccion";
                }
                return implode(", ", $sentencias);
            }
        }

        // 2. Si no hay orden múltiple válido, usar el orden simple (el tradicional)
        if (!empty($ordenSimple)) {
            return $ordenSimple;
        }

        // 3. Si todo está vacío, retornamos cadena vacía (luego se aplicará un orden por defecto)
        return "";
    }

    public function crearTablaTecnicos()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $ordenMultiple = isset($_POST['ordenMultiple']) ? urldecode($_POST['ordenMultiple']) : '';
            $ordenSimple   = isset($_POST['orden']) ? $_POST['orden'] : '';
            $tipoSimple    = isset($_POST['tipoOrden']) ? $_POST['tipoOrden'] : '';
        }

        $cond = '';
        $filaspagina = $filas * $pagina;

        // Determinar parámetros de orden según si hay orden múltiple
        if (!empty($ordenMultiple)) {
            // Caso orden múltiple: construir cláusula completa y tipo vacío
            $clausulaOrder = $this->construirClausulaOrderByTecnicos($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "id DESC"; // orden por defecto (coincide con el de la vista)
            }
            $ordenFinal = $clausulaOrder;
            $tipoFinal = '';
        } else {
            // Caso orden simple: usar los valores originales
            $ordenFinal = $ordenSimple;
            $tipoFinal = $tipoSimple;
        }

        // Lógica de búsqueda (sin cambios)
        if ($buscar != "") {
            $datos = json_decode($buscar);
            $tamanio = count((array) $datos);
            if ($tamanio > 0) {
                $cont = 0;
                $cond .= " AND  (";
                foreach ($datos as $key => $value) {
                    $cont++;
                    $y = ($cont < $tamanio) ? " LIKE '%$value%' AND " : " LIKE '%$value%' ) ";
                    if ($key == 'Nº') {
                        $cond .= "id" . $y;        // En la búsqueda se usa id, aunque el campo visible sea 'Nº'
                    }
                    if ($key == 'Nombre') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'Apellidos') {
                        $cond .= "apellidos" . $y;
                    }
                    if ($key == 'Email') {
                        $cond .= "correo" . $y;
                    }
                    if ($key == 'Teléfono') {
                        $cond .= "telefono" . $y;
                    }
                }
            }

            $tecnicos = $this->ModelTecnicos->obtenerTecnicosTablaClassBuscar($filas,$ordenFinal,$filaspagina,$tipoFinal,$cond);
        } else {
            $tecnicos = $this->ModelTecnicos->obtenerTecnicosTablaClass($filas,$ordenFinal,$tipoFinal,$filaspagina);
        }

        print(json_encode($tecnicos));
    }    

    public function totalRegistrosTecnicos()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];            
        }
        
        $cond = '';        
    
        if ($buscar != "") {               
            
            $datos = json_decode($buscar);
            
            $tamanio = count((array) $datos);
            if ($tamanio > 0) {
                                
                $cont = 0;
                $cond .= " AND  (";
                foreach ($datos as $key => $value) {
    
                    $cont++;                   
                    
                    if ($cont < ($tamanio) ) {                    
                        $y =  " LIKE " . "'%$value%'" . " AND ";
                    } else {                    
                        $y =  " LIKE " . "'%$value%'" . ") ";
                    }
                    if ($key == 'Nº') {
                        $cond .= "id" . $y;
                    }
                    if ($key == 'Nombre') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'Apellidos') {
                        $cond .= "apellidos" . $y;
                    }
                    if ($key == 'Email') {
                        $cond .= "correo" . $y;
                    }
                    if ($key == 'Teléfono') {
                        $cond .= "telefono" . $y;
                    }     

                }                                    
    
            }

            $contador = $this->ModelTecnicos->totalRegistrosTecnicosBuscar($cond);

        }else{
            $contador = $this->ModelTecnicos->totalRegistrosTecnicos();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }

    public function crearTecnico()
    {        
        $clientes = $this->ModelTecnicos->obtenerListaClientesActivo();        
        $datos = [
            "clientes" => $clientes
        ];               
        $this->vista('tecnicos/crearNuevoTecnico', $datos);
    }

    public function registrarTecnico()
    {            
        if ($_POST['nombres'] && $_POST['apellidos'] && $_POST['email'] ) {   
            
            $idsClientes = [];
            if (isset($_POST['idcliente'])) {
                $idsClientes = $_POST['idcliente'];
            }
            $editarTiempo = 0;
            if (isset($_POST['editarTiempo']) && $_POST['editarTiempo'] ==1) {
                $editarTiempo = $_POST['editarTiempo'];
            }
            $verTodas = 0;
            if (isset($_POST['verTodas']) && $_POST['verTodas'] ==1) {
                $verTodas = $_POST['verTodas'];
            }
            $recibeMails = 1;      
            if (isset($_POST['recibemails'])) {        
                $recibeMails = $_POST['recibemails'];
            }
            $editarClientes = 0;
            if (isset($_POST['editarClientes']) && $_POST['editarClientes'] ==1) {
                $editarClientes = $_POST['editarClientes'];
            }
            $verClientes = 0;
            if (isset($_POST['soloVerClientes']) && $_POST['soloVerClientes'] ==1) {
                $verClientes = $_POST['soloVerClientes'];
            }
            $codigotecnico = $this->ModelTecnicos->ultimoCodigoTecnico();
            $ultimo = $codigotecnico + 1;

            $datos = [
                'nombres' => $_POST['nombres'],
                'apellidos' => $_POST['apellidos'],
                'email' => $_POST['email'],            
                'activo' => 1,               
                'telefono' => $_POST['telefono'],
                'contrasenia' => $_POST['contra'],
                'idsClientes' => $idsClientes,
                'editarTiempo' => $editarTiempo,
                'verTodas' => $verTodas,
                "recibemails" => $recibeMails,
                "codigoTecnico" => $ultimo,
                "editarClientes" => $editarClientes,
                "verClientes" => $verClientes
            ];
            
            $ins = $this->ModelTecnicos->insertarNuevaTecnico($datos);
            
            if ($ins && $ins >0) {
                $this->actualizarTecnicosPorCliente($datos,$ins);
                $this->enviarEmailConfirmacionCreacionUsuario($_POST['email'],$ins,$_POST['contra']);
                $_SESSION['message'] = 'Se ha registrado el técnico corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar el técnico porque falta completar datos en el formulario.';
            }

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede registrar el técnico porque falta completar datos en el formulario.';
        }
        redireccionar('/Tecnicos');    
    }

        
    public function enviarEmailConfirmacionCreacionUsuario($email,$idsUsuario,$pass)
    {
        //contruyo array con datos de envío:
        $nombreRemitente = 'InfoMalaga';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Creación de usuario InfoMalaga";
        $emailsDestino = [$email];
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillabotonContrasenia.php");

        date_default_timezone_set("Europe/Madrid");
        $fechaActual = date("d-m-Y H:i");        
        $diaSiguiente = date("d-m-Y H:i",strtotime($fechaActual."+ 1 days"));  
                                        
        //construyo cuerpo de mensaje
        $enlace = 'Haz click en el enlace para cambiar la contraseña.';
        $contenido = 'Se ha creado el usuario '.$email.' en la plataforma de Infomálaga. La contraseña es '.$pass.'. Este enlace estará caducará el '.$diaSiguiente;
        $info = RUTA_URL."/Login/cambioContrasenia/1/".$idsUsuario;
        $cambiar = ['{ENLACE}','{CONTENIDO}','{RUTAWEB}'];
        $cambio = [$enlace, $contenido, $info];
        $mensaje = str_replace($cambiar,$cambio,$plantilla);        
        $message = html_entity_decode($mensaje);

        $tipoDoc = '';
        $attachment = '';
        enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);

    }

    public function actualizarTecnicosPorCliente($datos,$ins)
    {
        if (count($datos['idsClientes'])>0) {
            $idsClientes = $datos['idsClientes'];
            foreach ($idsClientes as $idCliente) {
                $this->ModelTecnicos->updateTecnicoEnCliente($ins,$idCliente);
            }
        }
    }

    public function editarTecnico()
    {        
        $datos = [];

        if ($_POST['id'] && $_POST['id'] >0) {                
            
            $detalles = $this->ModelTecnicos->obtenerDatosDetalleTecnico($_POST['id']);
            $datos = [
                'detalles' => $detalles
            ];
                                
        }       
        $this->vista('tecnicos/editarTecnico', $datos);
    }

    public function actualizarTecnico()
    {     

        if ($_POST['id'] && $_POST['nombres'] && $_POST['apellidos'] && $_POST['email'] ) {
            
            $editarTiempo = 0;
            if (isset($_POST['editarTiempo']) && $_POST['editarTiempo'] ==1) {
                $editarTiempo = $_POST['editarTiempo'];
            }
            $verTodas = 0;
            if (isset($_POST['verTodas']) && $_POST['verTodas'] ==1) {
                $verTodas = $_POST['verTodas'];
            }            
            $recibeMails =1;           
            if (isset($_POST['recibemails']) ) {        
                $recibeMails = 0;             
            }   
            $editarClientes = 0;
            if (isset($_POST['editarClientes']) && $_POST['editarClientes'] ==1) {
                $editarClientes = $_POST['editarClientes'];
            } 
            $verClientes = 0;
            if (isset($_POST['soloVerClientes']) && $_POST['soloVerClientes'] ==1) {
                $verClientes = $_POST['soloVerClientes'];
            }

            $datos = [
                'id' => $_POST['id'],
                'nombres' => $_POST['nombres'],
                'apellidos' => $_POST['apellidos'],
                'email' => $_POST['email'],                
                'telefono' => $_POST['telefono'],
                'contrasenia' => $_POST['contra'],
                'editarTiempo' => $editarTiempo,
                'verTodas' => $verTodas,
                "recibemails" => $recibeMails,
                "editarClientes" => $editarClientes,
                "verClientes" => $verClientes
            ];
            
            $upd = $this->ModelTecnicos->actualizarDatosTecnico($datos);

            if ($upd && $upd >0) {                            
                    $_SESSION['message'] = 'Se ha actualizado el registro corréctamente.';
            }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';                
            }


        }else{            
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';
        }
        redireccionar('/Tecnicos');
        
    }

    public function eliminarTecnico()
    {      
        if(isset($_POST['idTecnico']) && $_POST['idTecnico'] >0){
            
            $del = $this->ModelTecnicos->eliminarTecnicoByIdTecnico($_POST['idTecnico']);
            if ($del) {
                $_SESSION['message'] = 'Se ha eliminado el registro corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
            }
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
        }
        redireccionar('/Tecnicos');
                
    }
    

   
}