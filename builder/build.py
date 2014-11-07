#-------------------------------------------------------------------------------

# The very first initialization
import configurator
del(configurator)

#-------------------------------------------------------------------------------

from configobj import ConfigObj
import keyring
from ftplib import FTP, Error as FTPError
import sys
import os
from io import BytesIO
from datetime import datetime
import hashlib
import weakref

from Compressor.CodeCompressor import compress
from chatter import ask_user, tell_user
from ftp_mutex import Mutex, MutexError

#-------------------------------------------------------------------------------

class Sequence:
    def __init__(self, start_val = 0):
        self._val = start_val - 1

    def next(self):
        self._val += 1
        return self._val

#-------------------------------------------------------------------------------

# Статус локального файла:
seq = Sequence()
FILE_UNKNOWN            = seq.next()
FILE_IGNORED            = seq.next()  # Файл полностью игнорируется
FILE_MINIFIED           = seq.next()  # Файл минифицирован
FILE_NOT_MODIFIED       = seq.next()  # Версия на сервере совпадает с локальной
FILE_MODIFIED           = seq.next()  # Версия на сервере отличается от локальной
FILE_RECENTLY_MODIFIED  = seq.next()  # Версия на сервере отличается от локальной, причём послднее изменение было сделано другим пользователем недавно
FILE_NEW                = seq.next()  # Файл отсутствует на сервере
FILE_REMOTE_ONLY        = seq.next()  # Файл есть на сервере, но нет локальной копии

#-------------------------------------------------------------------------------

# Флаги, описывающие путь
PATH_ABSOLUTE           = 0x00000001
PATH_REMOTE             = 0x00000010
PATH_POSIX              = 0x00000100
PATH_ADD_MIN_MARK       = 0x00001000
PATH_REMOVE_MIN_MARK    = 0x00010000
PATH_ROOT_NAME          = 0x00100000

#-------------------------------------------------------------------------------

TIME_FORMAT = '%Y-%m-%d %H:%M:%S'

#-------------------------------------------------------------------------------

me  = None
now = datetime.utcnow().strftime(TIME_FORMAT)

local_config = None

##def utc_to_local(utc_dt):
##    return utc_dt.replace(tzinfo = timezone.utc).astimezone(tz = None)

#-------------------------------------------------------------------------------

class BuildError(Exception):
    pass

#-------------------------------------------------------------------------------

def to_posix(path):
    posix_sep = '/'
    if os.sep == posix_sep:
        return path
    else:
        return path.replace(os.sep, posix_sep)

#-------------------------------------------------------------------------------

def hashfile(filename, blocksize = 65536):
    hasher = hashlib.md5()
    with open(filename, 'rb') as file:
        block = file.read(blocksize)
        while len(block) > 0:
            hasher.update(block)
            block = file.read(blocksize)
    return hasher.hexdigest()

#-------------------------------------------------------------------------------

class FileObject:

    def __init__(self, root, path, path_absolute, remote = False):
        """Создать описание файла

        `root` --- корень (объект класса Root)
        """

        # Cтатус пока не известен
        self._status = FILE_UNKNOWN
        self._minified = False
        self._orphan = False
        self._hash = None
        self._remote = remote

        if path_absolute:
            # Вычисляем путь от корня
            self._path = os.path.relpath(path, start = root.get_path(PATH_ABSOLUTE))
        else:
            self._path = path

        # Если файл не находится внутри корня, это ошибка
        if (self._path[0:2] == '..'):
            raise BuildError('Root "{root}" is configured incorrectly'.format(root.name))

        # Сохраняем ссылку на корень
        self._root = weakref.proxy(root)


    def get_path(self, flags = 0):

        # TODO: check flags combination (not every is correct)

        if flags & PATH_ROOT_NAME:
            return self._root.name

        if flags & PATH_ABSOLUTE:
            path = os.path.join(self._root.get_path(flags), self._path)
        else:
            path = self._path

        if flags & PATH_ADD_MIN_MARK:
            min_mark = '.' + local_config['naming conventions']['minifier']['mark']
            noext, ext = os.path.splitext(path)
            path = noext + min_mark + ext

        if flags & PATH_REMOVE_MIN_MARK:
            min_mark = '.' + local_config['naming conventions']['minifier']['mark']
            noext, ext = os.path.splitext(path)
            if noext.endswith(min_mark):
                path = noext[:-len(min_mark)] + ext

        if flags & (PATH_POSIX | PATH_REMOTE):
            path = to_posix(path)
        else:
            path = os.path.normpath(path)

        return path

#-------------------------------------------------------------------------------

