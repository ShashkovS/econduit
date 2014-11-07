from base64 import *
PATH = ''
res = b64encode(open(PATH + 'Marks.woff', 'br').read())
pre = b"@font-face {\n    font-family: Marks;\n    src: url(data:font/truetype;charset=utf-8;base64,"
aft = b") format('woff');\n}"
f = open(PATH + 'Marks.css','bw')
f.write(pre+res+aft)
f.close()
