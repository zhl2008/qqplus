<?php
//config
$admin_qq='2739970029';
$command_array=['start','alive','stop','addtime','prepare','end_game','query_role_by_qq','query_group_by_qq'];
$config_array=[['巫神','预言家','猎神','白神','村民1','村民2','村民3','村民4','狼','狼','狼','狼'],//12
['巫神','预言家','猎神','守护神','村民1','村民2','村民3','村民4','狼','狼','狼'],//11
['巫神','预言家','猎神','白神','村民1','村民2','村民3','狼','狼','狼'], //10
['巫神','预言家','猎神','狼1','狼2','狼3'],//6
];
$game_array=['天黑请闭眼','天亮了','下面开始警长竞选','投票放逐','狼人猎杀','女巫解药','女巫毒药','守护神请选择守护目标','预言家验人'];
$game_func=['night_time','day_time','vote_for_police','vote_for_out','wolf_kill','witch_save','witch_kill','protect_protect','check_role'];


function test_echo($out){
	echo("<&&>SendMessage<&>".$GLOBALS["admin_qq"]."<&>the test output is:".$out."\n");
}

function get_command($Message){
	//命令的格式例如command:::ipconfig,如果是命令则返回ipconfig
	$result=explode(":::",$Message);
	//test_echo($result);
	if($result[0]=="command"){
		return $result[1];
	}else{
		return 0;
	}
}

function do_command($Message){
	//命令和参数分离
	$result=explode(":",$Message);
	$command=$result[0];
	test_echo(print_r($result,TRUE));
	if(count($result)>1){
		unset($result[0]);
	}
	if(in_array($command, $GLOBALS["command_array"])){
		
		return call_user_func_array($command,$result);	

	}else if(in_array($command, $GLOBALS["game_array"])){//game_array存放游戏阶段指令，也代表现在的游戏阶段
		
		call_user_func($command);

	}else{
		exec($command,$output,$a);
		return implode("\n",$output);
	}
}
function send_result($QQs,$result,$method){
if(!is_array($QQs)){
	$tmp=$QQs;
	$QQs=array();
	$QQs[]=$tmp;
}
foreach ($QQs as $QQ) {
			if($result){
				echo("<&&>".$method."<&>".$QQ."<&>".$result."\n");
			}
	}	

}
//通过qq号来查询玩家所在的qq群，这里默认一个玩家只在一个群内开始游戏
function query_group_by_qq($QQ){
	$groups=$GLOBALS['variables']['qq_group'];
	foreach ($groups as $group) {
		if(in_array($QQ, $GLOBALS['variables'][$group])){
			return explode('_',$group)[1];
		}
	}
	return 0;

}
//通过qq号来查询玩家角色
function query_role_by_qq($QQ){
	$game_name="game_".query_group_by_qq($QQ);
	for ($i=1; $i < count($GLOBALS['variables'][$game_name])+1; $i++) { 
		if(in_array($QQ, $GLOBALS['variables']['user_'.$i])){
			return urldecode($GLOBALS['variables']['user_'.$i][3]);
		}
	}
}

function query_qq_by_role($role){
	$game_name="game_".$GLOBALS["variables"]["game_name"];
	$result=array();
	for ($i=1; $i < count($GLOBALS['variables'][$game_name])+1; $i++) { 
		if(urlencode($role)==$GLOBALS['variables']['user_'.$i][3]){
			$result[]=$GLOBALS['variables']['user_'.$i][0];
		}
	}
	test_echo(print_r($result,TRUE));
	return $result;
}

function broardcast($game_name,$Message){
	$players=$GLOBALS['variables'][$game_name];
	foreach ($players as $player) {
		send_result($player,$Message,"SendMessage");
	}
}

//狼人杀函数

//游戏开始，下一步统计参与人数
function start(){
	//必须在群里面开始
	if(!isset($_POST["ExternalId"])){
		test_echo("必须在群里面开始游戏");
	}else{
		//不同状态下json文件内容不同，json文件中保存着用户数目，每名用户对应的身份,存活状态，昵称，QQ号；神明的身份，两瓶药的状态，是否保护了自己
		file_put_contents($_POST["ExternalId"].".json","");
		send_result($_POST["ExternalId"],"狼人杀游戏开始！请管理员对游戏进行配置","SendClusterMessage");
	}
}
function end_game(){
	file_put_contents('variable.json', '{"qq_group":["game_542858122"],"game_542858122":[]}');

}

function add_player($game_name){

test_echo("add success");
$user_number=count($GLOBALS["variables"][$game_name])+1;
array_push($GLOBALS["variables"][$game_name],$_POST["QQ"]); //array push要直接给全局变量
$GLOBALS["variables"]["user_".$user_number]=array();
array_push($GLOBALS["variables"]["user_".$user_number], $_POST['QQ'],urlencode($_POST['Nick']),"1","","0");//用户qq,昵称(经过urlencode），死亡状态,身份，是否是警长
print_r($GLOBALS["variables"]);
file_put_contents('variable.json', json_encode($GLOBALS["variables"]));
send_result($_POST["ExternalId"],"玩家".$_POST['Nick']."已加入游戏，游戏编号为 ".$user_number." 。","SendClusterMessage");
}

