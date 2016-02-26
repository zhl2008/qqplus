# -*- coding: utf-8 -*-
import sys
import MySQLdb
import os
import config
import time
import random

#打印出pid由php负责存入数据库，可以在end时通过taskkill来结束进程
open('pid','w').write(str(os.getpid()))
#config saved in config.py

def err_log(msg):
    open('error.log','a+').write("%s : %s\n"%(time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time())),msg))

class execute_sql():
    def __init__(self):
        self.con=MySQLdb.connect(host=config.host,user=config.user,passwd=config.passwd,db=config.db,\
                            port=config.port)
        self.cursor=self.con.cursor()
        
    def get_message(self,mid):
        sql="select is_qq_group,qq,message,qq_group,nickname from %s_message where mid=%d"%(sys.argv[1],1)
        return self.execute(sql)
        
        
    def write_return_data(self,mid,return_data):
        sql="update %s_message set return_data=%s where mid=%d"%(sys.argv[1],return_data,mid)
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
        self.cursor.execute()
        sql="insert into %s_personal (qq,nickname) values ('%s','%s')"%(qq,nickname)
        self.cursor.execute()
        

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
        
    def write_return_data(self,return_data):
        self.mysql.write_return_data(self.mid,return_data)

    def check_exist_player(self,qq):
        if not self.mysql.check_exist_player(qq):
            return 0
        else:
            return 1
    def insert_player(self,qq,qq_group,nickname):
        self.mysql.insert_player(qq,qq_group,nickname)
        
        

class night():
    def __init__(self):
        pass

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
    data=a.get_message()
    while data[0][0]==1:
        if (data[0][2]=="join" and not a.check_exist_player(data[0][1]) and not prepare):
            player+=1
            a.insert_player(data[0][1],data[0][3],data[0][4])
            a.write_return_data("%d号玩家%s已经加入游戏"%(player,data[0][4]))
        elif data[0][2]=="prepare" and is_authority(data[0][1]):
            #角色分配
            prepare=1
            
        elif data[0][2].isdigit() and prepare:
            if int(data[0][2])<len(config.config_array[player]):
                my_array=config.config_array[player][int(data[0][2])]
                a.write_return_data("您的游戏配置是 %s"%('，'.join(my_array)))
                random.shuffle(my_array)
                for role in my_array:
                    
                
            
            
        data=a.get_message()
        
    
            
if __name__ == "__main__":
    global a,b,c
    a=message()
    b=night()
    c=day()
    prepare()


#对于每一个消息处理完后，先确定下一个函数是什么，然后把处理完后的return_data和下一个函数对应的
#开始信息一起作为return_data写到数据库中

err_log("haozigege test")
a=message()
print a.get_message()
            
            
            
            
        
        
    



    