class Dir (FileObject):
    def __init__(self, root, path, path_absolute, remote = False):
        """Создать описание файла

        `root` --- корень (объект класса Root)
        """

        super().__init__(root, path, path_absolute, remote)

        # TODO: добавить возможность игнорировать директорию



    def check_remote_version(self, ftp, force = False):
        # Если статус уже известен, скорее всего ничего делать не надо
        if (self._status != FILE_UNKNOWN) and (not force):
            return

        # Проверяем, есть ли каталог на сервере
        try:
            ftp.cwd(self.get_path(PATH_ABSOLUTE | PATH_REMOTE))
            self._status = FILE_NOT_MODIFIED
        except FTPError:
            self._status = FILE_NEW


    def upload_to_server(self, ftp):
        # Создаём каталог на FTP. Считаем, что родительский уже существует.
        dirname = self.get_path(PATH_ABSOLUTE | PATH_REMOTE)
        ftp.mkd(dirname)


    def delete_from_server(self, ftp):
        # TODO: implement
        tell_user('WARNING. Method `Dir.delete_from_server` not implemented.')


#-------------------------------------------------------------------------------

class File (FileObject):
    """Описание файла

    self._root --- ссылка на корень (объект класса Root)
    self._path --- путь от корня
    """

    def __init__(self, root, path, path_absolute, remote = False):
        """Создать описание файла

        `root` --- корень (объект класса Root)
        """

        super().__init__(root, path, path_absolute, remote)

        # Проверяем, не является ли файл игнорируемым
        if root.ignores(self.get_path(PATH_POSIX)):
            self._status = FILE_IGNORED
            return

        # Проверяем, является ли файл минифицированным
        unminified_file_name = self.get_path(PATH_ABSOLUTE | PATH_REMOVE_MIN_MARK)
        if unminified_file_name != self.get_path(PATH_ABSOLUTE):
            self._minified = True
            if not os.path.isfile(unminified_file_name):
                self._orphan = True

        if self._remote:
            if self._minified and (not self._orphan):
                # Это минифицированная версия существующего файла
                self._status = FILE_IGNORED
            else:
                self._status = FILE_REMOTE_ONLY
        else:
            if self._minified and (not self._orphan):
                # На локальной машине таких файлов быть не должно
                self._status = FILE_MINIFIED

        # Считаем хеш-сумму
        if self._status == FILE_UNKNOWN:
            self._hash = hashfile(self.get_path(PATH_ABSOLUTE))


    def check_remote_version(self, remote_file_list, force = False):
        # Если статус уже известен, скорее всего ничего делать не надо
        if (self._status != FILE_UNKNOWN) and (not force):
            return

        # Проверяем, есть ли файл на сервере
        try:
            remote_file = remote_file_list[self._root.name][self.get_path(PATH_POSIX)]
        except KeyError:
            self._status = FILE_NEW
            return

        # Проверяем, изменился ли удалённый файл
        if remote_file['hash'] == self._hash:
            self._status = FILE_NOT_MODIFIED
        else:
            self._status = FILE_MODIFIED
            # Проверяем, кто и как давно его изменил
            if remote_file['author'] != me:
                # Считаем, сколько времени прошло с момента последней его модификации (в минутах)
                remote_file_lifetime = (now - datetime.strptime(remote_file['time'], TIME_FORMAT)).total_seconds() / 60.0
                if remote_file_lifetime < local_config['warnings']['recent_change_threshold']:
                    self._status = FILE_RECENTLY_MODIFIED
                    self._changed = {
                        'who'  : remote_file['author'],
                        'when' : remote_file['time'],
                    }


    def delete_from_server(self, ftp, remote_file_list):
        # Определяем имя файла на удалённом сервере
        filename = self.get_path(PATH_ABSOLUTE | PATH_REMOTE)
        ftp.delete(filename)

        # Also delete minified version
        filename = self.get_path(PATH_ABSOLUTE | PATH_REMOTE | PATH_ADD_MIN_MARK)
        ftp.delete(filename)

        # Delete file from remote_file_list (only non-minified version)
        try:
            del(remote_file_list[self.get_path(PATH_ROOT_NAME)][self.get_path(PATH_POSIX)])
        except KeyError:
            pass


    def upload_to_server(self, ftp, remote_file_list):
        # При неоходимости минифицируем файл
        if not self._minified:
            buffer = BytesIO()
            if compress(self.get_path(PATH_ABSOLUTE), buffer):
                buffer.seek(0)
                # Выгружаем минифицированный файл на FTP
                filename = self.get_path(PATH_ABSOLUTE | PATH_REMOTE | PATH_ADD_MIN_MARK)
                ftp.storbinary('STOR {}'.format(filename), buffer)

        # Выгружаем исходный файл на FTP
        with open(self.get_path(PATH_ABSOLUTE), 'rb') as fd:
            filename = self.get_path(PATH_ABSOLUTE | PATH_REMOTE)
            ftp.storbinary('STOR {}'.format(filename), fd)

        # Add file to remote_file_list (only non-minified version)
        remote_file_list[self.get_path(PATH_ROOT_NAME)][self.get_path(PATH_POSIX)] = {
            'hash'   : self._hash,
            'time'   : now,
            'author' : me,
        }

