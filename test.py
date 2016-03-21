class a():
	def haha(self):
		c()
def c():
	global a
	a.haha()
global a
a=a()
c()

