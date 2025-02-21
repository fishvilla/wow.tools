var Module = {
    onRuntimeInitialized: function() {
        createscene();
    }
};

function showError(error){
    $("#errors").append("<div class='error alert alert-danger'>" + error +"</div>")
}

window.onerror = function(message, source, lineno, colno, error) {
    showError("An error occured! You might have to reload the page.<br>Let us know via Discord (button in the menu).");
}

var Elements =
{
    Sidebar: document.getElementById('js-sidebar'),
    Counter: document.getElementById('fpsLabel')
};

var Current =
{
    buildConfig: "edece5d974f65c808160c75026123699",
    cdnConfig: "da2558dea92f537b793ad6fe1eaaaeeb",
    buildName: "8.2.5.31337",
    fileDataID: 397940,
    type: "m2",
    embedded: false
}

var Settings =
{
    showFPS: true
}

var screenshot = false;

function loadSettings(){
    /* Show/hide FPS counter */
    var storedShowFPS = localStorage.getItem('settings[showFPS]');
    if(storedShowFPS){
        if(storedShowFPS== "1"){
            Settings.showFPS = true;
        }else{
            Settings.showFPS = false;
            document.getElementById("fpsLabel").innerHTML = "";
        }
    }

    document.getElementById("showFPS").checked = Settings.showFPS;
}

function saveSettings(){
    if(document.getElementById("showFPS").checked){
        localStorage.setItem('settings[showFPS]', '1');
    }else{
        localStorage.setItem('settings[showFPS]', '0');
    }

    loadSettings();
}

// Sidebar button
document.getElementById( 'js-sidebar-button' ).addEventListener( 'click', function( )
{
    Elements.Sidebar.classList.toggle( 'closed' );
} );

try {
    if (typeof WebAssembly === "object" && typeof WebAssembly.instantiate === "function") {
        const module = new WebAssembly.Module(Uint8Array.of(0x0, 0x61, 0x73, 0x6d, 0x01, 0x00, 0x00, 0x00));
        if (module instanceof WebAssembly.Module)
            var testModule = new WebAssembly.Instance(module) instanceof WebAssembly.Instance;
        if(!testModule) showError("WebAssembly support is required but not supported by your browser.");
    }
} catch (e) {
    showError("WebAssembly support is required but not supported by your browser.");
}

var urlBuildConfig = new URL(window.location).searchParams.get("buildconfig");
if(urlBuildConfig){
    Current.buildConfig = urlBuildConfig;
}

var urlCDNConfig = new URL(window.location).searchParams.get("cdnconfig");
if(urlCDNConfig){
    Current.cdnConfig = urlCDNConfig;
}

var urlFileDataID = new URL(window.location).searchParams.get("filedataid");
if(urlFileDataID){
    Current.fileDataID = urlFileDataID;
}

var urlType = new URL(window.location).searchParams.get("type");
if(urlType){
    Current.type = urlType;
}

var urlEmbed = new URL(window.location).searchParams.get("embed");
if(urlEmbed){
    Current.embedded = true;
    $("#js-sidebar-button").hide();
    $("#fpsLabel").hide();
    console.log("Running modelviewer in embedded mode!");
}

