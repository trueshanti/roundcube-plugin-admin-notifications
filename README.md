Roundcube Plugin: admin_notifications
=====================================

Roundcube Webmail plugin to allow administrators to send global notifications to the users

Settings options of this plugin will only be shown to users if they are part of the configured admin users in [admin_options plugin][rcpluginadmin]. Admin users can create notifications which will popup to users after their login. The user can dismiss notifications and only will see new notifications on next login.

Stable versions are available from the [Roundcube plugin repository][rcplugin] or from the [releases section][releases] on GitHub repository.

Requirements
------------  

This plugin requires [admin_options plugin][rcpluginadmin].

Composer Installation
----------------------------------------

Add the plugin to your "require" section in `composer.json` file:

    "require": {
        (...)
        "valarauco/admin_notifications": "~0.1"
    }

And run `$ php composer.phar install`.

Manual Installation
----------------------------------------

Place this directory under your Rouncdube `plugins/` folder and rename it as `admin_notifications`, copy `config.inc.php.dist` to `config.inc.php` and modify it as necessary.

Then, import the database script:

    mysql -your_mysql_connection_options your_roundcube_database_name < SQL/mysql.initial.sql

:heavy_plus_sign: : The plugin ships with a MySQL/MariaDB script in `SQL/mysql.initial.sql`; you are welcome to contribute with other database drivers.


If you are using git, change your working directory to your Rouncdube `plugins/` folder and clone the repository:

    $ git clone https://github.com/valarauco/roundcube-plugin-admin-notifications.git admin-notifications

Then you can checkout the branch or release you want to use.

:bangbang: : Don't forget to enable the plugin in Roundcube configuration file `config/config.inc.php`.


License
----------------------------------------

This plugin is released under the [GNU General Public License Version 3+][gpl].

Contact
----------------------------------------

Comments and suggestions are welcome!

Please, feel free to open an issue if necessary on the [issues section][issues] on the Github repo

author: Manuel Delgado (ValaRaucO)

[rcpluginadmin]: https://plugins.roundcube.net/packages/valarauco/admin_notifications
[rcplugin]: https://plugins.roundcube.net/packages/valarauco/admin_options
[releases]: https://github.com/valarauco/roundcube-plugin-admin-notifications/releases
[issues]: https://github.com/valarauco/roundcube-plugin-admin-notifications/issues
[gpl]: https://www.gnu.org/licenses/gpl.html
