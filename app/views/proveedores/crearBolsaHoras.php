<div class="hidden overflow-x-hidden overflow-y-auto fixed inset-0 z-50 outline-none focus:outline-none justify-center items-center" id="crear-BolsaHoras">
  <div class="relative w-auto my-6 mx-auto max-w-3xl">
    <!--content-->
    <div class="border-0 rounded-lg shadow-lg relative flex flex-col w-full bg-white outline-none focus:outline-none">
      <!--header-->
      <div class="flex items-start justify-between p-2 border-b border-solid border-blueGray-200 rounded-t">
        <h3 class="text-xs md:text-sm lg:text-base font-semibold mr-2">
          Configurar horas/mes
        </h3>
        <button class="ml-auto bg-transparent border-0 text-black opacity-50 float-right text-3xl leading-none font-semibold outline-none focus:outline-none cerrarCrearBolsaHoras" >
          <span class="bg-transparent text-black opacity-1 h-6 w-6 text-2xl block outline-none focus:outline-none">
            ×
          </span>
        </button>
      </div>
      <!--body-->
      <div class="relative p-2 flex-auto">      
        
        <span id="msgValidaFormBolsa" class="font-bold font-bold text-blue-600"></span>

        <form class="flex flex-col space-y-5" id="bodyModalCrearBolsaHoras">
        
                <div class="grid grid-cols-3">

                    <div class="flex flex-col grid grid-cols-1 mr-2">
                    
                        <label for="modalidadHoras" class="text-sm font-semibold text-gray-500">Modalidad</label>
                                          
                        <select id="modalidadHorasCrear" name="modalidadHorasCrear" class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required>
                                <option value="horas">horas/mes</option>                     
         
                        </select>
                    </div>

                    <div class="flex flex-col grid grid-cols-1 mr-2">
                        <div class="flex items-center justify-between">
                            <label for="contratadoHorasCrear" class="text-sm font-semibold text-gray-500">Horas contratadas</label>              
                        </div> 
                        <input type="number" step="0.01" id="contratadoHorasCrear" name="contratadoHorasCrear"
                        class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required />
                    </div>

                    <div class="flex flex-col grid grid-cols-1 mr-2">
                        <div class="flex items-center justify-between">
                            <label for="contratadoEurosCrear" class="text-sm font-semibold text-gray-500">Precio bolsa(€)</label>              
                        </div> 
                        <input type="number" step="0.01" id="contratadoEurosCrear" name="contratadoEurosCrear"
                        class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required />
                    </div>
               
                  
                    <div class="flex flex-col grid grid-cols-1 mr-2">
                        <div class="flex items-center justify-between">
                            <label for="mesInicio" class="text-sm font-semibold text-gray-500">Desde</label>              
                        </div>
                        
                        <select id="mesInicio" name="mesInicio" class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                            <option disabled selected>Seleccionar</option>
                               
                            <?php
                      
                            $meses = ["Ene"=>1,"Feb"=>2,"Mar"=>3,"Abr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Ago"=>8,"Set"=>9,"Oct"=>10,"Nov"=>11,"Dic"=>12];
                            
                            foreach ($meses as $mes => $or) {
                                echo'<option value="'.$or.'">'.$mes.'</option>';
                            }
                            ?>
                            
                            </select>                        
                    </div>

                    <div class="flex flex-col grid grid-cols-1 mr-2">
                        <div class="flex items-center justify-between">
                            <label for="anioInicio" class="text-sm font-semibold text-gray-500">&nbsp;</label>              
                        </div>

                        <select id="anioInicio" name="anioInicio" class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                            <option disabled selected>Seleccionar</option>
                           
                              <?php
                                                            
                              if (isset($datos['aniosSelect']) && count($datos['aniosSelect'])>0) {
                                $anioAnterior = ($datos['aniosSelect'][0]->anio)-1;
                                $ultimo = count($datos['aniosSelect']) - 1;
                                $anioPosterior = ($datos['aniosSelect'][$ultimo]->anio)+1;

                                echo'<option value="'.$anioAnterior.'" >'.$anioAnterior.'</option>';                               
                                foreach ($datos['aniosSelect'] as $key) {
                                  echo'<option value="'.$key->anio.'" '.((date('Y')==$key->anio)? 'selected' : '').' >'.$key->anio.'</option>';
                                }
                                echo'<option value="'.$anioPosterior.'" >'.$anioPosterior.'</option>';
                                
                              }else{
                                $anios = [date('Y')-1,date('Y'),date('Y')+1];
                                foreach ($anios as $anio) {
                                    echo'<option value="'.$anio.'" '.((date('Y')==$anio)? 'selected' : '').'>'.$anio.'</option>';
                                } 
                              }                                 
                              ?>
                            </select>     
                    </div>
                    
                    <div class="flex flex-col grid grid-cols-1 mr-2">
                    </div>
                    
                    
                    <div class="flex flex-col grid grid-cols-1 mr-2">
                        <div class="flex items-center justify-between">
                            <label for="mesFin" class="text-sm font-semibold text-gray-500">Hasta</label>              
                        </div>
                        
                        <select id="mesFin" name="mesFin" class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                            <option disabled selected>Seleccionar</option>
                               
                            <?php
                      
                            $meses = ["Ene"=>1,"Feb"=>2,"Mar"=>3,"Abr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Ago"=>8,"Set"=>9,"Oct"=>10,"Nov"=>11,"Dic"=>12];

                            foreach ($meses as $mes => $or) {
                                echo'<option value="'.$or.'">'.$mes.'</option>';
                            }
                            ?>
                            
                            </select>                        
                    </div>

                    <div class="flex flex-col grid grid-cols-1 mr-2">
                        <div class="flex items-center justify-between">
                            <label for="anioFin" class="text-sm font-semibold text-gray-500">&nbsp;</label>              
                        </div>

                        <select id="anioFin" name="anioFin" class="py-1 px-2 text-sm xl:text-base transition duration-300 border border-gray-300 rounded focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-700" required >
                            <option disabled selected>Seleccionar</option>
                           
                              <?php
                                                            
                              if (isset($datos['aniosSelect']) && count($datos['aniosSelect'])>0) {
                                $anioAnterior = ($datos['aniosSelect'][0]->anio)-1;
                                $ultimo = count($datos['aniosSelect']) - 1;
                                $anioPosterior = ($datos['aniosSelect'][$ultimo]->anio)+1;

                                echo'<option value="'.$anioAnterior.'" >'.$anioAnterior.'</option>';                               
                                foreach ($datos['aniosSelect'] as $key) {
                                  echo'<option value="'.$key->anio.'" '.((date('Y')==$key->anio)? 'selected' : '').' >'.$key->anio.'</option>';
                                }
                                echo'<option value="'.$anioPosterior.'" >'.$anioPosterior.'</option>';
                                
                              }else{
                                $anios = [date('Y')-1,date('Y'),date('Y')+1];
                                foreach ($anios as $anio) {
                                    echo'<option value="'.$anio.'" '.((date('Y')==$anio)? 'selected' : '').'>'.$anio.'</option>';
                                } 
                              }                                 
                              ?>
                            </select>     
                    </div>

                    <div class="flex flex-col grid grid-cols-1 mr-2">
                    </div>

                </div>
                

        </form>


      </div>
      
      <!--footer-->
      <div class="flex items-center justify-end p-2 border-t border-solid border-blueGray-200 rounded-b">
        <button class="w-auto bg-gray-500 hover:bg-gray-700 rounded-lg shadow-xl font-medium text-xs md:text-sm text-white px-4 py-1 mr-2 cerrarCrearBolsaHoras">Cerrar</button>
        <button id="crearBolsaHoras" class="w-auto bg-violeta-oscuro hover:bg-blue-900 rounded-lg shadow-xl font-medium text-xs md:text-sm text-white px-4 py-1">Crear</button>


      </div>

    </div>
  </div>
</div>
<div class="hidden opacity-25 fixed inset-0 z-40 bg-black" id="crear-BolsaHoras-backdrop"></div>