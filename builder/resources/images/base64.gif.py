from base64 import *
PATH = ''
res = b64encode(open(PATH + '3px.gif', 'br').read())
pre = b"    background-image: url(data:image/gif;base64,"
aft = b");\n    background-size: 25%;"
f = open(PATH + '3px.css','bw')
res = b64encode(open(PATH + '3px_error.gif', 'br').read())
pre = b"    background-image: url(data:image/gif;base64,"
aft = b");\n    background-size: auto 10%;"
f = open(PATH + '3px_error.css','bw')
f.write(pre+res+aft)
f.close()
