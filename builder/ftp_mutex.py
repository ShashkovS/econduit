#-------------------------------------------------------------------------------

from datetime import datetime
from io import BytesIO
import posixpath

#-------------------------------------------------------------------------------

class MutexError(Exception):
    pass

#-------------------------------------------------------------------------------

class Mutex:

    def __init__(self, time_format):
        self._time_format = time_format
        self._locked = False


    def lock(self, ftp, wd, user):
        if self._locked:
            return

        mutex = posixpath.join(wd, 'mutex')

        line = '{user}\t{time}\n'.format(
            user = user,
            time = datetime.utcnow().strftime(self._time_format),
        ).encode('utf-8')

        # Отправляем нашу подпись на сервер
        buffer = BytesIO(line)
        ftp.storbinary('APPE ' + mutex, buffer)

        buffer = BytesIO()
        ftp.retrbinary('RETR ' + mutex, buffer.write)
        # Проверяем, что первая строка в файле добавлена нами
        buffer.seek(0)
        remote_line = buffer.readline()
        if (remote_line != line):
            # Мьютекс не наш
            raise MutexError(remote_line.decode('utf-8')[:-1])

        self._locked = True


    def unlock(self, ftp, wd):
        if not self._locked:
            return

        mutex = posixpath.join(wd, 'mutex')
        ftp.delete(mutex)