window.createscene = function () {
    Module["canvas"] = document.getElementById("wowcanvas");
    var gl = Module["canvas"].getContext("webgl2");

    if (!gl){
        showError("WebGL2 is required but not supported by your browser or device.");
        return;
    }

    var debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
    if(!debugInfo){
        console.log("Unknown WebGL unmasked renderer!");
    }else{
        var renderer = gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL);
        if(renderer){
            console.log("WebGL unmasked renderer: " + renderer);
        }
    }

    var url = "https://wow.tools/casc/file/fname?buildconfig=" + Current.buildConfig + "&cdnconfig=" + Current.cdnConfig +"&filename=";
    var urlFileId = "https://wow.tools/casc/file/fdid?buildconfig=" + Current.buildConfig + "&cdnconfig=" + Current.cdnConfig +"&filename=data&filedataid=";

    var ptrUrl = allocate(intArrayFromString(url), 'i8', ALLOC_NORMAL);
    var ptrUrlFileDataId = allocate(intArrayFromString(urlFileId), 'i8', ALLOC_NORMAL);

    Module._createWebJsScene(document.body.clientWidth, document.body.clientHeight, ptrUrl, ptrUrlFileDataId);

    loadModel(Current.type, Current.fileDataID, Current.buildConfig, Current.cdnConfig)

    _free(ptrUrl);
    _free(ptrUrlFileDataId);
    var lastTimeStamp = new Date().getTime();

    Module["canvas"].width = document.body.clientWidth;
    Module["canvas"].height = document.body.clientHeight;

    Module["animationArrayCallback"] = function(array) {
        $("#animationSelect").empty();
        $("#js-controls").removeClass("closed");
        $("#animationSelect").show();

        array.forEach(function(a) {
            if(a in animationNames){
                $('#animationSelect').append('<option value="' + a + '">' + animationNames[a] + ' (' + a + ')</option>');
            }else{
                $('#animationSelect').append('<option value="' + a + '">Animation ' + a + '</option>');
            }
        })

    };
    var renderfunc = function(){
        var currentTimeStamp = new Date().getTime();
        var timeDelta = 0;
        if (lastTimeStamp !== undefined) {
            timeDelta = currentTimeStamp - lastTimeStamp;
            if(Settings.showFPS && lastTimeStamp % 50 == 0){
                Elements.Counter.textContent = Math.round(1000.0 / timeDelta) + " fps";
            }
        }
        lastTimeStamp = currentTimeStamp;

        Module._gameloop(timeDelta / 1000.0);

        if(screenshot){
            screenshot = false;

            let canvasImage = Module["canvas"].toDataURL('image/png');

            let xhr = new XMLHttpRequest();
            xhr.responseType = 'blob';
            xhr.onload = function () {
                let a = document.createElement('a');
                a.href = window.URL.createObjectURL(xhr.response);
                a.download = 'wowtoolsmv-' + currentTimeStamp + '.png';
                a.style.display = 'none';
                document.body.appendChild(a);
                a.click();
                a.remove()
            };
            xhr.open('GET', canvasImage);
            xhr.send();
        }
        window.requestAnimationFrame(renderfunc);
    };

    window.requestAnimationFrame(renderfunc);
}

window.addEventListener('resize', () => {
    var canvas = document.getElementById("wowcanvas");
    if(canvas){
        canvas.width = document.body.clientWidth;
        canvas.height = document.body.clientHeight;
        if(Module && Module._setSceneSize){
            window.Module._setSceneSize(document.body.clientWidth, document.body.clientHeight);
        }
    }
});

$('#mvfiles').on('click', 'tbody tr td:first-child', function() {
    var data = Elements.table.row($(this).parent()).data();
    var mostRecentVersion = data[3][0];

    if(mostRecentVersion['buildconfig'] == Current.buildConfig && data[0] == Current.fileDataID){
        console.log("Clicked model already open in viewer, ignoring.");
        return;
    }

    $(".selected").removeClass("selected");
    $(this).parent().addClass('selected');
    loadModel(data[4], data[0], mostRecentVersion['buildconfig'], mostRecentVersion['cdnconfig']);
});

$('#js-sidebar').on('input', '.paginate_input', function(){
    if($(".paginate_input")[0].value != ''){
        $("#mvfiles").DataTable().page($(".paginate_input")[0].value - 1).ajax.reload(null, false)
    }
});

window.addEventListener('keydown', function(event){
    if($(".selected").length == 1){
         if(event.key == "ArrowDown"){
            if($(".selected")[0].rowIndex == 20) return;
            $(document.getElementById('mvfiles').rows[$(".selected")[0].rowIndex + 1].firstChild).trigger("click");
        }else if(event.key == "ArrowUp"){
            if($(".selected")[0].rowIndex == 1) return;
            $(document.getElementById('mvfiles').rows[$(".selected")[0].rowIndex - 1].firstChild).trigger("click");
        }
    }

    if(document.activeElement.tagName == "INPUT"){
        event.stopImmediatePropagation();
    }
}, true);

window.addEventListener('keyup', function(event){
    if(event.key == "PrintScreen" && !event.shiftKey && !event.ctrlKey && !event.altKey) screenshot = true;
    if(document.activeElement.tagName == "INPUT"){
        event.stopImmediatePropagation();
    }
}, true);

window.addEventListener('keypress', function(event){
    if(document.activeElement.tagName == "INPUT"){
        event.stopImmediatePropagation();
    }
}, true);

