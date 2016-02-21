<?php
$admin_qq="2739970029";
$db_host="127.0.0.1";
$db_port="3306";
$db_user="root";
$db_passwd="";
$db_database="langrensha";////如果第一次玩，请先建立langrensha这个数据库
$command_array=["start","send_result","err_log","execute_sql"];
$game_array=[];



function init_tabls($game_name){
	$sql="create table ".$game_name."_personal(uid int(4) not null AUTO_INCREMENT PRIMARY KEY,
			       			   qq int(10) not null,
                               nickname varchar(40) not null,
							   is_alive bool default 1 not null,
							   is_police bool default 0 not null)
create table ".$game_name."_public(next_speaker int(10) not null,
			                   speak_order bool default 1 not null)
create table ".$game_name."_role(role varchar(40) not null,
						   is_alive bool fefault 1 not null,
			               has_save bool default 1 not null,
			               has_poison bool default 1 not null);";

$sqls=explode(";",$sql);
foreach ($sqls as $sql) {
	execute_sql($sql,0);
}
}

function start(){

}

function err_log($msg){
	file_put_contents("error.log",date('y-m-d h:i:s',time())." : ".$msg, FILE_APPEND);
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

function test_echo($out){
	echo("<&&>SendMessage<&>".$GLOBALS["admin_qq"]."<&>the test output is:".$out."\n");
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

function execute_sql($query,$is_output){   
	$conn = new mysqli($GLOBALS["db_host"], $GLOBALS["db_user"], $GLOBALS["db_passwd"], $GLOBALS["db_database"], $GLOBALS["db_port"]);
	if(!$conn)
	{
		return -1;
		err_log("数据库连接错误");
	}
	$result=$conn->query($query);
	$row=array();
	if(is_object($result)){
		$row=$result->fetch_row();
	}
	if($is_output){
			if(isset($result)){
				echo "success!<br>";
    			while($row){
					$count=count($row);
					for($i=0;$i<($count);$i++){
						echo $row[$i];
						echo " ";			
					}
				echo "<br>";
				}
			}else
			{
			err_log("返回为空");
			}
	}
	$conn->close();
	return $row;
}

//消息的处理
if($_POST['Event']=="ReceiveNormalIM"||$_POST['Event']=="ReceiveClusterIM"){
	if($_POST['QQ']==$admin_qq&&$command=get_command($_POST['Message'])){
		$result=do_command($command);
		$QQ=isset($_POST['ExternalId'])?$_POST['ExternalId']:$_POST['QQ'];
		$method=isset($_POST['ExternalId'])?"SendClusterMessage":"SendMessage";
		send_result($QQ,$result,$method);
	}else{
		if($_POST['Event']=="ReceiveNormalIM"){

			

		}
		



	}



	//$ret = print_r($_POST, TRUE);
	//file_put_contents('1.txt', $ret);
	//echo("<&&>SendMessage<&>2739970029<&>收到数据：\n");
}
?>