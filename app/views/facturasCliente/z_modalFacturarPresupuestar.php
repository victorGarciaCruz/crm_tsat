<div class="hidden overflow-x-hidden overflow-y-auto fixed inset-0 z-50 outline-none focus:outline-none justify-center items-center" id="facturar-servicio">
  <div class="relative w-auto my-6 mx-auto max-w-3xl">
    <!--content-->
    <div class="border-0 rounded-lg shadow-lg relative flex flex-col w-full bg-white outline-none focus:outline-none">
      <!--header-->
      <div class="flex items-start justify-between p-5 border-b border-solid border-blueGray-200 rounded-t">
        <h3 class="text-sm 2xl:text-base font-semibold mr-2">
          Cambio de estado de la solicitud
        </h3>
        <button class="ml-auto bg-transparent border-0 text-black opacity-50 float-right text-3xl leading-none font-semibold outline-none focus:outline-none cerrarFacturarPresupuestar" >
          <span class="bg-transparent text-black opacity-1 h-6 w-6 text-2xl block outline-none focus:outline-none">
            ×
          </span>
        </button>
      </div>
      <!--body-->
      <div class="relative p-3 flex-auto">                  
        <input type="hidden" id="idIncidenciaPlay">
        <span id="msgIniciar" class="font-bold font-bold text-pink-600"></span>
        <form class="flex flex-col" id="bodyModalIniciarAtencion">                                      
          <input type="hidden" id="rolUsuarioIniciar" value="<?php echo $_SESSION['nombrerol']; ?>">                       
          <input type="hidden" id="idIncidenciaFact">


            <div class="grid grid-cols-1 gap-5 md:gap-8 my-2 mx-3">
              <div class="grid grid-cols-1">
                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Cambiar estado a:</label>              
                <select name="selectEstadoFactPres" id="selectEstadoFactPres" class="w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" type="text"></select>
              </div> 
            </div>  

            <div class="grid grid-cols-1 gap-5 md:gap-8 my-2 mx-3">
              <div class="grid grid-cols-1">
                <label class="uppercase md:text-sm text-xs text-gray-500 text-light font-semibold">Comentario</label>              
                <textarea name="comentarioDelFacturador" id="comentarioDelFacturador" maxlength="1000" class="w-full py-2 px-3 rounded-lg border-2 border-coolGray-300 mt-1 focus:outline-none focus:ring-2 focus:ring-blue-700 focus:border-transparent" placeholder="Ingrese un comentario (opcional)" type="text"></textarea>
              </div> 
            </div> 

            <div class="gap-5 md:gap-8 my-2 mx-3">              
                <a id='verHistorialFactPpto' class='w-auto bg-gray-400 hover:bg-gray-600 rounded-lg text-sm 2xl:text-base text-white px-2 py-2 mt-1 mr-3'><i class="fas fa-sort-down mr-2"></i> Ver historial </a>              
            </div>

            <div id="contenedorHistorialCambiosDeEstado" class="gap-5 md:gap-8 my-2 mx-3"  style="display: none;">                            
                <div id="contenedorComentarioParaFacturador"></div>
            </div>

        </form>
      </div>
      
      <!--footer-->
      <div class="flex items-center justify-end px-6 py-2 border-t border-solid border-blueGray-200 rounded-b">
        <button class="w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl text-xs lg:text-sm 3xl:text-base text-white px-4 py-1 mr-2 cerrarFacturarPresupuestar">Cerrar</button>
        <button id="facturarPresupuestar" class="w-auto bg-violeta-oscuro hover:bg-blue-700 rounded-lg shadow-xl text-xs lg:text-sm 3xl:text-base text-white px-4 py-1">Guardar</button>


      </div>

    </div>
  </div>
</div>
<div class="hidden opacity-25 fixed inset-0 z-40 bg-black" id="facturar-servicio-backdrop"></div>