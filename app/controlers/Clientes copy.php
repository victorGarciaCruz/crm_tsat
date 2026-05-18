<?php

class Clientes extends Controlador
{
    public function __construct()
    {
        session_start();
        $this->controlPermisos();
        $this->ModelClientes = $this->modelo('ModeloClientes');
        $this->ModelFacturasCliente = $this->modelo('ModeloFacturasCliente');
        $this->ModelCuentasBancarias = $this->modelo('ModeloCuentasBancarias');   
        $this->ModelIncidencias = $this->modelo('ModeloIncidencias');    
        
        if(file_get_contents("php://input")){
            $payload = file_get_contents("php://input");    
            $this->fetch = json_decode($payload, true);
        } 
    }

    public function index()
    {
        $aniosInc = $this->ModelClientes->aniosConIncidencias();
        $tecnicoConPermiso = false;
        if ($_SESSION['nombrerol'] == 'tecnico') {
            $tecnicoConPermiso = $this->ModelClientes->tienePermisoParaEditarClientes($_SESSION['idusuario']);
        }

        $permisosTecnico= false;
        if ($_SESSION['nombrerol'] == 'tecnico') {
            $permisosTecnico = $this->ModelClientes->permisosTecnicosClientes($_SESSION['idusuario']);
        }

        $datos = [
            "aniosSelect" => $aniosInc,
            'tecnicoConPermiso' => $tecnicoConPermiso
        ];

        if ($_SESSION['nombrerol'] == 'admin' || $datos['tecnicoConPermiso'] == 1) {
            $this->vista('clientes/clientes', $datos);
        }else if (isset($permisosTecnico->verclientes) && $permisosTecnico->verclientes== 1){            
            $this->vista('clientes/clientesTecnico', $datos);
        }else if($permisosTecnico->verclientes==0 && $permisosTecnico->editarclientes==0){
            $datos['vereditar'] = 'ok';
            $this->vista('clientes/clientes', $datos);
        }        
        
    }

    private function mapearCampoOrdenCliente($campoVisible) {
        $mapa = [
            'Nº'           => 'id',
            'Razón Social' => 'nombre',
            'Nom.Comercial'=> 'nombrecomercial',
            'CIF'          => 'cif',
            'Población'    => 'poblacion',
            'Provincia'    => 'provincia'
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByClientes($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    /*$dirOriginal = strtoupper($item['dir']);
                    if ($dirOriginal === 'DEFAULT') {
                        continue; // Saltamos este criterio
                    }*/
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    $campoSQL = $this->mapearCampoOrdenCliente($campoVisible);
                    $sentencias[] = "$campoSQL $direccion";
                }
                return implode(", ", $sentencias);
            }
        }

        // 2. Si no hay orden múltiple válido, usar el orden simple (el tradicional)
        if (!empty($ordenSimple)) {
            return $ordenSimple;
        }

