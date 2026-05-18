<?php

class Usuarios extends Controlador
{



    public function __construct()
    {
        session_start();
        $this->controlPermisos();
        $this->modeloUsuarios = $this->modelo('Usuario');
    }

    public function index()
    {
        $users = $this->modeloUsuarios->obtenerUsuarios();


        $datos = [
            "users" => $users
        ];

        $this->vista('usuarios/usuarios', $datos);
    }

    public function tablaUsuarios()
    {
        $users = $this->modeloUsuarios->obtenerUsuarios();


        print(json_encode($users));
        //die;
    }

    private function mapearCampoOrdenUsuario($campoVisible) {
        $mapa = [
            'Nombre'       => 'usu.nombre',
            'Apellidos'    => 'usu.apellidos',
            'Email'        => 'usu.correo',
            'Teléfono'     => 'usu.telefono',
            'Rol'          => 'roles.nombre',
            'Cliente'      => 'cli.nombre',
            'Cliente tipo' => 'usu.clientetipo'
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByUsuarios($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    $campoSQL = $this->mapearCampoOrdenUsuario($campoVisible);
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

    public function crearTablaUsuarios()
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
            $clausulaOrder = $this->construirClausulaOrderByUsuarios($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "usu.id DESC"; // orden por defecto (coincide con el de la vista)
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
                    if ($key == 'Nombre') {
                        $cond .= "usu.nombre" . $y;
                    }
                    if ($key == 'Apellidos') {
                        $cond .= "usu.apellidos" . $y;
                    }
                    if ($key == 'Email') {
                        $cond .= "usu.correo" . $y;
                    }
                    if ($key == 'Teléfono') {
                        $cond .= "usu.telefono" . $y;
                    }
                    if ($key == 'Rol') {
                        $cond .= "roles.nombre" . $y;
                    }
                    if ($key == 'Cliente') {
                        $cond .= "cli.nombre" . $y;
                    }
                    if ($key == 'Cliente tipo') {
                        $cond .= "usu.clientetipo" . $y;
                    }
                }
            }
        }

        $usuarios = $this->modeloUsuarios->obtenerUsuariosTablaClassBuscar($filas,$ordenFinal,$filaspagina,$tipoFinal,$cond);

        print(json_encode($usuarios));
    }    

    public function totalRegistrosUsuarios()
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
                    
