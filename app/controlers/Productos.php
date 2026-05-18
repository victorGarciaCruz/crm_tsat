<?php

class Productos extends Controlador
{
    private $fetch;

    public function __construct()
    {
        session_start();
        $this->controlPermisos();
        $this->ModelProductos = $this->modelo('ModeloProductos');
        $this->ModelProveedores = $this->modelo('ModeloProveedores');
        $this->ModelTiposIva = $this->modelo('ModeloTiposIva');   
        
        if(file_get_contents("php://input")){
            $payload = file_get_contents("php://input");    
            $this->fetch = json_decode($payload, true);
        }  
    }

    public function index()
    {        
        $datos = [];       

        if ($_SESSION['nombrerol'] == 'tecnico') {                
            $this->vista('productos/productosTecnico', $datos);
        }else if($_SESSION['nombrerol'] == 'admin'){                
            $this->vista('productos/productos', $datos);
        }  

    }

    public function tablaUsuarios()
    {
        $users = $this->ModelProductos->obtenerUsuarios();
        print(json_encode($users));        
    }

    private function mapearCampoOrdenProducto($campoVisible) {
        $mapa = [
            'Nº'     => 'pro.numero',
            'Nombre' => 'pro.nombre',
            'Stock'  => 'pro.stock',
            'Marca'  => 'pro.marca',
            'Iva'    => 'pro.iva',
            'P.Vta'  => 'pro.pvtadefault'
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByProductos($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    $campoSQL = $this->mapearCampoOrdenProducto($campoVisible);
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

    public function crearTablaProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];
            $filas = $_POST['filas'];
            $pagina = $_POST['pagina'];
            $ordenMultiple = isset($_POST['ordenMultiple']) ? urldecode($_POST['ordenMultiple']) : '';
            $ordenSimple   = isset($_POST['orden']) ? $_POST['orden'] : '';
            $tipoSimple    = isset($_POST['tipoOrden']) ? $_POST['tipoOrden'] : '';
        }

        $cond = '1';
        $filaspagina = $filas * $pagina;

        // Determinar parámetros de orden según si hay orden múltiple
        if (!empty($ordenMultiple)) {
            // Caso orden múltiple: construir cláusula completa y tipo vacío
            $clausulaOrder = $this->construirClausulaOrderByProductos($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "pro.numero ASC"; 
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
                        $cond .= "pro.numero" . $y;
                    }
                    if ($key == 'Nombre') {
                        $cond .= "pro.nombre" . $y;
                    }
                    if ($key == 'Stock') {
                        $cond .= "pro.stock" . $y;
                    }
                    if ($key == 'Marca') {
                        $cond .= "pro.marca" . $y;
                    }
                    if ($key == 'Iva') {
                        $cond .= "pro.iva" . $y;
                    }
                    if ($key == 'P.Vta') {
                        $cond .= "pro.pvtadefault" . $y;
                    }
                }
            }
        }

        $productos = $this->ModelProductos->obtenerProductosTablaClassBuscar(
            $filas,
            $ordenFinal,
            $filaspagina,
            $tipoFinal,
            $cond
        );

        print(json_encode($productos));
    }    

    public function totalRegistrosProductos()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $buscar = $_POST['busqueda'];            
        }
        
        $cond = '1';        
    
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
                        $cond .= "pro.numero" . $y;
                    }
                    if ($key == 'Nombre') {
                        $cond .= "pro.nombre" . $y;
                    }
                    if ($key == 'Stock') {
                        $cond .= "pro.stock" . $y;
                    }
                    if ($key == 'Marca') {
                        $cond .= "pro.marca" . $y;
                    }
                    if ($key == 'Iva') {
                        $cond .= "pro.iva" . $y;
                    }                                 
                    if ($key == 'P.Vta') {
                        $cond .= "pro.pvtadefault" . $y;
                    } 
                }                                    
    
            }            
        }

        $contador = $this->ModelProductos->totalRegistrosProductosBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }   

    public function altaProductos()
    {        
        $datos = [
            'tiposiva' => $this->ModelTiposIva->obtenerTipoIvaActivos()
        ];
        $this->vista('productos/altaProductos/altaProductos',$datos);
    }

    public function traerProveedores()
    {                
      

        $proveedores = $this->ModelProveedores->obtenerProveedoresActivos();              

        $html = TemplateHelperProducto::buildLineSuppliersPricesLine($proveedores);

        $retorno['fila'] = $html;
        
        if($proveedores && count($proveedores) > 0){            
            $retorno['respuesta'] = 1;
        }else{
            $retorno['respuesta'] = 0;        
        }
                
        print json_encode($retorno);
    }  

    public function crearProducto()
    {                         

        $retorno = [
            'respuesta' => 0           
        ];                   
        if ($_POST['form']) {
                                    
            $datos = [];            
            $proveedoresprecios = [];            
            $proveedores = [];
            $referencias = [];
            $precios = [];
            $preciosVta = [];
           
            foreach ($_POST['form'] as $row) {
            
                if ($row['name'] == 'proveedor' ) {
                    $proveedores[] = $row['value'];                    
                }
                if ($row['name'] == 'referenciaprov' ) {
                    $referencias[] = $row['value'];                    
                }
                if ($row['name'] == 'precio' ) {
                    $precios[] = $row['value'];                    
                }
                if ($row['name'] == 'margen' ) {
                    $margenes[] = $row['value'];                    
                }
                if ($row['name'] == 'precioventa' ) {
                    $preciosVta[] = $row['value'];                    
                }
               
                $datos[$row['name']] = $row['value'];
                                           
            }
         
            if (count($proveedores) >0) {
                
                for ($i=0; $i < count($proveedores) ; $i++) {                               
                        $idproveedor = $proveedores[$i];
                        $referencia = $referencias[$i];
                        $precio = $precios[$i];
                        $margen = $margenes[$i];
                        $precioVta = $preciosVta[$i];
                                                 
                        $tmp = [                                                      
                            'referencia' => $referencia,
                            'precio' => UtilsHelper::formatNumberTypePrice($precio),
                            'margen' => UtilsHelper::formatNumberTypePrice($margen),       
                            'precioVta' => UtilsHelper::formatNumberTypePrice($precioVta)
                        ];
                        $proveedoresprecios[$idproveedor] = $tmp;
                }                
            }      

                                                            
            $precioVtaDefault = 0;
            if(isset($datos['proveedordefault']) && $datos['proveedordefault'] > 0){
                $precioVtaDefault = $this->obtenerPrecioVtaDefaultProducto($proveedoresprecios, $datos['proveedordefault']);
            }
            $datos['pvtadefault'] = $precioVtaDefault;            

            $datos['proveedoresprecios'] = json_encode($proveedoresprecios);            
            
            $datos['numero'] = $this->ModelProductos->calcularNumeroCorrelativoProducto();
 
            $idProducto = $this->ModelProductos->insertarDatosProductoNuevo($datos);

            if ($idProducto && $idProducto >0) {                           
                
                $retorno = [
                    'respuesta' => 1,
                    'id' => $datos['numero']                    
                ];

            }          

        }  
        print json_encode($retorno);
    }   



    public function actualizarProducto()
    {
        if (isset($_POST['id']) && $_POST['id'] > 0) {
            
            $numProducto = $_POST['id'];

            $datosProducto = $this->ModelProductos->obtenerProductoByNumero($numProducto);                   
            $proveedores = $this->ModelProveedores->obtenerProveedoresActivos();
                        
            $proveedores_precios_html = TemplateHelperProducto:: buildLineSuppliersPricesLineWithData($proveedores, $datosProducto);
    
            $datos = [
                "producto" => $datosProducto,                
                "proveedores_precios_html" => $proveedores_precios_html,
                "tiposiva" => $this->ModelTiposIva->obtenerTipoIvaActivos()
            ];
            $this->vista('productos/actualizarProductos/actualizarProductos', $datos);

        }else{
            redireccionar('/Productos');
        }

    }

    public function obtenerDatosProducto()
    {
       $datos = [];        
        if (isset($this->fetch['id']) && $this->fetch['id'] > 0) {
            $idProducto = $this->fetch['id'];
            $datosProducto = $this->ModelProductos->obtenerProductoByNumero($idProducto);
            
          
            $datos['datosProducto'] = $datosProducto;
            $datos['precioProvDefault'] = $this->obtenerPrecioProveedorPorDefecto($datosProducto->provprecios, $datosProducto->proveedordefault);
        }
        print_r(json_encode($datos));   
    } 


    private function obtenerPrecioProveedorPorDefecto($provprecios, $proveedordefault)
    {
        $precio = '';
        if(isset($provprecios) && count(json_decode($provprecios)) > 0){
       
            foreach (json_decode($provprecios) as $prov) {
                if($prov->idproveedor == $proveedordefault){
                    $precio = $prov->precio;
                }    
            }
            
        }       
        return $precio;
    }

    public function editarProducto()
    {
                
        $retorno = [
            'respuesta' => 0           
        ];               

        if ($_POST['form']) {
                                    
            $datos = [];            
            $proveedoresprecios = [];            
            $proveedores = [];
            $referencias = [];
            $precios = [];
            $preciosVta = [];
            $idProducto = '';

            foreach ($_POST['form'] as $row) {

                if ($row['name'] == 'id' ) {
                    $idProducto = $row['value'];
                    $datos['id'] = $row['value'];
                }              

                if ($row['name'] == 'proveedor' ) {
                    $proveedores[] = $row['value'];                    
                }
                if ($row['name'] == 'referenciaprov' ) {
                    $referencias[] = $row['value'];                    
                }
                if ($row['name'] == 'precio' ) {
                    $precios[] = $row['value'];                    
                }
                if ($row['name'] == 'margen' ) {
                    $margenes[] = $row['value'];                    
                }
                if ($row['name'] == 'precioventa' ) {
                    $preciosVta[] = $row['value'];                    
                }
                $datos[$row['name']] = $row['value'];
                                           
            }

            if($idProducto!= '' && $idProducto > 0){                              

                if (count($proveedores) >0) {
                    
                    for ($i=0; $i < count($proveedores) ; $i++) {                               
                            $idproveedor = $proveedores[$i];
                            $referencia = $referencias[$i];
                            $precio = $precios[$i];
                            $margen = $margenes[$i];
                            $precioVta = $preciosVta[$i];

                            $tmp = [                                                           
                                    'referencia' => $referencia,
                                    'precio' => UtilsHelper::formatNumberTypePrice($precio),
                                    'margen' => UtilsHelper::formatNumberTypePrice($margen),       
                                    'precioVta' => UtilsHelper::formatNumberTypePrice($precioVta)
                            ];
                            $proveedoresprecios[$idproveedor] = $tmp;
                    }                
                }

                                                               
                $precioVtaDefault = 0;
                if(isset($datos['proveedordefault']) && $datos['proveedordefault'] > 0){
                    $precioVtaDefault = $this->obtenerPrecioVtaDefaultProducto($proveedoresprecios, $datos['proveedordefault']);
                }
                $datos['pvtadefault'] = $precioVtaDefault;

                $datos['proveedoresprecios'] = json_encode($proveedoresprecios);                   

                $upd = $this->ModelProductos->actualizarDatosProducto($datos);

                if ($upd) {                                                                   

                    $retorno = [
                        'respuesta' => 1,
                        'modificacion' => $this->ModelProductos->fechaUltimaModificacion($datos['id'])
                    ];

                }    

            }
      

        }  
        print json_encode($retorno);
    }

    public function obtenerPrecioVtaDefaultProducto($proveedoresprecios, $proveedordefault)
    {      
        if (isset($proveedoresprecios[$proveedordefault]) && isset($proveedoresprecios[$proveedordefault]['precioVta'])) {
            return $proveedoresprecios[$proveedordefault]['precioVta'];
        } else {
            return 0;
        }
    }



    public function eliminarProducto()
    {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $datosEliminar = [
                "id" => $_POST['idProductoDel']
            ];
            
            $del = $this->ModelProductos->borrarProducto($datosEliminar);

            if ($del ==1) {
                $_SESSION['message'] = 'Se ha eliminado el artículo corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error y no se ha podido eliminar el artículo.';
            }
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error y no se ha podido eliminar el artículo.';
        }

        redireccionar('/Productos');
    }

    
    public function buscarProducto()
    {     
        $like = "'"."%".$_POST['query']."%"."'" ;       
       
        $search = '';
        if (trim($like) != '') {
            $search = $this->ModelProductos->buscarProductosConLike($like);                       
        }
        print json_encode($search);
        
    }


}
