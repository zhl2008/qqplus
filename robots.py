# -*- coding: utf-8 -*-
import MySQLdb
import os
import config
import time
import random
import sys



#打印出pid由php负责存入数据库，可以在end时通过taskkill来结束进程
#config saved in config.py


def err_log(msg):
    open('error.log','a+').write("%s : %s\n"%(time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time())),msg))

class execute_sql():
    def __init__(self):
        self.con=MySQLdb.connect(host=config.host,user=config.user,passwd=config.passwd,db=config.db,\
                            port=config.port,charset="utf8")
        self.cursor=self.con.cursor()
        self.insert_pid()
        
    def get_message(self,mid):
        sql="select is_qq_group,qq,message,qq_group,nickname from %s_message where mid=%d"%(sys.argv[1],mid)
        return self.execute(sql)
        
    def insert_pid(self):
        sql="insert into %s_public (python_pid) values ('%s')"%(sys.argv[1],str(os.getpid()))
        return self.execute(sql)
    
    def write_return_data(self,mid,return_data):
        sql="update %s_message set return_data='%s' where mid=%d"%(sys.argv[1],return_data,mid-1)#修正为mid-1
        print "the return is %s"%return_data
        return self.execute(sql)

    def check_exist_player(self,qq):
        sql="select * from user where qq=%s"%qq
        return self.execute(sql)
    
    def execute(self,sql):
        self.cursor.execute(sql)
        self.con.commit()
        return self.cursor.fetchall()
    
    def insert_player(self,qq,qq_group,nickname):
        sql="insert into user (qq,qq_group,nickname) values ('%s','%s','%s')"%(qq,qq_group,nickname)
        self.cursor.execute(sql)
        sql="insert into %s_personal (qq,nickname) values ('%s','%s')"%(qq_group,qq,nickname)
        self.cursor.execute(sql)
        print "one player has joined game"
        
    def insert_role(self,qq,qq_group,role):
        sql="insert into %s_role (qq,role) values ('%s','%s')"%(qq_group,qq,role)
        self.cursor.execute(sql)
        
    def query_qq_by_id(self,uid):
        sql="select qq from %s_perosonal where uid=%d"%(sys.argv[1],uid)
        self.cursor.execute(sql)

    def query_qqs_by_role(self,role):
        sql="select qq from %s_role where role=%s"%(sys.argv[1],role)
        self.cursor.execute(sql)
    
    def role_is_alive_or_exist(self,role):
        sql="select qq form %s_role where role=%s and is_alive=1"%(sys.argv[1],role)
        self.cursor.execute(sql)



    def mysql_error(self):
        pass
    
    def __del__(self):
        self.cursor.close()

#对于对execute_sql类的应用全部包括message类中，message类包括对消息队列的循环获取，其他通过数据库获取相关信息的方法，     
class message():
    def __init__(self):
        self.mysql=execute_sql()
        self.mid=1
        
    def get_message(self):
        self.data=self.mysql.get_message(self.mid)
        while not self.data:
            self.data=self.mysql.get_message(self.mid)
            time.sleep(0.5)
        self.mid+=1
        return self.data
    def serialize_data(self,qq,method,return_data):
        return '<&&>'+method+'<&>'+qq+'<&>'+return_data
        
    def write_return_data(self,qq,method,return_data):
        self.mysql.write_return_data(self.mid,self.serialize_data(qq,method,return_data))

    def check_exist_player(self,qq):
        if not self.mysql.check_exist_player(qq):
            return 0
        else:
            return 1
    def insert_player(self,qq,qq_group,nickname):
        self.mysql.insert_player(qq,qq_group,nickname)

    def insert_role(self,qq,qq_group,role):
        self.mysql.insert_role(qq,qq_group,role)
        
    def send_return_data(self,qq,method,return_data):
        con=httplib.HTTPConnection("%s"%config.host,config.listen_port,timeout=5)
        con.request("GET","/?key=%s&a=%s"%(cofig.key,self.serialize_data(qq,method,return_data)))
        con.close()  


