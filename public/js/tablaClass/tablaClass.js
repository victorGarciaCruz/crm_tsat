export class creartabla {
    ruta = "";
    destino = "";
    filas = 30;
    pagina = 0;
    campoOrden = "";
    tipoOrden = "";
    campoOrdenInicial = "";
    tipoOrdenInicial = "";
    rutaTotalRegistros = "";
    destinobuscador = "";
    busqueda = "";
    segmento = [];
    contador = 0;
    clasesTabla = "";
    destinoPaginador = "";
    boton = [];
    rutaApp = "";
    id = "";
    anio = "";

    datosActuales = []; // Guardar los 30 registros actuales (ya no se ordenan en memoria)
    // Criterios de orden (array para soportar orden multiple)
    sortCriteria = []; // [{campo: 'Nº', dir: 'ASC'}]



    constructor(ruta, destino, orden, tipoOrden, pagina,destinobuscador, rutaTotalRegistros, segmento,
    clasesTabla, destinoPaginador, boton, rutaApp, id, anio) {

        this.ruta = ruta;
        this.rutaApp = rutaApp;
        this.id = id;
        this.anio = anio;
        this.destino = destino;
        this.pagina = pagina || 0;
        this.rutaTotalRegistros = rutaTotalRegistros;
        this.destinobuscador = destinobuscador;
        this.segmento = segmento;
        this.clasesTabla = clasesTabla;
        this.destinoPaginador = destinoPaginador;
        this.boton = boton;
        this.ordenOriginal = orden;           // string, ej: "inc.estado ASC, inc.creacion DESC"
        this.tipoOrdenOriginal = tipoOrden;   // string, ej: "DESC" (puede ser redundante pero se envía)
        this.sortCriteria = [];  

        // Guardar el orden primigenio (el que viene de la vista)
        this.ordenPrimigenio = [];
        if (orden && tipoOrden) {
            if (orden.includes(',')) {
                const partes = orden.split(',').map(p => p.trim());
                for (let parte of partes) {
                    const tokens = parte.split(' ');
                    const dir = tokens.pop().toUpperCase();
                    const campo = tokens.join(' ');
                    if (campo && (dir === 'ASC' || dir === 'DESC')) {
                        this.ordenPrimigenio.push({ campo, dir });
                    }
                }
            } else {
                this.ordenPrimigenio.push({ campo: orden, dir: tipoOrden });
            }
        }

        // Inicialmente no hay criterios activos del usuario
        this.sortCriteria = [];

        // Llamar al buscador y cargar la tabla con el orden primigenio
        this.buscador(this.destinobuscador, this.segmento);
        this.rendertabla(); // usará ordenPrimigenio porque sortCriteria está vacío
    }

    rendertabla() {
    // Determinar qué orden usar: si hay criterios activos, se usan; si no, el primigenio
    const ordenAUsar = this.sortCriteria.length > 0 ? this.sortCriteria : this.ordenPrimigenio;

        this.tabla(
            this.ruta, this.destino, this.busqueda,
            ordenAUsar, // array de criterios
            this.filas, this.pagina, this.clasesTabla,
            this.boton, this.rutaApp, this.id, this.anio
        );

        this.paginador(
            this.rutaTotalRegistros,
            this.filas, this.pagina,
            this.destinoPaginador,
            this.busqueda,
            this.id, this.anio
        );
    }

    // Establecer criterio de orden (mantiene orden multiple)
    setSortCriterion(campo, dir) {
        if (!campo) return;
        if (dir === 'DEFAULT') {
            this.sortCriteria = this.sortCriteria.filter(c => c.campo !== campo);
        } else {
            const idx = this.sortCriteria.findIndex(c => c.campo === campo);
            if (idx >= 0) {
                this.sortCriteria[idx].dir = dir;
            } else {
                this.sortCriteria.push({ campo, dir });
            }
        }
        this.actualizarIconosEncabezado();
        this.rendertabla();
    }

    // Mantengo utilidades por compatibilidad (puede usarse en el cliente si se necesita)
    _valorComparable(campo, valor) {
        if (valor === null || valor === undefined) return '';
        if (campo === 'Nº') {
            return parseInt(String(valor).replace(/\D/g, '')) || 0;
        }
        if (campo === 'Creación' || campo === 'CREACIÓN') {
            const s = String(valor).trim();
            if (s.indexOf('/') !== -1) {
                const parts = s.split('/');
                if (parts.length === 3) return new Date(parts[2] + '-' + parts[1] + '-' + parts[0]).getTime() || 0;
            }
            const d = new Date(s);
            return d.getTime() || 0;
        }
        return String(valor).toLowerCase();
    }

    actualizarIconosEncabezado() {
        const cont = document.getElementById(this.destino);
        if (!cont) return;
        const ths = cont.querySelectorAll('.titulos');
        ths.forEach(th => {
            const campo = th.getAttribute('data-campo');
            const btn = th.querySelector('.sort-toggle');
            if (!btn) return;
            const criterio = this.sortCriteria.find(c => c.campo === campo);
            const estado = criterio ? criterio.dir : 'DEFAULT';
            const iconMap = { 'DEFAULT': '<i class="fas fa-sort"></i>', 'ASC': '<i class="fas fa-sort-amount-up-alt"></i>', 'DESC': '<i class="fas fa-sort-amount-down-alt"></i>' };
            btn.setAttribute('data-state', estado);
            btn.innerHTML = iconMap[estado] || '<i class="fas fa-sort"></i>';
        });
    }

    tabla(ruta, destino, busqueda, activos, filas, pagina, clases, boton, rutaApp, id, anio) {
        var xhr = new XMLHttpRequest();
        let self = this;
        xhr.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                let datos = [];
                if (this.responseText != "") datos = JSON.parse(this.responseText);

                // Guardar los datos actuales (no se ordenan en memoria)
                self.datosActuales = datos;

                // Parsear busqueda para rellenar inputs
                let busquedaObj = {};
                try {
                    if (busqueda && typeof busqueda === 'string' && busqueda.trim() !== '') {
                        busquedaObj = JSON.parse(busqueda);
                    }
                } catch (e) { busquedaObj = {}; }

                var contenido = "";
                for (var i = 0; i < datos.length; i++) {
                    var titulos = Object.keys(datos[i]);
                    contenido += `\n                                <tr class="rows">`;
                    for (var j in titulos) {
                        if (titulos[j] == 'Estado') {
                            var claseTd = "";
                            var nombreEstado = '';
                            if (datos[i][titulos[j]] == 'pendiente') {
                                nombreEstado = 'pendiente'; claseTd = 'font-bold text-white bg-red-600 text-center border rounded-lg p-1';
                            } else if (datos[i][titulos[j]] == 'en curso') {
                                nombreEstado = 'en curso'; claseTd = 'font-bold text-white bg-yellow-400 text-center border rounded-lg p-1';
                            } else if (datos[i][titulos[j]] == 'terminada' || datos[i][titulos[j]] == 'terminadasinvalorar') {
                                nombreEstado = 'terminada'; claseTd = 'font-bold text-white bg-green-600 text-center border rounded-lg p-1';
                            }
                            contenido += `\n                                        <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm"><div class="${claseTd}">${nombreEstado}</div></td>`;
                        } else if (titulos[j] == 'Fact/Ppto') {
                            let claseTdFact = ""; let nombreEstado = datos[i][titulos[j]]; let colortexto = '';
                            if (['facturar','presupuestar','aceptado','presupuestado','facturado','rechazado','FParc'].indexOf(datos[i][titulos[j]]) >= 0) {
                                colortexto = 'text-white'; claseTdFact = ` ${datos[i][titulos[j]]} border rounded-lg p-1`;
                            } else if (datos[i][titulos[j]] == 'sin estado') nombreEstado = '';
                            contenido += `\n                                    <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm">\n                                        <div class="${claseTdFact} text-center clickestado font-bold ${colortexto}" data-tipo=${datos[i][titulos[j]]}>${nombreEstado}</div>\n                                    </td>`;
                        } else if (titulos[j] == 'Atención' || titulos[j] == 'verhorascliente' || titulos[j] == 'idTecnico' || titulos[j] == 'idusuario' || titulos[j] == 'pktabla') {
                            contenido += `\n                                    <td style="display:none" class="px-1 py-2 border-b border-gray-200 bg-white text-sm">${datos[i][titulos[j]] || ''}</td>`;
                        } else {
                            contenido += `\n                                    <td class="px-1 py-2 border-b border-gray-200 bg-white text-xs 3xl:text-sm text-center">${datos[i][titulos[j]]}</td>`;
                        }
                    }

                    if (boton != "") {
                        contenido += `\n                                    <td class="botones px-2 py-2 border-b border-gray-200 bg-white text-sm"><div class="flex">`;
                        for (let x = 0; x < boton.length; x++) {
                            if (boton[x] == 'pdffacturafila') contenido += `<a class="mr-1 pdffila text-gray-500 cursor-pointer" title="PDF" data-index="${datos[i]['Nº']}"><i class="fa fa-file-pdf" style="font-size: 1.25rem;"></i></a>`;
                            if (boton[x] == 'editar') contenido += `<a href="" class="mr-1 editar" title="Editar"><i class="fas fa-edit mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></a>`;
                            if (boton[x] == 'ver') contenido += `\n                                    <div class=""><form action="${rutaApp}" method="POST" title="ver">\n                                    <input type="number" class="hidden" name="id" value="${datos[i]['Nº']}">\n                                    <button type="submit" class="btnActualizar"><i class="fas fa-eye mr-1 fill-current text-yellow-500 text-sm lg:text-xl"></i></button>\n                                    </form></div>`;
                            if (boton[x] == 'eliminar') contenido += `<a class="mr-1 eliminar cursor-pointer" title="Eliminar" data-eliminar="${datos[i]['Nº']}"><i class="fas fa-trash-alt mr-1 fill-current text-red-600 text-sm lg:text-xl"></i></a>`;
                            if (boton[x] == 'validar') {
                                var verValidar = (datos[i]['Estado'] == 'terminadasinvalorar') ? 'block' : 'none';
                                contenido += `<a href="" class="mr-1 validar" title="Validar"><i class="fas fa-thumbs-up mr-1 fill-current text-blue-600 text-sm lg:text-xl" style="display:${verValidar}"></i></a>`;
                            }
                            if (boton[x] == 'terminar') {
                                var verTerminar = (datos[i]['Estado'] == 'terminada' || datos[i]['Estado'] == 'terminadasinvalorar') ? 'none' : 'block';
                                contenido += `<a href="" class="mr-1 terminar" title="Terminar"><i class="fas fa-calendar-check mr-1 fill-current text-blue-600 text-sm lg:text-xl" style="display:${verTerminar}"></i></a>`;
                            }
                            if (boton[x] == 'comentario') contenido += `<a href="" class="mr-1 comentario" title="Comentarios"><i class="fas fa-comment-dots mr-1 fill-current text-red-600 text-sm lg:text-xl"></i></a>`;
                            if (boton[x] == 'modificar') contenido += `<a href="" class="mr-1 modificar" title="Modificar"><i class="fas fa-shopping-cart mr-1 fill-current text-red-700 text-sm lg:text-xl"></i></a>`;
                            if (boton[x] == 'historial') contenido += `<a href="" class="mr-1 historial" title="ver historial"><i class="fas fa-history mr-1 fill-current text-gray-600 text-sm lg:text-xl"></i></a>`;
                            if (boton[x] == 'historialCliente') {
                                var verHorasCliente = (datos[i]['verhorascliente'] == 'horas') ? 'block' : 'none';
                                contenido += `<a href="" style="display:${verHorasCliente}" class="mx-1 historial" title="ver historial"><i class="fas fa-history mr-1 fill-current text-gray-600 text-sm lg:text-xl"></i></a>`;
                            }
                            // otros botones se conservan — reproducir según original si hace falta
                        }
                        contenido += `</div></td>`;
                    }

                    contenido += `</tr>`;
                }

                // Si la tabla ya existe -> actualizar tbody, si no -> construir cabeceras y cuerpo
                const cont = document.getElementById(destino);
                const existingTable = cont ? cont.querySelector('table#tabla1') : null;
                if (existingTable && existingTable.querySelector('tbody')) {
                    const tbody = existingTable.querySelector('tbody');
                    tbody.innerHTML = contenido;
                    self.actualizarIconosEncabezado();
                } else {
                    if (datos[0]) {
                        var titles = Object.keys(datos[0]);
                        var cabecera = "";
                        titles.forEach(function (element) {
                            var displaycab1 = 'table-cell';
                            if (['verhorascliente','idTecnico','idusuario','Atención','pktabla'].indexOf(element) >= 0) displaycab1 = 'none';
                            let estadoInicial = 'DEFAULT';
                            try { const criterioExist = self.sortCriteria.find(c => c.campo === element); if (criterioExist) estadoInicial = criterioExist.dir; } catch (e) { }
                            const iconMap = { 'DEFAULT': '<i class="fas fa-sort"></i>', 'ASC': '<i class="fas fa-sort-amount-up-alt"></i>', 'DESC': '<i class="fas fa-sort-amount-down-alt"></i>' };
                            let controls = `<button class="sort-toggle" data-campo="${element}" data-state="${estadoInicial}" title="Ordenar" style="margin-left:6px">${iconMap[estadoInicial] || '<i class="fas fa-sort"></i>'}</button>`;
                            cabecera += `<th class="titulos px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-xs md:text-sm font-semibold texto-violeta-oscuro uppercase tracking-wider text-center" style="display:${displaycab1}" data-campo="${element}">${element} ${controls}</th>`;
                        });
                        if (boton != "") cabecera += `<th class="text-center px-5 py-3 border-b-2 border-gray-200 bg-gray-100 text-left text-sm font-semibold texto-violeta-oscuro uppercase tracking-wider">Acciones</th>`;

                        var cabecera2 = "";
                        titles.forEach(function (element) {
                            var displaycab2 = 'table-cell';
                            if (['verhorascliente','idTecnico','idusuario','Atención','pktabla'].indexOf(element) >= 0) displaycab2 = 'none';
                            let valorInput = busquedaObj[element] || '';
                            cabecera2 += `<th class="tituloInputSearch p-2" style="background-color:#ffff;display:${displaycab2}">\n\n                                <div class="flex border rounded p-1 bg-transparent">\n                                        <i class="fas fa-search my-1 text-gray-300" style="font-size: 0.7rem;"></i>\n                                        <input class="rounded bg-white w-full inputKeyup" data-nombre="${element}" value="${valorInput}">\n                                </div>\n\n                                            </th>`;
                        });
                        if (boton != "") cabecera2 += `<th class="tituloInputSearch" style="background-color:#ffff"></th>`;

                        cont.innerHTML = `\n                                    <table class="${clases}" id="tabla1">\n                                        <thead>\n                                        <tr  id="prueba">${cabecera2}</tr>\n                                        <tr>${cabecera}</tr></thead>\n                                        <tbody id="tabla1tbody">\n                                            ${contenido}\n                                        </tbody>\n                                    </table>\n                                    `;
                        self.actualizarIconosEncabezado();
                    }
                }
            }
        };

        xhr.open("POST", ruta, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");

        // Construir ordenMultiple a partir de ordenAUsar (array de criterios con campo visible)
        let ordenMultiple = '';
        if (activos.length > 0) {
        // Solo enviamos los nombres visibles de los campos (ej. "Estado", "Nº")
        const criterios = activos.map(c => ({
            campo: c.campo,  
            dir: c.dir
        }));
        ordenMultiple = encodeURIComponent(JSON.stringify(criterios));
        }

        const body = `busqueda=${encodeURIComponent(busqueda || '')}&orden=${encodeURIComponent(this.ordenOriginal)}&tipoOrden=${encodeURIComponent(this.tipoOrdenOriginal)}&ordenMultiple=${ordenMultiple}&filas=${encodeURIComponent(filas)}&pagina=${encodeURIComponent(pagina)}&id=${encodeURIComponent(id)}&anio=${encodeURIComponent(anio)}`;
        xhr.send(body);
    } // fin metodo tabla

    buscador(destinobuscador, segmento) {
        let select = "";
        let valorSeleccionado = 30;
        for (let i = 0; i < segmento.length; i++) {
            const selected = segmento[i] == valorSeleccionado ? "selected" : "";
            select += `<option value="${segmento[i]}" ${selected}>${segmento[i]}</option>`;
        }
        document.getElementById(destinobuscador).innerHTML = `\n        \n        <p class="appearance-none h-full block appearance-none w-full text-gray-700 p-1 leading-tight focus:outline-none focus:bg-white focus:border-gray-500">Registros</p>\n        <select class="appearance-none h-full rounded-l rounded-r border block appearance-none w-full bg-white border-gray-400 text-gray-700 py-1 px-4 pr-8 leading-tight focus:outline-none focus:bg-white focus:border-gray-500" name="registros" id="registros">\n        ${select}\n        </select></p>\n        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">\n        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">\n                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z" />\n            </svg>\n        </div>\n        `;
    } // fin metodo buscador

    paginador(rutaTotalRegistros, fila, pagina, destinoPaginador, busqueda, id, anio) {
        let paginator = "";
        let total = 0;
        var xhr = new XMLHttpRequest();
        let self = this;
        xhr.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
                total = parseInt(this.responseText);
                paginator += `<div class="text-center custom-number-input w-full justify-center">\n                    <label for="custom-input-number" class="w-35 text-gray-700 text-sm font-semibold ">Página ${parseInt(pagina) + 1} de ${Math.ceil(total / fila)}\n                    </label>\n                    <div class="flex flex-row h-auto w-35 rounded-lg relative bg-transparent mt-1 justify-center">`;
                if (pagina > 0) {
                    paginator += `\n                    <button data-action="decrement" data-elementopaginador="0" class=" bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10 rounded-l cursor-pointer outline-none">\n                        <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i  class="fas fa-angle-double-left"></i></span>\n                      </button>\n                      <button data-action="decrement" data-elementopaginador="${parseInt(pagina) - 1}" class=" bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10  cursor-pointer outline-none">\n                        <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i  class="fas fa-angle-left"></i></span>\n                      </button>`;
                }
                paginator += `<input type="number" class="outline-none focus:outline-none text-center w-30 bg-gray-300 font-semibold text-md hover:text-black focus:text-black  md:text-basecursor-default flex items-center text-gray-700  outline-none inputPaginador" name="custom-input-number" value="${parseInt(pagina) + 1}" readonly></input>`;
                if (pagina < (Math.ceil(total / fila) - 1)) {
                    paginator += `<button data-action="increment" data-elementopaginador="${parseInt(pagina) + 1}" class="bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10  cursor-pointer">\n                      <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i  class="fas fa-angle-right"></i></span>\n                    </button>\n                    <button data-action="increment" data-elementopaginador="${Math.ceil(total / fila) - 1}" class="bg-gray-300 text-gray-600 hover:text-gray-700 hover:bg-gray-400 h-full w-10 rounded-r cursor-pointer">\n                      <span class="m-auto text-sm lg:text-base font-thin" style="pointer-events: none;"><i class="fas fa-angle-double-right"></i></span>\n                    </button>\n                  `;
                }
                paginator += `\n                    </div>\n                    <label for="custom-input-number" class="w-35 text-gray-700 text-sm font-semibold ">Total Registros:&nbsp; ${total}</label>\n                    `;
                document.getElementById(`${destinoPaginador}`).innerHTML = `${paginator}`;
            }
        };
        xhr.open("POST", rutaTotalRegistros, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send(`busqueda=${busqueda}&id=${id}&anio=${anio}`);
    } // fin metodo paginador

    exportarExcel() {
        let busqueda = {};
        document.querySelectorAll(".inputKeyup").forEach(input => { busqueda[input.name] = input.value; });
        let busquedaJSON = JSON.stringify(busqueda);
        let filas = document.getElementById("filas").value || 30;
        let pagina = document.getElementById("pagina").value || 1;
        let orden = document.getElementById("orden").value || "id";
        let tipoOrden = document.getElementById("tipoOrden").value || "ASC";
        let xhr = new XMLHttpRequest();
        let rutaExportacion = this.ruta + "/exportarFacturasExcel";
        xhr.open("POST", rutaExportacion, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.responseType = "blob";
        xhr.onload = function () {
            if (xhr.status === 200) {
                let blob = new Blob([xhr.response], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
                let url = window.URL.createObjectURL(blob);
                let a = document.createElement("a");
                a.href = url; a.download = "Facturas_Exportadas.xlsx"; document.body.appendChild(a); a.click(); window.URL.revokeObjectURL(url);
            }
        };
        xhr.send(`busqueda=${busquedaJSON}&orden=${orden}&tipoOrden=${tipoOrden}&filas=${filas}&pagina=${pagina}`);
    }

} // fin de la clase creartabla


export default function arrancar(objeto, ruta, destino, orden, tipoOrden, pagina, destinobuscador, rutaTotalRegistros, segmento, clasesTabla, destinoPaginador, boton, rutaApp, id, anio) {
    var objeto = new creartabla(ruta, destino, orden, tipoOrden, pagina, destinobuscador, rutaTotalRegistros, segmento, clasesTabla, destinoPaginador, boton, rutaApp, id, anio);

    let prueba1 = document.getElementById(destino);
    let debounceTimer;
    prueba1.addEventListener("keyup", function (e) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            var inputs = document.getElementsByClassName("inputKeyup");
            let algo = {};
            Array.prototype.forEach.call(inputs, function (element, index) {
                if (element.value != '') {
                    algo[element.getAttribute('data-nombre')] = element.value;
                }
            });
            objeto.busqueda = JSON.stringify(algo);
            objeto.pagina = 0; // Volver a primera página al buscar
            objeto.rendertabla();
        }, 300);
    });

    let selectFilas = document.getElementById('registros');
    selectFilas.addEventListener("change", function () {
        objeto.filas = selectFilas.value;
        objeto.pagina = 0;
        objeto.rendertabla();
    });

    let paginadores = document.getElementById(objeto.destinoPaginador);
    paginadores.addEventListener("click", function (e) {
        const boton = e.target.closest('button[data-elementopaginador]');
        if (boton) {
            objeto.pagina = boton.dataset.elementopaginador;
            objeto.rendertabla();
        }
    });

    let ordenador = document.getElementById(objeto.destino);
    ordenador.addEventListener("click", function (e) {
        const btn = e.target.closest('.sort-toggle');
        if (btn) {
            e.preventDefault();
            const campo = btn.getAttribute('data-campo');
            let estado = btn.getAttribute('data-state') || 'DEFAULT';
            let siguiente = 'ASC';
            if (estado === 'DEFAULT') siguiente = 'ASC';
            else if (estado === 'ASC') siguiente = 'DESC';
            else if (estado === 'DESC') siguiente = 'DEFAULT';
            objeto.setSortCriterion(campo, siguiente);
            return;
        }

        let thElement = e.target.closest(".titulos");
        if (thElement) {
            let campoMostrado = thElement.getAttribute('data-campo');
            objeto.contador += 1;
            let nuevaDir = ((objeto.contador % 2) == 0) ? "ASC" : "DESC";
            objeto.setSortCriterion(campoMostrado, nuevaDir);
        }
    });
};



