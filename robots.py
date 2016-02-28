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
        sql="update %s_public set python_pid='%s'"%(sys.argv[1],str(os.getpid()))
        return self.execute(sql)
    
    def write_return_data(self,mid,return_data):
        sql="update %s_message set return_data='%s' where mid=%d"%(sys.argv[1],return_data,mid-1)#修正为mid-1
        print "the return is %s"%return_data
        return self.execute(sql)

    def check_exist_player(self,qq):
        sql="select * from user where qq='%s'"%qq
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
        sql="select qq from %s_personal where uid=%d"%(sys.argv[1],uid)
        self.cursor.execute(sql)

    def query_qqs_by_role(self,role):
        sql="select qq from %s_role where role='%s'"%(sys.argv[1],role)
        self.cursor.execute(sql)

    def query_nickname_by_id(self,uid):
        sql="select nickname from %s_personal where uid=%d"%(sys.argv[1],uid)
        self.cursor.execute(sql)

    def query_role_by_uid(self,uid):
        sql="select role from %s_personal where uid=%d"%(sys.argv[1],uid)
        self.cursor.execute(sql)
    
    def role_is_alive_or_exist(self,role):
        sql="select qq form %s_role where role='%s' and is_alive=1"%(sys.argv[1],role)
        self.cursor.execute(sql)

    def wolf_kill(self,uid):
        sql="update %s_public set wolf_kill=%s"%(sys.argv[1],uid)
        self.cursor.execute(sql)

    def kill_player(self,uid):
        sql="update %s_role set is_alive=0 where qq='%s'"%(sys.argv[1],self.query_qq_by_id(uid)[0][0])
        self.cursor.execute(sql)
        sql="update %s_personal set is_alive=0 where uid=%d"%(sys.argv[1],uid)
        self.cursor.execute(sql)
        #只要有人被kill，就检查是否达成游戏胜利条件
        self.check_win()

    def witch_have_save(self):
        sql="selct have_save from %s_role where role='witch'"%(sys.argv[1])
        self.cursor.execute(sql)

    def witch_have_poison(self):
        sql="selct have_poison from %s_role where role='witch'"%(sys.argv[1])
        self.cursor.execute(sql)

    def last_protect(self):
        sql="select last_protect from %s_public "%(sys.argv[1])
        self.cursor.execute(sql)

    def check_win(self):
        pass

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
    def check_role(self,uid):
        if self.mysql.query_role_by_uid(uid)[0][0]!="wolf":
            return "good"
        else:
            return "bad"

    def insert_player(self,qq,qq_group,nickname):
        self.mysql.insert_player(qq,qq_group,nickname)

    def insert_role(self,qq,qq_group,role):
        self.mysql.insert_role(qq,qq_group,role)
        
    def send_return_data(self,qq,method,return_data):
        con=httplib.HTTPConnection("%s"%config.host,config.listen_port,timeout=5)
        con.request("GET","/?key=%s&a=%s"%(cofig.key,self.serialize_data(qq,method,return_data)))
        con.close()  

    def role_is_alive_or_exist(self,role):
        qqs=[]
        for qq in self.mysql.role_is_alive_or_exist(role):
            qqs.append(qq)
        if len(qqs)==0:
            return [0]
        return qqs
    def last_protect(self):
        uid=self.mysql.last_protect()[0][0]
        if uid:
            return "except player %d"%uid
        return ""
    def query_qq_by_id(self,uid):
        return self.mysql.query_qq_by_id()[0][0]







