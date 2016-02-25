# -*- coding: utf-8 -*-
import sys
import MySQLdb
import os
import config
import time

#config saved in config.py

def err_log(msg):
    open('error.log').write("%s : %s"%(time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time())),msg))

class execute():
    def __init__(self):
        con=MySQLdb.connect(host=config.host,user=config.user,passwd=config.passwd,db=config.passwd,\
                            port=config.port)
        cursor=con.cursor()
        
    def get_message(self,mid):
        sql="select is_qq_group,qq,message from %s_message where mid=%d"%(sys.argv[1],mid)
        
    def write_return_data(self,mid,return_data):
        sql="update %s_message set return_data=%s where mid=%d"%(sys.argv[1],return_data,mid)

        

    def __del__(self):
        self.cursor.execute(self.sql)
        self.cursor.fetchall()
        self.con.commit()
        self.cursor.close()
        
        


#对于每一个消息处理完后，先确定下一个函数是什么，然后把处理完后的return_data和下一个函数对应的
#开始信息一起作为return_data写到数据库中
class message():
    def __init__(self):
        #test the requirements
        pass
err_log("haozigege test")
            
            
            
            
        
        
    



    
