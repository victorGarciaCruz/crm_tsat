<?php

class CostesTecnicos extends Controlador {

    public function __construct() {
        session_start();
        $this->controlPermisos();
        $this->ModelCostesTecnicos = $this->modelo('ModeloCostesTecnicos');    
    }

    public function index() {
        $tecnicos = $this->ModelCostesTecnicos->listadoTecnicosActivos();
        $anios = $this->ModelCostesTecnicos->aniosConIncidencias();
        $datos = [
            "tecnicos" => $tecnicos,
            'anios' => $anios
        ];

        $this->vista('costesTecnicos/costesTecnicos', $datos);
    }

    private function mapearCampoOrdenCosteTecnico($campoVisible) {
        $mapa = [
            'Código'   => 'coste.codigotecnico',
            'Nombre'   => 'usu.nombre',
            'Apellidos'=> 'usu.apellidos',
            'Email'    => 'usu.correo',
            'Teléfono' => 'usu.telefono',
            'Mes'      => 'coste.mes',
            'Año'      => 'coste.anio',
            'Coste'    => 'coste.costehora'
        ];
        return $mapa[$campoVisible] ?? $campoVisible;
    }

    private function construirClausulaOrderByCostesTecnicos($ordenMultipleJson, $ordenSimple, $tipoSimple) {
        // 1. Si hay orden múltiple (JSON con array de criterios), se usa
        if (!empty($ordenMultipleJson)) {
            $ordenes = json_decode($ordenMultipleJson, true);
            if (is_array($ordenes) && count($ordenes) > 0) {
                $sentencias = [];
                foreach ($ordenes as $item) {
                    $campoVisible = $item['campo'];
                    $direccion = (strtoupper($item['dir']) === 'DESC') ? 'DESC' : 'ASC';
                    $campoSQL = $this->mapearCampoOrdenCosteTecnico($campoVisible);
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
    
    public function crearTablaCostesTecnicos()
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
            $clausulaOrder = $this->construirClausulaOrderByCostesTecnicos($ordenMultiple, $ordenSimple, $tipoSimple);
            if (empty($clausulaOrder)) {
                $clausulaOrder = "coste.id DESC"; // orden por defecto (coincide con la vista)
            }
            $ordenFinal = $clausulaOrder;
            $tipoFinal = '';
        } else {
            // Caso orden simple: usar los valores originales
            $ordenFinal = $ordenSimple;
            $tipoFinal = $tipoSimple;
        }

        // Lógica de búsqueda ORIGINAL (sin cambios)
        if ($buscar != "") {
            $datos = json_decode($buscar);
            $tamanio = count((array) $datos);
            if ($tamanio > 0) {
                $cont = 0;
                $cond .= " AND  (";
                foreach ($datos as $key => $value) {
                    $cont++;
                    $y = ($cont < $tamanio) ? " LIKE '%$value%' AND " : " LIKE '%$value%' ) ";
                    if ($key == 'Código') {
                        $cond .= "coste.codigotecnico" . $y;
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
                    if ($key == 'Mes') {
                        $cond .= "coste.mes" . $y;
                    }
                    if ($key == 'Año') {
                        $cond .= "coste.anio" . $y;
                    }
                    if ($key == 'Coste') {
                        $cond .= "coste.costehora" . $y;
                    }
                }
            }
        }

        $costesTecnicos = $this->ModelCostesTecnicos->obtenerCostesTecnicosTablaClassBuscar($filas,$ordenFinal,$filaspagina,$tipoFinal,$cond);
        print(json_encode($costesTecnicos));
    }  

    public function totalRegistrosCostesTecnicos()
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
                    if ($key == 'Código') {
                        $cond .= "coste.codigotecnico" . $y;
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
                    
                    if ($key == 'Mes') {
                        $cond .= "coste.mes" . $y;
                    }
                    if ($key == 'Año') {
                        $cond .= "coste.anio" . $y;
                    }
                    if ($key == 'Coste') {
                        $cond .= "coste.costehora" . $y;
                    }                   

                }                                    
    
            }        
        }        
        $contador = $this->ModelCostesTecnicos->totalRegistrosCostesTecnicosBuscar($cond);

        $cont = $contador->contador;        
        print_r($cont);
    }