class night():
    def __init__(self):
        self.is_first_night=1

    def start(self,a):
        a.send_return_data(sys.argv[1],"SendClusterMessage","the night is comming!")
        self.during_night(a)

    def during_night(self,a):
        #第一夜
        if(self.is_first_night):
            if(a.mysql.role_is_alive_or_exist('love_god')){
                a.write_return_data(a.mysql.role_is_alive_or_exist('love_god'),"SendMessage","please choose the number of the player you want to connect")
                #爱神的判断逻辑        
            }
            if(role_is_alive_or_exist('')):
                pass
            is_first_night=0
        #其他的夜晚   
        death=self.wolf_kill(a)
        protect_uid=self.protect(a)
        self.witch(a,death,protect_uid)
        self.prophet(a)

    def wolf_kill(self,a): 
        wolves=a.role_is_alive_or_exist("wolf")
        for wolf in wolves:
            a.send_return_data(wolf,"SendMessage","please choose a people to kill,just send the number of that people,and you only have 40s to decide")
        [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
        vote_array={}
        now_time=time.time()
        while (len(vote_array)<len(wolves)  or ((time.time()-now_time)<40):
            if not is_qq_group and message.isdigit() and (qq in wolves):
                vote_array[qq]=int(message)
                return_data=""
                for vote_wolf in vote_array:
                    return_data+="wolf %s has vote to kill %d\n"%(vote_wolf,vote_array[vote_wolf])
                for wolf in wolves:
                    a.write_return_data(wolf,"SendMessage",return_data+"you can vote again to change your choice")
                if check_vote(vote_array)=="1":
                    for wolf in wolves:
                        a.send_return_data(wolf,"SendMessage",return_data+"you have kill player %d"%vote_array[0])
                    break
            a.write_return_data("","","noreply")
            [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
        a.write_return_data("","","noreply")
        death=int(check_vote(vote_array))
        if death:
            a.mysql.wolf_kill(death)
        else:
            for wolf in wolves:
                a.send_return_data(wolf,"SendMessage","you have a unresonable vote,so there is no death tonight")
        return death

    def protect(self,a):
        protect=a.role_is_alive_or_exist("protect_god")[0]
        if protect:
            send_return_data(protect,"SendMessage","tonight would you protect one player"+a.last_protect()+"? send the player number for yes, n for no ,you have 30s to decide")
            [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
            now_time=time.time()
            while (time.time()-now_time<30):
                if not is_qq_group and (qq==protect):
                    if message.isdigit() and  a.check_exist_player(a.query_qq_by_id(int(message))):
                        if int(message)==a.last_protect():
                            a.write_return_data(protect,"SendMessage","unresonable choice")
                        else:
                            a.protect(int(message))
                            return int(message)
                a.write_return_data("","","noreply")  
                [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
            a.write_return_data(protect,"SendMessage","timeout")


    def witch(self,a,death,protect_uid):
        witch=a.role_is_alive_or_exist("witch")[0]
        if witch and death:
            if a.mysql.witch_have_save()[0][0]:
                send_return_data(witch,"SendMessage","tonight %d player %s has been killed by wolf ,would you like to save? y for yes, n for no ,you have 30s to decide"%(death,a.mysql.query_nickname_by_id(death)[0]))
                [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
                now_time=time.time()
                while (time.time()-now_time<30):
                    if not is_qq_group  and (qq==witch):
                        if message=="y":
                            a.write_return_data("","","noreply")
                            break
                        elif message=="n" and not death==protect_uid:
                            #这次玩家是真的挂了
                            a.mysql.kill_player(death)
                            a.write_return_data("","","noreply")
                            break
                    a.write_return_data("","","noreply")
                    [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
                a.write_return_data(protect,"SendMessage","timeout")

            if a.mysql.witch_have_poison()[0]:
                send_return_data(witch,"SendMessage","you have a posion ,would you like to kill? send the player number for yes, n for no ,you have 30s to decide"%(death,a.mysql.query_nickname_by_id(death)[0]))
                [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
                now_time=time.time()
                while (time.time()-now_time<30):
                    if not is_qq_group and (qq==witch):
                        if message.isdigit() and a.check_exist_player(a.query_qq_by_id(int(message))):
                            a.mysql.kill_player(message)
                            a.write_return_data("","","noreply")
                            return 1
                    a.write_return_data("","","noreply")
                    [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
                a.write_return_data(protect,"SendMessage","timeout")
            
    def prophet(self,a,death):
        prophet=a.role_is_alive_or_exist("prophet")[0]
        if prophet:
            send_return_data(prophet,"SendMessage","tonight would you like to check the role of someone?send the player number for yes, n for no ,you have 30s to decide")
            [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
            now_time=time.time()
            while (time.time()-now_time<30):
                if not is_qq_group and (qq==prophet):
                    if message.isdigit() and a.check_exist_player(a.query_qq_by_id(int(message))):
                        a.write_return_data(prophet,"SendMessage","he/she is %s"%a.check_role(int(message)))
                        return 1 
                a.write_return_data("","","noreply")  
                [is_qq_group,qq,message,qq_group,nickname]=a.get_message()
            a.write_return_data(protect,"SendMessage","timeout")


class day():
    def __init__(self):
        self.is_first_night=1
    def start(self):
        a.send_return_data(sys.argv[1],"SendClusterMessage","the day is comming!")
        self.during_day(a)

    def during_day(self,a):
        if(self.is_first_day):
            




#全局函数的定义
    
#判断用户是否有相关执行命令的权限，为设计修改方便，封装成函数
def is_authority(qq):
    if qq==config.admin_qq:
        return 1
    else:
        return 0    

def check_vote(vote_array):
    resonable_array=[]
    count_array=[0]*15
    for vote in vote_array:
        if vote not in resonable_array):
            resonable_array.append(vote)
            count_array[int(vote)]=1
        else:
            count_array[int(vote)]+=1
    if len(resonable_array)==len(vote_array):
        return "1"
    else:
        max=0
        for out in count_array:
            if count_array[out]>count_array[max]:
                max=out
        return max


   
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
                    a.send_return_data(a.query_qq_by_id(i,qq_group)[0][0],"SendMessage","your role is %s"%my_array[i])
                while 1:
                    b.start(a)
                    c.start(a)
                
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


            
            
            
            
        
        
    



    