        // 3. Si todo está vacío, retornamos cadena vacía y que el modelo decida el orden por defecto
        return "";
    }

    public function crearTabla()
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
            $clausulaOrder = $this->construirClausulaOrderByClientes($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "id DESC";
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
                    if ($key == 'Nº')            $cond .= "id" . $y;
                    if ($key == 'Razón Social')  $cond .= "nombre" . $y;
                    if ($key == 'Nom.Comercial') $cond .= "nombrecomercial" . $y;
                    if ($key == 'CIF')            $cond .= "cif" . $y;
                    if ($key == 'Población')      $cond .= "poblacion" . $y;
                    if ($key == 'Provincia')      $cond .= "provincia" . $y;
                }
            }

            $clientes = $this->ModelClientes->obtenerClientesTablaClassBuscar(
                $filas,
                $ordenFinal,
                $filaspagina,
                $tipoFinal,
                $cond
            );
        } else {
            $clientes = $this->ModelClientes->obtenerClientesTablaClass(
                $filas,
                $ordenFinal,
                $tipoFinal,
                $filaspagina
            );
        }

        print(json_encode($clientes));
    }    

    public function totalRegistros()
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
                    if ($key == 'Razón Social') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'Nom.Comercial') { 
                        $cond .= "nombrecomercial" . $y;
                    }                    
                    if ($key == 'CIF') {
                        $cond .= "cif" . $y;
                    }
                    if ($key == 'Población') {
                        $cond .= "poblacion" . $y;
                    }
                    if ($key == 'Provincia') {
                        $cond .= "provincia" . $y;
                    }                                                                                                           
                }                                    
     
            }

            /* echo"<br><br>cond<br>";
            print_r($cond); */

            $contador = $this->ModelClientes->totalRegistrosClientesBuscar($cond);

        }else{
            $contador = $this->ModelClientes->totalRegistrosClientes();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }


    public function nuevoCliente()
    {
        $datos = '';
        $form = $this->construirFormularioCliente($datos);
        $salida['form'] = $form;
        echo json_encode($salida);
    }

    public function nombreTecnicoPorId($idTecnico)
    {
        $dato = $this->ModelClientes->nombreTecnicoPorId($idTecnico);
        return $dato;
    }

    public function obtenerListaTecnicos()
    {
        $tecnicos = $this->ModelClientes->obtenerListaTecnicos();
        return $tecnicos;
    }

    public function construirFormularioCliente($datos)
    {        
        $id = '';
        $html = '';
        $nombre = '';
        $nombrecomercial = '';
        $cif = '';
        $direccion = '';
        $codigopostal = '';
        $observaciones = '';
        $poblacion = '';
        $provincia = '';        
        $tituloModal = 'Alta cliente';
        $btnSubmit = 'Agregar y cerrar';
        $idBtnSubmit = 'crearClienteNuevo';

        $btnSubmit2 = 'Agregar y seguir';
        $idBtnSubmit2 = 'crearClienteYSeguir';
        $ver = 'block';
        $idCliente = '';
        $fila = '';
                        
        $contactos ='<tr>                            
                        <td width="40%" class="font-bold">Contacto</td>
                        <td width="20%" class="font-bold">Email</td>
                        <td width="20%" class="font-bold">Teléfono</td>
                        <td width="10%" class="font-bold"></td>
                    </tr>';
        $tecnicos = $this->obtenerListaTecnicos();       

        $navtab = $this->contruirNavTab();        

        //si el cliente existe entrea a este IF
        if (isset($datos) && $datos != '') {
            $id = isset($datos->nombre)?$datos->id:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";
            $nombrecomercial = isset($datos->nombrecomercial)?$datos->nombrecomercial:"";
            $cif = isset($datos->cif)?$datos->cif:"";
            $direccion = isset($datos->direccion)?$datos->direccion:"";
            $codigopostal = isset($datos->codigopostal)?$datos->codigopostal:"";
            $observaciones = isset($datos->observaciones)?$datos->observaciones:"";
            $poblacion = isset($datos->poblacion)?$datos->poblacion:"";
            $provincia = isset($datos->provincia)?$datos->provincia:"";            
            $tituloModal = 'Editar cliente - ';
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarCliente';
            $idCliente = '<span>Nº '.$datos->id.'</span>';
            $ver = 'none';

            //construyo el apartado de técnicos asignados al cliente
            if ( isset($datos->tecnicos) && count(json_decode($datos->tecnicos)) > 0 ) {
                
                $fila .= '<tr>
                            <td width="20%" class="font-bold">id</td>
                            <td width="60%" class="font-bold">Técnico</td>
                            <td width="20%" class="font-bold"></td>
                        </tr>';
                foreach (json_decode($datos->tecnicos) as $key) {                   
                    $nombreTecnico = $this->nombreTecnicoPorId($key);                    

                    $fila .= '<tr>
                                <td width="20%"><input readonly name="codigoTecnico" value="'.$nombreTecnico->codigotecnico.'" style="width: 100%;"></td>
                                <td width="60%">'.$nombreTecnico->nombre. ' ' .$nombreTecnico->apellidos. '</td>
                                <td width="20%"><a href="" class="eliminarTecnico"><i class="fas fa-user-minus" style="color:red;"></i></a></td>
                            </tr>';
                }                
            }

            //construyo el apartado de contactos (nombre/email/telefono) asignados al cliente
            if ( isset($datos->contactos) && count(json_decode($datos->contactos)) > 0 ) {
                        
                foreach (json_decode($datos->contactos) as $contacto) {
                    $contactos .= '<tr>                                    
                                    <td width="40%"><input name="nombreContacto" value="'.$contacto->nombre.'" class="border-2 border-coolGray-300 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="20%"><input name="mailContacto" value="'.$contacto->email.'" class="border-2 border-coolGray-300 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="20%"><input type="text" regexp="[0-9]{0,9}" name="telefonoContacto" value="'.$contacto->telefono.'" class="border-2 border-coolGray-300 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="10%"><a href="" class="eliminarContactoCli"><i class="fas fa-user-minus" style="color:red;"></i></a></td>
                                </tr>';
                }                
            }
        }
        

        //aqui construyo la vista para alta cliente nuevo y para actualizar cliente
        $html .= '
                <input type="hidden" value="'.$id.'" id="idCliEdit">


                <div class="flex items-start justify-between p-3 border-b border-solid border-blueGray-200 rounded-t">
                    <h1 class="text-center text-sm lg:text-base uppercase texto-violeta-oscuro font-semibold pt-1">'.$tituloModal.' ' . $idCliente . '</h1>
                    <button class="cancelarCerrar p-1 ml-auto bg-transparent border-0 text-gray opacity-50 float-right leading-none font-semibold outline-none focus:outline-none" >
                        <span class="bg-transparent text-black opacity-1 text-xs md:text-sm block outline-none focus:outline-none">
                            Cerrar
                        </span>
                    </button>
                </div>';

                
        
        //inicio de tabs panels
        $html .= $navtab;       

        //primer tab
        $html .= '
            <div class="block" id="tab-profile">                    
                <form id="formAltaClientes">

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-2 md:gap-4 m-2">
                            <div class="grid grid-cols-1 md:col-span-2">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre fiscal</label>
                                <input name="nombre" id="nombre" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent"  placeholder="Nombre fiscal" value="'.$nombre.'" required/>
                            </div>                            
                            <div class="grid grid-cols-1 md:col-span-2">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre comercial</label>
                                <input name="nombrecomercial" id="nombrecomercial" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent"  placeholder="Nombre comercial" value="'.$nombrecomercial.'"/>
                            </div>
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">CIF/NIF</label>
                                <input type="text" regexp="[a-zA-Z0-9]{0,9}" name="cif" id="cif" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" placeholder="CIF" value="'.$cif.'" />
                            </div>
                                <div class="grid grid-cols-1 lg:col-span-2">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Dirección</label>
                                <input name="direccion" id="direccion" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Dirección" value="'.$direccion.'" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2 md:gap-4 m-2">
                        
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Población</label>
                                <input type="text" name="poblacion" id="poblacion" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Población" value="'.$poblacion.'" />
                            </div>
                        
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Provincia</label>
                                <input name="provincia" id="provincia" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Provincia" value="'.$provincia.'" />
                            </div>
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Código postal</label>
                                <input type="text" regexp="[0-9]{0,5}" name="codigopostal" id="codigopostal" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" placeholder="Código postal" value="'.$codigopostal.'" />
                            </div>';

                            if ($tituloModal == 'Alta cliente') {
                                $html .= '<div class="grid grid-cols-1">
                                            <label class="inline-flex items-center">
                                                <input type="checkbox" class="form-checkbox h-4 w-4" id="sucursalDefault" name="sucursalDefault" value="1">
                                                <span class="ml-3 uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Sucursal por defecto</span>
                                            </label>
                                        </div>';
                            }

        $html .= '</div>';

      

        $html .= $this->mostrarCuentasBancarias($id);

        $html .='

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-2 md:gap-4 m-2">                            
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Observaciones</label>
                                <textarea name="observaciones" id="observaciones" class="w-full py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">'.$observaciones.'</textarea>
                            </div>
                        </div>

                        <div class="inline-flex m-2">                       
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Contactos</label>
                                <a id="addContacto" title="Agregar contactos" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1 cursor-pointer"><i class="fas fa-plus-circle"></i></a>                        
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-1 gap-2 md:gap-4 m-2">
                            <div class="grid grid-cols-1">
                                <table id="tablaContactosCliente">
                                '.$contactos.'
                                </table>
                            </div>
                            <div class="grid grid-cols-1"></div>
                        </div> 

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4 m-2">
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Técnicos</label>
                                <select name="tecnicos" id="tecnicos" class="py-1 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">
                                    <option selected disabled>Seleccionar</option>';
                                foreach ($tecnicos as $tecnico) {
                                    $html .= '<option value="'.$tecnico->codigotecnico.'">'.$tecnico->nombre. ' ' .$tecnico->apellidos. '</option>';
                                }

                    $html .='
                                </select>
                            </div>';
                    $html .= '
                            <div class="grid grid-cols-1">                            
                            </div>
                        </div>';
                    $html .='
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 md:gap-4 m-2">
                            <div class="grid grid-cols-1">
                                <table id="tablaTecnicosCliente">
                                '.$fila.'
                                </table>
                            </div>
                            <div class="grid grid-cols-1"></div>
                        </div>                                                   
                        <div class="flex items-center justify-center px-6 pt-3 border-t border-solid border-blueGray-200 rounded-b">
                            <a class="cancelarCerrar w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-xs md:text-sm lg:text-base text-white px-4 py-1 mr-3" >Cerrar</a>
                            <button id="'.$idBtnSubmit.'" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl font-medium text-xs md:text-sm lg:text-base text-white px-4 py-1 mr-3">'.$btnSubmit.'</button>
                            <button id="'.$idBtnSubmit2.'" style="display:'.$ver.'" class="w-auto bg-blue-700 hover:bg-pink-500 rounded-lg shadow-xl font-medium text-xs md:text-sm lg:text-base text-white px-4 py-1">'.$btnSubmit2.'</button>
                        </div>
                </form>
            </div>';

        $html .= '
            <div class="hidden pastilla" id="tab-settings"></div>
            <div class="hidden pastilla" id="tab-options"></div>
            <div class="hidden pastilla" id="tab-bolsahoras"></div>
            <div class="hidden pastilla" id="tab-modopago"></div>        
            ';         


        //fin tabs panels
        $html .= '</div>
                </div>
            </div>
        </div>
        </div>';
        
        return $html;
    }

    public function mostrarCuentasBancarias($idCliente) {
        

        $cuentasBancarias =  [];
        if(!empty($idCliente)){
            $cuentasBancarias = $this->ModelClientes->buscarCuentasBancariasCliente($idCliente);
        }        
        $html = '';        

        $html .= '<div class="grid grid-cols-1 lg:grid-cols-2 gap-2 md:gap-4 m-2">
                    <div class="grid grid-cols-1" id="container-cuentas-bancarias">
                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Cuentas Bancarias</label>
                    <div class="inline-flex mt-2 mb-2">
                        <input type="text" maxlength="30" id="nuevaCuenta" placeholder="Ingrese número de cuenta" class="border p-2 rounded w-full">
                        <a id="agregarCuenta" title="Agregar cuentas" class="rounded-full flex items-center justify-center text-xs bg-blue-700 text-white flex-1 cursor-pointer p-2">Agregar</a>
                    </div>
                    <div id="cuentas-bancarias-list">';
    
        // Si existen cuentas bancarias, las mostramos
        if (!empty($cuentasBancarias)) {
            foreach ($cuentasBancarias as $cuenta) {
                $html .= '<div class="flex justify-between items-center mb-2" id="cuenta-' . $cuenta . '">
                            <span>' . $cuenta . '</span>
                            <input type="hidden" name="selectCuenta[]" value="' . $cuenta . '">
                            <button type="button" class="text-red-500 text-xs eliminarCuenta" data-id="' . $cuenta . '">Eliminar</button>
                        </div>';
            }
        }
    
        $html .= '  </div>
                </div></div>';
    
        return $html;
    }

    public function construirOptionsCuentasBancarias()
    {
        $html = '';
        $cuentas = $this->ModelCuentasBancarias->obtenerCuentasBancariasSelect();
        if(isset($cuentas[0]) && count($cuentas) > 0){
            
            foreach ($cuentas as $cuenta) {
                $html.= '<option value="'.$cuenta->id.'">'.$cuenta->numerocuenta.'</option>';   
            }

        }
        return $html;
    }

  

    public function contruirNavTab()
    {
        $html ='<div class="flex flex-wrap" id="tabs-id">
                    <div class="w-full">
                        <ul class="flex mb-0 list-none flex-wrap pt-3 pb-4 flex-row">';

                        $tecnicoConPermiso = '';
                        if ($_SESSION['nombrerol'] == 'tecnico') {
                            $tecnicoConPermiso = $this->ModelClientes->tienePermisoParaEditarClientes($_SESSION['idusuario']);
                        }

                        $permisosGuardar = $this->verificarPermisosTecnicos();        

                        if($permisosGuardar[1]===true){                        

                        //if ($_SESSION['nombrerol'] == 'admin' || $tecnicoConPermiso == 1) { 
                            
                            $html .='
                                <li class="my-1 mr-2 last:mr-0 flex-auto text-center">
                                    <a class="tab-clientes text-xs font-bold uppercase px-5 py-3 shadow-lg rounded block leading-normal text-white bg-violeta-oscuro" data-tab="tab-profile">
                                    <i class="fas fa-space-shuttle text-base mr-1"></i>  Datos del cliente
                                    </a>
                                </li>
                                <li class="my-1 mr-2 last:mr-0 flex-auto text-center">
                                    <a class="tab-clientes text-xs font-bold uppercase px-5 py-3 shadow-lg rounded block leading-normal texto-violeta-oscuro bg-white" data-tab="tab-settings" data-metodo="verSucursalesCliente">
                                    <i class="fas fa-cog text-base mr-1"></i>  Sucursales
                                    </a>
                                </li>
                                <li class="my-1 mr-2 last:mr-0 flex-auto text-center">
                                    <a class="tab-clientes text-xs font-bold uppercase px-5 py-3 shadow-lg rounded block leading-normal texto-violeta-oscuro bg-white" data-tab="tab-options" data-metodo="verEquiposCliente">
                                    <i class="fas fa-laptop-house"></i>  Equipos
                                    </a>
                                </li>';
                        }               
        
                        if ($_SESSION['nombrerol'] == 'admin' && EMPRESA == 'INFOMALAGA') {  
                                
                            
                                $html .= '
                                <li class="my-1 mr-2 last:mr-0 flex-auto text-center">
                                    <a class="tab-clientes text-xs font-bold uppercase px-5 py-3 shadow-lg rounded block leading-normal texto-violeta-oscuro bg-white" data-tab="tab-bolsahoras" data-metodo="verBolsaHorasCliente">
                                    <i class="fas fa-business-time"></i>  Bolsa horas
                                    </a>
                                </li>';
                            
                            
                                $html .= '<li class="my-1 mr-2 last:mr-0 flex-auto text-center">
                                    <a class="tab-clientes text-xs font-bold uppercase px-5 py-3 shadow-lg rounded block leading-normal texto-violeta-oscuro bg-white" data-tab="tab-modopago" data-metodo="verEquipoModosPago">
                                    <i class="fas fa-euro-sign"></i>  Mantenimiento
                                    </a>
                                </li>';
                            
                        }

                        if (($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') && EMPRESA == 'TELESAT') {

                            
                            $html .= '<li class="my-1 mr-2 last:mr-0 flex-auto text-center">
                                <a class="tab-clientes text-xs font-bold uppercase px-5 py-3 shadow-lg rounded block leading-normal texto-violeta-oscuro bg-white" data-tab="tab-modopago" data-metodo="verEquipoModosPago">
                                <i class="fas fa-euro-sign"></i>  Mantenimiento
                                </a>
                            </li>';

                        }

                        $html .='
                        </ul>
                        <div class="relative flex flex-col min-w-0 break-words bg-white w-full mb-6">
                            <div class="px-4 py-1 flex-auto">
                                <div class="tab-content tab-space">
                    ';
        return $html;
    } 

    public function agregarClienteNuevo()
    {       
        $retorno = [
            'respuesta' => 0,
            'clasetabla' => '',
            'formLleno' => ''            
        ];                        

            $tipo = '';

            if ($_POST['form'] && $_POST['tipo']) {
                
                $tipo = $_POST['tipo'];
                
                $datos = [];
                $tecnicos = [];
                $contactos = [];            
                $nombres = [];
                $mails = [];
                $telefonos = [];
                $cuentasBancarias = [];
                
                foreach ($_POST['form'] as $row) {
                    if ($row['name'] == 'codigoTecnico' ) {
                        $idTecnico = $this->ModelClientes->obtenerIdTecnicoDesdeCodigoTecnico($row['value']);
                        $tecnicos[] = $idTecnico;
                    }    
                    if ($row['name'] == 'nombreContacto' ) {
                        $nombres[] = $row['value'];                    
                    }
                    if ($row['name'] == 'mailContacto' ) {
                        $mails[] = $row['value'];                    
                    }
                    if ($row['name'] == 'telefonoContacto' ) {
                        $telefonos[] = $row['value'];                    
                    }
                    /* if ($row['name'] == 'selectCuenta') {                    
                        $cuentasBancarias[] = $row['value'];                   
                    } */
                    if (strpos($row['name'], 'selectCuenta') !== false) {  
                        $cuentasBancarias[] = $row['value'];  
                    }
                    $datos[$row['name']] = $row['value'];
                                               
                }
                $datos['idstecnicos'] = $tecnicos;  
                $datos['cuentasBancarias'] = $cuentasBancarias;  
    
                if (count($nombres) >0) {
                    
                    for ($i=0; $i < count($nombres) ; $i++) {                               
                            $nombre = $nombres[$i];
                            $email = $mails[$i];
                            $telefono = $telefonos[$i];
    
                            $tmp = [                            
                                'nombre' => $nombre,
                                'email' => $email,
                                'telefono' => $telefono
                            ];
                            $contactos[] = $tmp;
                    }                
                }
                $datos['contactos'] = $contactos;  
                          
     
               

                $ins = $this->ModelClientes->insertarDatosClienteNuevo($datos);
                if ($ins && $ins >0) {
    
    
                    if (isset($_POST['sucursalDefault']) && $_POST['sucursalDefault'] == 1) {
                        $datos['nombreSucursal'] = 'Principal'; 
                        $datos['direccionSucursal']= '';
                        $datos['poblacionSucursal'] = '';
                        $datos['provinciaSucursal'] = '';
                        $datos['codigopostalSucursal'] = '';                        
                        $datos['contactos'] = [];
                        $datos['idCliente'] = $ins;
                        $this->ModelClientes->insertarDatosSucursalNueva($datos);
                    }
    
                    $detalleCliente = $this->ModelClientes->detalleClientePorId($ins);
                    
                    $formLleno = '';
                    if ($tipo == 'agregarycontinuar') {          
                        $formLleno = $this->construirFormularioCliente($detalleCliente); 
                    }
                    //$fila = $this->crearFilaNuevaClienteConDatos($detalleCliente);                
                    $tabla = $this->contruirTablaClaseClientes();
    
                    $retorno = [
                        'respuesta' => 1,
                        'clasetabla' => $tabla,
                        'formLleno' => $formLleno,
                        'cuentasBancarias' => (isset($detalleCliente->cuentas))? json_decode($detalleCliente->cuentas):false
    
                    ];
    
                }          
    
            }         
       
        print json_encode($retorno);
        
    }

    public function contruirTablaClaseClientes()
    {
        $html = '
        <div id="destinoclientesajax"></div>                            
        <script type="module">
            import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClass.js"
            arrancar("tablaclientes","Clientes/crearTabla", "destinoclientesajax", "Nº", "DESC", 0, "buscador","Clientes/totalRegistros", [20, 30, 40, 50],"min-w-full leading-normal","paginador",["editar","eliminar"],"","");    
        </script>    
        ';
        return $html;
    }

    public function crearFilaNuevaClienteConDatos($detalleCliente)
    {      

        $html = '
        <tr class="rows">
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->id.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->nombre.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->cif.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->poblacion.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->provincia.'</td>
            <td class="botones px-5 py-5 border-b border-gray-200 bg-white text-sm">
                <div class="flex">
                    <a href="" class="mx-1 editar" title="Editar"><i class="fas fa-edit mr-2 fill-current text-yellow-500 text-2xl"></i></a>
                    <a href="" class="mx-1 eliminar" title="Eliminar"><i class="fas fa-trash-alt mr-2 fill-current text-red-600 text-2xl"></i></a>
                </div>
            </td>
        </tr>        
        ';
        return $html;

    }

    public function obtenerDetalleCliente()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] !='') {
            
            $detalleCliente = $this->ModelClientes->detalleClientePorId($_POST['id']);
            
            $fila = $this->construirFormularioCliente($detalleCliente);
            $retorno = [
                'respuesta' => 1,
                'fila' => $fila,
                'cuentasBancarias' => (isset($detalleCliente->cuentas))? json_decode($detalleCliente->cuentas):false
            ];

        }
        print json_encode($retorno);
    }

    public function eliminarCliente()
    {
        $retorno = 0;
        if(isset($_POST['id']) && $_POST['id'] >0){
            
            $del = $this->ModelClientes->eliminarCliente($_POST['id']);
            if ($del) {
                $retorno = 1;
            }
        }
        echo ($retorno);
        
    }


    private function verificarPermisosTecnicos()
    {
        $permisoEdicion=1;
        $permisoSoloVer=1;

        $permisoEditar = false;
        $permisoVer = false;

        if ($_SESSION['nombrerol'] == 'tecnico') {
            
            $buscarPermisos = $this->ModelClientes->permisosTecnicosClientes($_SESSION['idusuario']);
            $permisoEdicion = $buscarPermisos->editarclientes;
            $permisoSoloVer = $buscarPermisos->verclientes;
        }

        if ($_SESSION['nombrerol'] == 'admin' || $permisoEdicion == 1) {                     
            $permisoEditar=true;
            $permisoVer=true;
        }else if ($permisoSoloVer == 1) {
            $permisoEditar=false;
            $permisoVer=true;
        }        
        return array($permisoEditar,$permisoVer);
    }

    public function actualizarCliente()
    {                
        $retorno['error'] = true;
        $retorno['mensaje'] = 'Ha ocurrido un error y no se ha podido actualizar el cliente.';   
        $permisosGuardar = $this->verificarPermisosTecnicos();
        
        if($permisosGuardar[0]===true){


            if (isset($_POST['form']) && $_POST['id'] >0) {
            
                $datos = [];           
                $tecnicos = [];
                $contactos = [];            
                $nombres = [];
                $mails = [];
                $telefonos = [];
                $cuentasBancarias = [];
    
                foreach ($_POST['form'] as $row) {
                    if ($row['name'] == 'codigoTecnico' ) {
                        $idTecnico = $this->ModelClientes->obtenerIdTecnicoDesdeCodigoTecnico($row['value']);                    
                        $tecnicos[] = $idTecnico;
                    }
                    if ($row['name'] == 'nombreContacto' ) {
                        $nombres[] = $row['value'];                    
                    }
                    if ($row['name'] == 'mailContacto' ) {
                        $mails[] = $row['value'];                    
                    }
                    if ($row['name'] == 'telefonoContacto' ) {
                        $telefonos[] = $row['value'];                    
                    }
                  
                    if (strpos($row['name'], 'selectCuenta') !== false) {  
                        $cuentasBancarias[] = $row['value'];  
                    }
    
                    $datos[$row['name']] = $row['value'];
                    
                }
                $datos['idstecnicos'] = $tecnicos; 
                $datos['cuentasBancarias'] = $cuentasBancarias;  
              
    
                if (count($nombres) >0) {
                    
                    for ($i=0; $i < count($nombres) ; $i++) {                                 
                            $nombre = $nombres[$i];
                            $email = $mails[$i];
                            $telefono = $telefonos[$i];
    
                            $tmp = [                            
                                'nombre' => $nombre,
                                'email' => $email,
                                'telefono' => $telefono
                            ];
                            $contactos[] = $tmp;
                    }                
                }
                $datos['contactos'] = $contactos;  
    
                $datos['id'] = $_POST['id'];             
                          
                $upd = $this->ModelClientes->actualizarDatosClienteNuevo($datos);
                if ($upd) {                    
                    $retorno['error'] = false;
                    $retorno['mensaje'] = 'Se han actualizado los datos corréctamente.';
                }
    
            } 

        }else{
            $retorno['error'] = true;
            $retorno['mensaje'] = 'No tiene permiso para modificar datos de clientes.';
        }    
       
        print json_encode($retorno);
        
    }

    public function verSucursalesCliente()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] >0) {
            
            $sucursales = $this->ModelClientes->obtenerSucursalesPorCliente($_POST['id']);
            $html = '';

            $permisosGuardar = $this->verificarPermisosTecnicos();
        
            if($permisosGuardar[0]===true){
                $html .= '                
                <div class="inline-flex my-3 mx-7">                       
                        <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Agregar Sucursal</label>
                        <a href="#" id="addSucursal" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1" title="Nueva sucursal"><i class="fas fa-plus-circle"></i></a>                        
                </div>
                ';                
            }
                
            $tablaSucursales = $this->construirTablaSucursales($sucursales);

            $html .= $tablaSucursales;
            $retorno = [
                'respuesta' => 1,
                'tabla' => $html
            ];

        }

        print json_encode($retorno);
    }

    public function construirTablaSucursales($sucursales)
    {
        $tabla = "<div class='grid grid-cols-1 w-full shadow rounded-lg overflow-x-auto'> <table class='min-w-full leading-normal' id='tablaSucursales'>
        <thead>
            <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Nº</th>
            <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Sucursal</th>
            <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Dirección</th>
            <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Acciones</th>
        </thead>
        <tbody id='tablaSucursalesBody'>";

        if ($sucursales && count($sucursales) >0) {

            $cont =0;
            foreach ($sucursales as $sucursal) {
                $cont++;

                $direccion = $sucursal->direccion. " - " .$sucursal->poblacion. " - " .$sucursal->provincia. " - " .$sucursal->codigopostal;

                $tabla .= "<tr>";
                if(EMPRESA== 'INFOMALAGA'){
                    $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'>".$sucursal->id."</td>";
                }else{
                    $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'><span style='display:none;'>".$sucursal->id."-</span>".$cont."</td>";
                }
                $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'>".$sucursal->nombre."</td>";
                $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'>".$direccion."</td>";
                $tabla .= "<td class='botones p-2 border-b border-gray-200 bg-white text-sm'>
                            <div class='flex'>
                            <a href='' class='mx-1 editarSucursal' title='Editar'><i class='fas fa-user-edit mr-2 fill-current text-yellow-500 text-lg'></i></a>
                            <a href='' class='mx-1 eliminarSucursal' title='Eliminar'><i class='fas fa-user-minus mr-2 fill-current text-red-600 text-lg'></i></a>
                            </div>                
                        </td>";
                $tabla .= "</tr>";
            }
        
        }
        $tabla .= '</tbody></table></div>';

        return $tabla;

    }

    public function nuevaSucursal()
    {
        $datos = '';
        $form = $this->construirBodyFormularioSucursal($datos);
        $salida['form'] = $form;
        echo json_encode($salida);
    }

    public function obtenerDetalleSucursal()
    {
        $retorno = [
            'respuesta' => 0,
            'bodyModal' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] !='') {
            
            $detalleSucursal = $this->ModelClientes->detalleSucursalPorId($_POST['id']);
            $bodyModal = $this->construirBodyFormularioSucursal($detalleSucursal);
            $retorno = [
                'respuesta' => 1,
                'bodyModal' => $bodyModal,
            ];

        }
        print json_encode($retorno);
    }

    public function construirBodyFormularioSucursal($datos)
    {

        $id = '';
        $html = '';
        $nombre = '';
        $cif = '';
        $direccion = '';
        $codigopostal = '';
        $poblacion = '';
        $provincia = '';
        $btnSubmit = 'A&ntilde;adir';
        $idBtnSubmit = 'crearSucursalNueva';
        $idCliente = '';
        $fila = '';
                        
        $contactos ='<tr>                            
                        <td width="40%" class="font-bold">Contacto</td>
                        <td width="20%" class="font-bold">Email</td>
                        <td width="20%" class="font-bold">Teléfono</td>
                        <td width="10%" class="font-bold"></td>
                    </tr>';                

        //si el cliente existe entrea a este IF
        if (isset($datos) && $datos != '') {
            $id = isset($datos->nombre)?$datos->id:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";            
            $direccion = isset($datos->direccion)?$datos->direccion:"";
            $codigopostal = isset($datos->codigopostal)?$datos->codigopostal:"";
            $poblacion = isset($datos->poblacion)?$datos->poblacion:"";
            $provincia = isset($datos->provincia)?$datos->provincia:"";                 
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarSucursal';                   

            //construyo el apartado de contactos (nombre/email/telefono) asignados al cliente
            if ( isset($datos->contactos) && count(json_decode($datos->contactos)) > 0 ) {
                        
                foreach (json_decode($datos->contactos) as $contacto) {
                    $contactos .= '<tr>                                    
                                    <td width="40%"><input name="nombreContactoSuc" value="'.$contacto->nombre.'" class="border-2 border-coolGray-300 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="20%"><input name="mailContactoSuc" value="'.$contacto->email.'" class="border-2 border-coolGray-300 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="20%"><input type="text" regexp="[0-9]{0,9}" name="telefonoContactoSuc" value="'.$contacto->telefono.'" class="border-2 border-coolGray-300 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="10%"><a href="" class="eliminarContactoSuc"><i class="fas fa-user-minus" style="color:red;"></i></a></td>
                                </tr>';
                }                
            }
        }
        

        //aqui construyo la vista para alta cliente nuevo y para actualizar cliente

        $html .= '                       
                <form id="formAltaSucursales">                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8 mt-5 mx-7">
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre sucursal</label>
                                <input name="nombreSucursal" id="nombreSucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Nombre sucursal" value="'.$nombre.'" required/>
                            </div>
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Dirección</label>
                                <input name="direccionSucursal" id="direccionSucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Dirección" value="'.$direccion.'" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-5 md:gap-8 mt-5 mx-7">

                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Población</label>
                                <input type="text" name="poblacionSucursal" id="poblacionSucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Población" value="'.$poblacion.'" />
                            </div>
                    
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Provincia</label>
                                <input name="provinciaSucursal" id="provinciaSucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Provincia" value="'.$provincia.'" />
                            </div>
                            
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Código postal</label>
                                <input type="text" regexp="[0-9]{0,5}" name="codigopostalSucursal" id="codigopostalSucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" placeholder="Código postal" value="'.$codigopostal.'" />
                            </div>                                                                           
                        </div>

                        <div class="inline-flex mt-5 mx-7">                       
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Contactos</label>
                                <a href="#" id="addContactoSucursal" title="Agregar contactos" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1"><i class="fas fa-plus-circle"></i></a>                        
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-5 mx-7">
                            <div class="grid grid-cols-1">
                                <table id="tablaContactosSucursal">
                                '.$contactos.'
                                </table>
                            </div>
                            <div class="grid grid-cols-1"></div>
                        </div>';
                    $html .='                                        
                        <div class="flex items-center justify-center px-6 pt-3 border-t border-solid border-blueGray-200 rounded-b">
                            <a class="w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-white px-4 py-2 mr-3 cerrarModalEditSucursal">Cerrar</a>
                            <button id="'.$idBtnSubmit.'" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl font-medium text-white px-4 py-2">'.$btnSubmit.'</button>
                        </div>
                </form>';       
        
        return $html;
    }

    public function crearSucursalNueva()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];

        if (isset($_POST['form'])) {            
            
            $datos = [];            
            $contactos = [];            
            $nombres = [];
            $mails = [];
            $telefonos = [];
            foreach ($_POST['form'] as $row) {                   
                if ($row['name'] == 'nombreContactoSuc' ) {
                    $nombres[] = $row['value'];                    
                }
                if ($row['name'] == 'mailContactoSuc' ) {
                    $mails[] = $row['value'];                    
                }
                if ($row['name'] == 'telefonoContactoSuc' ) {
                    $telefonos[] = $row['value'];                    
                }
                $datos[$row['name']] = $row['value'];
                                
            }
            
            if (count($nombres) >0) {
                
                for ($i=0; $i < count($nombres) ; $i++) {                               
                        $nombre = $nombres[$i];
                        $email = $mails[$i];
                        $telefono = $telefonos[$i];

                        $tmp = [                            
                            'nombre' => $nombre,
                            'email' => $email,
                            'telefono' => $telefono
                        ];
                        $contactos[] = $tmp;
                }               
            }

            $datos['idCliente'] = $_POST['idCliente'];
            $datos['contactos'] = $contactos;  
            
            $ins = $this->ModelClientes->insertarDatosSucursalNueva($datos);
            if ($ins && $ins >0) {
                $detalleSucursal = $this->ModelClientes->detalleSucursalPorId($ins);
                //$fila = $this->crearFilaNuevaSucursalConDatos($detalleSucursal);
                $tablaRecargada = $this->recargarTablaSucursales($_POST['idCliente']);
                $retorno = [
                    'respuesta' => 1,
                    //'fila' => $fila,
                    'tablarecargada'=> $tablaRecargada
                ];

            }          

        }        
        print json_encode($retorno);
        
    }

    public function recargarTablaSucursales($idCliente)
    {
        $sucursales = $this->ModelClientes->obtenerSucursalesPorCliente($idCliente);

        $html = '';
        $permisosGuardar = $this->verificarPermisosTecnicos();
        
        if($permisosGuardar[0]===true){

            $html .= '                
            <div class="inline-flex my-3 mx-7">                       
                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Agregar Sucursal</label>
                    <a href="#" id="addSucursal" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1" title="Nueva sucursal"><i class="fas fa-plus-circle"></i></a>                        
            </div>
            ';

            
        }
            
            
        $tablaSucursales = $this->construirTablaSucursales($sucursales);

        $html .= $tablaSucursales;
        return $html;
    }

    public function crearFilaNuevaSucursalConDatos($detalleSucursal)
    {
        $direccion = $detalleSucursal->direccion. " - " .$detalleSucursal->poblacion. " - ".$detalleSucursal->provincia. " - ".$detalleSucursal->codigopostal;

        $html = '
        <tr class="rows">
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleSucursal->id.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleSucursal->nombre.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$direccion.'</td>
            <td class="botones px-5 py-5 border-b border-gray-200 bg-white text-sm">
                <div class="flex">
                    <a href="" class="mx-1 editarSucursal" title="Editar"><i class="fas fa-user-edit mr-2 fill-current text-yellow-500 text-lg"></i></a>
                    <a href="" class="mx-1 eliminarSucursal" title="Eliminar"><i class="fas fa-user-minus mr-2 fill-current text-red-600 text-lg"></i></a>
                </div>
            </td>
        </tr>        
        ';
        return $html;

    }

    public function actualizarSucursal()
    {              

        $retorno['error'] = true;
        $retorno['mensaje'] = 'Ha ocurrido un error y no se ha podido actualizar la sucursal.';   
        $permisosGuardar = $this->verificarPermisosTecnicos();
        
        if($permisosGuardar[0]===true){
            
            if (isset($_POST['form']) && $_POST['idSucursal'] >0 && $_POST['idCliente'] >0) {            
                
                $datos = [];            
                $contactos = [];            
                $nombres = [];
                $mails = [];
                $telefonos = [];
                foreach ($_POST['form'] as $row) {                   
                    if ($row['name'] == 'nombreContactoSuc' ) {
                        $nombres[] = $row['value'];                    
                    }
                    if ($row['name'] == 'mailContactoSuc' ) {
                        $mails[] = $row['value'];                    
                    }
                    if ($row['name'] == 'telefonoContactoSuc' ) {
                        $telefonos[] = $row['value'];                    
                    }
                    $datos[$row['name']] = $row['value'];
                                    
                }
                
                if (count($nombres) >0) {
                    
                    for ($i=0; $i < count($nombres) ; $i++) {                               
                            $nombre = $nombres[$i];
                            $email = $mails[$i];
                            $telefono = $telefonos[$i];

                            $tmp = [                            
                                'nombre' => $nombre,
                                'email' => $email,
                                'telefono' => $telefono
                            ];
                            $contactos[] = $tmp;
                    }               
                }
                            
                $datos['idSucursal'] = $_POST['idSucursal'];
                $datos['contactos'] = $contactos; 
                        
                $upd = $this->ModelClientes->actualizarDatosSucursalNueva($datos);
                if ($upd) {
                    //$retorno = 1;
                    $retorno['error'] = false;
                    $retorno['mensaje'] = 'Se han actualizado los datos corréctamente.';
                }

            }        

        }else{
            $retorno['error'] = true;
            $retorno['mensaje'] = 'No tiene permiso para modificar datos de sucursales.';
        }

        print json_encode($retorno);        
    }

    public function eliminarSucursal()
    {
        $retorno['error'] = true;
        $retorno['mensaje'] = 'Ha ocurrido un error y no se ha podido eliminar la sucursal.';   
        $permisosGuardar = $this->verificarPermisosTecnicos();
        
        if($permisosGuardar[0]===true){

            if(isset($_POST['id']) && $_POST['id'] >0){
            
                $del = $this->ModelClientes->eliminarSucursal($_POST['id']);
                if ($del) {
                    $retorno['error'] = false;
                    $retorno['mensaje'] = '';
                }
            }        
        }else{
            $retorno['error'] = true;
            $retorno['mensaje'] = 'No tiene permiso para eliminar sucursales.';
        }

        //echo ($retorno);
        print json_encode($retorno);        
    }

    public function verEquiposCliente()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] >0) {
            
            $sucursales = $this->ModelClientes->obtenerSucursalesActivasPorCliente($_POST['id']);
            
            
            $permisosGuardar = $this->verificarPermisosTecnicos();
            $addCreate = 'none';
            if($permisosGuardar[0]===true){
                $addCreate = '';
            }

            $html = '  
                <div class="flex items-center justify-center  md:gap-8 gap-4">
                    <span id="mensajeValidacionEquipo" class="text-xl font-bold text-pink-600 mx-4"></span>
                </div>   

                <div class="grid grid-cols-1 lg:grid-cols-2">

                    <div class="flex flex-col grid grid-cols-1 mr-2">                        
                        <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold ml-2">Sucursal</label>
                        <select name="sucursalSelect" id="sucursalSelect" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent w-full">
                            <option disabled selected>Seleccionar</option>';
                                            
                        foreach ($sucursales as $sucursal) {
                            $html .='
                            <option value="'.$sucursal->id.'" >'.$sucursal->nombre.'</option>';
                        }

                        $html .='                            
                        </select>
                    </div>
                
                    <div class="mr-2" style="display:'.$addCreate.';">
                        <div class="inline-flex my-3">                       
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ml-2">Agregar equipo</label>
                            <a href="#" id="addEquipo" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1" title="Nuevo equipo"><i class="fas fa-plus-circle"></i></a>
                        </div>
                    </div>
                
                </div>
                    
                ';                           
            
            $retorno = [
                'respuesta' => 1,
                'tabla' => $html
            ];

        }

        print json_encode($retorno);
    }

    public function verEquiposPorSucursal()
    {
        $tabla = "<div class='grid grid-cols-1 w-full shadow rounded-lg overflow-x-auto mt-3'><table class='min-w-full leading-normal' id='tablaEquiposPorSucursal'>
                    <thead>
                        <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Id</th>
                        <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Equipo</th>
                        <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Serie</th>
                        <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Marca</th>";

        if(EMPRESA=='INFOMALAGA'){
            $tabla .= "<th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>IP</th>";
        }else{
            $tabla .= "<th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Modelo</th>";
        }

        $tabla .= "<th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Acciones</th>
                    </thead>
                    <tbody id='tablaEquiposBody'>";

        $retorno = [
            'respuesta' => 0,
            'tabla' => $tabla
        ];

        if (isset($_POST['idSucursal']) && $_POST['idSucursal']>0) {

            $equipos = $this->ModelClientes->listadoEquiposPorSucursal($_POST['idSucursal']);

            if (isset($equipos) && count($equipos) >0 ) {                                         

                    foreach ($equipos as $equipo) {            

                        $tabla .= "<tr>";
                        $tabla .= "<td class='px-2 py-1 border-b border-gray-200 bg-white text-sm'>".$equipo->id."</td>";
                        $tabla .= "<td class='px-2 py-1 border-b border-gray-200 bg-white text-sm'>".$equipo->nombre."</td>";
                        $tabla .= "<td class='px-2 py-1 border-b border-gray-200 bg-white text-sm'>".$equipo->serie."</td>";
                        $tabla .= "<td class='px-2 py-1 border-b border-gray-200 bg-white text-sm'>".$equipo->marca."</td>";
                        $tabla .= "<td class='px-2 py-1 border-b border-gray-200 bg-white text-sm'>".$equipo->ip."</td>";
                        $tabla .= "<td class='botones px-2 py-1 border-b border-gray-200 bg-white text-sm'>
                                    <div class='flex'>
                                    <a href='' class='mx-1 editarEquipo' title='Editar'><i class='fas fa-edit mr-2 fill-current text-yellow-500 text-lg'></i></a>
                                    <a href='' class='mx-1 eliminarEquipo' title='Eliminar'><i class='fas fa-trash-alt mr-2 fill-current text-red-600 text-lg'></i></a>
                                    </div>                
                                </td>";
                        $tabla .= "</tr>";
                    }
                                            
                $retorno = [
                    'respuesta' => 1,
                    'tabla' => $tabla
                ];
            }            
        }
        $tabla .= '</tbody></table></div>';

        print json_encode($retorno);

    }

    public function nuevoEquipo()
    {
        $datos = [];
        if (isset($_POST['idsucursal']) && $_POST['idsucursal'] >0) {
            $datos['idsucursal'] = $_POST['idsucursal'];
            $datos['nombresucursal'] = $_POST['nombresucursal'];
            $datos['idcliente'] = $_POST['idCliente'];
        }
        $existe = 0;
        $form = $this->construirBodyFormularioEquipo($existe,$datos);
        $salida['form'] = $form;
        echo json_encode($salida);
    }


    public function construirBodyFormularioEquipo($existe,$datos)
    {
        $usuarios ='<tr>                            
                        <td width="20%" class="font-bold">Nombre</td>
                        <td width="20%" class="font-bold">Apellidos</td>                        
                        <td width="40%" class="font-bold">Email</td>
                        <td width="15%" class="font-bold">Tipo</td>
                        <td width="5%" class="font-bold"></td>
                    </tr>';    
        $html = '';
                
        if ($existe == 1) {
            
            //si el cliente existe entrea a este IF                
                
            if (isset($datos) && $datos != '') {
               
                $id = isset($datos->nombre)?$datos->id:"";
                $idSucursal = isset($datos->idsucursal)?$datos->idsucursal:"";
                $nomSucursal = isset($datos->nombresucursal)?$datos->nombresucursal:"";
                $nombre = isset($datos->nombre)?$datos->nombre:"";                             
                $descripcion = isset($datos->descripcion)?$datos->descripcion:"";
                $serie = isset($datos->serie)?$datos->serie:"";
                $marca = isset($datos->marca)?$datos->marca:"";
                $ip = isset($datos->ip)?$datos->ip:"";
                $btnSubmit = 'Guardar';
                $idBtnSubmit = 'actualizarEquipo';       
                
                $sistemaop = isset($datos->sistemaop)?$datos->sistemaop:"";
                $antivirus = isset($datos->antivirus)?$datos->antivirus:"";
                $versionoffice = isset($datos->versionoffice)?$datos->versionoffice:"";
                
                //construyo el apartado de contactos (nombre/email/telefono) asignados al cliente
                //ESTO DA ERRROR CORREGIR, ES PARA LA EDICIONI DEL EQUIPO
                /* 
                if (isset($datos) && $datos->id) {
                   
                    $usuariosAsign = $this->ModelClientes->obtenerUsuariosAsignadosAEquipoPorIdEquipo($datos->id);
                    if(isset($usuariosAsign) && count($usuariosAsign) >0){                    
                    
                        foreach ($usuariosAsign as $usuario) {
                            $usuarios .= '<tr>                                    
                                            <td width="20%">'.$usuario->nombre.'</td>
                                            <td width="20%">'.$usuario->apellidos.'</td>
                                            <td width="40%">'.$usuario->correo.'</td>
                                            <td width="15%">'.$usuario->clientetipo.'</td>
                                            <td width="5%"></td>
                                        </tr>';
                        }                
                    }
                }
                */
            }

        } else{
            $id = '';            
            $nombre = '';
            $descripcion = '';
            $serie = '';
            $marca = '';
            $ip = '';
            $idSucursal = $datos['idsucursal'];
            $nomSucursal = $datos['nombresucursal'];
            $btnSubmit = 'A&ntilde;adir';
            $idBtnSubmit = 'crearEquipoNuevo';        
            $fila = '';
            $sistemaop = "";
            $antivirus = "";
            $versionoffice = "";
            $usuariosCliente = $this->ModelClientes->usuariosActivosTipoSupervisorYUsuarioPorIdCliente($datos['idcliente']);
            
            
        }   
 
        

            //aqui construyo la vista para alta cliente nuevo y para actualizar cliente
            $iptext = (EMPRESA=='INFOMALAGA')?'IP Equipo':'Modelo';
            $display = (EMPRESA=='INFOMALAGA')?'':'none';
            $html .= '                       
                    <form id="formAltaEquipos">    
                        <input type="hidden" id="idSucursalEdit" value="'.$idSucursal.'">                    
                        <div style="height: 25rem;overflow-y:scroll;">
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre equipo</label>
                                    <input name="nombreEquipo" id="nombreEquipo" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Nombre equipo" value="'.$nombre.'" required/>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-2 mx-7">

                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Serie</label>
                                    <input name="serie" id="serie" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Serie" value="'.$serie.'" />                                    
                                </div>
                        
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Marca</label>
                                    <input type="text" name="marca" id="marca" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Marca" value="'.$marca.'" />
                                </div>
                                
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">'.$iptext.'</label>
                                    <input type="text" name="ip" id="ip" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="'.$iptext.'" value="'.$ip.'" />
                                </div>                                                                           
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-2 mx-7" style="display:'.$display.';">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Sist. operativo</label>
                                    <input name="sistemaop" id="sistemaop" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Sist. operativo" value="'.$sistemaop.'" />                                    
                                </div>
                        
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Antivirus</label>
                                    <input type="text" name="antivirus" id="antivirus" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Antivirus" value="'.$antivirus.'" />
                                </div>
                                
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Version office</label>
                                    <input type="text" name="versionoffice" id="versionoffice" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Version office" value="'.$versionoffice.'" />
                                </div>   
                            </div> ';  

                            $html .='<div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Descripción</label>
                                    <textarea name="descripcionEquipo" id="descripcionEquipo" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Descripción" required/>'.$descripcion.'</textarea>                                    
                                </div>
                            </div>';

                            $html .= $this->apartadoFicheroEquipo();  

                            if ($existe !=1 && EMPRESA!=='TELESAT') {                                
                            
                                $html .='
                                <div class="inline-flex mt-5 mx-7">                       
                                        <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Asignar usuarios</label>
                                        <a href="#" id="asignarUsuarioEquipo" title="Usuario nuevo" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1"><i class="fas fa-plus-circle"></i></a>    
                                </div>
                                <div class="inline-flex mt-5 mx-7">                           
                                        <select class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" name="buscarUsuario" id="buscarUsuario" style="width: 100%;">
                                            <option>Buscar usuarios</option>';
                                            if (isset($usuariosCliente) && count($usuariosCliente)>0) {                                            
                                                foreach ($usuariosCliente as $key) {
                                                    $html .= '<option value="'.$key->id.'">'.$key->nombre.' '.$key->apellidos.'</option>';
                                                }
                                            }

                                $html .= '
                                            </select>

                                    </div>                         
                                    <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7" style="display:'.$display.';">
                                        <div class="grid grid-cols-1">
                                            <table id="tablaUsuariosEquipos">
                                            '.$usuarios.'
                                            </table>
                                        </div>
                                        <div class="grid grid-cols-1"></div>
                                    </div>'; 
                            }

                        $html .='</div>';
                        $html .='                                        
                            <div class="flex items-center justify-center px-6 pt-3 border-t border-solid border-blueGray-200 rounded-b">
                                <a class="cerrarModalEditEquipo w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-white px-4 py-2 mr-3">Cerrar</a>
                                <button id="'.$idBtnSubmit.'" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl font-medium text-white px-4 py-2">'.$btnSubmit.'</button>
                            </div>
                    </form>';       
            
                     return $html;
        
    }

    public function apartadoFicheroEquipo()
    {
        $html ='<div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
            <div class="grid grid-cols-1">
            <div>                                
            <div class="inline-flex">                               
                <label class="py-1 2xl:py-2 flex-1 uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Adjuntar imágenes</label>
                                
                <a id="addFileEquipo" class="w-auto bg-gray-400 hover:bg-gray-500 rounded-lg shadow-xl text-sm lg:text-sm xl:text-base text-white px-2 ml-3 flex items-center justify-center"><i class="far fa-image mr-2 text-xl"></i>Agregar</a>
            </div> 
                                                
            <div id="formularioSubirFicheroEquipo" style="display:none;" class="grid grid-cols-1 mt-4">
                <p>Tamaño máximo de cada archivo = 2MB</p>
                <span id="msgValidaFichero" class="text-xs lg:text-sm xl:text-base font-bold text-pink-600 mx-4"></span>
                <input type="file" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-pink-700 focus:border-transparent inputFichero" name="ficheroCrearEquipo[]" id="ficheroCrearEquipo" multiple="" placeholder="Adjunte fichero">
            </div>                            
        </div>                                   
            </div>
        </div>';      
        return $html;
    }

    
   

    public function crearEquipoNuevo()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];       

        if (isset($_POST['form']) && $_POST['idCliente'] > 0 && $_POST['idSucursal'] > 0 && $_POST['nombreEquipo'] !='') {            
            
            $datos = [];                        
            $nombres = [];
            $apellidos = [];     
            $tipos = [];
            $emails = [];
            $idsUsuarios = [];
            $idsUsuariosNuevos = [];
           
            //trato los datos que llegan por post
            foreach ($_POST['form'] as $row) {           

                if ($row['name'] == 'idUsuarioAdd' ) {
                    $idsUsuarios[] = $row['value'];                                
                }      
                
                if ($row['name'] == 'idUsuarioNew' ) {
                    $idsUsuariosNuevos[] = $row['value'];                    
                }     
                            
                if ($row['name'] == 'nombreUsuarioNew' ) {
                    $nombres[] = $row['value'];
                }                
                if ($row['name'] == 'apellidosUsuarioNew' ) {
                    $apellidos[] = $row['value'];                    
                }
                if ($row['name'] == 'clienteTipoNew' ) {
                    $tipos[] = $row['value'];                    
                }
                if ($row['name'] == 'emailUsuarioNew' ) {
                    $emails[] = $row['value'];                    
                }                
                $datos[$row['name']] = $row['value'];

            }          
                      
            $usuariosAsignados = [];
            if ($idsUsuarios && count($idsUsuarios)>0){                       
                for ($i=0; $i < count($idsUsuarios); $i++) {                    
                    $usuariosAsignados[] = $idsUsuarios[$i];                    
                }
            } 
            
            $tmp2 = [];
            $crearUsuarios = [];
            if ($idsUsuariosNuevos && count($idsUsuariosNuevos)>0){
               
                for ($i=0; $i < count($idsUsuariosNuevos); $i++) { 
                    
                    $tmp2['nombre'] = $nombres[$i];
                    $tmp2['apellido'] = $apellidos[$i];
                    $tmp2['tipo'] = $tipos[$i];
                    $tmp2['email'] = $emails[$i];
                    
                    $crearUsuarios[] = $tmp2;
                }
            } 
            
            $datos['usuariosAsignados'] = $usuariosAsignados;           
            $datos['crearUsuarios'] = $crearUsuarios;  
                  
            $datos['idSucursal'] = $_POST['idSucursal'];
            $datos['idCliente'] = $_POST['idCliente'];
            $datos['nombreEquipo'] = $_POST['nombreEquipo'];
                       
            $ins = $this->ModelClientes->insertarDatosEquipoNuevo($datos);
            if ($ins && $ins >0) {
                               
                if ($crearUsuarios && count($crearUsuarios)>0) {
                    $this->crearUsuariosDesdeClienteEquipos($datos,$ins);
                }
                if ($usuariosAsignados && count($usuariosAsignados)>0) {
                    $this->asignarUsuariosDesdeClienteEquipos($datos,$ins);
                }
                
                $detalleEquipo = $this->ModelClientes->detalleEquipoPorId($ins);
                $fila = $this->crearFilaNuevoEquipoConDatos($detalleEquipo);

                $retorno = [
                    'respuesta' => 1,
                    'fila' => $fila,
                    'idequipo' => $ins
                ];
            }                      
        }        
        print json_encode($retorno);        
    }

    public function agregarImagenEquipoNuevo()
    {       
        $retorno = [];
        $nombres = $_FILES['ficheroCrearEquipo']['name'];
        $tipos = $_FILES['ficheroCrearEquipo']['type'];
        $tamanios = $_FILES['ficheroCrearEquipo']['size'];
        $temporales = $_FILES['ficheroCrearEquipo']['tmp_name'];
        $errores = $_FILES['ficheroCrearEquipo']['error'];
        $idEquipo = $_POST['idequipo'];
        $imagenes = [];
        $contImgs = 0;

        for ($i=0; $i < count($nombres); $i++) { 
            if ($tamanios[$i] >0 && $tamanios[$i] <= 4000000 && $errores[$i] == 0 ) {
                
                //para obtener la extension de los ficheros
                $extInst = new SplFileInfo($nombres[$i]);                                
                $extension = strtolower($extInst->getExtension());
                
                $extensionesImg = ["jpeg", "jpg", "png", "gif", "bmp", "svg", "doc", "docx", "xls", "xlsx", "pdf"]; 
               
                if (in_array($extension, $extensionesImg)) {
                                         
                    $tmp = [];
                    $tmp['nombre'] = $idEquipo."_".$nombres[$i];
                    $tmp['tipo'] = $tipos[$i];
                    $tmp['tamanio'] = $tamanios[$i];
                    $tmp['tmp'] = $temporales[$i];
                    $imagenes[] = $tmp;

                    $path = $temporales[$i];
               
                    // Extensión de la imagen
                    $type =  $tipos[$i];
                    
                    // Cargando la imagen
                    $directorio = DOCS_EQUIPOS;
                    $subir_archivo = $directorio . basename($idEquipo."_".$nombres[$i]);
                    if (move_uploaded_file($path, $subir_archivo)) {                        
                        $this->doCurl(RUTA_URL.'/public/documentos/Equipos/'.basename($idEquipo."_".$nombres[$i]));                        
                        
                        $insFile = $this->ModelClientes->insertarDatosFicheroEquipo($tmp, $idEquipo);
                        if($insFile){
                            $contImgs++;
                        }

                    } 

                }
                
            }           
        }

        if(count($nombres) > 0 && count($nombres) == $contImgs){
            $retorno['error'] = false;
            $retorno['mensaje'] = '';
        }else{
            $retorno['error'] = true;
            $retorno['mensaje'] = 'pero ha ocurrido un error al agregar las imágenes del equipo.';
        }        
        print json_encode($retorno);
    }

    public function crearUsuariosDesdeClienteEquipos($datos,$idEquipo)
    {            
        foreach ($datos['crearUsuarios'] as $key) {
            $arr = [];   
            $arr[] = $idEquipo;
            $jsonEquipo = json_encode($arr);

            $arr2 = [];   
            $arr2[] = $datos['idSucursal'];
            $jsonSucursal = json_encode($arr2);

            $ins = $this->ModelClientes->crearUsuarioNuevos($key,$jsonEquipo,$datos['idCliente'],$jsonSucursal);
            if ($ins && $ins>0) {
                $this->enviarEmailConfirmacionCreacionUsuario($key['email'],$ins);
            }
        }
    }

    
    public function enviarEmailConfirmacionCreacionUsuario($email,$idsUsuario)
    {
        //contruyo array con datos de envío:
        $nombreRemitente = 'InfoMalaga';
        $emailRemitente = CUENTA_CORREO;
        $asunto = "Creación de usuario InfoMalaga";
        $emailsDestino = [$email];
        $plantilla = $this->doCurl(RUTA_URL."/public/documentos/plantillasCorreo/plantillabotonContrasenia.php");
                                        
        //construyo cuerpo de mensaje
        $enlace = 'Haz click en el enlace para cambiar la contraseña.';
        $contenido = 'Se ha creado el usuario '.$email.' en la plataforma de Infomálaga. La contraseña es user';
        $info = RUTA_URL."/Login/cambioContrasenia/1/".$idsUsuario;
        $cambiar = ['{ENLACE}','{CONTENIDO}','{RUTAWEB}'];
        $cambio = [$enlace, $contenido, $info];
        $mensaje = str_replace($cambiar,$cambio,$plantilla);        
        $message = html_entity_decode($mensaje);

        $tipoDoc = '';
        $attachment = '';
        enviarEmail::enviarEmailDestinatario($nombreRemitente, $emailRemitente, $emailsDestino, $asunto, $message, $attachment, $tipoDoc);

    }

    public function asignarUsuariosDesdeClienteEquipos($datos,$idEquipo)
    {        
        $idSucursal = $datos['idSucursal'];
        
        foreach ($datos['usuariosAsignados'] as $key) {

            $this->ModelClientes->agregarEquipoEnTablaUsuario($key,$idEquipo);

            $sucursalAsignada = $this->ModelClientes->verificarSiUsuarioTieneLaSucursalAsignada($key,$idSucursal);
            if ($sucursalAsignada==0) {
                $this->ModelClientes->agregarSucursalEnTablaUsuario($key,$idSucursal);
            }            
        }
    }
    
    public function crearFilaNuevoEquipoConDatos($equipo)
    { 
        $html = '
        <tr class="rows">
            <td class="px-2 py-1 border-b border-gray-200 bg-white text-sm ">'.$equipo->id.'</td>
            <td class="px-2 py-1 border-b border-gray-200 bg-white text-sm ">'.$equipo->nombre.'</td>
            <td class="px-2 py-1 border-b border-gray-200 bg-white text-sm ">'.$equipo->serie.'</td>
            <td class="px-2 py-1 border-b border-gray-200 bg-white text-sm ">'.$equipo->marca.'</td>
            <td class="px-2 py-1 border-b border-gray-200 bg-white text-sm ">'.$equipo->ip.'</td>
            <td class="botones px-2 py-1 border-b border-gray-200 bg-white text-sm">
                <div class="d-flex">
                    <a href="" class="mx-1 editarEquipo" title="Editar"><i class="fas fa-edit mr-2 fill-current text-yellow-500 text-lg"></i></a>
                    <a href="" class="mx-1 eliminarEquipo" title="Eliminar"><i class="fas fa-trash-alt mr-2 fill-current text-red-600 text-lg"></i></a>
                </div>
            </td>
        </tr>        
        ';
        return $html;

    }

    public function obtenerDetalleEquipo()
    {
        $retorno = [
            'respuesta' => 0,
            'bodyModal' => '',
        ];
        
        if (isset($_POST['id']) && $_POST['id'] !='') {
                    
            $detalleEquipo = $this->ModelClientes->detalleEquipoPorId($_POST['id']);
            
            if(EMPRESA=='INFOMALAGA'){
                $bodyModal = $this->construirBodyFormularioEquipoEditar($detalleEquipo);
            }else{
                $bodyModal = $this->construirBodyFormularioEquipoEditarOtra($detalleEquipo);
            }            
           
            $retorno = [
                'respuesta' => 1,
                'bodyModal' => $bodyModal
            ];

        }
        print json_encode($retorno);
    }

    public function construirBodyFormularioEquipoEditarOtra($datos)
    {  
        $html = '';                                  
        //si el cliente existe entrea a este IF                
                
        if (isset($datos) && $datos != '') {
               
            $id = isset($datos->nombre)?$datos->id:"";
            $idSucursal = isset($datos->idsucursal)?$datos->idsucursal:"";
            $nomSucursal = isset($datos->nombresucursal)?$datos->nombresucursal:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";                       
            $descripcion = isset($datos->descripcion)?$datos->descripcion:"";
            $serie = isset($datos->serie)?$datos->serie:"";
            $marca = isset($datos->marca)?$datos->marca:"";
            $ip = isset($datos->ip)?$datos->ip:"";
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarEquipo';       
            
            $sistemaop = isset($datos->sistemaop)?$datos->sistemaop:"";
            $antivirus = isset($datos->antivirus)?$datos->antivirus:"";
            $versionoffice = isset($datos->versionoffice)?$datos->versionoffice:"";
            
            $usuariosAsignados = $this->construyoTablausuariosYSupervisoresAsignados($id);
          
            //aqui construyo la vista para alta cliente nuevo y para actualizar cliente

                $html .= '                       
                    <form id="formAltaEquipos">    
                        <input type="hidden" id="idSucursalEdit" value="'.$idSucursal.'">                    
                        <div style="height: 25rem;overflow-y:scroll;">
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre equipo</label>
                                    <input name="nombreEquipo" id="nombreEquipo" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Nombre equipo" value="'.$nombre.'" required/>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-2 mx-7">

                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Serie</label>
                                    <input name="serie" id="serie" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Serie" value="'.$serie.'" required/>                                    
                                </div>
                        
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Marca</label>
                                    <input type="text" name="marca" id="marca" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Marca" value="'.$marca.'" required/>
                                </div>
                                
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Modelo</label>
                                    <input type="text" name="ip" id="ip" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Modelo" value="'.$ip.'" required/>
                                </div>                                                                           
                            </div>';


                $html .= ' <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-2 mx-7" style="display:none;">
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Sist. operativo</label>
                            <input name="sistemaop" id="sistemaop" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Sist. operativo" value="'.$sistemaop.'" required/>                                    
                        </div>
                
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Antivirus</label>
                            <input type="text" name="antivirus" id="antivirus" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Antivirus" value="'.$antivirus.'" required/>
                        </div>
                        
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Version office</label>
                            <input type="text" name="versionoffice" id="versionoffice" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Version office" value="'.$versionoffice.'" required/>
                        </div>                                    
                    </div>  

                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Descripción</label>
                                    <textarea name="descripcionEquipo" id="descripcionEquipo" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Descripción" />'.$descripcion.'</textarea>
                                </div>
                            </div>';

                $html .= $this->apartadoFicheroEquipoEditar($id);

                            
                /*$html .= '
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <table id="tablaUsuariosEquipos">
                                    '.$usuariosAsignados.'
                                    </table>
                                </div>
                            </div>';*/
                           
                        $html .='</div>';
                        $html .='                                        
                            <div class="flex items-center justify-center px-6 pt-3 border-t border-solid border-blueGray-200 rounded-b">
                                <a class="cerrarModalEditEquipo w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-white px-4 py-2 mr-3">Cerrar</a>
                                <button id="'.$idBtnSubmit.'" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl font-medium text-white px-4 py-2">'.$btnSubmit.'</button>
                            </div>
                    </form>';       
            
        }
                     return $html;
        
    }    

    public function construirBodyFormularioEquipoEditar($datos)
    {
  
        $html = '';                                  
        //si el cliente existe entrea a este IF                
                
        if (isset($datos) && $datos != '') {
               
            $id = isset($datos->nombre)?$datos->id:"";
            $idSucursal = isset($datos->idsucursal)?$datos->idsucursal:"";
            $nomSucursal = isset($datos->nombresucursal)?$datos->nombresucursal:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";                       
            $descripcion = isset($datos->descripcion)?$datos->descripcion:"";
            $serie = isset($datos->serie)?$datos->serie:"";
            $marca = isset($datos->marca)?$datos->marca:"";
            $ip = isset($datos->ip)?$datos->ip:"";
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarEquipo';       
            
            $sistemaop = isset($datos->sistemaop)?$datos->sistemaop:"";
            $antivirus = isset($datos->antivirus)?$datos->antivirus:"";
            $versionoffice = isset($datos->versionoffice)?$datos->versionoffice:"";
            
            $usuariosAsignados = $this->construyoTablausuariosYSupervisoresAsignados($id);
          
            //aqui construyo la vista para alta cliente nuevo y para actualizar cliente

            $html .= '                       
                    <form id="formAltaEquipos">    
                        <input type="hidden" id="idSucursalEdit" value="'.$idSucursal.'">                    
                        <div style="height: 25rem;overflow-y:scroll;">
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre equipo</label>
                                    <input name="nombreEquipo" id="nombreEquipo" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Nombre equipo" value="'.$nombre.'" required/>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-2 mx-7">

                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Serie</label>
                                    <input name="serie" id="serie" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Serie" value="'.$serie.'" required/>                                    
                                </div>
                        
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Marca</label>
                                    <input type="text" name="marca" id="marca" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Marca" value="'.$marca.'" required/>
                                </div>
                                
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">IP Equipo</label>
                                    <input type="text" name="ip" id="ip" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="IP Equipo" value="'.$ip.'" required/>
                                </div>                                                                           
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Sist. operativo</label>
                                    <input name="sistemaop" id="sistemaop" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Sist. operativo" value="'.$sistemaop.'" required/>                                    
                                </div>
                        
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Antivirus</label>
                                    <input type="text" name="antivirus" id="antivirus" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Antivirus" value="'.$antivirus.'" required/>
                                </div>
                                
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Version office</label>
                                    <input type="text" name="versionoffice" id="versionoffice" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Version office" value="'.$versionoffice.'" required/>
                                </div>                                    
                            </div>   
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Descripción</label>
                                    <textarea name="descripcionEquipo" id="descripcionEquipo" class="py-2 px-2 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Descripción" />'.$descripcion.'</textarea>         
                                </div>
                            </div>';              

                $html .= $this->apartadoFicheroEquipoEditar($id);                            

                $html .= '
                            <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-2 mx-7">
                                <div class="grid grid-cols-1">
                                    <table id="tablaUsuariosEquipos">
                                    '.$usuariosAsignados.'
                                    </table>
                                </div>
                            </div>';
                           
                        $html .='</div>';
                        $html .='                                        
                            <div class="flex items-center justify-center px-6 pt-3 border-t border-solid border-blueGray-200 rounded-b">
                                <a class="cerrarModalEditEquipo w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-white px-4 py-2 mr-3">Cerrar</a>
                                <button id="'.$idBtnSubmit.'" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl font-medium text-white px-4 py-2">'.$btnSubmit.'</button>
                            </div>
                    </form>';       
            
        }
                     return $html;
        
    }

    public function apartadoFicheroEquipoEditar($idEquipo)
    {
        $html = '';
        $imagenes = $this->ModelClientes->obtenerImagenesEquipo($idEquipo);                
                      
            $html .= '<div class="grid grid-cols-1 mt-2 mx-7 " style="height: fit-content;">
            <div class="inline-flex mb-2" >
                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Imágenes</label>
                                
                <a id="addFileEquipo" class="w-auto bg-gray-400 hover:bg-gray-500 rounded-lg shadow-xl text-sm lg:text-sm xl:text-base text-white px-2 ml-3 flex items-center justify-center"><i class="far fa-image mr-2 text-xl"></i>Agregar</a>
            </div>

            <div id="formularioSubirFicheroEquipo" style="display:none;" class="grid grid-cols-1 mt-4">
                <p>Tamaño máximo de cada archivo = 2MB</p>
                <span id="msgValidaFichero" class="text-xs lg:text-sm xl:text-base font-bold text-pink-600 mx-4"></span>
                <input type="file" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-pink-700 focus:border-transparent inputFichero" name="ficheroCrearEquipo[]" id="ficheroCrearEquipo" multiple="" placeholder="Adjunte fichero">
            </div> 

            

            <div id="imagenesFicheroEdit">';                                              
                                 
        if($imagenes && count($imagenes) > 0){

                foreach ($imagenes as $img) {                                                        
                    $html .='<p id="imagen_equipo_'.$img->id.'">
                    <a class="verImagen texto-violeta-oscuro text-sm xl:text-base" data-idfichero="'.$img->id.'" style="cursor:pointer;">
                        <span class="font-semibold">'.$img->nombre.'</span>                        
                    </a><i class="fas fa-trash-alt fill-current text-red-600 ml-2 delete_file_equipo cursor-pointer" data-idimagen="'.$img->id.'"></i></p>';
                }    
                
                            
        }
        $html .= '</div></div>';
          
        return $html;
    }    

    public function eliminarImagenEquipo()
    {
        $retorno = ['error'=>true];
        
        if (isset($_POST['idimagen']) && $_POST['idimagen'] !='') {
            
            $datosImagen = $this->ModelClientes->obtenerDatosImagenEquipo($_POST['idimagen']);

            $nombreArchivo = $datosImagen->nombre;

            $rutaArchivo = DOCS_EQUIPOS . $nombreArchivo;
            
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            
            $delRow = $this->ModelClientes->eliminarFilaImagenEquipo($_POST['idimagen']);
            if($delRow){
                $retorno['error'] = false;    
            }
        }
        print json_encode($retorno);
    }

    public function construyoTablausuariosYSupervisoresAsignados($idEquipo)
    {                
        $usuarios ='<tr>                            
                        <td width="20%" class="font-bold">Nombre</td>
                        <td width="20%" class="font-bold">Apellidos</td>                        
                        <td width="40%" class="font-bold">Email</td>
                        <td width="15%" class="font-bold">Tipo</td>
                        
                    </tr>';  

        if (isset($idEquipo) && $idEquipo>0) {
        
            $usuariosAsign = $this->ModelClientes->obtenerUsuariosAsignadosAEquipoPorIdEquipo($idEquipo);
            
            if(isset($usuariosAsign) && count($usuariosAsign) >0){          
                
                foreach ($usuariosAsign as $usuario) {
                    $usuarios .= '<tr>              
                            <td width="20%">'.$usuario->nombre.'</td>
                            <td width="20%">'.$usuario->apellidos.'</td>
                            <td width="40%">'.$usuario->correo.'</td>
                            <td width="15%">'.$usuario->clientetipo.'</td>
                            
                        </tr>';
                }       
            }
        }           

        return $usuarios;
                    
    }

    public function verEquipoModosPago()
    {
        $retorno = [
            'respuesta' => 0,
            'tabla' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] >0) {

            $tablaEquiposModos = $this->contruirTablaEquiposModalidades($_POST['id']);
            
            $retorno = [
                'respuesta' => 1,
                'tabla' => $tablaEquiposModos
            ];

        }
        print json_encode($retorno);

    }

    public function contruirTablaEquiposModalidades($id) 
    {      
                 
        if(EMPRESA==='INFOMALAGA'){
            $botones = '["historial","modificar"]';
        }else{
            $botones = '["modificar"]';
        }
        
        $html = '
        <div class="my-2 flex sm:flex-row flex-col">
            <div class="flex flex-row mb-1 sm:mb-0">
                <div class="relative flex" id="buscadorModosPago">                            
                </div>                
            </div>            
        </div>
        <span id="msgModalidad" class="font-bold font-bold text-pink-600"></span>
        <div class="overflow-x-auto">
            <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                <div id="destinoequiposmodospagoajax"></div>
            </div>
        </div>                
        <div id="paginadorModosPago"></div>
        <script  type="module">
            import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClass.js" 
            arrancar("tablamodpago","Clientes/crearTablaEquiposModalidades", "destinoequiposmodospagoajax", "Nº", "DESC", 0, "buscadorModosPago","Clientes/totalRegistrosEquiposModalidades", [10,20,30],"min-w-full leading-normal","paginadorModosPago",'.$botones.',"'.RUTA_URL.'/Clientes/historialEquiposModalidades","'.$id.'");
        </script>
        ';

        return $html;

    }

    public function crearTablaEquiposModalidades() 
    {           

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];
            $id = $_POST['id'];
        }
        
        $filaspagina = $filas * $pagina;
        
        $cond = " AND eq.idcliente = '$id' ";

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
                        $cond .= "eq.id" . $y;
                    }
                    if ($key == 'Nombre equipo') {
                        $cond .= "eq.nombre" . $y;
                    }                                        
                    if ($key == 'Coste actual') {
                        $cond .= "eq.valor" . $y;
                    }
                    if ($key == 'Sucursal') {
                        $cond .= "suc.nombre" . $y;
                    }                                                                   
                }                                    
    
            }
            
        }
        $clientes = $this->ModelClientes->obtenerEquiposTablaClass($filas,$orden,$tipoOrden,$filaspagina,$cond);
              
        print(json_encode($clientes));  
    }

    public function totalRegistrosEquiposModalidades() //falta terminar
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $id = $_POST['id'];
        }            
        
        $cond = " AND eq.idcliente = '$id' ";

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
                        $cond .= "eq.id" . $y;
                    }
                    if ($key == 'Nombre equipo') {
                        $cond .= "eq.nombre" . $y;
                    }                                        
                    if ($key == 'Coste actual') {
                        $cond .= "eq.valor" . $y;
                    }
                    if ($key == 'Sucursal') {
                        $cond .= "suc.nombre" . $y;
                    }                                                                   
                }                                    
    
            }
            
        }/*else{*/
            $contador = $this->ModelClientes->totalEquiposClientes($cond);
        //}

        $cont = $contador->contador;        
        print_r($cont);
    }

    public function verEditorParaModificarModalidad()
    {
        $retorno = [
            'respuesta' => 0,
            'html' => '',
            'empresa' => '',
        ];
        if ($_POST['id'] && $_POST['id'] >0) {
            if(EMPRESA==='INFOMALAGA'){
                $retorno= $this->contruirContenidoModalMantenimientoEquipos($_POST['id']);
            }else{
                $retorno= $this->contruirContenidoModalMantenimientoEquiposTelesat($_POST['id']);
            }                               
        }
        print json_encode($retorno);
        
    }

    private function contruirContenidoModalMantenimientoEquipos($id)
    {         
            $equipo = $this->ModelClientes->modalidadDePagoPorEquipos($id);

            $html = '';
        
            $modalidades = ["fijo"=>"euros equipo/mes"];
            
            $meses = ["Ene"=>1,"Feb"=>2,"Mar"=>3,"Abr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Ago"=>8,"Set"=>9,"Oct"=>10,"Nov"=>11,"Dic"=>12];

            $html .= '
            <div class="grid grid-cols-2">

                <div class="flex flex-col grid grid-cols-1 mr-2">
                
                    <label for="modalidadActual" class="text-sm font-semibold text-gray-500">Modalidad</label>';
                    
                $html .= '
                    <select id="modalidadActual" name="modalidadActual" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required>';
                        foreach ($modalidades as $key => $value) {
                            $html .='<option value="'.$key.'" '.(($equipo->modalidad && $equipo->modalidad==$key)? 'selected' : '').' >'.$value.'</option>';
                        }
                $html .='
                    </select>
                </div>
                <div class="flex flex-col grid grid-cols-1 ml-2">
                    <div class="flex items-center justify-between">
                        <label for="contratado" class="text-sm font-semibold text-gray-500">Precio contratado(€)</label>              
                    </div> 
                    <input type="text" id="contratado" name="contratado"
                    class="px-4 py-2 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required />
                </div>
        
                <div class="flex flex-col grid grid-cols-1 mr-2">
                    <div class="flex items-center justify-between">
                        <label for="mesInicio" class="text-sm font-semibold text-gray-500">Desde</label>              
                    </div>';
                    $html .= '
                    <select id="mesInicio" name="mesInicio" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                        <option disabled selected>Seleccionar</option>';
                        foreach ($meses as $mes => $or) {
                            $html .='<option value="'.$or.'">'.$mes.'</option>';
                        }
                        $html .='
                        </select>                        
                </div>

                <div class="flex flex-col grid grid-cols-1 ml-2">
                    <div class="flex items-center justify-between">
                        <label for="anioInicio" class="text-sm font-semibold text-gray-500">&nbsp;</label>              
                    </div>';
                    $html .= '
                    <select id="anioInicio" name="anioInicio" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                        <option disabled selected>Seleccionar</option>';
                      
                        $aniosInc = $this->ModelClientes->aniosConIncidencias();
                           
                        if (isset($aniosInc) && count($aniosInc)>0) {
                            $anioAnterior = ($aniosInc[0]->anio)-1;
                            $ultimo = count($aniosInc) - 1;
                            $anioPosterior = ($aniosInc[$ultimo]->anio)+1;

                            $html .='<option value="'.$anioAnterior.'" >'.$anioAnterior.'</option>';                               
                            foreach ($aniosInc as $key) {
                              $html .='<option value="'.$key->anio.'" '.((date('Y')==$key->anio)? 'selected' : '').' >'.$key->anio.'</option>';
                            }
                            $html .='<option value="'.$anioPosterior.'" >'.$anioPosterior.'</option>';
                            
                          }else{
                            $anios = [date('Y')-1,date('Y'),date('Y')+1];
                            foreach ($anios as $anio) {
                                $html .='<option value="'.$anio.'" '.((date('Y')==$anio)? 'selected' : '').'>'.$anio.'</option>';
                            } 
                          }   


                        $html .='
                        </select>     
                </div>


                <div class="flex flex-col grid grid-cols-1 mr-2">
                    <div class="flex items-center justify-between">
                        <label for="mesFin" class="text-sm font-semibold text-gray-500">Hasta</label>              
                    </div>';
                    $html .= '
                    <select id="mesFin" name="mesFin" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                        <option disabled selected>Seleccionar</option>';
                        foreach ($meses as $mes => $or) {
                            $html .='<option value="'.$or.'">'.$mes.'</option>';
                        }
                        $html .='
                        </select>                        
                </div>

                <div class="flex flex-col grid grid-cols-1 ml-2">
                    <div class="flex items-center justify-between">
                        <label for="anioFin" class="text-sm font-semibold text-gray-500">&nbsp;</label>              
                    </div>';
                    $html .= '
                    <select id="anioFin" name="anioFin" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                        <option disabled selected>Seleccionar</option>';
                      
                        $aniosInc = $this->ModelClientes->aniosConIncidencias();
                           
                        if (isset($aniosInc) && count($aniosInc)>0) {
                            $anioAnterior = ($aniosInc[0]->anio)-1;
                            $ultimo = count($aniosInc) - 1;
                            $anioPosterior = ($aniosInc[$ultimo]->anio)+1;

                            $html .= '<option value="'.$anioAnterior.'" >'.$anioAnterior.'</option>';                               
                            foreach ($aniosInc as $key) {
                              $html .= '<option value="'.$key->anio.'" '.((date('Y')==$key->anio)? 'selected' : '').' >'.$key->anio.'</option>';
                            }
                            $html .= '<option value="'.$anioPosterior.'" >'.$anioPosterior.'</option>';
                            
                          }else{
                            $anios = [date('Y')-1,date('Y'),date('Y')+1];
                            foreach ($anios as $anio) {
                                $html .= '<option value="'.$anio.'" '.((date('Y')==$anio)? 'selected' : '').'>'.$anio.'</option>';
                            } 
                          }   


                        $html .='
                        </select>     
                </div>

            </div>
            ';

            $retorno = [
                'respuesta' => 1,
                'html' => $html,
                'empresa' => 'INFOMALAGA'
            ];

            return $retorno;

    }

    /////nuevos

    public function actualizarComentarioEquipoMntto()
    {
        
        $retorno = true;
        if ($_POST['idEquipo']>0 && isset($_POST['comentarios'])) {               
            $retorno = $this->ModelClientes->actualizarComentariosEquipo($_POST['idEquipo'], $_POST['comentarios']);
        }                
        print json_encode($retorno);
    }

    private function contruirContenidoModalMantenimientoEquiposTelesat($idEquipo)
    {
        $modalidades = $this->ModelClientes->modalidadesMantenimientoEquipos();
        $comentario = $this->ModelClientes->comentarioContratoMantenimientoEquipo($idEquipo);
        $html = '';
        if($_SESSION['nombrerol']=='admin'){
        $html .= '
        <div class="grid grid-cols-2">

            <div class="flex flex-col grid grid-cols-1 mr-2">            
                <label for="modalidadMntto" class="text-sm font-semibold text-gray-500">Modalidad</label>';
                
            $html .= '
                <select id="modalidadMntto" name="modalidadMntto" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required>';
                    foreach ($modalidades as $modalidad) {
                        $html .='<option value="'.$modalidad->id.'" >'.$modalidad->modalidad.'</option>';
                    }
            $html .='
                </select>
            </div>
            <div class="flex flex-col grid grid-cols-1 ml-2">
                <div class="flex items-center justify-between">
                    <label for="contratadoMntto" class="text-sm font-semibold text-gray-500">Precio contratado(€)</label>              
                </div> 
                <input type="text" id="contratadoMntto" name="contratadoMntto"
                class="px-4 py-2 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required />
            </div>    
            <div class="flex flex-col grid grid-cols-1 mr-2">
                <div class="flex items-center justify-between">
                    <label for="fechaInicio" class="text-sm font-semibold text-gray-500">Fecha inicio</label>              
                </div>';
                $html .= '
                <input type="date" id="fechaInicio" name="fechaInicio" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
            </div>
        </div>';
                }

                $html .= '
        <div class="grid grid-cols-1">        
            <div class="flex flex-col grid grid-cols-1 mr-2">
                <div class="flex items-center justify-between">
                    <label for="comentarios" class="text-sm font-semibold text-gray-500">Comentarios</label>              
                </div>
                <textarea id="comentarios" name="comentarios" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >'.$comentario->comentarios.'</textarea>';
                
                if($_SESSION['nombrerol']=='admin'){
                $html .= '  
                <div class="flex items-center justify-start mt-1">
                    <a class="w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl text-sm xl:text-base text-white px-4 py-1 mr-2 cursor-pointer" id="guardarComentariosEquipo">Guardar comentarios</a> 
                    <a id="modifModalidadMntto" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl text-sm xl:text-base text-white px-4 py-1">Guardar todo</a>
                </div>';
                }
                
                $html .= '                     
            </div>
        </div>
        ';

        $html .= $this->construirTablaHistorialModalidades($idEquipo);

        $retorno = [
            'respuesta' => 1,
            'html' => $html,
            'empresa' => 'TELESAT'
        ];

        return $retorno;
    }

    public function eliminarMantenimientoEquipo()
    {
        $retorno= false;
        if(isset($_POST['idMod']) && $_POST['idMod'] > 0){
            $retorno = $this->ModelClientes->eliminarContratoMnttoEquipo($_POST['idMod']);
        }
        print json_encode($retorno);
    }

    public function construirTablaHistorialModalidades($idEquipo)
    {

        $html = '<div class="flex flex-col space-y-5" id="bodyHistorialModalidad" style="height: 12rem;overflow-y:scroll;" >
        <table id="tablaHistorialModalidadMntto" class="rounded-t-lg rounded-b-lg m-5 w-full mx-auto bg-gradient-to-l text-xs md:text-sm">
          <thead>
            <tr class="text-center border-b-2 border-violeta-oscuri text-white bg-violeta-oscuro">
                <th class="p-1 text-center">F.Inicio</th>                
                <th class="p-1 text-center">Modalidad</th>
                <th class="p-1 text-center">Precio</th>
                <th class="p-1"></th>
            </tr>
          </thead>
          <tbody>';
          $html.= $this->construirTablaHistorialContratos($idEquipo); 
          $html.= '</tbody>
        </table>
                
      </div>';

        return $html;

    }

    private function construirTablaHistorialContratos($idEquipo)
    {
        $datos = $this->ModelClientes->obtenerHistoriaContratosMnttoEquipos($idEquipo);

        $html = '';

        if(isset($datos) && count($datos) > 0){
            foreach ($datos as $dato) {
                
                $fecha = new DateTime($dato->fechainicio);                
                $fechaFormateada = $fecha->format('d/m/Y');

                $html .= "<tr class='text-center text-violeta-oscuro border-b-2 border-violeta-oscuro' id='fila_mod_".$dato->id."'>
                    <td class='p-1 text-center'>".$fechaFormateada."</td>
                    <td class='p-1 text-center'>".$dato->nommodalidad."</td>
                    <td class='p-1 text-center'>".number_format($dato->contratado,2,',','.')."</td>
                    <td class='p-1'>";
                
                    if($_SESSION['nombrerol']=='admin'){
                        $html .= "
                        <div class='flex'><i class='fas fa-trash-alt eliminar-mod-mntto' data-mod='".$dato->id."' title='Eliminar' mr-1 fill-current text-red-600 text-sm lg:text-xl'></i></a></div>";
                    }
                    
                    $html .="
                    </td>
                </tr>";
            }            
        }
        return $html;
    }
    
    private function procesarDatosModalidadesMantenimiento($post)
    {
        $retorno = ['error'=>true, 'tablabody'=>''];
        $historial = '';

        if ($post['idEquipo']>0 && $post['modalidad']!='' && $post['contratado']!='' && $post['contratado']>0  && $post['fechaInicio']>0 && $post['idCliente']>0) {                  
            $ins = $this->ModelClientes->crearModalidadMntto($post);

            if($ins){
                $this->ModelClientes->actualizarComentariosEquipo($post['idEquipo'], $post['comentarios']);
                $error = false;
            }
            $historial = $this->construirTablaHistorialContratos($post['idEquipo']);       
        }
        
        $retorno = [
            'error'=>$error, 
            'tablabody'=> $historial
        ];
        return $retorno;
    }

   //modificar
    public function actualizarModalidadDePago()
    {                    
        $retorno = 0;

        if(isset($_POST['empresa']) && $_POST['empresa']== 'telesat'){
            $retorno = $this->procesarDatosModalidadesMantenimiento($_POST);
        }else{
                        
            if ($_POST['idEquipo']>0 && $_POST['modalidad']!='' && $_POST['contratado']!='' && $_POST['contratado']>0  && $_POST['mesInicio']>0 && $_POST['anioInicio']>0 && $_POST['mesFin']>0 && $_POST['anioFin']>0 && $_POST['idCliente']>0) {       

                $idEquipo = $_POST['idEquipo'];
                $modalidad = $_POST['modalidad'];
                $contratado = $_POST['contratado'];            
                $idCliente = $_POST['idCliente'];
                        
                $mesInicio = $_POST['mesInicio'];
                $anioInicio = $_POST['anioInicio'];
                $mesFin = $_POST['mesFin'];
                $anioFin = $_POST['anioFin'];

                //años iguales 
                if ($anioInicio == $anioFin) {
                    
                    for ($i=$mesInicio; $i <= $mesFin ; $i++) {                   
                
                        date_default_timezone_set("Europe/Madrid");

                        $datos = [              
                            "idEquipo" => $idEquipo,
                            "idCliente" => $idCliente,
                            "modalidad" => $modalidad,
                            "contratado" => $contratado,
                            "mes" => $i,
                            "anio" => $anioInicio,
                            "creacion" => date('Y-m-d')
                        ];          
                        $this->ModelClientes->borraPreciosAsignadoEquipo($datos);                    
                        $this->ModelClientes->insertarModalidadpagoEquipo($datos);                                        
                    }       
                    $this->ModelClientes->actualizarModalidadPagoDefault($_POST);  
                    $retorno = 1;     
                }else if ($anioFin > $anioInicio){ //años diferentes

                    for ($i=$mesInicio; $i <= 12 ; $i++) {                   

                        date_default_timezone_set("Europe/Madrid");

                        $datos = [              
                            "idEquipo" => $idEquipo,
                            "idCliente" => $idCliente,
                            "modalidad" => $modalidad,
                            "contratado" => $contratado,
                            "mes" => $i,
                            "anio" => $anioInicio,
                            "creacion" => date('Y-m-d')
                        ];          
                        $this->ModelClientes->borraPreciosAsignadoEquipo($datos);  
                        $this->ModelClientes->insertarModalidadpagoEquipo($datos);
                    }

                    for ($j=1; $j <= $mesFin ; $j++) {                   

                        date_default_timezone_set("Europe/Madrid");

                        $datos = [              
                            "idEquipo" => $idEquipo,
                            "idCliente" => $idCliente,
                            "modalidad" => $modalidad,
                            "contratado" => $contratado,
                            "mes" => $j,
                            "anio" => $anioFin,
                            "creacion" => date('Y-m-d')
                        ];          
                        $this->ModelClientes->borraPreciosAsignadoEquipo($datos);  
                        $this->ModelClientes->insertarModalidadpagoEquipo($datos);
                    }
                    $this->ModelClientes->actualizarModalidadPagoDefault($_POST);
                    $retorno = 1;
                }               
            }
        }

        print json_encode($retorno);
    }

    public function historialModalidadDepago()
    {
        $retorno = [
            'respuesta' => 0,
            'html' => '',
        ];

        if ($_POST['id'] && $_POST['id'] >0) {
            $html = '';
            $historial = $this->ModelClientes->obtenerHistorialModalidadPorEquipo($_POST['id']);

            if (isset($historial) && count($historial) >0) {
                foreach ($historial as $key) {
                    
                    $meses = ["Ene"=>"1","Feb"=>"2","Mar"=>"3","Abr"=>"4","May"=>"5","Jun"=>"6","Jul"=>"7","Ago"=>"8","Set"=>"9","Oct"=>"10","Nov"=>"11","Dic"=>"12"];
                    $mes = array_search($key->mes, $meses);

                    $html .= "<tr class='text-center text-violeta-oscuro border-b-2 border-violeta-oscuro'>
                                <td class='p-1 text-center'>".$mes."</td>
                                <td class='p-1 text-center'>".$key->anio."</td>
                                <td class='p-1 text-center'>".$key->modalidad."</td>
                                <td class='p-1'>".$key->valor."</td>
                            </tr>";
                }
                $retorno = [
                    'respuesta' => 1,
                    'html' => $html
                ];
            }
        }
        print json_encode($retorno);
    }

    public function actualizarEquipo()
    {           
        $retorno['error'] = true;
        $retorno['mensaje'] = 'Ha ocurrido un error y no se ha podido actualizar el equipo.';   
        $permisosGuardar = $this->verificarPermisosTecnicos();
        
        if($permisosGuardar[0]===true){


            if (isset($_POST['form']) && $_POST['idEquipo'] >0 && $_POST['nombreEquipo'] !='' ) {   
            
                $datos = [];            
                $usuarios = [];            
                $nombres = [];
                $puestos = [];            
                foreach ($_POST['form'] as $row) {                
                    if ($row['name'] == 'puestoUsuario' ) {
                        $puestos[] = $row['value'];                    
                    }
                    
                    $datos[$row['name']] = $row['value'];
                                    
                }
                
                if (count($nombres) >0) {
                    
                    for ($i=0; $i < count($nombres) ; $i++) {                               
                            $nombre = $nombres[$i];
                            $puesto = $puestos[$i];                        
    
                            $tmp = [                            
                                'nombre' => $nombre,
                                'puesto' => $puesto,                            
                            ];
                            $usuarios[] = $tmp;
                    }               
                }
                                       
                $datos['usuarios'] = $usuarios;  
                $datos['idEquipo'] = $_POST['idEquipo'];  
                $datos['nombreEquipo'] = $_POST['nombreEquipo'];  
                
                
                $upd = $this->ModelClientes->actualizarDatosEquipo($datos);
                if ($upd) {
                    $retorno['error'] = false;
                    $retorno['mensaje'] = '';   
                }
    
            }                    

        }else{
            $retorno['error'] = true;
            $retorno['mensaje'] = 'No tiene permiso para modificar datos de equipos.';
        }

        print json_encode($retorno);      
    }

    public function eliminarEquipo()
    {
        //$retorno = 0;
        $retorno['error'] = 1;
        $retorno['mensaje'] = 'Ha ocurrido un error y no se ha podido eliminar el equipo.';   
        $permisosGuardar = $this->verificarPermisosTecnicos();
        
        if($permisosGuardar[0]===true){

            if(isset($_POST['id']) && $_POST['id'] >0){
                
                $tieneIncidencias = $this->ModelClientes->validarSiEquipoTieneIncidenciasRegistradas($_POST['id']);
                if ($tieneIncidencias == 0) {
                    
                    $del = $this->ModelClientes->eliminarEquipoDefinitivamente($_POST['id']);                

                    if ($del) {
                        $this->ModelClientes->eliminarEquipoAsignadoAUsuarios($_POST['id']);
                        $this->ModelClientes->eliminarEquipoDeTablaMantenimientoEquipos($_POST['id']);
                        //$retorno = 1;
                        $retorno['error'] = 0;
                        $retorno['mensaje'] = '';
                    }

                }else{
                    $retorno['error'] = 2;
                    $retorno['mensaje'] = 'Existen solicitudes registradas para ese equipo. No se ha podido eliminar el equipo.';
                }

            }

        
        }else{
            $retorno['error'] = 3;
            $retorno['mensaje'] = 'No tiene permiso para modificar datos de equipos.';
        }

        print json_encode($retorno);      
    }

    public function verBolsaHorasCliente()
    {
        $retorno = [
            'respuesta' => 0,
            'tabla' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] >0) {
            $tablaBolsaHoras = $this->construirHistorialBolsaHorasCliente($_POST['id']);            
            $retorno = [
                'respuesta' => 1,
                'tabla' => $tablaBolsaHoras
            ];
        }
        print json_encode($retorno);        
    }

    public function construirHistorialBolsaHorasCliente($id)
    {        
        $html = '
            <div class="inline-flex my-3 mr-7">
                    <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Configurar mes</label>
                    <a href="#" id="addNuevoMes" class="rounded-full h-6 w-6 flex items-center justify-center  bg-blue-700 text-white flex-1" title="Nueva mes"><i class="fas fa-plus-circle"></i></a>                        
            </div>
            <div class="my-2 flex sm:flex-row flex-col">
                <div class="flex flex-row mb-1 sm:mb-0">
                    <div class="relative flex" id="buscadorHoras">                            
                    </div>                
                </div>            
            </div>
            <span id="msgModalHoras" class="font-bold font-bold text-pink-600"></span>
            <div class="overflow-x-auto">

                <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                    <div id="destinomodalidadhorasajax"></div>
                </div>
            </div>                
            <div id="paginadorModoHoras"></div>
            <script type="module">
                import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClass.js" 
                arrancar("tablamodhoras","Clientes/crearTablaModalidadHoras", "destinomodalidadhorasajax", "moda.anio", "DESC", 0, "buscadorHoras","Clientes/totalRegistrosModalidadHoras", [10,20,30],"min-w-full leading-normal","paginadorModoHoras",["modificarBolsa","eliminar"],"'.RUTA_URL.'/Clientes/historialModalidadHoras","'.$id.'");
            </script>
        ';
        return $html;
    }

    
    public function crearTablaModalidadHoras() 
    {           

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];
            $id = $_POST['id'];
        }
                
        $filaspagina = $filas * $pagina;
                
        $cond = " AND moda.idcliente = '$id' ";

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
                        $cond .= "moda.id" . $y;
                    }
                    if ($key == 'Contratado') {
                        $cond .= "moda.valor" . $y;
                    }                    
                    if ($key == 'Mes') {

                        $meses = ["enero"=>" moda.mes = 1 ","febrero"=>" moda.mes = 2 ","marzo"=>" moda.mes = 3 ","abril"=>" moda.mes = 4 ","mayo"=>" moda.mes = 5 ","junio"=>" moda.mes = 6 ","julio"=>" moda.mes = 7 ","agosto"=>" moda.mes = 8 ","setiembre"=>" moda.mes = 9 ","octubre"=>" moda.mes = 10 ","noviembre"=>" moda.mes = 11 ","diciembre"=>" moda.mes = 12 "];
                                                    
                        $condEstado = ' ';
                        $numEstados = 0;
                        $arrEstados = [];
    
                        foreach ($meses as $mes => $parte) {                       
    
                            $pos = stripos($mes, $value);
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
                    if ($key == 'Creación') {
                        
                        $fechaEstandar = " DATE_FORMAT( moda.creacion, '%d/%m/%Y' ) LIKE '%".$value."%' ";
                        
                        if ($cont < ($tamanio) ) {                    
                            $m =  " AND ";
                        } else {                    
                            $m =  " ) ";
                        }
    
                        $cond .= $fechaEstandar . $m;
                    }                    
                    if ($key == 'Año') {
                        $cond .= " moda.anio" . $y;
                    }
                                                
                   
                }                                    
    
            }
            
        }

            $historial = $this->ModelClientes->obtenerBolsasHorasTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);
          
        print(json_encode($historial));  
    }

    public function totalRegistrosModalidadHoras() //falta terminar
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $id = $_POST['id'];
        }        
                 
        $cond = " AND moda.idcliente = '$id' ";
    
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
                        $cond .= "moda.id" . $y;
                    }
                    if ($key == 'Contratado') {
                        $cond .= "moda.valor" . $y;
                    }                    
                    if ($key == 'Mes') {

                        $meses = ["enero"=>" moda.mes = 1 ","febrero"=>" moda.mes = 2 ","marzo"=>" moda.mes = 3 ","abril"=>" moda.mes = 4 ","mayo"=>" moda.mes = 5 ","junio"=>" moda.mes = 6 ","julio"=>" moda.mes = 7 ","agosto"=>" moda.mes = 8 ","setiembre"=>" moda.mes = 9 ","octubre"=>" moda.mes = 10 ","noviembre"=>" moda.mes = 11 ","diciembre"=>" moda.mes = 12 "];
                                                    
                        $condEstado = ' ';
                        $numEstados = 0;
                        $arrEstados = [];
    
                        foreach ($meses as $estado => $parte) {                       
    
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
                    if ($key == 'Creación') {
                        
                        $fechaEstandar = " DATE_FORMAT( moda.creacion, '%d/%m/%Y' ) LIKE '%".$value."%' ";
                        
                        if ($cont < ($tamanio) ) {                    
                            $m =  " AND ";
                        } else {                    
                            $m =  " ) ";
                        }
    
                        $cond .= $fechaEstandar . $m;
                    }                    
                    if ($key == 'Año') {
                        $cond .= " moda.anio" . $y;
                    }
                                                
                   
                }                                    
    
            }            

        }/*else{*/
            $contador = $this->ModelClientes->totalRegistrosModalidadHorasBuscar($cond);
        //}

        $cont = $contador->contador;        
        print_r($cont);
    }

    public function verEditorParaModificarBolsaHoras()
    {
        $retorno = [
            'respuesta' => 0,
            'html' => '',
        ];
        if ($_POST['id'] && $_POST['id'] >0) {

            $equipo = $this->ModelClientes->obtenerDetalleBolsaHorasMes($_POST['id']);
            
            $html = '';
            
            $modalidades = ["horas"=>"horas/mes"];
            
            $meses = ["Ene"=>"1","Feb"=>"2","Mar"=>"3","Abr"=>"4","May"=>"5","Jun"=>"6","Jul"=>"7","Ago"=>"8","Set"=>"9","Oct"=>"10","Nov"=>"11","Dic"=>"12"];
            $anios = [date('Y')-1,date('Y'),date('Y')+1];

                $html .= '
                <div class="grid grid-cols-1 md:grid-cols-3">
                    <div class="flex flex-col grid grid-cols-1 mr-2">
                    
                        <label for="modalidadHoras" class="text-sm font-semibold text-gray-500">Modalidad horas/mes</label>';
                        
                    $html .= '
                        <select id="modalidadHoras" name="modalidadHoras" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required>';
                            foreach ($modalidades as $key => $value) {
                                $html .='<option value="'.$key.'" '.(($equipo->modalidad && $equipo->modalidad==$key)? 'selected' : '').' >'.$value.'</option>';
                            }
                    $html .='
                        </select>
                    </div>
                    <div class="flex flex-col grid grid-cols-1 ml-2">
                        <div class="flex items-center justify-between">
                            <label for="contratadoHoras" class="text-sm font-semibold text-gray-500">Horas contratadas</label>              
                        </div> 
                        <input type="text" id="contratadoHoras" name="contratadoHoras"
                        class="px-4 py-2 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" value="'.$equipo->valor.'" required />
                    </div>
                    <div class="flex flex-col grid grid-cols-1 ml-2">
                        <div class="flex items-center justify-between">
                            <label for="contratadoEuros" class="text-sm font-semibold text-gray-500">Precio bolsa(€)</label>              
                        </div> 
                        <input type="text" id="contratadoEuros" name="contratadoEuros"
                        class="px-4 py-2 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" value="'.$equipo->preciototal.'" required />
                    </div>
                </div>
                <div class="grid grid-cols-2">';
                        $html .= '
                        <select style="display: none;" id="mesBolsaHoras" name="mesBolsaHoras" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                            <option disabled>Seleccionar</option>';
                            foreach ($meses as $mes => $or) {
                                $html .='<option value="'.$or.'" '.(($equipo->mes && $equipo->mes==$or)? 'selected' : '').' >'.$mes.'</option>';
                            }
                            $html .='
                            </select>';
                        $html .= '
                        <select style="display: none;" id="anioBolsaHoras" name="anioBolsaHoras" class="py-2 px-3 transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                            <option disabled selected>Seleccionar</option>';
                            foreach ($anios as $anio) {
                                $html .='<option value="'.$anio.'" '.(($anio==$equipo->anio)? 'selected' : '').'>'.$anio.'</option>';
                            }
                            $html .='
                            </select>                    
                </div>
                ';

                $retorno = [
                    'respuesta' => 1,
                    'html' => $html
                ];
            //}
        }
        print json_encode($retorno);
    }

    public function crearBolsaHoras()
    {     
        $retorno = [
            'respuesta' => 0,
            'tabla' => ''
        ];        

        if ($_POST['idCliente'] && $_POST['modalidad']!='' && $_POST['contratado']!='' && $_POST['contratado']>0  && $_POST['mesInicio']>0 && $_POST['anioInicio']>0 && $_POST['mesFin']>0 && $_POST['mesFin']>0 &&  $_POST['contratadoPrecio']!='' && $_POST['contratadoPrecio']>0) {

            $precioHora = round(($_POST['contratadoPrecio']/$_POST['contratado']),2);

            //editando
            $mesInicio = $_POST['mesInicio'];
            $anioInicio = $_POST['anioInicio'];
            $mesFin = $_POST['mesFin'];
            $anioFin = $_POST['anioFin'];

            $modalidad = $_POST['modalidad'];
            $contratado = $_POST['contratado'];
            $contratadoPrecio = $_POST['contratadoPrecio'];
            $idCliente = $_POST['idCliente'];            

            //años iguales 
            if ($anioInicio == $anioFin) {
                
                for ($i=$mesInicio; $i <= $mesFin ; $i++) {                             

                    $datos = [                        
                        "idCliente" => $idCliente,                        
                        "modalidad" => $modalidad,
                        "contratado" => $contratado,
                        "contratadoPrecio" => $contratadoPrecio,
                        "mes" => $i,
                        "anio" => $anioInicio                        
                    ];          
                    $this->ModelClientes->borrarBolsaHorasMes($datos);
                    $this->ModelClientes->insertarBolsaHorasNueva($datos, $precioHora);                                        
                }       
                  
                $retorno = [
                    'respuesta' => 1,
                    'tabla' => $this->construirHistorialBolsaHorasCliente($_POST['idCliente'])
                ];    
            }else if ($anioFin > $anioInicio){ //años diferentes

                for ($i=$mesInicio; $i <= 12 ; $i++) {                   

                    date_default_timezone_set("Europe/Madrid");

                    $datos = [              
                        "idCliente" => $idCliente,                        
                        "modalidad" => $modalidad,
                        "contratado" => $contratado,
                        "contratadoPrecio" => $contratadoPrecio,
                        "mes" => $i,
                        "anio" => $anioInicio                        
                    ];          
                    $this->ModelClientes->borrarBolsaHorasMes($datos);  
                    $this->ModelClientes->insertarBolsaHorasNueva($datos, $precioHora);
                }

                for ($j=1; $j <= $mesFin ; $j++) {                   

                    date_default_timezone_set("Europe/Madrid");

                    $datos = [              
                        "idCliente" => $idCliente,                        
                        "modalidad" => $modalidad,
                        "contratado" => $contratado,
                        "contratadoPrecio" => $contratadoPrecio,
                        "mes" => $j,
                        "anio" => $anioFin                        
                    ];          
                    $this->ModelClientes->borrarBolsaHorasMes($datos);  
                    $this->ModelClientes->insertarBolsaHorasNueva($datos, $precioHora);
                }
                
                $retorno = [
                    'respuesta' => 1,
                    'tabla' => $this->construirHistorialBolsaHorasCliente($_POST['idCliente'])
                ];
            }

            //editando
            //$ins = $this->ModelClientes->insertarBolsaHorasNueva($_POST,$precioHora);
                           
        }
        print json_encode($retorno);
    }

    public function actualizarBolsaHoras()
    {
        $retorno = [
            'respuesta' => 0,
            'tabla' => ''
        ];

        if ($_POST['idCliente']>0 && $_POST['contratado']!='' && $_POST['contratado']>0  && $_POST['mes']>0 && $_POST['anio']>0 && $_POST['idBolsaMes'] >0 && $_POST['contratadoEuros']!='' && $_POST['contratadoEuros']>0 ) {

            $precioHora = round(($_POST['contratadoEuros']/$_POST['contratado']),2);

            $ins = $this->ModelClientes->actualizarBolsaHorasNueva($_POST,$precioHora);
            if ($ins >0) {
                $retorno = [
                    'respuesta' => 1,
                    'tabla' => $this->construirHistorialBolsaHorasCliente($_POST['idCliente'])
                ];
            }                             
        }
        print json_encode($retorno);

    }   

    public function nuevaFilaUsuarioExistente()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => ''
        ];
        if (isset($_POST['idUsuario']) && $_POST['idUsuario'] >0) {
            
            $datos = $this->ModelClientes->datosUsuarioPorId($_POST['idUsuario']);
        
            $fila = '<tr>                        
                        <td class="text-sm" width="20%"><input type="hidden" name="idUsuarioAdd" value="'.$datos->id.'"><input readonly value="'.$datos->nombre.'" class="border-2 border-pink-200 rounded-lg border-opacity-50" style="width: 100%;"></td>                        
                        <td class="text-sm" width="20%"><input readonly value="'.$datos->apellidos.'" class="border-2 border-pink-200 rounded-lg border-opacity-50" style="width: 100%;"></td>
                        <td class="text-sm" width="40%"><input readonly value="'.$datos->correo.'" class="border-2 border-pink-200 rounded-lg border-opacity-50" style="width: 100%;"></td>
                        <td class="text-sm" width="15%"><input readonly value="'.$datos->clientetipo.'" class="border-2 border-pink-200 rounded-lg border-opacity-50" style="width: 100%;"></td>
                        <td class="text-sm" width="5%"><a class="eliminarUsuario"><i class="fas fa-user-minus" style="color:red;"></i></a></td>             
                    </tr>';

            $retorno = [
                'respuesta' => 1,
                'fila' => $fila
            ];
        }
        print json_encode($retorno);

    }

    public function eliminarBolsaHoras()
    {
        $retorno = 0;
        if (isset($_POST['idBolsaHoras']) && $_POST['idBolsaHoras'] > 0 ) {
            $del = $this->ModelClientes->eliminarBolsaHoras($_POST['idBolsaHoras']);
            if ($del == 1) {
                $retorno = 1;
            }            
        }
        print json_encode($retorno);
    }

    public function traerEmailsContactos(){

        if(isset($this->fetch['id']) && $this->fetch['id'] > 0){

            $idDoc = $this->fetch['id'];
            $idCliente= $this->ModelFacturasCliente->idClientePorIdFactura($idDoc);
            if(!empty($this->fetch['tipodoc']) && $this->fetch['tipodoc']=='parte'){
                $idCliente= $this->ModelIncidencias->idClientePorIncidencia($idDoc);
            }            

            $option = '<option disabled selected value="">Seleccionar email</option>';
            if($idCliente){
                $contactos = $this->ModelClientes->obtenerContactosCliente($idCliente);
                if($contactos){
                    
                    $decode = json_decode($contactos);
                    
                    if(isset($decode) && count($decode)> 0){
                     
                        foreach ($decode as $contacto) {
                            if(isset($contacto->email) && trim($contacto->email) != ''){
                                $option .= '<option value="'.$contacto->email.'">'.$contacto->email.'</option>';
                            }
                        }              
                        
                    }
                }
            }
            print json_encode($option);
        }


    }
    

    public function buscarCliente()
    {     
        if(isset($_POST['cargarIniciales']) && $_POST['cargarIniciales'] === 'true') {
            // Cargar los primeros 100
            //$search = $this->ModelClientes->obtenerPrimeros100Clientes();
            $search = $this->ModelClientes->obtenerPrimeros100ClientesConNombreComercial();
            
        } else {
            // Búsqueda normal
            $like = "'"."%".$_POST['query']."%"."'" ;
            $search = '';
            if (trim($like) != '') {
                //$search = $this->ModelClientes->buscarClientesConLike($like);                       
                $search = $this->ModelClientes->buscarClientesConLikeYNombreComercial($like);     
            }
        }

        print json_encode($search);
        
    }

    public function buscarImagenEquipo()
    {
        $retorno = [                    
            'imgsrc' => '', 'nombre' => ''
        ];
        if (isset($_POST['idFichero']) && $_POST['idFichero'] >0) {
            $img = $this->ModelClientes->obtnerImagenEquiposDesdeIdFichero($_POST['idFichero']);
            if(isset($img) && $img != ''){
                $path = "public/documentos/Equipos/".$img;

                $extension = $this->obtenerExtension($img);
                $retorno = ['imgsrc' => $path, 'nombre' => $img, 'extension' => $extension];    
            }
            
        }
        echo json_encode($retorno);    
    }

    public function obtenerExtension($nombreFichero) {        
        $infoFichero = pathinfo($nombreFichero);        
        return isset($infoFichero['extension']) ? $infoFichero['extension'] : '';
    }
}

