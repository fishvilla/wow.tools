<?php require_once("../inc/header.php");

$filelimit = 20000;

$kfq = $pdo->query("SELECT id, filename FROM wow_rootfiles WHERE verified = 0")->fetchAll();
foreach($kfq as $row){
	$knownfiles[$row['id']] = $row['filename'];
}

$uq = $pdo->prepare("UPDATE wow_rootfiles SET filename = ? WHERE id = ? AND verified = 0");

$cq = $pdo->prepare("SELECT id FROM wow_rootfiles WHERE filename = ?");

if(!empty($_SESSION['loggedin'])){
	if(!empty($_POST['files'])){

		$files = explode("\n", $_POST['files']);

		if(count($files) > $filelimit){
			die("There currently is a limit of <b>".$filelimit." files</b> per request. You entered ".count($files)." files.");
		}

		$log = [];
		$suggestedfiles = [];

		foreach($files as $file){
			if(empty($file))
				continue;

			$split = explode(";", $file);
			$fdid = (int)$split[0];
			if(count($split) != 2)
			{
				$log[] = "An error occurred parsing a line, please check that the format is valid (fdid;filename).";
				continue;
			}

			$fname = strtolower(str_replace("\\", "/", trim($split[1])));

			if(array_key_exists($fdid, $knownfiles)){
				if(empty($knownfiles[$fdid])){
					// No filename currently set
					$cq->execute([$fname]);
					$cr = $cq->fetch(PDO::FETCH_ASSOC);
					if(empty($cr)){
						$log[] = "Adding <kbd>".$fname."</kbd> to ".$fdid;
						$suggestedfiles[$fdid] = $fname;
					}else{
						$log[] = "<b>WARNING!</b> Filename " . $fname . " already exists as FileDataID " . $cr['id']."!";
					}

				}else if($knownfiles[$fdid] != $fname){
					// Submitted filename differs from current filename
					if(!isset($_POST['onlynew'])){
						$log[] = "Overriding <kbd>".$knownfiles[$fdid]."</kbd> (".$fdid.") with <kbd>".$fname."</kbd>";
						$suggestedfiles[$fdid] = $fname;
					}else{
						$log[] = "Would usually overriding <kbd>".$knownfiles[$fdid]."</kbd> (".$fdid.") with <kbd>".$fname."</kbd>, but checkbox to skip known files is set";
					}
				}else{
					// Submitted filename is the same
					$log[] = "Skipping <kbd>".$fname."</kbd>, same as <kbd>".$knownfiles[$fdid]."</kbd> (".$fdid.")";
				}
			}else{
				// File does not exist
				$log[] = "<b>WARNING!</b> FileDataID " . $fdid . " does not exist or is a file with a bruteforcable lookup!";
			}
		}

		$isq = $pdo->prepare("INSERT INTO wow_rootfiles_suggestions (filedataid, filename, userid, submitted) VALUES (?, ?, ?, ?)");

		if(empty($_POST['checkBox'])){
			if(isset($_POST['skipQueue']) && isset($_SESSION['rank']) && $_SESSION['rank'] > 0){
				// Send to wow_rootfiles
				foreach($suggestedfiles as $fdid => $fname){
					$uq->execute([$fname, $fdid]);
				}
			}else{
				// Send to queue

				// Set insert time to one value in case in case things take longer than a second to insert
				$time = date("Y-m-d H:i:s");

				foreach($suggestedfiles as $fdid => $fname){
					$isq->execute([$fdid, $fname, $_SESSION['userid'], $time]);
				}
				if(count($suggestedfiles) > 0){
					$message = "Submitted " . count($suggestedfiles) . " files to the [moderation queue](https://wow.tools/files/submitQueue.php).```";
					foreach($suggestedfiles as $fdid => $fname){
						$line = $fdid . " => " . $fname ."\n";
						if((strlen($message) + strlen($line) + 3) < 2000){
							$message.= $line;
						}
					}
					$message .= "```";
					$json = json_encode([ "username" => getUsernameByUserID($_SESSION['userid']), "content" => $message]);
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $discordfilenames);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
					curl_setopt($ch, CURLOPT_USERAGENT, "WoW.Tools Discord Integration");
					curl_setopt($ch, CURLOPT_HTTPHEADER, ["Length" => strlen($json), "Content-Type" => "application/json"]);
					$response = curl_exec($ch);
					curl_close($ch);
				}
			}
		}else{
			echo "<div class='alert alert-warning'><b>Warning</b> Currently only comparing with listfile, not saving anything to database.</div>";
		}

		$wroteSomething = false;

		if($wroteSomething){
			flushQueryCache();
		}

		echo "<div class='container-fluid'>";
		echo "<h4>Log</h4>";
		echo "<pre style='max-height: 500px; overflow-y: scroll'>";
		echo implode("\n", $log);
		echo "</pre>";
		echo "</div>";
	}
}

?>
<div class="container-fluid">
	<?php if(empty($_SESSION['loggedin'])){?>
		<div class='alert alert-danger'>
			You need to be logged in to submit filenames.
		</div>
	<?php }else{ ?>
		<p>Enter files in the textbox below to suggest filenames for the community listfile. Each line must start with a filedataid, followed by the <kbd>;</kbd> and then the suggested filename.<br><b>Please note:</b> All submitted files will have to be checked by a moderator before being added to the listfile to prevent purposefully incorrect filenames being added to the system.</p>
		<p>Formatting example:<br><kbd>2961114;world/expansion07/doodads/dungeon/doodads/8du_mechagon_anvil01.m2</kbd><br><kbd>2961119;world/expansion07/doodads/dungeon/doodads/8du_mechagon_anvil0100.skin</kbd></p>
		<div class='alert alert-warning'>A maximum of <b><?=$filelimit?> files</b> per request is allowed.</div>
		<form method='post' action='submitFiles.php'>
			<input id='checkBox' type='checkbox' name='checkBox'> <label for='checkBox'>Do not actually submit anything and just compare with current listfile ("dry-run")</label>
			<br>
			<input id='onlynewBox' type='checkbox' name='onlyNew'> <label for='onlynewBox'>Skip files that already have a name and only add new ones</label>
			<br>
			<?php if($_SESSION['rank'] > 0){ ?><input id='skipBox' type='checkbox' name='skipQueue'> <label for='skipBox'>Skip queue and write directly to DB <b>(Mod-only, please only use if queue is broken)</b></label><br><?}?>
			<textarea name='files' rows='15' cols='200'></textarea>
			<br>
			<input class='btn btn-success' type='submit' value='Submit'>
		</form>
	<?php } ?>
</div>

<?php include("../inc/footer.php"); ?>