#-------------------------------------------------------------------------------

class Root:
    def __init__(self, name, settings, project_directory):
        self.name = name
        self._local_path = settings['path']
        self._project_directory = project_directory

        try:
            self._ignore = [
                to_posix(file)
                for file in settings['ignore']
            ]
        except KeyError:
            self._ignore = []

        self._files = {}
        self._dirs  = []


    def set_remote_path(self, value):
        self._remote_path = value


    def get_path(self, flags = 0):
        if flags & PATH_REMOTE:
            path = self._remote_path
        else:
            if flags & PATH_ABSOLUTE:
                path = os.path.join(self._project_directory, self._local_path)
            else:
                path = self._local_path

        if flags & (PATH_POSIX | PATH_REMOTE):
            path = to_posix(path)
        else:
            path = os.path.normpath(path)

        return path


    def add_local_file(self, file_name):
        file = File(self, file_name, path_absolute = True)
        self._files[file.get_path(PATH_POSIX)] = file


    def add_local_dir(self, dir_name):
        dir = Dir(self, dir_name, path_absolute = True)
        self._dirs.append(dir)


    def add_remote_file(self, file_name):
        file = File(self, file_name, path_absolute = False, remote = True) # TODO: может, тоже перейти на флаги ?
        self._files[file.get_path(PATH_POSIX)] = file


    def ignores(self, file_name):
        return file_name in self._ignore


    def select_files(self, statuses):
        return sorted([file for file in self._files.values() if file._status in statuses], key = lambda file: file.get_path())


    def select_dirs(self, statuses):
        return sorted([dir for dir in self._dirs if dir._status in statuses], key = lambda dir: dir.get_path())


    def check_remote_files(self, remote_file_list):
        # Вначале для каждого из локальных файлов проверяем, как дела на сервере
        for file in self._files.values():
            file.check_remote_version(remote_file_list)

        # Затем для всех файлов на сервере проверяем, есть ли они локально
        try:
            remote_files = remote_file_list[self.name]
        except KeyError:
            # На сервере этого корня вообще нет
            remote_files = {}

        for file_name in remote_files:
            if file_name not in self._files:
                self.add_remote_file(file_name)


    def check_remote_dirs(self, ftp):
        # Вначале для каждой из локальных директорий проверяем, есть ли она на сервере
        for dir in self._dirs:
            dir.check_remote_version(ftp)

        # TODO: Проверяем, нет ли на сервере лишних директорий

#-------------------------------------------------------------------------------

