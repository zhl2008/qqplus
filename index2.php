<?php
$admin_qq="2739970029";
$db_host="127.0.0.1";
$db_port="3306";
$db_user="root";
$db_passwd="";
$db_database="langrensha";////如果第一次玩，请先建立langrensha这个数据库
$command_array=["start","send_result","err_log","execute_sql","end_game","query_user_by_qq","end_game"];
$game_array=[];

/*2/23 select last_insert_id 有问题，导致mid不正确，建议重写execute_sql函数(已解决：last_insert_id的获取必须在conn没有被释放的时候进行,
因为mysql面向对象连接获取id时并没有直接可以使用的函数，所以改用面向过程连接，面向过程连接是遇到的两个问题：1.mysql——connetc中db port加在哪？
2.需要加mysql——select——db来选择数据库；此外，err——log函数也可以正常报错了)


*/
function init_tables($game_name){
	$sql="create table ".$game_name."_personal(uid int(4) not null AUTO_INCREMENT PRIMARY KEY,
			       			qq varchar(10) not null,
                            nickname varchar(40) not null,
							is_alive bool default 1 not null,
							is_police bool default 0 not null);
	create table ".$game_name."_public(next_speaker varchar(10) default 1 not null,
			                speak_order bool default 1 not null,
			                wolf_kill int(4) default 0 not null,
			                last_protect int(4) default 0 not null
			                python_pid varchar(5) default 0 not null);
	insert into ".$game_name."_pulic (python_pid) values ('0');
	create table ".$game_name."_role (role varchar(40) not null,
							qq varchar(10) not null,
						    is_alive bool default 1 not null,
			                has_save bool default 1 not null,
			                has_poison bool default 1 not null,
			                last_save_people int(4) default 0 not null);
	create table ".$game_name."_message(mid int(5) not null AUTO_INCREMENT PRIMARY KEY,
							is_qq_group bool not null,
							qq_group varchar(10) default 0 not null,
							qq varchar(10) not null,
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
	$sql="drop table ".$game_name."_personal;drop table ".$game_name."_public;drop table ".$game_name."_role;drop table ".$game_name."_message;";
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
		execute_sql("insert into qq_group (qq_group,Robot_qq) values (".$_POST['ExternalId'].",".$_POST['RobotQQ'].");",0);
		file_put_contents('tmp',$_POST['ExternalId']);
	}
}

function end_game(){
	if($_POST['Event']=="ReceiveClusterIM"){
		send_result($_POST['ExternalId'],"狼人杀游戏结束!","SendClusterMessage");
		drop_tabels($_POST['ExternalId']);
		$pid=execute_sql("select python_pid from ".$_POST['ExternalId']."_public;");
		exec("taskkill /PID ".$pid);
	}
}

function err_log($msg){
	file_put_contents("error.log",date('y-m-d h:i:s',time())." : ".$msg."\r\n", FILE_APPEND);
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

function execute_sql($query,$is_output_mid){   
	$conn = mysql_connect($GLOBALS["db_host"], $GLOBALS["db_user"], $GLOBALS["db_passwd"], $GLOBALS["db_database"]);
	if(!$conn)
	{
		return -1;
		err_log("数据库连接错误");
	}
	mysql_select_db($GLOBALS["db_database"],$conn);
	//echo $query;
	if(!$query){
		return "";
	}
	$result=mysql_query($query,$conn);
	$row=array();
	if(is_resource($result)){
		$row=mysql_fetch_row($result);
	}
	if($is_output_mid){
		$row=mysql_insert_id();
	}
	
	if($error=mysql_error($conn)){
		err_log($error." your query is :".$query);
	}
	mysql_close($conn);
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
	$result="";
	//echo $mid;
	while(!$result){
		$result=execute_sql("select return_data from ".$game_name."_message where mid=".$mid.";",0)[0];
		sleep(0.5);
	}
	//如果返回时noreply说明不需要返回，这时返回为空字符串
	if(strpos("noreply",$result)){
		return "";
	}
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
			
			$mid=execute_sql("insert into ".$game_name."_message (is_qq_group,qq,nickname,message) values (0,'".$_POST['QQ']."','".$_POST['NickName']."','".filter($_POST['Message'])."');",1);

			
		//群里消息处理
		}else if($_POST['Event']=="ReceiveClusterIM"&&strlen($_POST['Message'])<=40){
			$game_name=$_POST['ExternalId'];
			//echo "insert into ".$game_name."_message (is_qq_group,qq,nickname,message) values (1,'".$_POST['QQ']."','".$_POST['Nick']."','".filter($_POST['Message'])."');";
			$mid=execute_sql("insert into ".$game_name."_message (is_qq_group,qq,nickname,message,qq_group) values (1,'".$_POST['QQ']."','".$_POST['Nick']."','".filter($_POST['Message'])."','".$game_name."');",1);
		}
		//var_dump(execute_sql("select last_insert_id();",0));
		sleep(1);
		$result=get_result($game_name,$mid);
		if($command=get_command($result)){
			$result=do_command($command)
		}
		
	}
	//在python中会对消息格式进行规范化，直接输出即可
	echo $result;



	//$ret = print_r($_POST, TRUE);
	//file_put_contents('1.txt', $ret);
	//echo("<&&>SendMessage<&>2739970029<&>收到数据：\n");
}
?>