<?php
    $ver = 'sm:block';
    if ($_SESSION['nombrerol']=='cliente') {
      $ver = 'none';
    }
?>
<aside style="display: <?php echo $ver ;?>;" class="<?php print BG_SIDEBAR; ?> relative w-72 md:w-64 hidden sm:block shadow-xl ">
    <div class="p-4">
        <img class="" src="<?php echo RUTA_URL;  ?>/public/img/logo_telesat.jpg">
    </div>
    <nav class="text-white text-base font-semibold pt-1" id="menu">
           
            <?php foreach($_SESSION['permisos'] as $menu){  ?>
              <div class="m-2">
              <div class="flex items-center active-nav-link text-white opacity-75 hover:opacity-100 py-1 pl-6 nav-item menu-btn">
              <?php print "<span><i class='" . $menu[1] . " mr-3'></i></span>";
              print($menu[2]); ?>
              <span><i class="fas fa-angle-right ml-3"></i></span>
              <?php  if(isset($menu[3])){ ?>
                  </div> 
                  <div class="bg-violeta-claro hidden flex-col rounded ml-6 mt-1 p-1 text-sm w-32 dropdown">
                    <?php for($i=0;$i<count($menu[3]);$i++){ 
                      if($menu[3][$i][1] != "link"){ 
                      
                        $visible = ($menu[3][$i][0] == '/ModalidadesMantto' && EMPRESA === 'INFOMALAGA')? 'display:none':'';                                           
                        ?>
                      <a style="<?php echo $visible;?>" href="<?php echo RUTA_URL . $menu[3][$i][0]; ?>" class="px-3 py-1 hover:<?php print BG_SUBMENU_HOVER; ?>"><?php print($menu[3][$i][1]); ?></a>
                    <?php 
                    } 
                    ?>

                    <?php
                    }; //fin del segundo foreach ?>
                  </div> 
                <?php } else {  ?>
                  </div> 
                <?php  }; // fin del if ?>
                

        </div> <!-- fin del div class m-2 -->
        <?php  }; // fin del primer foreach ?>
    </nav>
</aside>