<?php

?>
<html><body>
<h2>RadioCapture-local web development environment</h2>
<?php



$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$channelizers = $redis->sMembers('channelizers');

$demod_types = array("p25", "moto", "edacs");

$demods = array();
foreach($demod_types as $demod_type){
	$demods[$demod_type] = $redis->sMembers("demod:{$demod_type}");
}

$channelizers_deep = array();

foreach($channelizers as $key => $channelizer_uuid){
	$channelizer = $redis->get($channelizer_uuid);
	$channelizers_deep[$channelizer_uuid] = json_decode($channelizer, true);
}
$channelizers = $channelizers_deep;

$demods_deep = array();
foreach($demod_types as $demod_type){
	foreach($demods[$demod_type] as $key => $demod_uuid){
		$demod = $redis->get($demod_uuid);
		$demods_deep[$demod_uuid] = json_decode($demod, true);
		$demods_deep[$demod_uuid]['demod_type'] = $demod_type;
	}
}

$demods = $demods_deep;

?>
<h3>Active Channelizers</h3>
<table border=1>
<tr><th>UUID</th><th>pid</th><th>Uptime</th><th>Host</th><th>Addr:Port</th><th>channels</th><th>sources</th><th>source params (center_freq:bandwidth)</th></tr>
<?php
foreach($channelizers as $uuid => $c){
	$start_time = new DateTime();
	$start_time->setTimestamp(round($c['start_time']));
	$current_time = new DateTime();
	$current_time->setTimestamp(round($c['current_time']));
	$uptime = $current_time->diff($start_time)->format('%a:%H:%I:%S');
	?>
	<tr>
		<td><?=$c['instance_uuid'];?></td>
		<td><?=$c['pid'];?></td>
		<td><?=$uptime;?></td>
		<td><?=$c['hostname'];?></td>
		<td><?=$c['address'];?>:<?=$c['port'];?></td>
		<td><?=$c['channel_count'];?></td>
		<td><?=$c['source_count'];?></td>
		<td>
		<?php
			foreach($c['sources'] as $key => $source){
				?>(<?=$source[0];?>:<?=$source[1];?>)<?php
			}
?>
		</td>

	</tr>
<?php
}
?>
</table>
<br /><br />
<style>
.demods>tbody>tr>td:nth-child(1),
.demods>tbody>tr>td:nth-child(2),
.demods>tbody>tr>td:nth-child(3),
.demods>tbody>tr>td:nth-child(4),
.demods>tbody>tr>td:nth-child(5)

{

	font-size: 5px;
}

</style>
<h2> Active Demodulators</h2><br />
(Quality is a bit wonky because some sites have double data rates so their max is 200% quality)
<table border=1 class="demods">
<tr><th >instance_uuid</th><th>system_uuid</th><th>site_uuid</th><th>transmit_site_uuid</th><th>overseer_uuid</th><th>WACN</th><th>System ID</th><th>RFSS-Site ID</th><th>Service Class</th><th>RFSS Net conn</th><th>NAC</th><th>Control Channel</th><th>Type<th>quality last 10 min</th></tr>
<?php
foreach($demods as $uuid => $d){
?><tr>
	<td><?=$d['instance_uuid'];?></td>
	<td><?=$d['system_uuid'];?></td>
	<td><?=$d['site_uuid'];?></td>
	<td><?=$d['transmit_site_uuid'];?></td>
	<td><?=$d['overseer_uuid'];?></td>
	<td><?=$d['site_detail']['WACN ID'];?></td>
	<td><?=$d['site_detail']['System ID'];?></td>
	<td><?=$d['site_detail']['RF Sub-system ID'];?>-<?=$d['site_detail']['Site ID'];?></td>
	<td><?=$d['site_detail']['System Service Class'];?></td>
	<td><?=$d['site_detail']['RFSS Network Connection'];?></td>
	<td><?=$d['site_detail']['NAC'];?></td>
	<td><?=$d['site_detail']['Control Channel'];?></td>
	<td><?=$d['demod_type'];?> / <?=$d['system_modulation'];?></td>
	<td bgcolor="#696969"><canvas id="quality-<?=$uuid;?>"></canvas></td>
</tr>
<?php
}
?>
</table>
<script>
function drawBar(ctx, upperLeftCornerX, upperLeftCornerY, width, height,color){
    ctx.save();
    ctx.fillStyle=color;
    ctx.fillRect(upperLeftCornerX,upperLeftCornerY,width,height);
    ctx.restore();
}
function loaddata(){
<?php
foreach($demods as $uuid =>$d){
	?>
var canvas = document.getElementById("quality-<?=$uuid;?>");
canvas.width=300;
canvas.height=20;
var ctx= canvas.getContext("2d");

	<?php
	$i = 1;
	foreach($d['site_status'] as $key => $status){
		if( $status < 0.3){
			$color = "#FF00FF";
		}elseif($status < 0.5){
			$color = "#FF0000";
		}elseif($status < 0.85){
			$color = "#FFFF00";
		}elseif($status < 1.1){
			$color = "#00FF00";
		}elseif($status < 1.3){
			$color = "#FF00FF";
		}elseif($status < 1.5){
			$color = "#FF0000";
		}else{
			$color = "#00FF00";
		}

		$y = 20-round($status*10);
		$width = 5;
		$x = $width*$i;
		$height = round(20-($y));
		
		echo "drawBar(ctx, $x, $y, $width, $height, '$color');\n";
		$i++;
	}	

}
?>
};
loaddata();
</script>
