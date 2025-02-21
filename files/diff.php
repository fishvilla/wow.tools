<?php
require_once("../inc/config.php");

if (empty($_GET['from']) || empty($_GET['to']) || empty($_GET['filedataid'])) {
    die("Not enough information!");
}

$fq = $pdo->prepare("SELECT type FROM wow_rootfiles WHERE id = ?");
$fq->execute([$_GET['filedataid']]);
$row = $fq->fetch();
if(empty($row)){
    die("File not found in database or type is unknown!");
}

$type = $row['type'];

if($type == "blp"){
    $frombuild = getVersionByBuildConfigHash($_GET['from'], "wow");
    $tobuild = getVersionByBuildConfigHash($_GET['to'], "wow");

    $fromparams = "/fdid?buildconfig=".$frombuild['buildconfig']['hash']."&cdnconfig=".$frombuild['cdnconfig']['hash']."&filename=".$_GET['filedataid'].".blp&filedataid=".$_GET['filedataid'];
    $toparams = "/fdid?buildconfig=".$tobuild['buildconfig']['hash']."&cdnconfig=".$tobuild['cdnconfig']['hash']."&filename=".$_GET['filedataid'].".blp&filedataid=".$_GET['filedataid'];

    ?>
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="sbs-tab" data-toggle="tab" href="#sbs" role="tab" aria-controls="sbs" aria-selected="true">Side-by-Side</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="toggle-tab" data-toggle="tab" href="#toggle" role="tab" aria-controls="toggle" aria-selected="false">Switcher</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="imagediff-tab" data-toggle="tab" href="#imagediff" role="tab" aria-controls="imagediff" aria-selected="false">Diff</a>
        </li>
    </ul>
    <div class="tab-content">
        <div class="tab-pane show active" id="sbs" role="tabpanel" aria-labelledby="sbs-tab">
            <div class='row'>
                <div class='col-md-6' id='from-diff'><h3>Build <?=$frombuild['buildconfig']['description']?> (Before)</h3><img id='fromImage' style='max-width: 100%;' src='//wow.tools/casc/preview<?=$fromparams?>'></div>
                <div class='col-md-6' id='to-diff'><h3>Build <?=$tobuild['buildconfig']['description']?> (After)</h3><img id='toImage' style='max-width: 100%;' src='//wow.tools/casc/preview<?=$toparams?>'></div>
            </div>
        </div>
        <div class="tab-pane" id="toggle" role="tabpanel" aria-labelledby="toggle-tab">
            <div id='toggle-content' data-current='from'><div class='col-md-6' id='from-diff'><h3>Build <?=$frombuild['buildconfig']['description']?></h3><img style='max-width: 100%;' src='//wow.tools/casc/preview<?=$fromparams?>'></div></div>
            <button class='btn btn-primary' id='toggle-button'>Switch</button>
        </div>
         <div class="tab-pane" id="imagediff" role="tabpanel" aria-labelledby="imagediff-tab">
            <canvas id='fromImageCanvas' style='display: none'></canvas><canvas id='toImageCanvas' style='display: none'></canvas><canvas id='resultImageCanvas'></canvas><br>
            <span id='diffContents'></span>
        </div>
    </div>
    <script src="https://unpkg.com/pixelmatch"></script>
    <script type='text/javascript'>
         $(document).ready(function() {
            $("#toggle-content").html($("#from-diff").html());
            $( "#toggle-button" ).click(function() {
                if(document.getElementById("toggle-content").dataset.current == "from"){
                    $("#toggle-content").html($("#to-diff").html());
                    document.getElementById("toggle-content").dataset.current = "to";
                }else{
                    $("#toggle-content").html($("#from-diff").html());
                    document.getElementById("toggle-content").dataset.current = "from";
                }
            });

            $("#imagediff-tab").on('show.bs.tab', function (e){
                var fromImage = document.getElementById('fromImage');
                var toImage = document.getElementById('toImage');

                var fromCanvas = document.getElementById('fromImageCanvas');
                var toCanvas = document.getElementById('toImageCanvas');
                var resultCanvas = document.getElementById('resultImageCanvas');

                fromCanvas.width = fromImage.width;
                fromCanvas.height = fromImage.height;
                fromCanvas.getContext('2d').drawImage(fromImage, 0, 0);

                toCanvas.width = toImage.width;
                toCanvas.height = toImage.height;
                toCanvas.getContext('2d').drawImage(toImage, 0, 0);

                const fromImageData = fromCanvas.getContext('2d').getImageData(0, 0, fromImage.width, fromImage.height);
                const toImageData = toCanvas.getContext('2d').getImageData(0, 0, toImage.width, toImage.height);

                // TODO: Get res for largest image?
                var width = toImage.width;
                var height = toImage.height;

                resultCanvas.width = width;
                resultCanvas.height = height;

                const diff = resultCanvas.getContext('2d').createImageData(width, height);

                const diffCount = pixelmatch(fromImageData.data, toImageData.data, diff.data, width, height);
                document.getElementById('diffContents').innerHTML = diffCount + " pixels changed!";
                resultCanvas.getContext('2d').putImageData(diff, 0, 0);
            });

            module = {}
         });
    </script>
    <?php
}else{
    $diff_api_url = "/files/scripts/diff_api.php?from=" . $_GET['from'] . "&to=" . $_GET['to'] . "&filedataid=" . $_GET['filedataid'] . "&raw=" . $_GET['raw'];

    ?>
    <link rel="stylesheet" type="text/css" href="/css/diff2html.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script type="text/javascript" src="/js/diff2html-ui.min.js"></script>
    <script type="text/javascript" src="/js/diff2html.min.js"></script>

    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
            if(localStorage.getItem('theme') == "dark"){
                $('#previewModalContent').append('<link rel="stylesheet" type="text/css" href="/css/diff2html-dark.css?v= ' + Date.now() +'">');
            }
            $.get("<?= $diff_api_url ?>", function(data) {
                var diffHtml = Diff2Html.getPrettyHtml(
                    data, {
                        inputFormat: 'diff',
                        showFiles: false,
                        matching: 'lines',
                        outputFormat: 'side-by-side'
                    }
                    );
                document.getElementById("diff").innerHTML = diffHtml;
            });
        });
    </script>

<?php
}
?>

 <div id="diff"></div>