<?php require_once("inc/header.php"); ?>
<div class='container-fluid'>
	<h3>Welcome to WoW.tools!</h3>
	<p>
		Having had many different sites for many of the tools I work on, I decided to move them all under one roof. This is what WoW.tools is, a collection for all those tools under one roof, allowing for better integration between the tools and costing me less time to keep everything up-to-date separately. Keep in mind many of the tools are still only meant for the technical/datamining minded amongst you, some stuff might not be friendly to beginners at this stage. I plan on adding tutorials/guides on how to get started doing some basic stuff.
	</p>
	<div class='row'>
		<div class='col-md-6'>
			<h4>Recent updates</h4>
			<table class='table table-striped table-condensed' style='width: 100%'>
			<thead><tr><th style='min-width: 140px'>Project</th><th>Description</th><th style='min-width: 300px'>Author / date</th></tr></thead>
			<?php
				if(!$memcached->get("github.commits.json") || strtotime("-5 minutes") > $memcached->get("github.commits.lastupdated")){
					$commits = [];

					$i = 0;
					$res = githubRequest("repos/marlamin/wow.tools/commits");
					foreach($res as $commit){
						$commits[] = array("repo" => "Website", "message" => $commit['commit']['message'], "author" => $commit['author']['login'], "timestamp" => strtotime($commit['commit']['author']['date']), "url" => $commit['html_url']);
						$i++;
						if($i > 10) break;
					}

					$i = 0;
					$res = githubRequest("repos/marlamin/casctoolhost/commits");
					foreach($res as $commit){
						$commits[] = array("repo" => "File backend","message" => $commit['commit']['message'], "author" => $commit['author']['login'], "timestamp" => strtotime($commit['commit']['author']['date']), "url" => $commit['html_url']);
						$i++;
						if($i > 10) break;
					}

					$i = 0;
					$res = githubRequest("repos/marlamin/dbcdumphost/commits");
					foreach($res as $commit){
						$commits[] = array("repo" => "DBC backend","message" => $commit['commit']['message'], "author" => $commit['author']['login'], "timestamp" => strtotime($commit['commit']['author']['date']), "url" => $commit['html_url']);
						$i++;
						if($i > 10) break;
					}

					$i = 0;
					$res = githubRequest("repos/wowdev/wowdbdefs/commits");
					foreach($res as $commit){
						$commits[] = array("repo" => "DBC definitions", "message" => $commit['commit']['message'], "author" => $commit['author']['login'], "timestamp" => strtotime($commit['commit']['author']['date']), "url" => $commit['html_url']);
						$i++;
						if($i > 10) break;
					}

					usort($commits, "compareTimestamp");
					$memcached->set("github.commits.json", json_encode(array_slice($commits, 0, 15)));
					$memcached->set("github.commits.lastupdated", strtotime("now"));
				}

				$commits = json_decode($memcached->get("github.commits.json"));
				foreach($commits as $commit){
					echo "<tr><td>".$commit->repo."</td><td><a target='_BLANK' href='".$commit->url."'>".$commit->message."</a></td><td>By <b>".$commit->author."</b> on <b>".date("Y-m-d H:i:s", $commit->timestamp)."</b></td></tr>";
				}
			?>
			</table>
		</div>
		<div class='col-md-6'>
			<h4>Current WoW versions per branch</h4>
			<table class='table table-condensed table-striped table-hover' style='width: 450px;'>
				<thead><tr><th>Name</th><th>Version</th><th>Build time (PT)</th></tr></thead>
				<?php
				$productq = $pdo->query("SELECT id, name FROM ngdp_urls WHERE url LIKE '%wow%versions' ORDER BY ID ASC");
				while($row = $productq->fetch(PDO::FETCH_ASSOC)){
					$histq = $pdo->prepare("SELECT newvalue, timestamp FROM ngdp_history WHERE url_id = ? AND event = 'valuechange' ORDER BY ID DESC LIMIT 1");
					$histq->execute([$row['id']]);
					$histr = $histq->fetch(PDO::FETCH_ASSOC);
					$bc = parseBPSV(explode("\n", $histr['newvalue']));
					$highestBuild = 0;
					$highestBuildName = "<i>Unknown</i>";
					$buildTime = "<i>Unknown</i>";
					foreach($bc as $bcregion){
						if($bcregion['BuildId'] > $highestBuild){
							$highestBuild = $bcregion['BuildId'];
							$highestBuildName = $bcregion['VersionsName'];
							$highestConfig = $bcregion['BuildConfig'];
							$build = getBuildConfigByBuildConfigHash($bcregion['BuildConfig']);
							if(!empty($build['builton'])){
								$buildTime = $build['builton'];
							}
						}
					}

					echo "<tr><td>".str_replace(" Versions", "", $row['name'])."</td><td>" . $highestBuildName."</td><td>".$buildTime."</td></tr>";
				}
				?>
			</table>
		</div>
	</div>
</div>
<?php require_once("inc/footer.php"); ?>