$("#animationSelect").change(function () {
    var display = $("#animationSelect option:selected").attr("value");
    Module._setAnimationId(display);
});

$("#skinSelect").change(function() {
    var display = $("#skinSelect option:selected").attr("value").split(',');

    if(display.length == 3){
        // Creature
        setModelTexture(display, 11);
    }else{
        // Item
        setModelTexture(display, 2);
    }
});

function loadModel(type, filedataid, buildconfig, cdnconfig){
    Current.buildConfig = buildconfig;
    Current.cdnConfig = cdnconfig;
    Current.fileDataID = filedataid;
    Current.type = type;

    $.ajax({
        url: "https://wow.tools/files/scripts/filedata_api.php?filename=1&filedataid=" + Current.fileDataID
    })
    .done(function( filename ) {
        Current.filename = filename;

        updateURLs();

        history.pushState({id: 'modelviewer'}, 'Model Viewer', 'https://wow.tools/mv/?buildconfig=' + Current.buildConfig + '&cdnconfig=' + Current.cdnConfig + '&filedataid=' + Current.fileDataID + '&type=' + Current.type);

        $("#js-controls").addClass("closed");
        $("#animationSelect").hide();
        $("#skinSelect").hide();

        var alwaysLoadByFDID = false;
        if(noNameBuilds.includes(buildconfig)){
            alwaysLoadByFDID = true;
        }

        if(Current.type == "adt"){
            alwaysLoadByFDID = false;
        }

        if (Current.filename != "" && !alwaysLoadByFDID) {
            console.log("Loading " + Current.filename + " " + Current.fileDataID + " (" + Current.type + ")");
            var ptrName = allocate(intArrayFromString(Current.filename), 'i8', ALLOC_NORMAL);
            if (Current.type == "adt") {
                Module._setScene(2, ptrName, -1);
            }else if (Current.type == "wmo") {
                Module._setScene(1, ptrName, -1);
            } else if (Current.type == "m2") {
                Module._setScene(0, ptrName, -1);
                loadModelTextures();
            } else {
                console.log("Unsupported type: " + Current.type);
            }
        } else {
            console.log("Loading " + Current.fileDataID + " (" + Current.type + ")");
            if (Current.type == "adt") {
                Module._setSceneFileDataId(2, Current.fileDataID, -1);
            }else if (Current.type == "wmo") {
                Module._setSceneFileDataId(1, Current.fileDataID, -1);
            } else if (Current.type == "m2") {
                Module._setSceneFileDataId(0, Current.fileDataID, -1);
                loadModelTextures();
            } else {
                console.log("Unsupported type: " + Current.type);
            }
        }
    });
}

function loadModelTextures() {
    //TODO build, fix wrong skin showing up after initial load
    var loadedTextures = Array();

    $.ajax({url: "https://wow.tools/api/texture/" + Current.fileDataID + "?build=" + Current.buildName}).done( function(data) {
        console.log(data);
        var i = 0;
        $("#skinSelect").empty();
        for (let displayId in data) {
            if (!data.hasOwnProperty(displayId)) continue;

            if(data[displayId][0] == 0)
                continue;

            var intArray = data[displayId];

            // Open controls overlay
            $("#js-controls").removeClass("closed");
            $("#skinSelect").show();
            // Always select first set
            if(i == 0){
                if(intArray.length == 3){
                    // Creature
                    setModelTexture(intArray, 11);
                }else{
                    // Item
                    setModelTexture(intArray, 2);
                }
            }

            if(loadedTextures.includes(data[displayId].join(',')))
                continue;

            loadedTextures.push(data[displayId].join(','));

            console.log(loadedTextures);
            $.ajax({
                type: 'GET',
                url: "https://wow.tools/files/scripts/filedata_api.php",
                data: {
                    filename: 1,
                    filedataid : intArray.join(",")
                }
            })
            .done(function( filename ) {
                var textureFileDataIDs = decodeURIComponent(this.url.replace("https://wow.tools/files/scripts/filedata_api.php?filename=1&filedataid=", '')).split(',');
                var textureFileDataID = textureFileDataIDs[0];
                if(filename != ""){
                    var nopathname = filename.replace(/^.*[\\\/]/, '');
                    $('#skinSelect').append('<option value="' + textureFileDataIDs + '">(' + textureFileDataID + ') ' + nopathname + '</option>');
                }else{
                    $('#skinSelect').append('<option value="' + textureFileDataIDs + '">' + textureFileDataID + '</option>');
                }
            });
        }
    });
}