                    if ($key == 'Nombre') {
                        $cond .= "usu.nombre" . $y;
                    }
                    if ($key == 'Apellidos') {
                        $cond .= "usu.apellidos" . $y;
                    }
                    if ($key == 'Email') {
                        $cond .= "usu.correo" . $y;
                    }
                    if ($key == 'Teléfono') {
                        $cond .= "usu.telefono" . $y;
                    }
                    if ($key == 'Rol') {
                        $cond .= "roles.nombre" . $y;
                    }
                    if ($key == 'Cliente') {
                        $cond .= "cli.nombre" . $y;
                    }
                    if ($key == 'Cliente tipo') {
                        $cond .= "usu.clientetipo" . $y;
                    }               
                   
                }                                    
    
            }            
        }

        $contador = $this->modeloUsuarios->totalRegistrosUsuariosBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }


    public function componente()
    {

        $this->vista('usuarios/component');
    }

    public function altaUsuarios()
    {
        $clientes = $this->modeloUsuarios->obtenerListaClientesActivo();
        $datos = [
            "clientes" => $clientes
        ];
        $this->vista('usuarios/altaUsuarios/altaUsuarios',$datos);
    }

    public function crearUsuario()
    {                          
        if ($_SERVER['REQUEST_METHOD'] == "POST") {

            $idCliente = 0;
            if (isset($_POST['idcliente']) && $_POST['idcliente']>0) {
                $idCliente = $_POST['idcliente'];
            }
            $clienteTipo = '';
            if (isset($_POST['accountType']) && $_POST['accountType'] !='') {
                $clienteTipo = $_POST['accountType'];
            }
            $equipos = [];
            $sucursales = [];
            if (isset($_POST['checkVerTodos']) && $_POST['checkVerTodos'] =='on') {
                $equipos = $this->arrayListaTodosLosEquiposDelCliente($idCliente);     
                $sucursales = $this->arrayListaTodosLasSucursalesDelCliente($idCliente);
            }else if(isset($_POST['idEquipoSelected']) && count($_POST['idEquipoSelected'])>0){
                $equipos = $_POST['idEquipoSelected'];
                $sucursales = $this->arrayListaTodosLasSucursalesDelUsuario($equipos);           
            }
            $recibeMails = 1;      
            if (isset($_POST['recibemails'])) {        
                $recibeMails = $_POST['recibemails'];
            }
            
            
            $jsonEquipos = json_encode($equipos);
            $jsonSucursales = json_encode($sucursales);  
           
            date_default_timezone_set("Europe/Madrid");
            $caducidadEnlace = date('Y-m-d H:i:s');
            $contra = 'user';

            $datosInsertar = [
                "nombre" =>  $_POST['nombre'],
                "apellidos" => $_POST['apellidos'],
                "correo" => $_POST['correo'],
                //"contra" => $_POST['contra'],
                "contra" => $contra,
                "rol" => $_POST['rol'],
                "estado" => $_POST['estado'],
                "cambiar" => 1, //1: cambiar constraseña, 0: no cambiar
                "idcliente" => $idCliente,
                "clientetipo" => $clienteTipo,
                "equipos" => $jsonEquipos,
                "sucursales" => $jsonSucursales,
                "recibemails" => $recibeMails,
                "caducidadenlace" => $caducidadEnlace
            ];         
            
            $ins = $this->modeloUsuarios->agregarUsuario($datosInsertar);
            if ($ins && $ins >0) {
                $_SESSION['message'] = 'Se ha creado el usuario corréctamente.';
                $this->enviarEmailConfirmacionCreacionUsuario($_POST['correo'],$ins,$contra);
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede crear el usuario. Consulte con el administrador';
            }
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede crear el usuario porque faltan datos en el formulario.';
        }             
        redireccionar('/Usuarios');
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
           
    public function arrayListaTodosLosEquiposDelCliente($idCliente)
    {
        $arr = $this->modeloUsuarios->listaTodosLosEquiposDelCliente($idCliente);   
        $a = [];
        if ($arr && count($arr)>0) {
            foreach ($arr as $key) {
                $a[] = $key->id;
            }
        } 
        return $a;
    }   

    public function arrayListaTodosLasSucursalesDelCliente($idCliente)
    {
        $arr = $this->modeloUsuarios->listaTodasLasSucursalesDelCliente($idCliente);   
        $a = [];
        if ($arr && count($arr)>0) {
            foreach ($arr as $key) {
                $a[] = $key->id;
            }
        } 
        return $a;
    }
     
    public function arrayListaTodosLasSucursalesDelUsuario($equipos)
    {
        $a = [];
        if ($equipos && count($equipos)>0) {
            foreach ($equipos as $key) {
                $idSucursal = $this->modeloUsuarios->obtenerIdDeLaSucursalDelEquipo($key);   
                if ($idSucursal && $idSucursal > 0) {
                    $a[] = $idSucursal;
                }
            }
            $a = array_unique($a);
        }
       
        return $a;
    }   

    public function actualizarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $idUsuario = $_POST['id'];
        }
        $datosUsuario = $this->modeloUsuarios->obtenerUsuarioId($idUsuario);
        
        $nombreCliente = [];
        $sucursales = [];
        if ($datosUsuario->rol == 1) {
            $nombreCliente = $this->modeloUsuarios->obtenerNombreCliente($datosUsuario->idcliente);
            $sucursales = $this->modeloUsuarios->obtenerSucursalesActivasPorCliente($datosUsuario->idcliente);
        }        
        $nombreRol = $this->modeloUsuarios->obtenerNombreRol($datosUsuario->rol);
        
        $idsEquipos = json_decode($datosUsuario->equipos);

        $equipos = [];
        if ($idsEquipos && count($idsEquipos)>0) {
            foreach ($idsEquipos as $key) {
                $datosEquipos = $this->modeloUsuarios->obtenerDetalleEquipos($key);
                $equipos[] = $datosEquipos;
            }            
        }

        $datos = [
            "usuario" => $datosUsuario,
            "nombreCliente" => $nombreCliente,
            "nombreRol" => $nombreRol,
            "sucursales" => $sucursales,
            "equipos" => $equipos
        ];
        $this->vista('usuarios/actualizarUsuarios/actualizarUsuarios', $datos);
    }

    public function editarUsuario()
    {
        
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $idCliente = 0;
            if (isset($_POST['idcliente']) && $_POST['idcliente']>0) {
                $idCliente = $_POST['idcliente'];
            }
            $clienteTipo = '';
            if (isset($_POST['accountType']) && $_POST['accountType'] !='') {
                $clienteTipo = $_POST['accountType'];
            }
            
            $equipos = [];
            $sucursales = [];
            if (isset($_POST['checkVerTodos']) && $_POST['checkVerTodos'] =='on') {
                $equipos = $this->arrayListaTodosLosEquiposDelCliente($idCliente);     
                $sucursales = $this->arrayListaTodosLasSucursalesDelCliente($idCliente);
            }else if(isset($_POST['idEquipoSelected']) && count($_POST['idEquipoSelected'])>0){
                $equipos = $_POST['idEquipoSelected'];
                $sucursales = $this->arrayListaTodosLasSucursalesDelUsuario($equipos);           
            }
            $recibeMails =1;           
            if (isset($_POST['recibemails']) ) {        
                $recibeMails = 0;             
            }          
            
            $jsonEquipos = json_encode($equipos);
            $jsonSucursales = json_encode($sucursales);
            
            $datosActualizar = [
                "id" => $_POST['id'],
                "nombre" =>  $_POST['nombre'],
                "apellidos" => $_POST['apellidos'],
                "correo" => $_POST['correo'],
                "contra" => $_POST['contra'],
                "rol" => $_POST['rol'],
                "estado" => $_POST['estado'],
                "cambiar" => 1,
                "idcliente" => $idCliente,
                "clientetipo" => $clienteTipo,
                "equipos" => $jsonEquipos,
                "sucursales" => $jsonSucursales,
                "recibemails" => $recibeMails
            ];
           
        
            $upd = $this->modeloUsuarios->actualizarUsuario($datosActualizar);

            if ($upd && $upd >0) {
                $_SESSION['message'] = 'Se ha actualizado el usuario corréctamente.';               
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el usuario. Consulte con el administrador';
            }
         
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el usuario porque faltan datos en el formulario.';
        }
        redireccionar('/Usuarios');
    }

    public function eliminarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $datosEliminar = [
                "id" => $_POST['idUsuarioDel']
            ];
            
            $del = $this->modeloUsuarios->borrarUsuario($datosEliminar);

            if ($del ==1) {
                $_SESSION['message'] = 'Se ha eliminado el usuario corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error y no se ha podido eliminar el usuario.';
            }
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error y no se ha podido eliminar el usuario.';
        }

        redireccionar('/Usuarios');
    }

    public function esClienteAdmin($idUsuario)
    {
        $tipo = $this->modeloUsuarios->obtenerTipoCliente($idUsuario);
        if (isset($tipo) && $tipo ==1) {
            $res = 1;
        }else{
            $res = 0;
        }
        return $res;

    }
    public function esClienteSupervisor($idUsuario)
    {
        $tipo = $this->modeloUsuarios->obtenerTipoCliente($idUsuario);
        if (isset($tipo) && $tipo ==2) {
            $res = 1;
        }else{
            $res = 0;
        }
        return $res;
    }
    public function esClienteUsuario($idUsuario)
    {
        $tipo = $this->modeloUsuarios->obtenerTipoCliente($idUsuario);
        if (isset($tipo) && $tipo ==3) {
            $res = 1;
        }else{
            $res = 0;
        }
        return $res;
    }

    public function construirContenedoresParaCliente()
    {                
        $retorno = [
            'respuesta' => 0,            
            'sucursales' => '',
            'equipos' => '',
            'tablaEquipos' => ''
        ];
        if ($_POST['tipo'] && $_POST['tipo']!='' && $_POST['idCliente'] && $_POST['idCliente']>0) {

            $tipo = $_POST['tipo'];
            $idCliente = $_POST['idCliente'];
            
            $sucursales = $this->construirApartadoClienteSucursales($tipo,$idCliente);
            $equipos = $this->construirApartadoClienteEquipos($tipo);
            $tablaEquipos = $this->construirCabeceraTablaEquipos($tipo);

            $retorno = [
                'respuesta' => 1,                
                'sucursales' => $sucursales,
                'equipos' => $equipos,
                'tablaEquipos' => $tablaEquipos
            ];

        }

        print json_encode($retorno);        
    }

    public function construirApartadoClienteSucursales($tipo,$idCliente)
    {        
        $sucursales = $this->modeloUsuarios->obtenerSucursalesActivasPorCliente($idCliente);

        $contenido = '';
        if ($tipo == 'administrador') {
            $contenido .= '<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Este usuario podrá actuar sobre todos las sucursales y equipos</label>';
        }else if($tipo == 'supervisor' || $tipo == 'usuario' ){
            $contenido .='<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold" >Seleccionar sucursal</label>
                        <select name="idSucursalCli" id="idSucursalCli" class="py-2 px-3 rounded-lg border border-gray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" >
                        <option disabled selected>Seleccionar</option>';
                                               
                        foreach ($sucursales as $sucursal) {
                            $contenido .='
                            <option value="'.$sucursal->id.'" >'.$sucursal->nombre.'</option>';
                        }

            $contenido .='                            
                        </select>';
        }
        return $contenido;
    }

    public function construirApartadoClienteEquipos($tipo)
    {
        $contenido = '';
        if ($tipo == 'administrador') {
            $contenido .= '';
        }else if($tipo == 'supervisor' || $tipo == 'usuario' ){
            $contenido .='<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold" >Seleccionar equipos</label>
                        <select class="todos py-2 px-3 rounded-lg border border-gray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" >
                        <option disabled selected>Seleccionar</option>                                            
                        </select>';
        }
        return $contenido;
    }

    public function construirCabeceraTablaEquipos($tipo)
    {
        $contenido = '';
        if ($tipo == 'administrador') {
            $contenido .= '';
        }else if($tipo == 'supervisor' || $tipo == 'usuario' ){
            $contenido .='<section class="container mx-auto p-2">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold" >Equipos asignados</label>
                            <div class="w-full mb-8 overflow-hidden rounded-lg shadow-lg">
                                <div class="w-full overflow-x-auto">
                                <table class="w-full" id="tablaListadoEquiposAsignados">
                                    <thead>
                                    <tr class="text-sm font-semibold tracking-wide text-left text-gray-900 bg-gray-100 border-b border-gray-600">
                                        <th class="px-2 py-2">Nº</th>
                                        <th class="px-2 py-2">Sucursal</th>
                                        <th class="px-2 py-2">Equipos</th>
                                        <th class="px-2 py-2">Eliminar</th>
                                    </tr>
                                    </thead>
                                    <tbody class="bg-white">                                            
                                    </tbody>
                                </table>
                                </div>
                            </div>
                        </section>';
        }
        return $contenido;
    }

    public function construirContenedoresClienteTipos()
    {            
        $tipos = $this->construirApartadoClienteTipos();
        $sucursales = $this->construirApartadoSucursalesTodas();
        
        $retorno = [
            'respuesta' => 1,           
            'tipos' => $tipos,
            'sucursales' => $sucursales
        ];
        
        print json_encode($retorno);   
    }

    public function construirApartadoClienteTipos()
    {                
        $contenido ='<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Seleccionar cliente tipo</label>
                    <div class="flex flex-col items-left justify-center">
                        <label class="inline-flex items-center ml-6 my-2">
                            <input type="radio" class="form-radio text-indigo-600 h-6 w-6 radioType" name="accountType" value="administrador" checked>
                            <span class="ml-2">Administrador</span>
                        </label>
                        <label class="inline-flex items-center ml-6 my-2">
                            <input type="radio" class="form-radio text-indigo-600 h-6 w-6 radioType" name="accountType" value="supervisor">
                            <span class="ml-2">Supervisor</span>
                            <label class="inline-flex items-center ml-2">
                                <input type="checkbox" class="form-checkbox h-6 w-6 check" id="checkVerTodos" name="checkVerTodos" disabled>
                                <span class="ml-3 text-lg">Ver todo</span>
                            </label>
                        </label>
                        <label class="inline-flex items-center ml-6 my-2">
                            <input type="radio" class="form-radio text-indigo-600 h-6 w-6 radioType" name="accountType" value="usuario">
                            <span class="ml-2">Usuario</span>
                        </label>
                    </div>';

        return $contenido;
    }

    public function construirApartadoSucursalesTodas()
    {                
        $contenido = '<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Este usuario podrá actuar sobre todos las sucursales y equipos</label>';

        return $contenido;
    }


    public function llenarSelectOptionsDeSucursales()
    {
        $retorno = [
            'respuesta' => 0,            
            'options' => ''
        ];
        if ($_POST['idSucursal'] && $_POST['idSucursal']>0 ) {

            $idSucursal = $_POST['idSucursal'];
            
            $html = $this->construirOptionsSelectEquipos($idSucursal);
            
            $retorno = [
                'respuesta' => 1,                
                'options' => $html                
            ];

        }

        print json_encode($retorno);
    }

    public function construirOptionsSelectEquipos($idSucursal)
    {
        $html = '<label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold" >Seleccionar equipos</label>
                <select name="idEquipoCli[]" id="idEquipoCli" multiple="multiple" class="todos py-2 px-3 rounded-lg border border-gray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" >
                    <option disabled selected>Seleccionar</option> ';
        $equipos= $this->modeloUsuarios->obtenerEquiposPorSucursal($idSucursal);

        if (isset($equipos) && count($equipos) >0) {
            foreach ($equipos as $key) {
                $html .= "<option value='".$key->id."'>".$key->nombre."</option>";
            }
        }
        $html .= '</select><button id="btnAgregarEquipo"  class="w-auto bg-gray-100 hover:bg-gray-200 rounded-lg shadow-xl font-medium px-2 py-2">Agregar equipo(s)</button>';
        return $html;
    }
    
    public function traerFilasEquiposSeleccionados()
    {
        $retorno = [
            'respuesta' => 0,            
            'filas' => ''
        ];
        if ($_POST['equipos'] && count($_POST['equipos'])>0 ) {

            $equipos = $_POST['equipos'];
            
            $html = $this->construirFilasEquiposAsignados($equipos);
            
            $retorno = [
                'respuesta' => 1,                
                'filas' => $html                
            ];

        }

        print json_encode($retorno);
    }

    public function construirFilasEquiposAsignados($equipos)
    {
        $html = '';
        foreach ($equipos as $key) {
            
            $datosEquipo = $this->modeloUsuarios->obtenerDetalleEquipos($key);

            $html .= "<tr class='text-sm text-gray-700'>
                        <td style='width: 8%;' class='px-2 py-2'><input style='width: 90%;' value='".$key."' name='idEquipoSelected[]'></td>
                        <td class='px-2 py-2'>".$datosEquipo->nombresucursal."</td>
                        <td class='px-2 py-2'>".$datosEquipo->nombreequipo."</td>
                        <td style='width: 8%;' class='px-2 py-2'><a href='' class='eliminarEquipo'><i class='fas fa-user-minus' style='color:red;'></i></a></td>
                    <tr>";            
        }
        return $html;
    }
}
