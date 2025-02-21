<?
require_once("../inc/header.php");

$encrypted = [];
$tactkeys = [];

foreach($pdo->query("SELECT keyname, filedataid FROM wow_encrypted") as $row){
	$encrypted[$row['keyname']][] = $row['filedataid'];
}

foreach($pdo->query("SELECT * FROM wow_tactkey") as $row){
	$tactkeys[$row['keyname']] = $row;
}



?>
<div class="container-fluid" style='margin-top: 15px;'>
	<? if(!empty($_GET['build'])){
		$build = $_GET['build'];
	}else{
		$build = "8.2.5.31337";
	}
	?>
	<div id='output' style='font-family: "Courier New", monospace; white-space: pre;'>

	</div>
	<script type='text/javascript'>
		var tactkeys = <?=json_encode($tactkeys)?>;
		var encrypted = <?=json_encode($encrypted)?>;
		function loadBuild(build){
			var output = new Array();
			$("#output").html("");
			console.log("Loading " + build);

			$.ajax({
				url: "/api/data/tactkey/?build=" + build + "&draw=1&start=0&length=500",
				context: document.body
			}).done(function(tactkeydata) {
				var tactkeydb = new Array();
				console.log(tactkeydata);
				tactkeydata.data.forEach(function(tactkeyentry, tactkeykey){

					var fullkey = "";
					for(var i = 1; i < 17; i++){
						fullkey += "" + parseInt(tactkeyentry[i]).toString(16).padStart(2, '0').toUpperCase();
					}

					tactkeydb[tactkeyentry[0]] = fullkey;
				});

				$.ajax({
					url: "/api/data/tactkeylookup/?build=" + build + "&draw=1&start=0&length=500",
					context: document.body
				}).done(function(data) {
					data.data.forEach(function(entry, key){
						var lookup = "";
						var reversed = entry.reverse();
						for(var i = 0; i < 8; i++){
							lookup += parseInt(reversed[i]).toString(16).padStart(2, '0').toUpperCase();
						};

						entry.reverse();
						if(lookup in tactkeys){
							if(tactkeydb[entry[0]] != null){
								tactkeys[lookup]['keybytes'] = tactkeydb[entry[0]];
							}
							if(tactkeys[lookup]['keybytes'] == null || tactkeys[lookup]['keybytes'] == ""){
								tactkeys[lookup]['keybytes'] = "????????????????????????????????";
							}

							if(tactkeys[lookup]['added'] == null || tactkeys[lookup]['added'] == ""){
								tactkeys[lookup]['added'] = "                       ";
							}

							if(tactkeys[lookup]['description'] == null){
								if(lookup in encrypted){
									if(encrypted[lookup].length > 7){
										tactkeys[lookup]['description'] = "starts at fdid " + encrypted[lookup][0] + ", " + encrypted[lookup].length + " files";
									}else{
										tactkeys[lookup]['description'] = "fdid " + encrypted[lookup].join(', ');
									}
								}else{
									tactkeys[lookup]['description'] = "";
								}
							}

							output[parseInt(entry[0])] = " <a target='_BLANK' href='https://wow.tools/files/#search=encrypted%3A" + lookup + "'>" + lookup + "</a>  " + tactkeys[lookup]['keybytes'] + "  salsa20   " + entry[0].padEnd(3, ' ') + " " + tactkeys[lookup]['added'].padEnd(24, ' ') + "  " + tactkeys[lookup]['description'];
						}else{
							var keybytes = "????????????????????????????????";
							if(tactkeydb[entry[0]] != null){
								keybytes = tactkeydb[entry[0]];
							}

							if(lookup in encrypted){
								if(encrypted[lookup].length > 7){
									var desc  = "starts at fdid " + encrypted[lookup][0] + ", " + encrypted[lookup].length + " files";
								}else{
									var desc  = "fdid " + encrypted[lookup].join(', ');
								}
							}else{
								var desc = "";
							}
							output[parseInt(entry[0])] = " <a target='_BLANK' href='https://wow.tools/files/#search=encrypted%3A" + lookup + "'>" + lookup + "</a>  " + keybytes + "  salsa20   " + entry[0].padEnd(3, ' ') + "                           " + desc;
						}
					});

					for(var i = 0; i < Object.keys(tactkeys).length; i++){
						var values = Object.values(tactkeys)[i];
						if(!(values.id in output)){
							if(tactkeydb[values.id] != undefined){
								values.keybytes = tactkeydb[values.id];
							}else if(values.keybytes == null || values.keybytes == ""){
								values.keybytes = "????????????????????????????????";
							}

							if(values.added == null || values.added == ""){
								values.added = "                       ";
							}

							output[values.id] = " <a target='_BLANK' href='https://wow.tools/files/#search=encrypted%3A" + values.keyname +"'>" + values.keyname + "</a>  " + values.keybytes + "  salsa20   " + values.id.toString().padEnd(3, ' ') + " " + values.added.padEnd(24, ' ') + "  " + values.description;
						}
					}

					output.forEach(function(line){
						$("#output").append(line + "\n");
					});
				});
			});


		}

		loadBuild("<?=$build?>");

		$('#buildFilter').on('change', function() {
			loadBuild(this.value);
		});
	</script>
</div>
<?
require_once("../inc/footer.php");