class Builder:

    def __init__(self, server):

        # Читаем настроечный файл
        global local_config
        local_config = ConfigObj('config.ini', encoding = 'utf8')

        try:
            self._ftp_config = local_config['ftp'][server]
        except KeyError:
            raise BuildError('Unknown FTP-server: ' + server)

        global me
        me = local_config['user']['name']

        # Строим список корней
        project_directory = local_config['local']['project_dir']
        self._roots = {name: Root(name, settings, project_directory) for name, settings in local_config['roots'].items()}

        self._ftp = None
        self._remote_config = None
        self._remote_file_list = None

        self._mutex = Mutex(TIME_FORMAT)

    def analyze_local_repository(self):

        for root in self._roots.values():
            # Проверяем, что корень указывают на существующую директорию
            if not os.path.isdir(root.get_path(PATH_ABSOLUTE)):
                raise BuildError('Root "{}" targets to non-existent directory'.format(root.name))

            # Строим список локальных файлов и директорий
            for base_abs_path, dirs, files in os.walk(root.get_path(PATH_ABSOLUTE)):
                for dir_name in dirs:
                    root.add_local_dir(os.path.join(base_abs_path, dir_name))

                for file_name in files:
                    root.add_local_file(os.path.join(base_abs_path, file_name))

        # Проверяем наличие минифицированных файлов
        self.process_minified_files()


    def analyze_remote_repository(self):

        self.connect_to_ftp()

        self.lock_mutex()

        self.read_remote_config()

        self.compare_local_and_remote_roots()

        self.read_remote_file_list(delete = True)

        for root in self._roots.values():
            root.set_remote_path(self._remote_config['roots'][root.name]['path'])
            root.check_remote_dirs(self._ftp)
            root.check_remote_files(self._remote_file_list)

        self.disconnect_from_ftp()

        self.check_recent_modifications()


    def _select_files(self, *statuses):
        return sum([root.select_files(statuses) for root in self._roots.values()], [])


    def _select_dirs(self, *statuses):
        return sum([root.select_dirs(statuses) for root in self._roots.values()], [])


    def process_minified_files(self):
        # Если на локальной машине обнаружены минифицированные файлы, предлагаем их удалить на фиг.
        minified_files = self._select_files(FILE_MINIFIED)
        if minified_files:
            tell_user('There are minified files in the local repository:')
            for file in minified_files:
                tell_user('  ' + file.get_path(PATH_ABSOLUTE))

            tell_user('Generally files are minified on the fly and stored only server-side.')
            answer = ask_user(
                'What do you want to do with listed files?',
                variants = ['d','i','g','?'],
                descriptions = ['delete', 'ignore', 'add to ignore-list', 'decide individually'],
            )

            if answer == '?':
                # Ask again for every file
                decisions = []
                tell_user('\nWARNING. Changes are not applied interactively. So you can cancel everything by selecting `abort`.')
                for file in minified_files:
                    answer = ask_user(
                        'What do you want to do with {file}?'.format(file = file.get_path(PATH_ABSOLUTE)),
                        variants = ['d','i','g','a'],
                        descriptions = ['delete', 'ignore', 'add to ignore-list', 'abort'],
                    )
                    if answer == 'a':
                        raise BuildError('Operation cancelled')
                    else:
                        decisions.append(answer)
            else:
                decisions = [answer for file in minified_files]

            update_config = False

            for file, decision in zip(minified_files, decisions):
                if decision == 'd':
                    # Delete file
                    file_name = file.get_path(PATH_ABSOLUTE)
                    os.unlink(file_name)
                    tell_user('File deleted: {}'.format(file_name))

                elif decision == 'g':
                    # Add file to ignore-list
                    try:
                        local_config['roots'][file.get_path(PATH_ROOT_NAME)]['ignore'].append(file.get_path(PATH_POSIX))
                    except KeyError:
                        local_config['roots'][file.get_path(PATH_ROOT_NAME)]['ignore'] = [file.get_path(PATH_POSIX)]

                    update_config = True
                    tell_user('File added to ignore-list: {}'.format(file.get_path(PATH_ABSOLUTE)))

            if update_config:
                local_config.write()


    def connect_to_ftp(self):
        # Подсоединяемся к удалённому серверу
        try:
            host, login = self._ftp_config['host'], self._ftp_config['login']
        except KeyError:
            raise BuildError('FTP settings not found in config.ini')

        password = keyring.get_password('FTP ' + self._ftp_config['host'], self._ftp_config['login'])
        if password is None:
            raise BuildError('FTP password not set')

        self._ftp = FTP(host = host, user = login, passwd = password)


    def disconnect_from_ftp(self):
        if self._ftp:
            self._ftp.quit()
            self._ftp = None


    def read_remote_config(self):
        # Добываем конфиг с сервера
        self._ftp.cwd(self._ftp_config['build_dir'])
        buffer = BytesIO()
        self._ftp.retrbinary('RETR config.ini', buffer.write)
        buffer.seek(0)

        # Загружаем в память
        self._remote_config = ConfigObj(buffer, encoding = 'utf8')  # TODO: process parse error (?)


    def compare_local_and_remote_roots(self):
        # Проверяем, что список корней сервера соответствует локальному
        local_only_roots  = [root for root in self._roots if root not in self._remote_config['roots']]
        remote_only_roots = [root for root in self._remote_config['roots'] if root not in self._roots]

        for root in local_only_roots:
            tell_user('Error: local root directory {} is not configured on the remote server'.format(root))

        for root in  remote_only_roots:
            tell_user('Error: remote root directory {} is not configured in the local repository'.format(root))

        if local_only_roots or remote_only_roots:
            raise BuildError('Inconsistent roots configuration')


    def read_remote_file_list(self, delete = False):
        try:
            # Добываем с сервера список файлов
            buffer = BytesIO()
            self._ftp.cwd(self._ftp_config['build_dir'])
            self._ftp.retrbinary('RETR files.ini', buffer.write)
            buffer.seek(0)

            # Загружаем в память
            self._remote_file_list = ConfigObj(buffer, encoding = 'utf8')

        except:
            # On error consider it empty
            self._remote_file_list = ConfigObj(
                {root : {} for root in self._roots},
                indent_type = '    ',
                encoding = 'utf8',
            )

