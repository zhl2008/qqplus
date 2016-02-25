<?php
$admin_qq="2739970029";
$db_host="127.0.0.1";
$db_port="3306";
$db_user="root";
$db_passwd="";
$db_database="langrensha";////如果第一次玩，请先建立langrensha这个数据库
$command_array=["start","send_result","err_log","execute_sql","end_game","query_user_by_qq","err_log"];
$game_array=[];

//2/23 select last_insert_id 有问题，导致mid不正确，建议重写execute_sql函数

function init_tables($game_name){
	$sql="create table ".$game_name."_personal(uid int(4) not null AUTO_INCREMENT PRIMARY KEY,
			       			qq int(10) not null,
                            nickname varchar(40) not null,
							is_alive bool default 1 not null,
							is_police bool default 0 not null);
	create table ".$game_name."_public(next_speaker int(10) not null,
			                speak_order bool default 1 not null,
			                message_number int(5) default 0 not null);
	create table ".$game_name."_role (role varchar(40) not null,
						    is_alive bool default 1 not null,
			                has_save bool default 1 not null,
			                has_poison bool default 1 not null);
	create table ".$game_name."_message(mid int(5) not null AUTO_INCREMENT PRIMARY KEY,
							is_qq_group bool not null,
							qq int(10) not null,
							nickname varchar(40) not null,
							message varchar(40) not null,
							return_data varchar(1000) not null);";
	//echo $sql;
	$sqls=explode(";",$sql);
	foreach ($sqls as $sql) {
		if($sql!=""){
			execute_sql($sql,0);
		}
	}
}

function drop_tabels($game_name){
	$sql="drop table ".$game_name."_personal;drop table ".$game_name."_public;drop table ".$game_name."_role;drop table ".$game_name."_message";
	$sqls=explode(";",$sql);
	foreach ($sqls as $sql) {
		execute_sql($sql,0);
	}
}

function start(){
	if($_POST['Event']=="ReceiveClusterIM"){
		//防止残留数据表的影响
		send_result($_POST['ExternalId'],"狼人杀游戏开始！正在启动脚本，请玩家回复join加入！","SendClusterMessage");
		drop_tabels($_POST['ExternalId']);
		init_tables($_POST['ExternalId']);
		execute_sql("insert into qq_group (qq_group,Robot_qq) values (".$_POST['ExternalId'].",".$_POST['RobotQQ'].")",0);
		exec("python robots.py ".$_POST['ExternalId'],$output,$a);
		if(!$a){
			err_log($output);
		}
	}
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
	if($error=mysql_error()){
		err_log($error);
	}
	return $row;
}

function query_user_by_qq($QQ){
	$result=execute_sql("select qq_group from user where qq=".$QQ.";",0)[0];
	return $result;
}

function filter($str)
{	
	$old_str=$str;
    $str=trim($str);
    $str=str_replace(" ", "", $str);
    $str=str_replace("'", "", $str);
    $str=str_replace('"', "", $str);
	$str=str_replace("\"", "", $str);
    $str=str_replace("%", "", $str);
    $str=str_replace("&", "", $str);
    $str=str_replace("|", "", $str);
    $str=str_replace("=", "", $str);
    $str=str_replace("+", "", $str);
    $str=str_replace("\t", "", $str);
    $str=str_replace("\r", "", $str);
	$str=str_replace("<?", "", $str);
	$str=str_replace("?>", "", $str);
	$str=str_replace("\\", "", $str);
	$str=str_replace("eval", "", $str);
    return $str;
    if($old_str!=$str){
    	err_log("sql injection detected!");
    }
}



//获取python处理消息后的返回
function get_result($game_name,$mid){
	$result=0;
	echo $mid;
	//while(!$result){
		$result=execute_sql("select return_data from ".$game_name."_message where mid=".$mid.";",0)[0];
		//sleep(0.5);
	//}
	echo $result;
	//while(!$result=execute_sql("select return_data from ".$game_name."_message where mid=".$mid.";",0)[0]){
		
	//}
	return $result;
}

//消息的处理
if($_POST['Event']=="ReceiveNormalIM"||$_POST['Event']=="ReceiveClusterIM"){
	if($_POST['QQ']==$admin_qq&&$command=get_command($_POST['Message'])){
		$result=do_command($command);
		$QQ=isset($_POST['ExternalId'])?$_POST['ExternalId']:$_POST['QQ'];
		$method=isset($_POST['ExternalId'])?"SendClusterMessage":"SendMessage";
		send_result($QQ,$result,$method);
	}else{
		//私聊的消息处理
		if($_POST['Event']=="ReceiveNormalIM"&&strlen($_POST['Message'])<=40&&$game_name=query_user_by_qq($_POST['QQ'])){
			
			execute_sql("insert into ".$game_name."_message (is_qq_group,qq,nickname,message) values (0,'".$_POST['QQ']."','".$_POST['NickName']."','".filter($_POST['Message'])."');",0);

			
		//群里消息处理
		}else if($_POST['Event']=="ReceiveClusterIM"&&strlen($_POST['Message'])<=40){
			$game_name=$_POST['ExternalId'];
			//echo "insert into ".$game_name."_message (is_qq_group,qq,nickname,message) values (1,'".$_POST['QQ']."','".$_POST['Nick']."','".filter($_POST['Message'])."');";
			execute_sql("insert into ".$game_name."_message (is_qq_group,qq,nickname,message) values (1,'".$_POST['QQ']."','".$_POST['Nick']."','".filter($_POST['Message'])."');",0);
		}
		//var_dump(execute_sql("select last_insert_id();",0));
		$mid=execute_sql("select last_insert_id();",0)[0];
		sleep(1);
		$result=get_result($game_name,$mid);
		
	}
	//在python中会对消息格式进行规范化，直接输出即可
	echo $result;



	//$ret = print_r($_POST, TRUE);
	//file_put_contents('1.txt', $ret);
	//echo("<&&>SendMessage<&>2739970029<&>收到数据：\n");
}
?>