<?php require_once(RUTA_APP . '/views/includes/header-tailwind.php'); ?>
<?php require_once(RUTA_APP . '/views/includes/navbar-tailwind.php'); ?>
<?php require_once(RUTA_APP . '/views/includes/sidebar-tailwind.php'); ?>


    <div class="w-full overflow-auto border-t flex flex-col">
      
        <main class="w-full flex-grow p-6">        

          <!-- ****** AQUI DENTRO EL CONTENIDO DE CADA PAGINA ****** -->        
                    
          <!-- ****** INICIO DEL LISTADO DE FACTURAS ****** -->
          <div class="container mx-auto px-1 xl:px-2">
            <h2 class="text-2xl font-semibold leading-tight flex-1 mr-2">Listado de facturas - Clientes</h2>

            <div class="inline-flex my-2">                                            
                <a href="<?php echo RUTA_URL;  ?>/FacturasCliente/crearFactura" id="nuevaFactura" class='w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl font-medium text-white px-4 py-2'><i class="fas fa-plus mr-2 text-base"></i> Nueva factura</a>
            </div> 

            <!-- ****** ALERTAS DE ERROR ****** -->
              
            <?php                              
          
                    //control de mensajes de error o éxito:
                    $color ='';
                    if(isset($_SESSION['message'])){
                      if( strpos( $_SESSION['message'], 'corréctamente' ) != false ){
                        $color = 'bg-green-700';
                      }else{
                        $color = 'bg-blue-700';
                      }
            ?>
            <div class="text-white px-6 py-4 border-0 rounded relative mb-4 <?php echo $color;?>">
                    <span class="text-xl inline-block mr-5 align-middle">
                    <i class="fas fa-bell"></i>
                    </span>
                    <span class="inline-block align-middle mr-8">
                      <b><?php echo $_SESSION['message'];?></b>
                    </span>
                    <button class="butonCerrarAlerta absolute bg-transparent text-2xl font-semibold leading-none right-0 top-0 mt-4 mr-6 outline-none focus:outline-none">
                      <span>×</span>
                    </button>
            </div>
            <?php
                        unset($_SESSION['message']);
                      }
            ?>

            <div class="py-4" id="contenedorListadoAdmin">                

                  <?php //LISTADO DE FACTURAS POR CADA TÉCNICO ?>
                                              
                    <?php //MONTAJE DEL BUSCADOR QUE VIENE DE LA CLASE JS TABLACLASS ?>
                    <div class="my-2 flex sm:flex-row flex-col">
                        <div class="flex flex-row mb-1 sm:mb-0">
                            <div class="relative flex" id="buscador">                            
                            </div>                          
                        </div>                      
                    </div>

                    <?php //MONTAJE DE LA TABLA QUE VIENE DE LA CLASE JS TABLACLASS ?>
                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full shadow rounded-lg overflow-hidden">
                            <div id="destinofacturasclientetodasajax"></div>
                        </div>
                    </div>        

                    <?php //MONTAJE DEL PAGINADOR QUE VIENE DE LA CLASE JS TABLACLASS ?>
                    <div id="paginador"></div>
                    <!-- ========= End  ajax puro =================== -->            
                    <script  type="module">

                    import arrancar from "<?php print RUTA_URL;  ?>/public/js/tablaClass/tablaClass.js" 
                    arrancar("tablafacturascliente","FacturasCliente/crearTablaFacturasAdmin", "destinofacturasclientetodasajax", "fac.fecha DESC, fac.numerointerno DESC", "DESC", 0, "buscador","FacturasCliente/totalRegistrosFacturasAdmin", [10, 20, 30],"min-w-full leading-normal","paginador",["ver","cambiarestadofactura","pdffacturafila","eliminar"],"<?php echo RUTA_URL.'/FacturasCliente/verFactura';?>","");

                    </script>
            </div>
            
          </div>    
          <!-- ****** FIN DEL LISTADO DE FACTURAS ****** -->
        </main>


        

    </div>

    
    
  </div>

</main> <!--Esta etiqueta Main es el fin del sidebar -->

<?php require_once(RUTA_APP . '/views/includes/footer.php'); ?>
<?php require_once(RUTA_APP . '/views/facturasCliente/cambiarEstadoFactura.php'); ?>
<?php require_once(RUTA_APP . '/views/incidencias/modalLoadAjax.php'); ?>