class night():
    def __init__(self):
        self.is_first_night=1

    def start(self,a):
        a.send_return_data(qq_group,"SendClusterMessage","the night is comming!")
        self.fisrt_night(a)

    def fisrt_night(self,a):
        #第一夜
        if(self.is_first_night):
            if(role_is_alive_or_exist('love_god')){
                a.write_return_data(role_is_alive_or_exist('love_god'),"SendMessage","please choose the number of the player you want to connect")
                #爱神的判断逻辑        
            }
            if(role_is_alive_or_exist('')):
                pass
            is_first_night=0
        #其他的夜晚   
        self.wolf_kill(a)
    def wolf_kill(self,a):   
        for wolf in range(self.role_is_alive_or_exist("wolf")):
            a.send_return_data(wolf,"SendMessage","please choose a people to kill,just send the number of that people,and you only have 40s to decide")
        [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
        vote_array={}
        now_time=time.time()
        while (len(vote_array)<len(role_is_alive_or_exist("wolf")) or check_vote(vote_array) or(time.time()-now_time)>40):
            if not is_qq_group and message.isdigit():
                vote_array[qq]=int(message)
                return_data=""
                for vote_wolf in vote_array:
                    return_data+="wolf %s has vote to kill %d\n"%(vote_wolf,vote_array[vote_wolf])
                for wolf in self.role_is_alive_or_exist("wolf"):
                     a.send_return_data(wolf,"SendMessage",return_data+"you can vote again to change your choice")
            [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
        if(check_vote(vote_array)):
            


            



class day():
    def __init__(self):
        pass


#全局函数的定义
    
#判断用户是否有相关执行命令的权限，为设计修改方便，封装成函数
def is_authority(qq):
    if qq==config.admin_qq:
        return 1
    else:
        return 0    
    
#准备游戏，先统计游戏人数
def prepare():
    global a,b,c
    player=0
    prepare=0
    [is_qq_group,qq,message,qq_group,nickname]=a.get_message()

    while is_qq_group==1:
        if (message=="join" and not a.check_exist_player(qq) and not prepare):
            player+=1
            a.insert_player(qq,qq_group,nickname)
            a.write_return_data(qq_group,"SendClusterMessage","player %d %s has joined the game "%(player,nickname))
        elif message=="prepare" and is_authority(qq):
            if config.config_array.has_key(player):
                prepare=1
                return_data="you can choose the config below:\n"
                i=1
                for choice_array in  config.config_array[player]:
                    return_data=return_data+str(i)+' '+','.join(choice_array)+'\n'
                    i+=1
                a.write_return_data(qq_group,"SendClusterMessage",return_data)
            else:
                a.write_return_data(qq_group,"SendClusterMessage","player number does not match any of your config!")
           
        elif message.isdigit() and prepare and is_authority(qq):
            if int(message)<len(config.config_array[player]):
                my_array=config.config_array[player][int(message)]
                random.shuffle(my_array)
                a.write_return_data(qq_group,"SendClusterMessage","your game config is %s"%(','.join(my_array))+return_data)
                for i in range(player):
                    a.insert_role(qq,qq_group,my_array[i])
                    a.send_return_data(a.mysql.query_qq_by_uid(i,qq_group)[0][0],"SendMessage","your role is %s"%my_array[i])
                b.start(a)
                
                #在调用相关的night函数之前需要
        else:
            a.write_return_data(qq_group,"SendClusterMessage","noreply")
            
        [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
        
    
            
if __name__ == "__main__":
    sys.argv.append(open('tmp','r').readline())
    print "#langrensha_robots v 1.00"
    print "#copyright @haozi2016"
    print "\n"
    global a,b,c
    a=message()
    b=night()
    c=day()
    prepare()


#对于每一个消息处理完后，先确定下一个函数是什么，然后把处理完后的return_data和下一个函数对应的
#开始信息一起作为return_data写到数据库中


            
            
            
            
        
        
    



    