function prepare(){
	$game_name='game_'.$_POST['ExternalId'];
	$user_number=count($GLOBALS["variables"][$game_name]);
	$output="";
	//echo $user_number;
	if($user_number<13&&$user_number>9){
		$role_config=$GLOBALS['config_array'][12-$user_number];
		shuffle($role_config);
		//进行游戏的qq群
		$GLOBALS["variables"]["game_name"]=$_POST["ExternalId"];
		//初始化角色数组
		$GLOBALS["variables"]["roles"]=array();
		//初始化下一个执行的函数(用其在game_array中的位置表示)
		$GLOBALS["variables"]["next_function"]="0";
		//默认是第一天夜晚
		$GLOBALS["variables"]["is_first_night"]="1";
		//默认是第一天白天
		$GLOBALS["variables"]["is_first_day"]="1";
		//默认第一个发言的人
		$GLOBALS["variables"]["first_speaker"]="1";
		//默认发言的顺序(递增1或递减0)
		$GLOBALS["variables"]["speak_order"]="1";

		for ($i=1; $i < $user_number+1; $i++) { 
			$output=$output.$i.'号玩家：'.urldecode($GLOBALS["variables"]["user_".$i][1])."\n";
			$user_output=$i.'号玩家：您的身份是：'.$role_config[$i-1];
			send_result($GLOBALS["variables"]["user_".$i][0],$user_output,"SendMessage");
			$GLOBALS["variables"]["user_".$i][3]=urlencode($role_config[$i-1]);
			//是否有解药，是否有毒药，保护的人的id，是否存活
			array_push($GLOBALS["variables"]["roles"],array(urlencode($role_config[$i-1]),"1","1","0","1"));

		}

        send_result($_POST["ExternalId"],$output,"SendClusterMessage");
		test_echo(print_r($role_config,TRUE));
		file_put_contents('variable.json', json_encode($GLOBALS["variables"]));//更新变量表
		send_result($_POST["ExternalId"],$GLOBALS["game_array"][$GLOBALS["variables"]["next_function"]],"SendClusterMessage");
		call_user_func("night_time");
	}else{
		send_result($_POST["ExternalId"],"游戏人数错误！！！","SendClusterMessage");
		//end_game();
	}
}

function alive(){
	$game_name='game_'.$_POST['ExternalId'];
	$user_number=count($GLOBALS["variables"][$game_name]);
	$alive_player=array();
	for ($i=1; $i < $user_number+1; $i++) { 
		echo $GLOBALS["variables"]["user_".$i][2];
		if($GLOBALS["variables"]["user_".$i][2]){
			$alive_player_with_role[]=array($i,urldecode($GLOBALS["variables"]["user_".$i][1]),urldecode($GLOBALS["variables"]["user_".$i][3]));
			$alive_player[]=array($i,urldecode($GLOBALS["variables"]["user_".$i][1]));
		}
	}
	send_result($_POST["ExternalId"],print_r($alive_player,TRUE),"SendClusterMessage");
	test_echo(print_r($alive_player_with_role,TRUE));
}
function store(){
	file_put_contents('variable.json', json_encode($GLOBALS["variables"]));
}

//游戏函数
function night_time(){
	//判断是否是第一天晚上

	if($GLOBALS["variables"]["is_first_night"]){
		/*这里会有盗贼，丘比特，混血儿，野孩子的处理函数
		*/
		$GLOBALS["variables"]["is_first_night"]="0";
		store();
	}
		//守护神睁眼
		if(in_array("守护神",$GLOBALS["variables"]["roles"]) ){
			$GLOBALS["variables"]["next_function"]="7";
			store();
			send_result(query_qq_by_role("守护神"),$GLOBALS["game_array"][$GLOBALS["variables"]["next_function"]],"SendMessage");
		}else{
			//如果没有守护神，狼人睁眼
			$GLOBALS["variables"]["next_function"]="4";
			send_result(query_qq_by_role("狼"),$GLOBALS["game_array"][$GLOBALS["variables"]["next_function"]],"SendMessage");
		}
	
}

//消息的处理

if($_POST['Event']=="ReceiveNormalIM"||$_POST['Event']=="ReceiveClusterIM"){
	//$Message=deal_message($_POST['Message']);
	//加载json变量文件
	
	//echo $str;
	$variables=json_decode(file_get_contents("variable.json"),TRUE);
	foreach ($variables as $key => $value) {
		$$key=$value;
	}


	
	if($_POST['QQ']==$admin_qq&&$command=get_command($_POST['Message'])){
		$result=do_command($command);
		$QQ=isset($_POST['ExternalId'])?$_POST['ExternalId']:$_POST['QQ'];
		$method=isset($_POST['ExternalId'])?"SendClusterMessage":"SendMessage";
		send_result($QQ,$result,$method);
	}else{

		if($_POST['Message']=='join'&&$_POST['Event']=="ReceiveClusterIM"){
			$game_name="game_".$_POST['ExternalId'];
			//echo $game_name;
			//print_r($$game_name);
			if(!in_array($_POST['QQ'], $$game_name)){
				add_player($game_name);
			}
		}



	}



	$ret = print_r($_POST, TRUE);
	file_put_contents('1.txt', $ret);
	//echo("<&&>SendMessage<&>2739970029<&>收到数据：\n");
}


?>
