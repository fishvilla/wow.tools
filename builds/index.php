<?php
require_once("../inc/header.php");

$query = "SELECT
wow_versions.id as versionid,
wow_versions.cdnconfig,
wow_versions.buildconfig,
wow_versions.patchconfig,
wow_versions.complete as versioncomplete,
wow_versions.product as versionproduct,
wow_buildconfig.id as buildconfigid,
wow_buildconfig.description,
wow_buildconfig.product,
wow_buildconfig.encoding,
wow_buildconfig.encoding_cdn,
wow_buildconfig.root,
wow_buildconfig.root_cdn,
wow_buildconfig.install,
wow_buildconfig.install_cdn,
wow_buildconfig.download,
wow_buildconfig.download_cdn,
wow_buildconfig.unarchivedcount,
wow_buildconfig.unarchivedcomplete,
wow_buildconfig.complete as buildconfigcomplete,
wow_buildconfig.builton,
wow_cdnconfig.archivecount,
wow_cdnconfig.archivecomplete,
wow_cdnconfig.indexcomplete,
wow_cdnconfig.patcharchivecount,
wow_cdnconfig.patcharchivecomplete,
wow_cdnconfig.patchindexcomplete,
wow_cdnconfig.complete as cdnconfigcomplete,
wow_patchconfig.patch,
wow_patchconfig.complete as patchconfigcomplete
FROM wow_versions
LEFT OUTER JOIN wow_buildconfig ON wow_versions.buildconfig=wow_buildconfig.hash
LEFT OUTER JOIN wow_cdnconfig ON wow_versions.cdnconfig=wow_cdnconfig.hash
LEFT OUTER JOIN wow_patchconfig ON wow_versions.patchconfig=wow_patchconfig.hash
ORDER BY wow_buildconfig.description DESC
";
$res = $pdo->query($query);
$allbuilds = $res->fetchAll();
$odd = false;
?>
<script type='text/javascript' src='/builds/js/builds.js?v=<?=filemtime("/var/www/wow.tools/builds/js/builds.js")?>'></script>
<div class="modal" id="installDiffModal" tabindex="-1" role="dialog" aria-labelledby="installDiffModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="installDiffModalLabel">Install diff</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body" id="installDiffModalContent">
				<i class="fa fa-refresh fa-spin" style="font-size:24px"></i>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
			</div>
		</div>
	</div>