function setModelTexture(textures, offset){
    //Create real texture replace array
    const typedArray = new Int32Array(18);

    for(i = 0; i < textures.length; i++){
        typedArray[offset + i] = textures[i];
    }

    // Allocate some space in the heap for the data (making sure to use the appropriate memory size of the elements)
    buffer = Module._malloc(typedArray.length * typedArray.BYTES_PER_ELEMENT);

    // Assign the data to the heap - Keep in mind bytes per element
    Module.HEAP32.set(typedArray, buffer >> 2);

    Module._setTextures(buffer, typedArray.length);

    Module._free(buffer);
}

function updateURLs(){
    var url = "https://wow.tools/casc/file/fname?buildconfig=" + Current.buildConfig + "&cdnconfig=" + Current.cdnConfig +"&filename=";
    var urlFileId = "https://wow.tools/casc/file/fdid?buildconfig=" + Current.buildConfig + "&cdnconfig=" + Current.cdnConfig +"&filename=data&filedataid=";

    var ptrUrl = allocate(intArrayFromString(url), 'i8', ALLOC_NORMAL);
    var ptrUrlFileDataId = allocate(intArrayFromString(urlFileId), 'i8', ALLOC_NORMAL);

    Module._setNewUrls(ptrUrl, ptrUrlFileDataId);

    _free(ptrUrl);
    _free(ptrUrlFileDataId);
}

(function() {
    loadSettings();
    $('#wowcanvas').bind('contextmenu', function(e){
        return false;
    });
    Elements.table = $('#mvfiles').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "/files/scripts/api.php",
            "data": function ( d ) {
                return $.extend( {}, d, {
                    "src": "mv",
                    "showADT": $("#showADT").is(":checked"),
                    "showWMO": $("#showWMO").is(":checked"),
                    "showM2": $("#showM2").is(":checked")
                } );
            }
        },
        "pageLength": 20,
        "autoWidth": false,
        "pagingType": "input",
        "orderMulti": false,
        "dom":"<'row'<'col-sm-12 col-md-12'f>>" + "<'row'<'col-sm-12'tr>>" + "<'row'<'col-sm-12 col-md-12'p>>",
        "ordering": true,
        "order": [[0, 'asc']],
        "dom": 'fprt',
        "columnDefs":
        [
        {
            "targets": 0,
            "orderable": false,
            "visible": false
        },
        {
            "targets": 1,
            "orderable": false,
            "createdCell": function (td, cellData, rowData, row, col) {
                if (!cellData && !rowData[7]) {
                    $(td).css('background-color', '#ff5858');
                    $(td).css('color', 'white');
                }
            },
            "render": function ( data, type, full, meta ) {
                if(full[1]) {
                    var test = full[1].replace(/^.*[\\\/]/, '');
                }else{
                    if(!full[4]){
                        full[4] = "unk";
                    }
                    if(full[7]){
                        var test = full[7].replace(/^.*[\\\/]/, '');;
                    }else{
                        var test = "Unknown filename (Type: " + full[4] + ", ID " + full[0] + ")";
                    }
                }

                return test;
            }
        },
        {
            "targets": 2,
            "orderable": false,
            "render": function ( data, type, full, meta ) {
                var test = "";
                if(full[3].length > 1){
                    test = "<div class='btn-group'><button class='btn btn-sm dropdown-toggle historybutton' type='button' data-toggle='dropdown'><i class='fa fa-clock-o'></i></button>";
                    test += "<div class='dropdown-menu'>";

                    full[3].forEach(function (value) {
                        test += "<a class='dropdown-item filedropdown' href='#' onClick='loadModel(\"" + full[4] + "\", " + full[0] + ", \"" + value['buildconfig'] + "\", \"" + value['cdnconfig'] + "\");'>" + value['description'] + "</a>";
                    });

                    test += "</div></div>";
                }else{
                    test = "<div class='btn-group'><button class='btn btn-sm historybutton' type='button' disabled><i class='fa fa-clock-o'></i></button></div>";
                }

                return test;
            }
        }
        ],
        "language": {
            search: "",
            searchPlaceholder: "Search"
        }
    });

    $(".filterBox").on('change', function(){
        Elements.table.ajax.reload();
    });
}());