##        else:
##            # Удаляем файл с FTP
##            if delete:
##                self._ftp.cwd(self._ftp_config['build_dir'])
##                self._ftp.delete('files.ini')
                  # Нельзя удалять оглавление! А то если пользователь передумает, оно так и пропадёт.


    def write_remote_file_list(self):
        buffer = BytesIO()
        self._remote_file_list.write(buffer)
        buffer.seek(0)

        self._ftp.cwd(self._ftp_config['build_dir'])
        self._ftp.storbinary('STOR files.ini', buffer)


    def check_recent_modifications(self):

        # Если есть файлы в статусе RECENTLY_MODIFIED, предупреждаем пользователя.
        recently_modified_files = self._select_files(FILE_RECENTLY_MODIFIED)

        if recently_modified_files:
            tell_user('\nSome files on the server were modified by another user(s) not long ago:')

            for file in recently_modified_files:
                tell_user('  {file} by {user} at {time}'.format(file = file.get_path(PATH_ABSOLUTE), user = file._changed['who'], time = file._changed['when']))

            answer = ask_user(
                'Do you still want to continue?',
                variants = ['n','y'],
            )
            if answer == 'n':
                raise BuildError('Operation cancelled')


    def synchronize_all(self):

        # Директории
        dirs_to_create  = self._select_dirs(FILE_NEW)
        dirs_to_delete  = self._select_dirs(FILE_REMOTE_ONLY)

        # Файлы
        files_to_update = self._select_files(FILE_MODIFIED, FILE_RECENTLY_MODIFIED, FILE_NEW)
        files_to_delete = self._select_files(FILE_REMOTE_ONLY)

        if not any((files_to_update, files_to_delete, dirs_to_create, dirs_to_delete)):
            raise BuildError('Everything is up to date. No actions required.')

        # Покажем пользователю, какие изменения мы планируем произвести на сервере.
        if dirs_to_create:
            tell_user('\nFollowing directories will be created on the server:')
            for dir in dirs_to_create:
                tell_user('  ' + dir.get_path(PATH_ABSOLUTE | PATH_REMOTE))

        if dirs_to_delete:
            tell_user('\nFollowing directories will be deleted from the server:')
            for dir in dirs_to_delete:
                tell_user('  ' + dir.get_path(PATH_ABSOLUTE | PATH_REMOTE))

        if files_to_update:
            tell_user('\nFollowing files will be uploaded to the server:')
            for file in files_to_update:
                tell_user('  ' + file.get_path(PATH_ABSOLUTE))

        if files_to_delete:
            tell_user('\nFollowing files will be deleted from the server:')
            for file in files_to_delete:
                tell_user('  ' + file.get_path(PATH_ABSOLUTE | PATH_REMOTE))

        answer = ask_user(
            'Do you wish to start?',
            variants = ['y','n'],
        )
        if answer == 'n':
            raise BuildError('Operation cancelled')

        # Apply changes
        self.connect_to_ftp()

        for dir in dirs_to_create:
            dir.upload_to_server(self._ftp)

        for file in files_to_update:
            # minify and upload file
            file.upload_to_server(self._ftp, self._remote_file_list)

        for file in files_to_delete:
            file.delete_from_server(self._ftp, self._remote_file_list)

        for dir in dirs_to_delete:
            dir.delete_from_server(self._ftp)

        self.write_remote_file_list()

        self.unlock_mutex()

        self.disconnect_from_ftp()

        tell_user('Mission accomplished')


    def lock_mutex(self):
        self._mutex.lock(self._ftp, self._ftp_config['build_dir'], me)


    def unlock_mutex(self):
        if not self._ftp:
            self.connect_to_ftp()

        self._mutex.unlock(self._ftp, self._ftp_config['build_dir'])


    def __enter__(self):
        return self


    def __exit__(self, type, value, traceback):
        try:
            self.disconnect_from_ftp()
        except:
            pass

#-------------------------------------------------------------------------------

# Определяем сервер
try:
    server = sys.argv[1]
except IndexError:
    tell_user('FTP-server not specified')
    exit(0)

# TODO: Добавить режим force --- игнорируем оглавление

with Builder(server) as builder:
    try:
        builder.analyze_local_repository()
        builder.analyze_remote_repository()
        builder.synchronize_all()
    except MutexError as e:
        user, time = str(e).split('\t')
        tell_user('User {user} works on the site right now ({time}). Unable to proceed...'.format(
            user = user,
            time = time,
        ))
    except BaseException as e:
        try:
            builder.unlock_mutex()
        except:
            pass
        tell_user(str(e))
