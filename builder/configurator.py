#-------------------------------------------------------------------------------

import os.path
from importlib import import_module

from chatter import ask_user, tell_user

#-------------------------------------------------------------------------------

APP_VERSION = '1.0'

#-------------------------------------------------------------------------------

class ConfigError(Exception):
    pass

#-------------------------------------------------------------------------------

# Install missing dependencies

# Check if pip is installed
try:
    import pip
    pip_installed = True
except ImportError:
    pip_installed = False
except AttributeError:
    # Возможно падение при обращении к sys.__stdout__.encoding
    # Но если запускать из консоли, такого не будет
    # TODO: перенаправить stdout в этом случае (туда же, куда смотрит print)
    pip_installed = False


modules = [
    ('configobj', 'http://configobj.readthedocs.org/en/latest/configobj.html#downloading'),
    ('keyring', 'https://bitbucket.org/kang/python-keyring-lib/#rst-header-installation-instructions'),
]

for module_name, module_help in modules:
    try:
        import_module(module_name)
    except ImportError:
        if pip_installed:
            decision = ask_user(
                'It looks like you don\'t have `{name}` package. Install it now?'.format(name = module_name),
                variants = ['y', 'n'],
            )
            if decision == 'y':
                rc = pip.main(['install', module_name])
                if rc:
                    # Some error occured
                    raise ConfigError('PIP error: {}'.format(rc))
            else:
                tell_user('Installation instructions are available at {help}.'.format(
                    name = module_name,
                    help = module_help
                ))
                raise ConfigError('Unable to proceed...')
        else:
            tell_user('It looks like you don\'t have `{name}` package. Installation instructions are available at {help}.'.format(
                name = module_name,
                help = module_help
            ))
            raise ConfigError('Unable to proceed...')

#-------------------------------------------------------------------------------

# Create config if missing

from configobj import ConfigObj, Section
import keyring

config_name = 'config.ini'

def find_missing_roots(project_dir, roots):
    missing_dirs = []
    for root in roots.values():
        dir = os.path.normpath(os.path.join(parent_dir, root['path']))
        if not os.path.isdir(dir):
            missing_dirs.append(dir)

    return missing_dirs


if not os.path.isfile(config_name):
    tell_user('Starting initial setup...')
    config = ConfigObj(
        indent_type = '    ',
        encoding = 'utf8',
    )

    # Версия конфига
    config['version'] = '1.0'

    # Настраиваем корни
    config['roots'] = {}
    config['roots']['webroot'] = {}
    config['roots']['webroot']['path'] = 'htdocs/www/conduit'
    config['roots']['webroot']['ignore'] = [
        '.htaccess',
    ]
    config['roots']['include'] = {}
    config['roots']['include']['path'] = 'include/conduit'
    config['roots']['include']['ignore'] = [
        'Credentials.inc.php',
        'Settings.inc.php',
        'KhinkoReminder.php',
    ]

    # Определяем, где находится проект. По идее он должен быть в родительском каталоге текущего.
    parent_dir = os.path.dirname(os.path.dirname(os.path.realpath(__file__)))
    if not find_missing_roots(parent_dir, config['roots']):
        project_dir = parent_dir
    else:
        while True:
            project_dir = ask_user(
                'Where is your project located (enter empty string for abort)?',
                case_sensitive = True,
            )

            if project_dir == '':
                raise ConfigError('Abort requested')

            project_dir = os.path.abspath(project_dir)
            missing_dirs = find_missing_roots(project_dir, config['roots'])

            if not missing_dirs:
                break

            for dir in missing_dirs:
                tell_user('Directory {} not found'.format(dir))

    config['local'] = {}
    config['local']['project_dir'] = project_dir

    # Определяем пользователя
    gitconfig = ConfigObj(os.path.join(os.path.expanduser('~'), '.gitconfig'))
    if gitconfig:
        user = '{name} ({email})'.format(
            name = gitconfig['user']['name'],
            email = gitconfig['user']['email'],
        )
    else:
        user = ask_user('What\'s your name?', case_sensitive = True)

    config['user'] = {}
    config['user']['name'] = user

    # Настраиваем список FTP-серверов
    config['ftp'] = {}
    tell_user(
        'Enter FTP settings. Each line must have following structrure:\n' +
        '{alias} {host} {login} {password} {build directory}\n' +
        'for example:\n' +
        'test ftp46.hostland.ru host1333511 P@$$W0RD /econduit.ru/build/test/\n' +
        'Enter blank line to stop.'
    )
    while True:
        settings = input().split() # TODO: сделать через chatter'а

        if not settings:
            break

        if len(settings) != 5:
            tell_user('Invalid line format. Try again.')
            continue

        if settings[0] in config['ftp']:
            tell_user('You have already defined setting `{}`'.format(settings[0]))
            continue

        # TODO: test setting (connect to server and check build_dir)

        config['ftp'][settings[0]] = {}
        config['ftp'][settings[0]]['host'] = settings[1]
        config['ftp'][settings[0]]['login'] = settings[2]
        config['ftp'][settings[0]]['build_dir'] = settings[4]
        keyring.set_password('FTP ' + settings[1], settings[2], settings[3])

    # Прочие настройки
    config['warnings'] = {}
    config['warnings']['recent_change_threshold'] = 30
    config['warnings'].comments['recent_change_threshold'] = ['Лимит времени, в течение которого не должно быть изменений, сделанных другими пользователями (в минутах)']
    config['naming conventions'] = {}
    config['naming conventions']['minifier'] = {}
    config['naming conventions']['minifier']['mark'] = 'min'
    config['naming conventions']['minifier'].comments['mark'] = ['Минифицированная версия файла filename.ext должна называться filename.$mark.ext']

    # Записываем конфиг
    with open(config_name, 'wb') as fd:
        config.write(fd)

    tell_user('Setup complete\n')

#-------------------------------------------------------------------------------

# TODO: Migrate config if its version is less than {APP_VERSION}

#-------------------------------------------------------------------------------
