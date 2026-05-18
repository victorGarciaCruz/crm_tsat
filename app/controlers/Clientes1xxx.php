<?php

class Clientes extends Controlador
{



    public function __construct()
    {
        session_start();
        $this->controlPermisos();
        $this->ModelClientes = $this->modelo('ModeloClientes');
    }

    /*public function componente()
    {

        $this->vista('clientes/component');
    }*/

    public function index()
    {
        $clientes = $this->ModelClientes->obtenerClientes();

        $datos = [
            "clientes" => $clientes
        ];

        $this->vista('clientes/clientes', $datos);
    }

    public function crearTabla()
    {

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $orden = $_POST['orden'];
            $tipoOrden = $_POST['tipoOrden'];
        }
        
        $cond = '';
        $filaspagina = $filas * $pagina;
    
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
                    if ($key == 'id') {
                        $cond .= "id" . $y;
                    }
                    if ($key == 'Razón Social') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'CIF') {
                        $cond .= "cif" . $y;
                    }
                                                     
                    if ($key == 'Estado') {
                        
                        $estados = [
                            "Activo" => " ( activo = 1 ) ", 
                            "Inactivo" => " ( activo = 0 ) "                        
                                ];
                        
                        
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
    
                   
                }                                    
    
            }

            $clientes = $this->ModelClientes->obtenerClientesTablaClassBuscar($filas,$orden,$filaspagina,$tipoOrden,$cond);

        }else{
            $clientes = $this->ModelClientes->obtenerClientesTablaClass($filas,$orden,$tipoOrden,$filaspagina);
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
                    if ($key == 'id') {
                        $cond .= "id" . $y;
                    }
                    if ($key == 'Razón Social') {
                        $cond .= "nombre" . $y;
                    }
                    if ($key == 'CIF') {
                        $cond .= "cif" . $y;
                    }
                                                     
                    if ($key == 'Estado') {
                        
                        $estados = [
                            "Activo" => " ( activo = 1 ) ", 
                            "Inactivo" => " ( activo = 0 ) "                        
                                ];
                        
                        
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
    
                   
                }                                    
    
            }

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
        $cif = '';
        $direccion = '';
        $codigopostal = '';
        $poblacion = '';
        $provincia = '';
        $activo = 1;
        $tituloModal = 'Alta cliente';
        $btnSubmit = 'A&ntilde;adir';
        $idBtnSubmit = 'crearClienteNuevo';
        $idCliente = '';
        $fila = '';
                        
        $contactos ='<tr>                            
                        <td width="40%" class="font-bold">Contacto</td>
                        <td width="20%" class="font-bold">Email</td>
                        <td width="20%" class="font-bold">Teléfono</td>
                        <td width="10%" class="font-bold"></td>
                    </tr>';

        $tecnicos = $this->obtenerListaTecnicos();

        //si el cliente existe entrea a este IF
        if (isset($datos) && $datos != '') {
            $id = isset($datos->nombre)?$datos->id:"";
            $nombre = isset($datos->nombre)?$datos->nombre:"";
            $cif = isset($datos->cif)?$datos->cif:"";
            $direccion = isset($datos->direccion)?$datos->direccion:"";
            $codigopostal = isset($datos->codigopostal)?$datos->codigopostal:"";
            $poblacion = isset($datos->poblacion)?$datos->poblacion:"";
            $provincia = isset($datos->provincia)?$datos->provincia:"";
            $activo = isset($datos->activo)?$datos->activo:"";
            $tituloModal = 'Editar cliente - ';
            $btnSubmit = 'Guardar';
            $idBtnSubmit = 'actualizarCliente';
            $idCliente = '<span>Nº '.$datos->id.'</span>';            

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
                                <td width="20%">'.$key.'</td>
                                <td width="60%">'.$nombreTecnico->nombre.'</td>
                                <td width="20%"><a href="" class="eliminarTecnico"><i class="fas fa-user-minus" style="color:red;"></i></a></td>
                            </tr>';
                }                
            }

            //construyo el apartado de contactos (nombre/email/telefono) asignados al cliente
            if ( isset($datos->contactos) && count(json_decode($datos->contactos)) > 0 ) {

                        
                foreach (json_decode($datos->contactos) as $contacto) {
                    $contactos .= '<tr>                                    
                                    <td width="40%"><input name="nombreContacto" value="'.$contacto->nombre.'" class="border-2 border-blue-200 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="20%"><input name="mailContacto" value="'.$contacto->email.'" class="border-2 border-blue-200 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="20%"><input name="telefonoContacto" value="'.$contacto->telefono.'" class="border-2 border-blue-200 rounded-lg border-opacity-50" style="width: 100%;"></td>
                                    <td width="10%"><a href="" class="eliminarContactoCli"><i class="fas fa-user-minus" style="color:red;"></i></a></td>
                                </tr>';
                }                
            }
        }
        

        //aqui construyo la vista para alta cliente nuevo y para actualizar cliente
        $html .= '        
            <form id="formAltaClientes">
                    <input type="hidden" value="'.$id.'" id="idCliEdit">
                    <h1 class="text-center text-xl my-4 uppercase text-blue-500 font-semibold">'.$tituloModal.' ' . $idCliente . '</h1>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8 mt-5 mx-7">
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Nombre fiscal</label>
                            <input name="nombre" id="nombre" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Nombre fiscal" value="'.$nombre.'" required/>
                        </div>
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">CIF/NIF</label>
                            <input name="cif" id="cif" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="CIF" value="'.$cif.'" required/>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8 mt-5 mx-7">
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Dirección</label>
                            <input name="direccion" id="direccion" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Dirección" value="'.$direccion.'" required/>
                        </div>
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Población</label>
                            <input type="text" name="poblacion" id="posblacion" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Población" value="'.$poblacion.'" required/>
                        </div>
                    </div>


                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 md:gap-8 mt-5 mx-7">
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Provincia</label>
                            <input name="provincia" id="provincia" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Provincia" value="'.$provincia.'" required/>
                        </div>
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Código postal</label>
                            <input type="text" name="codigopostal" id="codigopostal" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" placeholder="Código postal" value="'.$codigopostal.'" required/>
                        </div>
                    
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Estado</label>
                            <select name="activo" id="activo" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">';
                            
                            $estados = [1=>"Activo", 0=>"Inactivo"];
                            foreach ($estados as $key => $value) {
                                $html .='
                                <option value="'.$key.'" '.(($key == $activo)? "selected": "").'>'.$value.'</option>';
                            }

                $html .='                            
                            </select>                        
                        </div>                        
                    </div>

                    <div class="inline-flex mt-5 mx-7">                       
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold mr-4 ">Contactos</label>
                            <a href="#" id="addContacto" class="rounded-full h-6 w-6 flex items-center justify-center  bg-violeta-claro text-white flex-1"><i class="fas fa-plus-circle"></i></a>                        
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-5 md:gap-8 mt-5 mx-7">
                        <div class="grid grid-cols-1">
                            <table id="tablaContactosCliente">
                            '.$contactos.'
                            </table>
                        </div>
                        <div class="grid grid-cols-1"></div>
                    </div> 

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8 mt-5 mx-7">
                        <div class="grid grid-cols-1">
                            <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Técnicos</label>
                            <select name="tecnicos" id="tecnicos" class="py-2 px-3 rounded-lg border-2 border-blue-200 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">';
                            foreach ($tecnicos as $tecnico) {
                                $html .= '<option value="'.$tecnico->id.'">'.$tecnico->nombre.'</option>';
                            }

                $html .='                                
                            </select>
                        </div>';
                $html .= '
                        <div class="grid grid-cols-1">                            
                        </div>
                    </div>';
                $html .='
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-8 mt-5 mx-7">
                        <div class="grid grid-cols-1">
                            <table id="tablaTecnicosCliente">
                            '.$fila.'
                            </table>
                        </div>
                        <div class="grid grid-cols-1"></div>
                    </div>                    
                    <div class="flex items-center justify-center  md:gap-8 gap-4 pt-5 pb-5">
                        <a class="w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-white px-4 py-2" id="cancelarCerrar">Cancelar</a>
                        <button id="'.$idBtnSubmit.'" class="w-auto bg-violeta-claro hover:bg-blue-500 rounded-lg shadow-xl font-medium text-white px-4 py-2">'.$btnSubmit.'</button>
                    </div>
            </form>';
            
           
        
        return $html;
    }


    public function agregarClienteNuevo()
    {      
      
        $retorno = [
            'respuesta' => 0,
            'fila' => '',
        ];

        if (isset($_POST['form'])) {            
            
            $datos = [];
            $tecnicos = [];
            $contactos = [];            
            $nombres = [];
            $mails = [];
            $telefonos = [];
            foreach ($_POST['form'] as $row) {
                if ($row['name'] == 'idTecnico' ) {
                    $tecnicos[] = $row['value'];
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
                $datos[$row['name']] = $row['value'];
                
                $datos['idstecnicos'] = $tecnicos;                
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

                $datos['contactos'] = $contactos;  
            }
            
                      

            $ins = $this->ModelClientes->insertarDatosClienteNuevo($datos);
            if ($ins && $ins >0) {
                $detalleCliente = $this->ModelClientes->detalleClientePorId($ins);
                $fila = $this->crearFilaNuevaClienteConDatos($detalleCliente);

                $retorno = [
                    'respuesta' => 1,
                    'fila' => $fila,
                ];

            }          

        }        
        print json_encode($retorno);
        
    }

    public function crearFilaNuevaClienteConDatos($detalleCliente)
    {
        $estado = 'Inactivo';
        if ($detalleCliente->id == 1) {
            $estado = 'Activo';
        }

        $html = '
        <tr class="rows">
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->id.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->nombre.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$detalleCliente->cif.'</td>
            <td class="px-5 py-5 border-b border-gray-200 bg-white text-sm ">'.$estado.'</td>
            <td class="botones px-5 py-5 border-b border-gray-200 bg-white text-sm">
                <div class="d-flex">
                    <a href="" class="mx-1 editar" title="Editar"><i class="fas fa-user-edit mr-2 fill-current text-yellow-500 text-lg"></i></a>
                    <a href="" class="mx-1 eliminar" title="Eliminar"><i class="fas fa-user-minus mr-2 fill-current text-red-600 text-lg"></i></a>
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


    public function actualizarCliente()
    {
        $retorno = 0;

        if (isset($_POST['form']) && $_POST['id'] >0) {
            
            $datos = [];           
            $tecnicos = [];
            $contactos = [];            
            $nombres = [];
            $mails = [];
            $telefonos = [];
            foreach ($_POST['form'] as $row) {
                if ($row['name'] == 'idTecnico' ) {
                    $tecnicos[] = $row['value'];
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

                $datos[$row['name']] = $row['value'];
                $datos['idstecnicos'] = $tecnicos;
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

                $datos['contactos'] = $contactos;  
            }
            $datos['id'] = $_POST['id'];
                      
            $upd = $this->ModelClientes->actualizarDatosClienteNuevo($datos);
            if ($upd) {
                $retorno = 1;
            }

        }        
        print json_encode($retorno);
        
    }






}
