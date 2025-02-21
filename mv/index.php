<?php require_once("../inc/header.php");

if(!empty($_GET['embed'])){
?>
<style type='text/css'>
nav{
	display: none !important;
}
</style>
<?php
}

?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.19/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.datatables.net/plug-ins/1.10.19/pagination/input.js" crossorigin="anonymous"></script>
<link href="/mv/modelviewer.css?v=<?=filemtime("/var/www/wow.tools/mv/modelviewer.css")?>" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js" crossorigin="anonymous"></script>
	<button id="js-sidebar-button" class="hamburger">
		<i class='fa fa-reorder'></i>
	</button>
	<div id="js-sidebar" class="overlay sidebar closed container">
		<b style='margin-left: 75px; margin-top: 0px;'>Uses WIP modelviewer by Deamon</b>
		<div class='row justify-content-md-center'>
			<div class='col-md-11'>
				<div class="btn-group" role="group">
					<button style='margin-left: 48px;' class='btn btn-mv btn-sm' data-toggle='modal' data-target='#settingsModal'><i class='fa fa-gear'></i> Settings</button>
					<a class='btn btn-mv btn-sm' href='#' data-toggle='modal' data-target='#changelogModal'><i class='fa fa-bug'></i> Changelog</a>
					<button class='btn btn-mv btn-sm' data-toggle='modal' data-target='#helpModal'><i class='fa fa-info-circle'></i> Help/About</button>
				</div>
			</div>
		</div>
		<div class='row justify-content-center' style='margin-top: 10px;'>
			<div class='col-md-4' style='text-align: center'><label title='terrain files (makes search slower)' for='showADT'>Show ADT: <input class='filterBox' type='checkbox' id='showADT'></label></div>
			<div class='col-md-4' style='text-align: center'><label title='larger models (buildings, cities, dungeons, raids etc)' for='showWMO'>Show WMO: <input class='filterBox' type='checkbox' id='showWMO' CHECKED></label></div>
			<div class='col-md-4' style='text-align: center'><label title='smaller more complex models (creatures, foliage, props etc)' for='showM2'>Show M2: <input class='filterBox' type='checkbox' id='showM2' CHECKED></label></div>
		</div>
		<div class='row'>
			<div class='col'>
				<table id='mvfiles' class="table table-striped table-bordered table-condensed" cellspacing="0" width="100%">
					<thead>
						<tr>
							<th style='width: 50px;'>ID</th>
							<th>Filename</th>
							<th style='width: 15px;'>&nbsp;</th>
						</tr>
					</thead>
				</table>
			</div>
		</div>
	</div>
	<div id="js-controls" class="overlay controls closed">
		<div>
			<select id='animationSelect' class='form-control' style='display: none'>
				<option>No options for model</option>
			</select>
			<select id='skinSelect' class='form-control'>
				<option>No options for model</option>
			</select>
		</div>
	</div>
	<div id='errors'></div>
	<div id='fpsLabel'></div>
	<canvas id="wowcanvas"></canvas>
	<div class="modal" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="settingsModalLabel">Settings</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<form id='settingsForm'>
						<input type='checkbox' id='showFPS' name='settings[showFPS]'> <label for='showFPS'>Show FPS</label>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" onclick="saveSettings();" data-dismiss="modal">Save</button>
				</div>
			</div>
		</div>
	</div>
	<div class="modal" id="helpModal" tabindex="-1" role="dialog" aria-labelledby="helpModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="helpModalLabel">Help/About</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<p>
						This tool is being developed by the WoW community for the WoW community and as such won't have watermarks all over it but will likely also be more unstable/bleeding edge. Currently it supports rendering most M2 models (smaller more complex models, characters unsupported for now), WMO models (larger models) and ADT files (terrain files with WMOs and M2s).
						<br>These parts of it are open source (more coming in the future!):
					<ul>
						<li><a target='_BLANK' href='https://github.com/Deamon87/WebWowViewerCpp/tree/emscripten'>Model viewer itself</a></li>
						<li><a target='_BLANK' href='https://github.com/Marlamin/WoWFormatTest/tree/master/DBCDumpHost'>DBC backend (used for texture lookups)</a></li>
						<li><a target='_BLANK' href='https://github.com/Marlamin/CASCToolHost'>CASC backend (serves files)</a></li>
					</ul>
					</p>
					<h5>Requirements</h5>
					<p>
						This requires a browser that supports both WebAssembly (<a href='https://caniuse.com/#search=wasm' target='_BLANK'>see list here</a>) and WebGL 2.0 (<a href='https://caniuse.com/#search=webgl2' target='_BLANK'>see list here</a>). If your browser does not support this the modelviewer will definitely get upset and will definitely throw errors towards your general direction. We're aware this is not always supported on some configurations, but are hopeful it will be in the future!
					</p>
					<h5>Controls</h5>
					<p>
						For ADTs and WMOs, the model viewer uses a free-roam camera. It can be controlled via dragging the mouse and <tt>WASD</tt> keys. Holding <tt>SHIFT</tt> increases camera speed.<br><br>
						For M2s, the model viewer uses a rotational camera. You can rotate the model by dragging the mouse and zoom out with the scroll wheel. If skins/animations are available, a menu will pop up with options for these.
					</p>
					<h5>Issues</h5>
					<p>
						This modelviewer (and especially the UI) is still a heavy work in progress. Many bugs will appear. Report any issues/feature requests via GitHub by clicking the "Report issues" button or via Discord by joining through the link in the menu.
					</p>
					<h5>Embed</h5>
					<p>
						An embeddable version of the modelviewer <a href='//marlamin.com/u/mvxdomain.html' target='_BLANK'>is</a> being worked on, but the full fledged version still has priority. Decisions on how exactly it will work (or how costs will be dealt with if it gets popular) are still to be decided.
					</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
	<div class="modal" id="changelogModal" tabindex="-1" role="dialog" aria-labelledby="changelogModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="changelogModalLabel">Changelog</h5>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				</div>
				<div class="modal-body">
					<h5>Version 0.9.2 <small>(12-04-2019)</small></h5>
					<ul>
						<li>(UI) Fix manual page input not working</li>
						<li>(UI) More UI changes to fit to new site theme</li>
					</ul>
					<h5>Version 0.9.1 <small>(08-04-2019)</small></h5>
					<ul>
						<li>(UI) Add navigation through the list with arrows keys</li>
						<li>(UI) Various UI changes to fit to new site theme</li>
						<li>(UI) Fix issue where same file row could be clicked twice</li>
					</ul>
					<h5>Version 0.9.0 <small>(18-01-2019)</small></h5>
					<ul>
						<li>(MV) PARTICLE SUPPORT!</li>
						<li>(UI) Performance improvements</li>
					</ul>
					<h5>Version 0.8.1 <small>(01-12-2018)</small></h5>
					<ul>
						<li>(UI) Layout improvements</li>
						<li>(UI) Add filters for ADT/WMO/M2 (ADT disabled by default for search speed)</li>
						<li>(UI) Removed (hopefully all) non-supported files from list</li>
						<li>(UI) Updated help/about</li>
						<li>(UI) Decrease FPS counter update speed for better page performance (Thanks Pavel!)</li>
						<li>(UI) Highlight currently selected file</li>
					</ul>
					<h5>Version 0.8.0 <small>(21-11-2018)</small></h5>
					<ul>
						<li>(MV) Animation changing support</li>
						<li>(UI) Animation picker</li>
					</ul>
					<h5>Version 0.7.1 <small>(12-11-2018)</small></h5>
					<ul>
						<li>(MV) Replaceable item texture support</li>
					</ul>
					<h5>Version 0.7.0 <small>(07-11-2018)</small></h5>
					<ul>
						<li>(MV) Touch controls</li>
						<li>(MV) Prep for item texture support</li>
						<li>(MV) Fix WMO water</li>
						<li>(UI) History feature re-enabled</li>
					</ul>
					<h5>Version 0.6.2 <small>(29-10-2018)</small></h5>
					<ul>
						<li>(MV) Environment mapping improvements</li>
						<li>(MV) Billboarding improvements</li>
						<li>(UI) Various UI improvements suggested by Balkron</li>
						<li>(UI) Temporarily disable history feature</li>
					</ul>
					<h5>Version 0.6.1 <small>(22-10-2018)</small></h5>
					<ul>
						<li>(MV) Fix crash when loading some WMOs</li>
						<li>(MV) Load less textures at a time to prevent lagspikes</li>
					</ul>
					<h5>Version 0.6.0 <small>(18-10-2018)</small></h5>
					<ul>
						<li>(UI) Enable history function</li>
						<li>(MV) Support history feature</li>
					</ul>
					<h5>Version 0.5.2 <small>(16-10-2018)</small></h5>
					<ul>
						<li>(UI) Add changelog</li>
						<li>(UI) Fix some scaling issues</li>
						<li>(UI) Add (currently disabled) history feature</li>
						<li>(MV) Add rotational M2 camera</li>
						<li>(MV) Fix M2 lights</li>
					</ul>
					<h5>Version 0.5.1 <small>(10-10-2018)</small></h5>
					<ul>
						<li>(UI) Hide file IDs</li>
						<li>(UI) Show ID/type if filename is unknown</li>
						<li>(MV) Add SHIFT hotkey to increase camera speed</li>
						<li>(MV) Fix M2 animations not working properly</li>
					</ul>
					<h5>Version 0.5.0 <small>(09-10-2018)</small></h5>
					<ul>
						<li>(UI) Move to /mv/</li>
						<li>(UI) Add search</li>
						<li>(UI) Add URL updating when switching models</li>
						<li>(UI) Add FPS counter</li>
						<li>(UI) Add settings</li>
					</ul>
					<h5>Version 0.4.0 <small>(08-10-2018)</small></h5>
					<ul>
						<li>(MV) Add support for 8.1 WMO format changes</li>
						<li>(MV) Add M2s to file picker</li>
						<li>(MV) Fix M2 animations not working properly</li>
					</ul>
					<h5>Version 0.3.2 <small>(04-10-2018)</small></h5>
					<ul>
						<li>(MV) Fix flickering issue on Intel embedded GPUs</li>
						<li>(MV) Fix resizing issue</li>
					</ul>
					<h5>Version 0.3.1 <small>(02-10-2018)</small></h5>
					<ul>
						<li>(MV) Fix issue where some missing WMOs would crash</li>
					</ul>
					<h5>Version 0.3.0 <small>(01-10-2018)</small></h5>
					<ul>
						<li>(UI) Add checks to see if user meets WebGL/WASM requirements</li>
						<li>(UI) More datatables functionality copied from regular file browser</li>
						<li>(MV) Add "O" hotkey to reset camera</li>
						<li>(MV) Fix issue where some missing WMO meshes were missing</li>
					</ul>
					<h5>Version 0.2.0 <small>(30-09-2018)</small></h5>
					<ul>
						<li>(UI) Add UI</li>
						<li>(UI) Add file picker (WMO only)</li>
						<li>(MV) Fix issue where WMO exterior would disappear</li>
					</ul>
					<h5>Version 0.1.0 <small>(29-09-2018)</small></h5>
					<ul>
						<li>WMO viewing</li>
					</ul>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
				</div>
			</div>
		</div>
	</div>
	<script type="text/javascript"><?php $nonfilenamebuilds = $pdo->query("SELECT hash FROM wow_buildconfig WHERE description LIKE '%8.2%'")->fetchAll(PDO::FETCH_COLUMN); ?>
	var noNameBuilds = <?=json_encode($nonfilenamebuilds)?>;
	</script>
	<script src="/mv/modelviewer.js?v=<?=filemtime("/var/www/wow.tools/mv/modelviewer.js")?>"></script>
	<script src="/mv/anims.js?v=<?=filemtime("/var/www/wow.tools/mv/anims.js")?>"></script>
	<script src="/mv/project.js?v=<?=filemtime("/var/www/wow.tools/mv/project.js")?>"></script>
</body>
</html>