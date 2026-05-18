<?php

class Proveedores extends Controlador
{
    public function __construct()
    {
        session_start();
        $this->controlPermisos();
        $this->ModelProveedores = $this->modelo('ModeloProveedores');
    }

    public function index()
    {
        $aniosInc = $this->ModelProveedores->aniosConIncidencias();
        $tecnicoConPermiso = '';

        $datos = [
            "aniosSelect" => $aniosInc,
            'tecnicoConPermiso' => $tecnicoConPermiso
        ];

        $this->vista('proveedores/proveedores', $datos);
    }

    private function mapearCampoOrdenProveedor($campoVisible) {
        $mapa = [
            'Nº'           => 'id',
            'Razón Social' => 'nombre',
            'CIF'          => 'cif',
            'Población'    => 'poblacion',
            'Provincia'    => 'provincia'
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByProveedores($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    $campoSQL = $this->mapearCampoOrdenProveedor($campoVisible);
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
            $clausulaOrder = $this->construirClausulaOrderByProveedores($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "id DESC"; // orden por defecto
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
                        $cond .= "id" . $y;
                    }
                    if ($key == 'Razón Social') {
                        $cond .= "nombre" . $y;
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

            $proveedores = $this->ModelProveedores->obtenerProveedoresTablaClassBuscar($filas,$ordenFinal,$filaspagina,$tipoFinal,$cond);
        } else {
            $proveedores = $this->ModelProveedores->obtenerProveedoresTablaClass($filas,$ordenFinal,$tipoFinal,$filaspagina);
        }

        print(json_encode($proveedores));
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

            $contador = $this->ModelProveedores->totalRegistrosProveedoresBuscar($cond);

        }else{
            $contador = $this->ModelProveedores->totalRegistrosProveedores();
        }

        $cont = $contador->contador;        
        print_r($cont);
    }


    public function nuevoProveedor()
    {
        $datos = '';
        $form = $this->construirFormularioProveedor($datos);
        $salida['form'] = $form;
        echo json_encode($salida);
    }

    public function nombreTecnicoPorId($idTecnico)
    {
        $dato = $this->ModelProveedores->nombreTecnicoPorId($idTecnico);
        return $dato;
    }

    public function obtenerListaTecnicos()
    {
        $tecnicos = $this->ModelProveedores->obtenerListaTecnicos();
        return $tecnicos;
    }

    public function construirFormularioProveedor($datos)
    {        
        $id = '';
        $html = '';
        $nombre = '';
        $cif = '';
        $direccion = '';
        $codigopostal = '';
        $observaciones = '';
        $poblacion = '';
        $provincia = '';        
        $tituloModal = 'Alta proveedor';
        $btnSubmit = 'Agregar y cerrar';
        $idBtnSubmit = 'crearProveedorNuevo';

        $btnSubmit2 = 'Agregar y seguir';
        $idBtnSubmit2 = 'crearProveedorYSeguir';
        $ver = 'block';
        $idProveedor = '';
        $fila = '';
                        
        $contactos ='<tr>                            
                        <td width="40%" class="font-bold">Contacto</td>
                        <td width="20%" class="font-bold">Email</td>
                        <td width="20%" class="font-bold">Teléfono</td>
                        <td width="10%" class="font-bold"></td>
                    </tr>';        

        $navtab = $this->contruirNavTab();        

        //si el proveedor existe entrea a este IF
        if (isset($datos) && $datos != '') {
            $id = isset($datos->nombre)?$datos->id:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";
            $cif = isset($datos->cif)?$datos->cif:"";
            $direccion = isset($datos->direccion)?$datos->direccion:"";
            $codigopostal = isset($datos->codigopostal)?$datos->codigopostal:"";
            $observaciones = isset($datos->observaciones)?$datos->observaciones:"";
            $poblacion = isset($datos->poblacion)?$datos->poblacion:"";
            $provincia = isset($datos->provincia)?$datos->provincia:"";            
            $tituloModal = 'Editar proveedor - ';
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarProveedor';
            $idProveedor = '<span>Nº '.$datos->id.'</span>';
            $ver = 'none';

            //construyo el apartado de contactos (nombre/email/telefono) asignados al proveedor
            if ( isset($datos->contactos) && count(json_decode($datos->contactos)) > 0 ) {
                
                $contactos = TemplateHelperProveedor::buildContactsSuppliers(json_decode($datos->contactos));
                              
            }
        }
        
        //aqui construyo la vista para alta proveedor nuevo y para actualizar proveedor
        $html .=  TemplateHelperProveedor::buildHeaderModalSupplierForm($id, $tituloModal, $idProveedor);              
        
        //inicio de tabs panels
        $html .= $navtab;       

        //primer tab

        $html .= TemplateHelperProveedor::buildFormCreateEditSupplier($nombre, $cif, $direccion, $poblacion, $provincia, $codigopostal, $tituloModal, $observaciones, $contactos, $idBtnSubmit, $btnSubmit, $idBtnSubmit2, $btnSubmit2, $ver);
         
        $html .= '
        <div class="hidden pastilla" id="tab-settings"></div>    
        ';  

        //fin tabs panels
        $html .= '</div>
                </div>
            </div>
        </div>
        </div>';
        
        return $html;
    }

    public function contruirNavTab()
    {
        $rol = $_SESSION['nombrerol'];

        $html = TemplateHelperProveedor::buildNavBarSuppliers($rol);
        
        return $html;
    } 

    public function agregarProveedorNuevo()
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
            $contactos = [];            
            $nombres = [];
            $mails = [];
            $telefonos = [];
            foreach ($_POST['form'] as $row) {
                /*
                if ($row['name'] == 'codigoTecnico' ) {
                    $idTecnico = $this->ModelProveedores->obtenerIdTecnicoDesdeCodigoTecnico($row['value']);
                    $tecnicos[] = $idTecnico;
                }  
                */  
                if ($row['name'] == 'nombreContacto' ) {
                    $nombres[] = $row['value'];                    
                }
                if ($row['name'] == 'mailContacto' ) {
                    $mails[] = $row['value'];                    
                }
                if ($row['name'] == 'telefonoContacto' ) {
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
            $datos['contactos'] = $contactos;  
                      
 
            $ins = $this->ModelProveedores->insertarDatosProveedorNuevo($datos);
            if ($ins && $ins >0) {

              

                if (isset($datos['sucursalDefault']) && $datos['sucursalDefault'] == 1) {

               

                    $datosAlmacen['nombreSucursal'] = 'Principal'; 
                    $datosAlmacen['direccionSucursal']= '';
                    $datosAlmacen['poblacionSucursal'] = '';
                    $datosAlmacen['provinciaSucursal'] = '';
                    $datosAlmacen['codigopostalSucursal'] = '';                        
                    $datosAlmacen['contactos'] = [];
                    $datosAlmacen['idProveedor'] = $ins;

                  

                    $almacen = $this->ModelProveedores->insertarDatosAlmacenNuevo($datosAlmacen);

                }

                

                $detalleProveedor = $this->ModelProveedores->detalleProveedorPorId($ins);
                
                $formLleno = '';
                if ($tipo == 'agregarycontinuar') {          
                    $formLleno = $this->construirFormularioProveedor($detalleProveedor); 
                }
                          
                $tabla = $this->contruirTablaClaseProveedores();

                $retorno = [
                    'respuesta' => 1,
                    'clasetabla' => $tabla,
                    'formLleno' => $formLleno
                ];

              

            }          

        }        
        print json_encode($retorno);
        
    }

    public function contruirTablaClaseProveedores()
    {
        $html = '
        <div id="destinoproveedoresajax"></div>                            
        <script type="module">
            import arrancar from "'.RUTA_URL.'/public/js/tablaClass/tablaClass.js"
            arrancar("tablaproveedores","Proveedores/crearTabla", "destinoproveedoresajax", "Nº", "DESC", 0, "buscador","Proveedores/totalRegistros", [20, 30, 40, 50],"min-w-full leading-normal","paginador",["editar","eliminar"],"","");    
        </script>    
        ';
        return $html;
    }

    /*
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
    */

    public function obtenerDetalleProveedor()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] !='') {
            
            $detalleProveedor = $this->ModelProveedores->detalleProveedorPorId($_POST['id']);
            
            $fila = $this->construirFormularioProveedor($detalleProveedor);
            $retorno = [
                'respuesta' => 1,
                'fila' => $fila,
            ];

        }
        print json_encode($retorno);
    }

    public function eliminarProveedor()
    {
        $retorno = 0;
        if(isset($_POST['id']) && $_POST['id'] >0){
            
            $del = $this->ModelProveedores->eliminarProveedor($_POST['id']);
            if ($del) {
                $retorno = 1;
            }
        }
        echo ($retorno);
        
    }


    public function actualizarProveedor()
    {
        
        $retorno = 0;

        if (isset($_POST['form']) && $_POST['id'] >0) {
            
            $datos = [];           
            //$tecnicos = [];
            $contactos = [];            
            $nombres = [];
            $mails = [];
            $telefonos = [];
            foreach ($_POST['form'] as $row) {
                /*
                if ($row['name'] == 'codigoTecnico' ) {
                    $idTecnico = $this->ModelProveedores->obtenerIdTecnicoDesdeCodigoTecnico($row['value']);                    
                    $tecnicos[] = $idTecnico;
                }
                */
                if ($row['name'] == 'nombreContacto' ) {
                    $nombres[] = $row['value'];                    
                }
                if ($row['name'] == 'mailContacto' ) {
                    $mails[] = $row['value'];                    
                }
                if ($row['name'] == 'telefonoContacto' ) {
                    $telefonos[] = $row['value'];                    
                }

                $datos[$row['name']] = $row['value'];
                
            }
            //$datos['idstecnicos'] = $tecnicos;
            

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
                      
            $upd = $this->ModelProveedores->actualizarDatosProveedorNuevo($datos);
            if ($upd) {
                $retorno = 1;
            }

        }        
        print json_encode($retorno);
        
    }

    public function verSucursalesProveedor()
    {
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] >0) {
            
            $sucursales = $this->ModelProveedores->obtenerAlmacenesPorProveedor($_POST['id']);
            
            $html = '                
                <div class="inline-flex my-3 mx-7">                       
                        <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Agregar Almacén</label>
                        <a href="#" id="addSucursal" class="rounded-full h-6 w-6 flex items-center justify-center  bg-violeta-claro text-white flex-1" title="Nuevo almacén"><i class="fas fa-plus-circle"></i></a>                        
                </div>
                ';
                
            $tablaSucursales = $this->construirTablaAlmacenes($sucursales);

            $html .= $tablaSucursales;
            $retorno = [
                'respuesta' => 1,
                'tabla' => $html
            ];

        }

        print json_encode($retorno);
    }

    public function construirTablaAlmacenes($sucursales)
    {
        $tabla = "<div class='grid grid-cols-1 w-full shadow rounded-lg overflow-x-auto'> <table class='min-w-full leading-normal' id='tablaSucursales'>
        <thead>
            <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Nº</th>
            <th class='px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider'>Almacén</th>
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
                $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'>".$sucursal->id."</td>";
                $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'>".$sucursal->nombre."</td>";
                $tabla .= "<td class='p-2 border-b border-gray-200 bg-white text-sm'>".$direccion."</td>";
                $tabla .= "<td class='botones p-2 border-b border-gray-200 bg-white text-sm'>
                            <div class='flex'>
                            <a href='' class='mx-1 editarAlmacen' title='Editar'><i class='fas fa-user-edit mr-2 fill-current text-yellow-500 text-lg'></i></a>
                            <a href='' class='mx-1 eliminarAlmacen' title='Eliminar'><i class='fas fa-user-minus mr-2 fill-current text-red-600 text-lg'></i></a>
                            </div>                
                        </td>";
                $tabla .= "</tr>";
            }
        
        }
        $tabla .= '</tbody></table></div>';

        return $tabla;

    }

    public function nuevoAlmacen()
    {
        $datos = '';
        $form = $this->construirBodyFormularioAlmacen($datos);
        $salida['form'] = $form;
        echo json_encode($salida);
    }

    public function obtenerDetalleAlmacen()
    {
        $retorno = [
            'respuesta' => 0,
            'bodyModal' => '',
        ];
        if (isset($_POST['id']) && $_POST['id'] !='') {
            
            $detalleAlmacen = $this->ModelProveedores->detalleAlmacenPorId($_POST['id']);
            $bodyModal = $this->construirBodyFormularioAlmacen($detalleAlmacen);
            $retorno = [
                'respuesta' => 1,
                'bodyModal' => $bodyModal,
            ];

        }
        print json_encode($retorno);
    }

    public function construirBodyFormularioAlmacen($datos)
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
        $idBtnSubmit = 'crearAlmacenNuevo';
        $idProveedor = '';
        $fila = '';
                        
        $contactos ='<tr>                            
                        <td width="40%" class="font-bold">Contacto</td>
                        <td width="20%" class="font-bold">Email</td>
                        <td width="20%" class="font-bold">Teléfono</td>
                        <td width="10%" class="font-bold"></td>
                    </tr>';                

        //si el proveedor existe entrea a este IF
        if (isset($datos) && $datos != '') {
            $id = isset($datos->nombre)?$datos->id:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";            
            $direccion = isset($datos->direccion)?$datos->direccion:"";
            $codigopostal = isset($datos->codigopostal)?$datos->codigopostal:"";
            $poblacion = isset($datos->poblacion)?$datos->poblacion:"";
            $provincia = isset($datos->provincia)?$datos->provincia:"";                 
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarAlmacen';                   

            //construyo el apartado de contactos (nombre/email/telefono) asignados al proveedor
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
        

        //aqui construyo la vista para alta proveedor nuevo y para actualizar proveedor

        $html .= '                       
                <form id="formAltaSucursales">                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8 mt-5 mx-7">
                            <div class="grid grid-cols-1">
                                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre almacén</label>
                                <input name="nombreSucursal" id="nombreSucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Nombre almacén" value="'.$nombre.'" required/>
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
                                <a href="#" id="addContactoSucursal" title="Agregar contactos" class="rounded-full h-6 w-6 flex items-center justify-center  bg-violeta-claro text-white flex-1"><i class="fas fa-plus-circle"></i></a>                        
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

    public function crearAlmacenNuevo()
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

            $datos['idProveedor'] = $_POST['idProveedor'];
            $datos['contactos'] = $contactos;  
            
            $ins = $this->ModelProveedores->insertarDatosAlmacenNuevo($datos);
            if ($ins && $ins >0) {
                $detalleAlmacen = $this->ModelProveedores->detalleAlmacenPorId($ins);
                $fila = $this->crearFilaNuevoAlmacenConDatos($detalleAlmacen);

                $retorno = [
                    'respuesta' => 1,
                    'fila' => $fila,
                ];

            }          

        }        
        print json_encode($retorno);
        
    }

    public function crearFilaNuevoAlmacenConDatos($detalleAlmacen)
    {
        $direccion = $detalleAlmacen->direccion. " - " .$detalleAlmacen->poblacion. " - ".$detalleAlmacen->provincia. " - ".$detalleAlmacen->codigopostal;

        $html = '
        <tr class="rows">
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleAlmacen->id.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleAlmacen->nombre.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$direccion.'</td>
            <td class="botones px-5 py-5 border-b border-gray-200 bg-white text-sm">
                <div class="flex">
                    <a href="" class="mx-1 editarAlmacen" title="Editar"><i class="fas fa-user-edit mr-2 fill-current text-yellow-500 text-lg"></i></a>
                    <a href="" class="mx-1 eliminarAlmacen" title="Eliminar"><i class="fas fa-user-minus mr-2 fill-current text-red-600 text-lg"></i></a>
                </div>
            </td>
        </tr>        
        ';
        return $html;

    }

    public function actualizarAlmacen()
    {      
        $retorno = 0;

        if (isset($_POST['form']) && $_POST['idAlmacen'] >0 && $_POST['idProveedor'] >0) {
            

            
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
                        
            $datos['idAlmacen'] = $_POST['idAlmacen'];
            $datos['contactos'] = $contactos; 
                      
            $upd = $this->ModelProveedores->actualizarDatosSucursalNueva($datos);
            if ($upd) {
                $retorno = 1;
            }

        }        
        print json_encode($retorno);        
    }

    public function eliminarAlmacen()
    {
        $retorno = 0;
        if(isset($_POST['id']) && $_POST['id'] >0){
            
            $del = $this->ModelProveedores->eliminarAlmacen($_POST['id']);
            if ($del) {
                $retorno = 1;
            }
        }
        echo ($retorno);
    }  


    
}