</div>
<div class='container-fluid'>
	<h3 style='float: left'><?=count($allbuilds)?> builds in DB</h3>
	<div style='float: left; margin-left: 10px; position: sticky; top: 0;'><a href='#' class='btn btn-primary btn-sm disabled' id='diffButton'>Diff builds</a> <a href='#' class='btn btn-success btn-sm' style='display :none' id='openDiffButton' target='_BLANK'>Open diff</a> <a href='#' class='btn btn-info btn-sm' style='display :none' id='openInstallDiffButton' href='#'>Open install diff</a> <a href='#' class='btn btn-danger btn-sm' style='display: none' id='resetButton'>Reset</a></div>
	<form>
		<input type='hidden' id='buildFrom'><input type='hidden' id='buildTo'>
	</form>
	<table id='buildtable' class='table table-sm table-hover maintable'>
		<thead><tr><th>Patch</th><th>Build</th><th>Branch</th><th>Build config</th><th>Patch config</th><th>CDN config</th><th>Build time</th><th>&nbsp;</th></tr></thead>
		<?php foreach($allbuilds as $row){
			if($odd){
				echo "<tr class='odd'>";
			}else{
				echo "<tr>";
			}

			if(empty($row['product'])) $row['product'] = $row['versionproduct'];

			$buildarr = parseBuildName($row['description']);
			echo "<td style='width: 50px'>".$buildarr['patch']."</td>";
			echo "<td style='width: 50px'>".$buildarr['build']."</td>";
			echo "<td style='width: 100px'>".prettyBranch($row['product'])."</td>";
			echo "<td style='width: 600px'>";
			echo "<span class='hash buildconfighash'>".$row['buildconfig']."</span>";

			if(empty($row['buildconfig']) || !doesFileExist("config", $row['buildconfig'], $allowedproducts["wow"]['cdndir'])) {
				echo "<span class='badge badge-danger'>Does not exist</span>";
			}

			if($row['buildconfigcomplete'] == 0) {
				echo " <span class='badge badge-danger'>Incomplete</span>";
			}

			echo "</td>";
			echo "<td style='width: 300px'>";
			echo "<span class='hash'>".$row['patchconfig']."</span>";

			if(!empty($row['patchconfig']) && !doesFileExist("config", $row['patchconfig'], $allowedproducts["wow"]['cdndir'])) {
				echo "<span class='badge badge-danger'>Does not exist</span>";
			}

			if(isset($row['patchconfigcomplete'])){
				if($row['patchconfigcomplete'] == 0){
					echo " <span class='badge badge-danger'>Incomplete</span>";
				}
			}

			echo "</td>";
			echo "<td style='width: 300px;'>";
			echo "<span class='hash'>".$row['cdnconfig']."</span>";

			if(empty($row['cdnconfig']) || !doesFileExist("config", $row['cdnconfig'], $allowedproducts["wow"]['cdndir'])) {
				echo "<span class='badge badge-danger'>Does not exist</span>";
			}

			if(isset($row['cdnconfigcomplete'])){
				if($row['cdnconfigcomplete'] == 0){
					echo " <span class='badge badge-danger'>Incomplete</span>";
				}
			}
			echo "</td>";

			echo "<td style='width: 150px'>".$row['builton']."</td>";
			echo "<td style='width: 100px'>";
			echo "<a data-toggle='collapse' href='#versiondetails".$row['versionid']."'>Show details</a>";
			echo "</td>";
			echo "</tr>";
			echo "<tr class='collapse' id='versiondetails".$row['versionid']."'>";
			echo "<td colspan='3'>&nbsp;</td>";
			echo "<td style='width: 600px'>";
			echo "<table class='table table-sm'>";
			echo "<tr>";
			echo "<td>Encoding</td>";
			echo "<td>";
			if(!empty($row['encoding'])) { echo "<span class='badge badge-secondary hash'>".$row['encoding']."</span>"; }
			if(!empty($row['encoding_cdn']) && doesFileExist("data", $row['encoding_cdn'], $allowedproducts["wow"]['cdndir'])) {
				echo " <span class='badge badge-success hash'>";
				if(!empty($_SESSION['loggedin'])){
					echo "<a target='_BLANK' style='color: white;' href='".generateURL("data", $row['encoding_cdn'], $allowedproducts["wow"]['cdndir'])."'>".$row['encoding_cdn']."</a>";
				}else{
					echo "<span style='color: white;'>".$row['encoding_cdn']."</span>";
				}
				echo "</span>";
			} else {
				echo " <span class='badge badge-danger hash'>".$row['encoding_cdn']."</span>";
			}
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Root</td>";
			echo "<td>";
			if(!empty($row['root'])) { echo "<span class='badge badge-secondary hash'>".$row['root']."</span>"; }
			if(!empty($row['root_cdn']) && doesFileExist("data", $row['root_cdn'], $allowedproducts["wow"]['cdndir'])) {
				echo " <span class='badge badge-success hash'>";
				if(!empty($_SESSION['loggedin'])){
					echo "<a target='_BLANK' style='color: white;' href='".generateURL("data", $row['root_cdn'], $allowedproducts["wow"]['cdndir'])."'>".$row['root_cdn']."</a>";
				}else{
					echo "<span style='color: white;'>".$row['root_cdn']."</span>";
				}
				echo "</span>";
			} else {
				echo " <span class='badge badge-danger hash'>".$row['root_cdn']."</span>";
			}
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Install</td>";
			echo "<td>";
			if(!empty($row['install'])) { echo "<span class='badge badge-secondary hash'>".$row['install']."</span>"; }
			if(!empty($row['install_cdn']) && doesFileExist("data", $row['install_cdn'], $allowedproducts["wow"]['cdndir'])) {
				echo " <span class='badge badge-success hash'>";
				if(!empty($_SESSION['loggedin'])){
					echo "<a target='_BLANK' style='color: white;' href='".generateURL("data", $row['install_cdn'], $allowedproducts["wow"]['cdndir'])."'>".$row['install_cdn']."</a>";
				}else{
					echo "<span style='color: white;'>".$row['install_cdn']."</span>";
				}
				echo "</span> <a href='/builds/extract.php?type=install&product="."wow"."&build=".$row['buildconfig']."'>(details)</a>";
			} else {
				echo " <span class='badge badge-danger hash'>".$row['install_cdn']."</span>";
			}
			echo "</td>";
			echo "</tr>";
			echo "<tr>";
			echo "<td>Download</td>";
			echo "<td>";
			if(!empty($row['download'])) { echo "<span class='badge badge-secondary hash'>".$row['download']."</span>"; }
			if(!empty($row['download_cdn']) && doesFileExist("data", $row['download_cdn'], $allowedproducts["wow"]['cdndir'])) {
				echo " <span class='badge badge-success hash'>";
				if(!empty($_SESSION['loggedin'])){
					echo "<a target='_BLANK' style='color: white;' href='".generateURL("data", $row['download_cdn'], $allowedproducts["wow"]['cdndir'])."'>".$row['download_cdn']."</a>";
				}else{
					echo "<span style='color: white;'>".$row['download_cdn']."</span>";
				}
				echo "</span>";

			} else {
				echo " <span class='badge badge-danger hash'>".$row['download_cdn']."</span>";
			}
			echo "</td>";
			echo "</tr>";
			if($row['unarchivedcount'] == 0){ $row['unarchivedcount'] = "???"; }
			if($row['unarchivedcomplete'] == $row['unarchivedcount']){
				echo "<tr style='color: green'>";
			}else{
				echo "<tr style='color: red'>";
			}

			echo "<td>Unarchived</td><td>".$row['unarchivedcomplete']."/".$row['unarchivedcount']."</td></tr>";
			echo "<td colspan='2'><a href='/builds/config.php?bc=".$row['buildconfig']."' target='_BLANK'>More information</a></td></tr>";
			echo "</table>";
			echo "</td>";

			echo "<td>";
			echo "<table>";
			echo "<tr>";
			echo "<td>";
			if(!empty($row['patch']) && doesFileExist("patch", $row['patch'], $allowedproducts["wow"]['cdndir'])) {
				echo " <span class='badge badge-success hash'>";
				if(!empty($_SESSION['loggedin'])){
					echo "<a target='_BLANK' style='color: white;' href='".generateURL("patch", $row['patch'], $allowedproducts["wow"]['cdndir'])."'>".$row['patch']."</a>";
				}else{
					echo "<span style='color: white;'>".$row['patch']."</span>";
				}
				echo "</span>";
			} else {
				echo " <span class='badge badge-danger hash'>".$row['patch']."</span>";
			}
			echo "</td>";
			echo "</tr>";
			echo "</table>";
			echo "</td>";

			echo "<td style='width: 300px'>";
			if(!empty($row['cdnconfig']) && doesFileExist("config", $row['cdnconfig'], $allowedproducts["wow"]['cdndir'])) {
				echo "<table class='table table-sm'>";
				if($row['archivecomplete'] == $row['archivecount']){
					echo "<tr style='color: green'>";
				}else{
					echo "<tr style='color: red'>";
				}
				echo "<td>Archives</td><td>".$row['archivecomplete']."/".$row['archivecount']."</td></tr>";
				if($row['indexcomplete'] == $row['archivecount']){
					echo "<tr style='color: green'>";
				}else{
					echo "<tr style='color: red'>";
				}
				echo "<td>Archive indexes</td><td>".$row['indexcomplete']."/".$row['archivecount']."</td></tr>";
				if($row['patcharchivecomplete'] == $row['patcharchivecount']){
					echo "<tr style='color: green'>";
				}else{
					echo "<tr style='color: red'>";
				}
				echo "<td>Patch archives</td><td>".$row['patcharchivecomplete']."/".$row['patcharchivecount']."</td></tr>";
				if($row['patchindexcomplete'] == $row['patcharchivecount']){
					echo "<tr style='color: green'>";
				}else{
					echo "<tr style='color: red'>";
				}
				echo "<td>Patch archive indexes</td><td>".$row['patchindexcomplete']."/".$row['patcharchivecount']."</td></tr>";
				echo "<td colspan='2'><a href='/builds/config.php?cdnc=".$row['cdnconfig']."' target='_BLANK'>More information</a></td></tr>";
				echo "</table>";
			}
			echo "</td>";
			echo "<td>&nbsp;</td>";
			echo "<td>&nbsp;</td>";
			echo "</tr>";
			if($odd){
				$odd = false;
			}else{
				$odd = true;
			}
		} ?>
	</table>
</div>
<?php require_once("../inc/footer.php"); ?>