    public function registrarCosteTecnico()
    {         

        if (isset($_POST['idTecnicoCrear']) && $_POST['idTecnicoCrear'] >0 && isset($_POST['mesInicio']) && $_POST['mesInicio'] >0 && isset($_POST['anioInicio']) && $_POST['anioInicio'] >0 && isset($_POST['costeHoras']) && $_POST['costeHoras'] >0 && isset($_POST['mesFin']) && $_POST['mesFin'] >0 && isset($_POST['anioFin']) && $_POST['anioFin'] >0 ) {                   
            
            $mesInicio = $_POST['mesInicio'];
            $anioInicio = $_POST['anioInicio'];
            $mesFin = $_POST['mesFin'];
            $anioFin = $_POST['anioFin'];
              
            $idTecnico = $_POST['idTecnicoCrear'];
            $codigotecnico = $this->ModelCostesTecnicos->codigoTecnicoPorIdUsuario($idTecnico);

            //años iguales 
            if ($anioInicio == $anioFin) {
                
                for ($i=$mesInicio; $i <= $mesFin ; $i++) {                   
            
                    date_default_timezone_set("Europe/Madrid");

                    $datos = [              
                        "idtecnico" => $idTecnico,
                        "codigotecnico" => $codigotecnico,
                        "costehora" => $_POST['costeHoras'],
                        "mes" => $i,
                        "anio" => $anioInicio,
                        "creacion" => date('Y-m-d')
                    ];          
                    $this->ModelCostesTecnicos->borraCosteAsignadoMes($datos);                    
                    $this->ModelCostesTecnicos->insertarNuevoCosteTecnico($datos);                                        
                }              
            }else if ($anioFin > $anioInicio){ //años diferentes

                for ($i=$mesInicio; $i <= 12 ; $i++) {                   

                    date_default_timezone_set("Europe/Madrid");

                    $datos = [              
                        "idtecnico" => $idTecnico,
                        "codigotecnico" => $codigotecnico,
                        "costehora" => $_POST['costeHoras'],
                        "mes" => $i,
                        "anio" => $anioInicio,
                        "creacion" => date('Y-m-d')
                    ];          
                    $this->ModelCostesTecnicos->borraCosteAsignadoMes($datos);  
                    $this->ModelCostesTecnicos->insertarNuevoCosteTecnico($datos);
                }

                for ($j=1; $j <= $mesFin ; $j++) {                   

                    date_default_timezone_set("Europe/Madrid");

                    $datos = [              
                        "idtecnico" => $idTecnico,
                        "codigotecnico" => $codigotecnico,
                        "costehora" => $_POST['costeHoras'],
                        "mes" => $j,
                        "anio" => $anioFin,
                        "creacion" => date('Y-m-d')
                    ];          
                    $this->ModelCostesTecnicos->borraCosteAsignadoMes($datos);  
                    $this->ModelCostesTecnicos->insertarNuevoCosteTecnico($datos);
                }

            }else{
                $_SESSION['message'] = 'El año final no puede ser mayor al inicial.';
            }

        
            $_SESSION['message'] = 'Se han registrado los costes hora del técnico corréctamente.';

        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se pueden registrar los costes hora del técnico porque falta completar datos en el formulario.';
        }
        redireccionar('/CostesTecnicos');

    
    }


    public function editarCosteTecnico()
    {        
        $datos = [];

        if ($_POST['idCoste'] && $_POST['idCoste'] >0) {                
            
            $coste = $this->ModelCostesTecnicos->obtenerDatosDetalleCoste($_POST['idCoste']);
            $datos = [
                'coste' => $coste
            ];
                                
        }       
        print(json_encode($datos));  
    }

    public function actualizarCosteTecnico()
    {     
        
        
        if ($_POST['idEditCoste'] && $_POST['idEditCoste'] >0 && isset($_POST['costeHorasEditar']) && $_POST['costeHorasEditar'] >0 ) {

            $upd = $this->ModelCostesTecnicos->actualizarDatosCosteTecnico($_POST['idEditCoste'],$_POST['costeHorasEditar']);

            if ($upd && $upd >0) {                            
                    $_SESSION['message'] = 'Se ha actualizado el registro corréctamente.';
            }else{
                    $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';                
            }


        }else{            
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede actualizar el registro porque falta completar datos en el formulario.';
        }
        redireccionar('/CostesTecnicos');
        
    }

    public function eliminarCosteTecnico()
    {      
        if(isset($_POST['idCosteDel']) && $_POST['idCosteDel'] >0){
            
            $del = $this->ModelCostesTecnicos->eliminarCosteTecnico($_POST['idCosteDel']);
            if ($del) {
                $_SESSION['message'] = 'Se ha eliminado el registro corréctamente.';
            }else{
                $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
            }
        }else{
            $_SESSION['message'] = 'Ha ocurrido un error. No se puede eliminar el registro.';
        }
        redireccionar('/CostesTecnicos');
                
    }
    

   
}