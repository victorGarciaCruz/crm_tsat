<?php require_once(RUTA_APP . '/views/includes/header-tailwind.php'); ?>
<?php require_once(RUTA_APP . '/views/includes/navbar-tailwind.php'); ?>
<?php require_once(RUTA_APP . '/views/includes/sidebar-tailwind.php'); ?>

<?php

    $idIncidencia = '';
    $title = '';

    if(isset($datos['detalles'])){
        $detalles = $datos['detalles'];

        $idIncidencia = $detalles->id;
        $title = 'Nº '. $idIncidencia;    
        $sucursal = $detalles->id;       
    }

    $imagenes = '';
    if($datos['imagenes'] && count($datos['imagenes'])>0 ){
        $imagenes = $datos['imagenes'];
    }
    $documentos = '';
    if($datos['documentos'] && count($datos['documentos'])>0 ){
        $documentos = $datos['documentos'];
    }         

   
?>

<div class="w-full overflow-x-hidden border-t flex flex-col">
    
      <main class="w-full flex-grow sm:p-0 lg:p-6">
        
        <!-- ****** AQUI DENTRO EL CONTENIDO DE CADA PAGINA ****** -->
              
            <div class="flex items-center justify-center  mt-8">
              

              <div class="grid bg-white rounded-lg shadow-xl w-11/12">     
              
                      
                <div class="flex items-center justify-center border-b border-solid border-blueGray-200">  
                    <h2 class="text-base lg:text-lg font-semibold leading-tight mr-2 my-2">Ver solicitud <?php echo $title; ?></h2>
                </div>
              
                <form method="POST" action="<?php echo RUTA_URL; ?>/Incidencias/registrarIncidencia" id="formVerIncidencia">

                    <input type="hidden" id="idIncidenciaVer" name="idIncidenciaVer" value="<?php echo $idIncidencia;?>" >
                    <input type="hidden" id="idEquipo" name="idEquipo" value="<?php echo $detalles->idequipo;?>" >                    
                    

                    <div class="grid grid-cols-1 gap-5 md:gap-8 mt-5 gap-5 mx-3">                                                        
                            <div class="flex justify-end">      
                                <!-- <a id="exportpdpf" class="mr-2 pdffila text-red-500">
                                    <i class="fa fa-file-pdf cursor-pointer" style="font-size: 1.5rem;"></i>
                                </a> -->

                                <a id="exportpdpf" class="pdffila w-auto bg-white-300 border-2 border-red-300 hover:bg-red-500 rounded-lg shadow-xl font-medium text-xs text-red-500 hover:text-white px-4 py-1 mr-3 flex gap-2 items-center  cursor-pointer">
                                    <i class="fa fa-file-pdf" style="font-size: 1.25rem;"></i><span>PDF</span>
                                </a>

                                <a class="w-auto bg-white-300 border-2 border-gray-300 hover:bg-gray-500 rounded-lg shadow-xl font-medium text-xs text-gray-500 hover:text-white px-4 py-1 mr-3 flex gap-2 items-center"  id="enviar_email_parte" style="cursor:pointer;"><i class="fa fa-envelope" style="font-size: 1.25rem;"></i><span>EMAIL</span></a>

                                <a href="<?php echo RUTA_URL; ?>/Incidencias" class="w-auto bg-gray-300 border-2 border-gray-300 hover:bg-white rounded-lg shadow-xl font-medium text-xs text-white-500 hover:text-gray-700 rounded-lg  px-4 py-1 mr-3 flex gap-2 items-center"><span>CERRAR</span></a>                                

                            </div>                        
                    </div>
                    

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-8 mt-5 mx-3">

                        <div class="grid grid-cols-1">
                            <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Fecha registro</label>
                            <input name="creacion" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->fecha;?>" readonly />
                        </div>

                        <div class="grid grid-cols-1">
                            <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Solicitante</label>
                            <input name="solicitante" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->nombreusuario;?>" readonly />
                        </div>

                        
                        <div class="grid grid-cols-1 md:grid-cols-1 lg:col-span-2  ">
                            <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Cliente</label>
                            <!-- <input id="cliente_editar" name="nom_cliente" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-pink-700 focus:border-transparent" value="<?php //echo $detalles->nombrecliente;?>" readonly /> -->
                            <div class="cont_select_dinamic">
                                <select id="cliente_editar" name="nom_cliente" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-pink-700 focus:border-transparent">
                                    <option value="<?php echo $detalles->idcliente; ?>" selected><?php echo $detalles->nombrecliente; ?></option>
                                </select>
                            </div>
                        </div>
                    
                                             
                    </div>

                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-8 mt-2 mx-3">                     
                            
                            <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Sucursal</label>

                                <?php 
                                    if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) {
                                ?>

                                    <select id="sucursalEdit" name="sucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">
                                        <?php
                                            echo'<option value="" disabled selected>Seleccionar</option>';
                                            if(!empty($datos['sucursales'])){
                                                
                                                foreach ($datos['sucursales'] as $s) {
                                                    $idSucursalSelected = ($s->id==$detalles->sucursal)? 'selected': '';
                                                    echo'<option value="'.$s->id.'" '.$idSucursalSelected.'>'.$s->nombre.'</option>';
                                                }
                                            }

                                        ?>
                                    </select>

                                <?php
                                    }else{
                                ?>
                                
                                    <input id="sucursal" name="sucursal" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->nombresucursal;?>" readonly />

                                <?php
                                    }
                                ?>                                

                            </div>        



                            <?php 
                                if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) {
                            ?>
                            
                                <div class="grid grid-cols-1" id="contenedorEquiposEdit">
                                   
                                    <div class="inline-flex">

                                        <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Equipo implicado</label>
                                        <?php                                    
                                            if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) { 
                                                echo'<i class="ml-2 fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm cursor-pointer edit_field" data-field="equiposTecnico"></i>';    
                                            }                                    
                                        ?>

                                    </div>

                                    <select id="equiposTecnico" name="equiposTecnico" class="todos py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent">
                                        
                                        <?php                                            
                                            //echo'<option value="" disabled selected>Seleccionar</option>';
                                            if(!empty($datos['equipos'])){                                                
                                                foreach ($datos['equipos'] as $eq) {
                                                    $idEquipoSelected = ($eq->id==$detalles->idequipo)? 'selected': '';
                                                    echo'<option value="'.$eq->id.'" '.$idEquipoSelected.'>'.$eq->nombre.'</option>';
                                                }
                                            }
                                        ?>

                                        <!-- <option value="<?php //echo $detalles->idequipo;?>" selected><?php //echo $detalles->nombreequipo;?></option> -->
                                    </select>
                                </div> 
                            
                            <?php
                                }else{
                            ?>

                                <div class="grid grid-cols-1">
                                    <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Equipo</label>
                                    <input id="equipo" name="equipo" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->nombreequipo;?>" readonly />
                                </div>           


                            <?php
                                }
                            ?>
                            
    



                            <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Estado</label>
                                <input name="estado" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->nombreestado;?>" readonly />
                            </div>

                            <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Técn. Asig.</label>
                                <input name="tecnicos" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->nombrestecnicos;?>" readonly />
                            </div>
                            <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Marca</label>
                                <input name="marca" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->marca;?>" readonly />
                            </div>

                            <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Serie</label>
                                <input name="serie" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->serie;?>" readonly />
                            </div>                      
                            
                            <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">IP ficha equipo</label>
                                <input name="ip" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->ipficha;?>" readonly />
                            </div>   
                            <?php
                                if ($detalles->ipficha != $detalles->ipincidencia) {
                            ?>
                              
                              <div class="grid grid-cols-1">
                                <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">IP Incidencia</label>
                                <input class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo $detalles->ipincidencia;?>" readonly />
                            </div>
                            <?php
                                }
                            ?>    
                             
                            <div class="grid grid-cols-1">
                                <div class="inline-flex">
                                    <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Fecha/Hora</label>
                                    <?php                                    
                                        if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) { 
                                            echo'<i class="ml-2 fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm cursor-pointer edit_field" data-field="fechahora"></i>';    
                                        }                                        
                                    ?>                           
                                </div>
                                <input type="datetime-local" name="fechahora" class="py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" value="<?php echo !empty($detalles->fechahora) ? $detalles->fechahora : null; ?>" />
                            </div>   


                    </div>

                    <div class="grid grid-cols-1 gap-5 md:gap-8 mt-5 mx-3">
                        
                            <div>             

                                <div class="inline-flex">
                                    <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Descripción de la solicitud</label>
                                    <?php
                                    
                                        if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) { 
                                            echo'<i class="ml-2 fas fa-user-edit mr-1 fill-current text-yellow-500 text-sm cursor-pointer edit_field" data-field="descripcion"></i>';    
                                        }
                                        

                                    ?>
                                </div>
                                <div>                                   
                                    <div class='mb-8 mt-4'>                                     
                                        <textarea name="descripcion" class="w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text" ><?php echo $detalles->descripcion;?></textarea>                                       
                                    </div>
                                </div>        
                                
                                <?php                                   
                                    //$verFacPpto = $datos['verFacPpto'];
                                    $optionEstados = $datos['optionEstados'];                                                                    
                                    
                                    if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) {                      
                                ?>
                                
                                
                                <!--apartado Facturar presupuestar-->
                                <div id="contenedorFacturarPresupuestarEdit">
                                                              
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 md:gap-8 mt-5">
                
                                        <div class="grid grid-cols-1">
                                            <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Facturar / Presupuestar</label>      
                                            <div class="flex">          
                                                <select name="estadoFactPptoEdit" id="estadoFactPptoEdit" class="py-1 px-3 mr-4 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" style="width: 50%;">                   
                                                    <?php echo $optionEstados;?>
                                                </select>
                                                <a id='enviarSolicitudPppto' class='w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg text-sm 2xl:text-base text-white px-2 py-2 mt-1 mr-3' style="height: 90%;"><i class='far fa-share-square'></i> Enviar </a>
                                            </div>
                                        </div>                
                                    </div>

                                    <div class="grid grid-cols-1 gap-5 md:gap-8 my-5">
                                        <div class="grid grid-cols-1">
                                            <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Comentario para facturador</label>              
                                            <textarea name="comentarioParaFacturadorEdit" id="comentarioParaFacturadorEdit" maxlength="1000" class="w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" placeholder="Ingrese un comentario para el Dpto. de facturación (opcional)" type="text"></textarea>
                                        </div> 
                                    </div>  

                                </div>                  
                                
                                <?php require_once(RUTA_APP . '/views/incidencias/apartadoFactura.php'); ?>

                                <a id='verHistorialFactPpto' class='w-auto bg-gray-400 hover:bg-gray-600 rounded-lg text-sm 2xl:text-base text-white px-2 py-2 mt-1 mr-3'><i class="fas fa-sort-down mr-2"></i> Ver historial </a>
                                <br>
                                
                                <div id="contenedorHistorialCambiosDeEstado" class="my-4"  style="display: none;">
                                
                                    <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold mb-4">Historial estados facturar/presupuestar</label>
                                    <?php 
                                    if(isset($datos['historialEstados']) && count($datos['historialEstados'])>0)
                                    foreach ($datos['historialEstados'] as $key) {
                                        echo"
                                        <div class='w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent'>El <span>".$key->fecha."</span> <span class='italic'>".$key->remitente." </span> cambió el estado a <span class='italic'> ".$key->estado." </span> y comentó: <span class='italic'>".$key->comentario." </span> </div>";
                                    }
                                    ?>
                                </div>

                                <?php } ?>
                                <!--fin-->



                                <!--apartado para que el cliente pueda solicitar un presupuesto -->   
                                <div id="contenedorClienteSolictarPpto" class="my-4">

                                    <?php                             
                                        if ($datos['tienePptos'] == 0) {                                    
                                            if ($_SESSION['nombrerol'] == 'cliente') {
                                    ?>
                                                <div class="grid grid-cols-1 lg:grid-cols-3 2xl:grid-cols-4 gap-5 md:gap-8 my-5">
                                                    <div class="grid grid-cols-1">
                                                        <label class="inline-flex items-center">
                                                            <input type="checkbox" class="form-checkbox h-4 w-4" name="marcarPresupuestar" id="marcarPresupuestar" value="1">
                                                            <span class="ml-3 uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Solicitar un presupuesto</span>
                                                            <a id='clienteSolicitarPresupuesto' class='w-auto bg-pink-600 hover:bg-blue-700 rounded-lg shadow-xl text-sm 2xl:text-base text-white px-2 py-1 ml-3'><i class='far fa-share-square'></i> Enviar </a>   
                                                        </label>
                                                    </div>
                                                
                                                </div>

                                                <div class="grid grid-cols-1 gap-5 md:gap-8 my-5">
                                                    <div class="grid grid-cols-1">                                                                 
                                                        <textarea name="comentarioParaPresupuesto" id="comentarioParaPresupuesto" maxlength="1000" class="w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" placeholder="Agregue una observación o comentario para el presupuesto" type="text"></textarea>
                                                    </div> 
                                                </div>
                                    <?php
                                            }

                                        }else if($datos['tienePptos'] > 0){
                                            foreach ($datos['presupuestos'] as $ppto) {
                                                echo"
                                                <label class='flex-1 uppercase text-sm xl:text-base text-gray-500 text-light font-semibold'>PRESUPUESTO SOLICITADO POR EL CLIENTE</label><div class='w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent'>Presupuesto solicitado por el usuario <span class='italic'> ".$ppto->nombreusuario." </span> con fecha <span class='italic'> ".$ppto->creacion." </span>. Comentario: <span class='italic'> ".$ppto->comentario." </span>  </div>";
                                            }
                                    
                                        }
                                    ?>

                                </div>



                                <div class="grid grid-cols-1" style="height: fit-content;">

                                    <div class="inline-flex" >
                                        <!-- <label class="flex-1 uppercase text-sm xl:text-base text-gray-500 text-light font-semibold">Imágenes</label>  -->                                                                               
                                    </div>                                       
                                    <div>                                              
                                        <?php
                                            if ($imagenes && $imagenes !='') {
                                                $imgBase64 = $imagenes[0]->base;
                                                echo"
                                                <div class='w-full mb-8 overflow-hidden rounded-lg shadow-lg mt-4'>
                                                    <div class='w-full overflow-x-auto'>";
                                                    echo "<div class='flex items-center justify-center' id='contenedorImagen'><img class='imgIncidencia' id='imagenIncidencia' style='width: 100%;' src='".$imgBase64."' /></div>
                                                    ";

                                                    $numeros = "<div class='my-2 flex items-center justify-center'>";
                                                    $cont = 0;
                                                    foreach ($imagenes as $key) {
                                                        $cont++;
                                                        $numeros .= "<button class='verImagen w-auto bg-violeta-claro hover:bg-gray-700 rounded-lg shadow-xl text-sm xl:text-base text-white px-2 py-0 mr-3' data-idfichero='".$key->id."'>".$cont."</button>";                 
                                                    }
                                                    $numeros .= '</div>';

                                                    echo $numeros;
                                                    
                                                    echo"
                                                    </div>
                                                </div>
                                                ";                                                                                               
                                            }                 

                                        ?>
                                    </div>

                                    
                                    <br><br>


                                    <!--Inicio Subir ficheros-->
                                    <div class="inline-flex" >
                                        <label class="flex-1 uppercase text-sm xl:text-base text-gray-500 text-light font-semibold mb-3">Ficheros adjuntos</label>                                                                                
                                    </div>   
                                    
                                    <div>                                
                                        <div class="inline-flex">                                                                                    
                                            <a id="desplegar" class="w-auto bg-gray-400 hover:bg-gray-500 rounded-lg shadow-xl text-sm 2xl:text-base text-white py-1 px-2 flex items-center justify-center"><i class="far fa-image mr-2 text-base"></i>Agregar ficheros</a>
                                        </div>                               

                                        <div id="formularioSubirFicheroIncidencia" style="display:none;" class="grid grid-cols-1 mt-4 rounded-lg border-2 border-coolGray-300 p-2">
                                            <p>Tamaño máximo de cada archivo = 6MB</p>
                                            <span id="msgValidaFichero" class="text-xs lg:text-sm xl:text-base font-bold text-pink-600"></span> 
                                            <input type="file" class="py-2 sm:w-full rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent inputFichero fichero-input" name="ficheroEditarIncidencia[]" id="ficheroEditarIncidencia[]" multiple="" placeholder="Adjunte fichero">
                                            <div class="inline-flex mt-1">
                                                <a id="agregarFicheroIncidenciaEdit" class="w-auto bg-violeta-oscuro hover:bg-blue-700  rounded-lg shadow-xl text-sm 2xl:text-base text-white py-1 px-2 flex items-center justify-center"> 
                                                    <i class="fas fa-times mx-2 text-base"></i>Guardar
                                                </a>
                                            </div>
                                        </div>

                                    </div>
                                    <!--Inicio Subir ficheros-->
                                    
                                    <br>

                                    <div id="container_ficheros_editar">                                              
                                        <?php
                                            if ($documentos && $documentos !='') {                                                
                                                    foreach ($documentos as $doc) {                                                        
                                                        echo'
                                                        <p id="contenedor_fichero_'.$doc->id.'">
                                                            <a href="'.RUTA_URL.'/public/documentos/Incidencias/'.$doc->nombre.'" target="_blank" class="texto-violeta-oscuro text-sm xl:text-base"><span class="font-semibold">'.$doc->nombre.'</span> <i class="fas fa-download ml-2 "></i>
                                                            </a>

                                                            <button class="ml-1 right-2 text-red-500 hover:text-red-700 focus:outline-none eliminarFicheroInc" title="Eliminar fichero" data-idfichero="'.$doc->id.'"> <i class="fas fa-trash-alt"></i></button>
                                                        </p>
                                                        ';                                                        
                                                    }                                                                                                                                               
                                            }
                                        ?>
                                    </div>


                                </div>

                                <br><br>

                                <div class="grid grid-cols-1" id="comentariosAgregados">                                    

                                    <div class="mr-2">
                                        <div class="inline-flex my-3">   
                                            <label class="uppercase text-sm xl:text-base text-gray-500 text-light font-semibold lg:mt-1 mr-3">Comentarios</label>
                                            
                                            <?php
                                            
                                            if ( $_SESSION['nombrerol']=='tecnico' || $_SESSION['nombrerol']=='admin' ) {
                                                echo"
                                                <a id='addComentario' class='w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl text-sm 2xl:text-base text-white px-2 py-1 mr-3'><i class='far fa-comments'></i> Agregar </a>";
                                            
                                            }else if ($_SESSION['nombrerol']=='cliente' && ($detalles->nombreestado == 'en curso' || $detalles->nombreestado == 'pendiente') ){
                                                echo"
                                                <a id='addComentarioCliente' class='w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl text-sm 2xl:text-base text-white px-2 py-1 mr-3'><i class='far fa-comments'></i> Agregar </a>";
                                            }
                                            
                                            ?>
                 
                                        </div>
                                    </div>

                                    <div class="container rounded-lg border-2 border-coolGray-300">
                                        <div class="flex flex-col md:grid grid-cols-12 text-gray-50" id="contenedorComentarios">
                                            <?php
                                                if ($datos['comentarios'] && count($datos['comentarios']) >0 ) {

                                                    $comentarios = $datos['comentarios'];
                                                
                                                    foreach ($comentarios as $comentario) {

                                                        $clase = '';

                                                        if ($comentario->rol == 'cliente') {
                                                            $clase = 'bg-tex-lila';
                                                        }else if ( ($comentario->rol == 'tecnico' || $comentario->rol == 'admin') && $comentario->tipo=='interno' ) {
                                                            $clase = 'bg-red-400';
                                                        }else{
                                                            $clase = 'bg-gray-comentario';
                                                        }

                                                        if ($_SESSION['nombrerol']=='cliente' && $comentario->tipo =='externo') {
                                                            echo'
                                                                        
                                                            <div class="flex md:contents">
                                                                <div class="col-start-2 col-end-4 mr-10 md:mx-auto relative">
                                                                    <div class="h-full w-6 flex items-center justify-center">
                                                                    <div class="h-full w-1 '.$clase.' pointer-events-none"></div>
                                                                    </div>
                                                                    <div class="w-6 h-6 absolute top-1/2 -mt-3 rounded-full '.$clase.' shadow text-center">
                                                                    <i class="fas fa-check-circle text-white"></i>
                                                                    </div>
                                                                </div>

                                                                <div class="col-start-4 col-end-12 ">
                                                                    <div class="'.$clase.' p-4 rounded-xl mt-4 mr-auto shadow-md w-full">
                                                                        <h3 class="font-semibold text-lg mb-1">'.$comentario->comentario.'</h3>
                                                                        <p class="leading-tight text-justify w-full">
                                                                        '.date('d/m/Y H:i',strtotime($comentario->fechacreacion)).'
                                                                        </p>
                                                                        <p class="leading-tight text-justify w-full">
                                                                        '.$comentario->nombreusuario.'
                                                                        </p>';
                                                                        
                                                                            if ($comentario->valoracion && $comentario->valoracion >0) {                                                            
                                                                                echo'                                                            
                                                                                <div class="flex flex-col space-y-5">                          
                                                                                    
                                                                                    <p>';
                                                                                    for ($i=1; $i <= 5 ; $i++) {
                                                                                        $claseEstrella = '';
                                                                                        if ($i <= $comentario->valoracion) {
                                                                                            $claseEstrella = 'text-yellow-400';
                                                                                        }
                                                                                        echo'<label><i class="fas fa-star '.$claseEstrella.'" mx-1></i></label>';
                                                                                    }
                                                                                    echo'                                                                                                                                                                                                                    
                                                                                    </p>
                                                                                </div>';
                                                                            }                                                                    
                                                                        echo'  
                                                                    </div>
                                                                    <div class="container_files_coment">';

                                                                     // Mostrar los ficheros asociados al comentario
                                                                     if (!empty($comentario->ficheros)) {
                                                                        echo '<ul>';
                                                                        foreach ($comentario->ficheros as $fichero) {

                                                                            echo'
                                                                            <p><a href="'.RUTA_URL.'/public/documentos/TrabajosTerminados/'.$fichero->nombre.'" target="_blank" class="texto-violeta-oscuro text-sm xl:text-base"><span class="font-semibold">'.$fichero->nombre.'</span> <i class="fas fa-download ml-2 "></i></a></p>
                                                                            ';    
                                                                        }                                                            
                                                                    }

                                                                    echo'</div>
                                                                </div>


                                                            </div>';
                                                        }else if ($_SESSION['nombrerol']=='tecnico' || $_SESSION['nombrerol']=='admin') {
                                                            echo'
                                                                        
                                                            <div class="flex md:contents" id="comentario_id_'.$comentario->id.'">
                                                                <div class="col-start-2 col-end-4 mr-10 md:mx-auto relative">
                                                                    <div class="h-full w-6 flex items-center justify-center">
                                                                    <div class="h-full w-1 '.$clase.' pointer-events-none"></div>
                                                                    </div>
                                                                    <div class="w-6 h-6 absolute top-1/2 -mt-3 rounded-full '.$clase.' shadow text-center">
                                                                    <i class="fas fa-check-circle text-white"></i>
                                                                    </div>
                                                                </div>

                                                                <div class="col-start-4 col-end-12 ">
                                                                    <div class="'.$clase.' p-4 rounded-xl mt-4 mr-auto shadow-md w-full">

                                                                        <button class="right-2 text-red-500 hover:text-red-700 focus:outline-none eliminarComentario float-right" title="Eliminar comentario" data-idcomentario="'.$comentario->id.'"> <i class="fas fa-trash-alt"></i>
                                                                        </button>

                                                                        <h3 class="font-semibold text-lg mb-1">'.$comentario->comentario.'</h3>
                                                                        <p class="leading-tight text-justify w-full">
                                                                        '.date('d/m/Y H:i',strtotime($comentario->fechacreacion)).'
                                                                        </p>
                                                                        <p class="leading-tight text-justify w-full">
                                                                        '.$comentario->nombreusuario.'
                                                                        </p>';
                                                                        //por el momento solo lo ve el administrador del sistema
                                                                        if ($_SESSION['nombrerol']== 'admin') {
                                                                                                                                                
                                                                            if ($comentario->valoracion && $comentario->valoracion >0) {                                                            
                                                                                echo'                                                            
                                                                                <div class="flex flex-col space-y-5">                          
                                                                                    
                                                                                    <p>';
                                                                                    for ($i=1; $i <= 5 ; $i++) {
                                                                                        $claseEstrella = '';
                                                                                        if ($i <= $comentario->valoracion) {
                                                                                            $claseEstrella = 'text-yellow-400';
                                                                                        }
                                                                                        echo'<label><i class="fas fa-star '.$claseEstrella.'" mx-1></i></label>';
                                                                                    }
                                                                                    echo'                                                                                                                                                                                                                    
                                                                                    </p>
                                                                                </div>';
                                                                            }
                                                                        }
                                                                        echo'  
                                                                    </div>
                                                                    <div class="container_files_coment">';
                                                                    
                                                                    // Mostrar los ficheros asociados al comentario
                                                                    if (!empty($comentario->ficheros)) {
                                                                        echo '<ul>';
                                                                        foreach ($comentario->ficheros as $fichero) {

                                                                            echo'
                                                                            <p><a href="'.RUTA_URL.'/public/documentos/TrabajosTerminados/'.$fichero->nombre.'" target="_blank" class="texto-violeta-oscuro text-sm xl:text-base"><span class="font-semibold">'.$fichero->nombre.'</span> <i class="fas fa-download ml-2 "></i></a></p>
                                                                            ';    
                                                                        }                                                            
                                                                    }

                                                                    echo'</div>
                                                                </div>
                                                            </div>';
                                                        }
                                                    
                                                       
                                                                    
                                                    }
                                                }
                                            ?>
                                        
                                        </div>
                                    
                                    </div>
                                </div>

                                <?php
                                    if (  ($_SESSION['nombrerol'] == 'admin' || $_SESSION['nombrerol'] == 'tecnico') ) {
                                ?>

                                <!-- Inicio apartado firma de parte-->                                
                                <?php
                                    $guardada =0;
                                    if(!empty($detalles->guardada) && $detalles->guardada == 1){
                                        $guardada =1;
                                    }                                   
                                ?>

                                <firma-incidencia
                                    urlGuardarFirma="<?php echo RUTA_URL; ?>/Incidencias/guardarFirma"
                                    idIncidencia="<?php echo $idIncidencia;?>"
                                    firmaGuardada="<?php echo $guardada;?>"
                                    urlFirma="<?php echo $detalles->firma;?>"
                                    mostrarBotonLimpiar="<?php echo ($guardada==1)? 'false': 'true';?>" 
                                    mostrarBotonGuardar="<?php echo ($guardada==1)? 'false': 'true';?>"
                                >
                                </firma-incidencia>                               
                                <!--Fin apartado firma de parte-->
                                
                                <?php
                                    }
                                ?>

                                <br>

                                <!--Inicio apartado emails enviados-->
                                <div id="contenedorHistorialEmailsPartesEnviados" class="my-4">
                                                                    
                                    <?php 
                                    print($datos['emailsEnviados']);
                                    ?>
                                </div>
                                <!--Fin apartado emails enviados-->


                            </div>
                    </div>                   

                    <div class="flex items-center justify-center px-6 py-3 border-t border-solid border-blueGray-200 rounded-b mt-5">
                        <a href="<?php echo RUTA_URL; ?>/Incidencias" class='w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-white px-4 py-2 mr-3'>Cerrar</a>                        
                    </div>

                </form>
            </div>
        </div>



        <?php require_once(RUTA_APP . '/views/incidencias/agregarComentario.php'); ?>
        <?php require_once(RUTA_APP . '/views/incidencias/agregarComentarioDelCliente.php'); ?>       
        <?php require_once(RUTA_APP . '/views/incidencias/modalLoadAjax.php'); ?>   
        <?php require_once(RUTA_APP . '/views/incidencias/seleccionarFactura.php'); ?>   
        
    
        <!-- ****** FIN DEL CONTENIDO DE CADA PAGINA ****** -->
      </main>
  </div>

</div>

</main> <!--Esta etiqueta Main es el fin del sidebar -->

<?php require_once(RUTA_APP . '/views/includes/footer.php'); ?>
<?php require_once(RUTA_APP . '/views/facturasCliente/modalBuscarProducto.php'); ?>
<?php require_once(RUTA_APP . '/views/incidencias/modalEnviarEmailParte.php'); ?>