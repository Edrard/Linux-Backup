# Linux Backup

Version 2.1.0

Linux Backup is a small PHP backup runner for Linux servers. It can sync files to an FTP destination, create dated full archives, create monthly full archives with daily increments, and dump MySQL databases before syncing them.

## Requirements

- Linux server with PHP 7.0 or newer
- Composer
- Git
- PHP FTP functions enabled
- PHP `exec()` enabled
- System `zip` command available
- MySQL access for database backups

`exec()` is used because this project creates archives through the Linux `zip` utility instead of PHP `ZipArchive`.

## Installation

```bash
git clone https://github.com/Edrard/Linux-Backup.git
cd Linux-Backup
composer install
```

## Running Backups

The main configuration file is `ftp.json`.

Run a backup manually:

```bash
php run_sync.php
```

The repository also includes `backup_cron`. Copy or adapt its jobs into `/etc/cron.d/` when the configuration is ready.

## Config Generator

You can create or edit a configuration with the web generator:

```text
gen.html
```

Open it in a browser, upload an existing `ftp.json` if needed, then generate the updated config file.

## Configuration Example

```json
{
  "backup": {
    "1": {
      "src": "/usr/local/backup",
      "dstfolder": "/site/base",
      "local": "base/",
      "type": "now",
      "days": "0",
      "months": "0",
      "filename": "base",
      "fileinc": "d-m-Y",
      "typebackup": "file",
      "exclude": "",
      "mysqlbase": "",
      "mysqlconfig": "",
      "dst": "1"
    },
    "2": {
      "src": "/var/www/project",
      "dstfolder": "/site/project",
      "local": "base/project/",
      "type": "increment",
      "days": "0",
      "months": "2",
      "full_backup_date": "1",
      "filename": "project",
      "fileinc": "d-m-Y",
      "typebackup": "file",
      "exclude": "cache logs/tmp",
      "mysqlbase": "",
      "mysqlconfig": "",
      "dst": "1"
    },
    "3": {
      "src": "",
      "dstfolder": "/site/mysql",
      "local": "base/mysql/",
      "type": "time",
      "days": "5",
      "months": "0",
      "filename": "mysql",
      "fileinc": "d-m-Y",
      "typebackup": "mysql",
      "exclude": "",
      "mysqlbase": "db_name",
      "mysqlbase_table_setup": {
        "db_name": {
          "no-data": {
            "config": "",
            "lang": ""
          }
        }
      },
      "mysqlconfig": "1",
      "dst": "1"
    }
  },
  "config": {
    "1": {
      "host": "mx.com",
      "user": "test",
      "pass": "1234",
      "type": "ftp"
    }
  },
  "mysql": {
    "1": {
      "host": "localhost",
      "user": "root",
      "pass": "123456"
    }
  },
  "log": {
    "file": {
      "dst": "nlog",
      "full": "1"
    },
    "mail": {
      "user": "",
      "pass": "",
      "smtp": "",
      "port": "25",
      "from": "",
      "to": "",
      "separate": "1",
      "hostname": "My Server"
    }
  }
}
```

## Backup Jobs

Each item under `backup` describes one backup job.

`src` is the source path for file backups. Multiple paths can be separated with commas.

`dstfolder` is the destination folder on the remote storage.

`local` is the local folder used to store generated backup files before syncing. Relative paths are resolved from the project directory.

`type` controls the backup strategy:

- `now`: sync current file changes without creating local archives.
- `time`: create full archives and keep the last `days` days.
- `increment`: create a full archive once per month and daily increment archives on other days.

`days` is the number of days to keep archives for `time` backups.

`months` is the number of months to keep full monthly archives for `increment` backups.

`full_backup_date` is used only by `increment` backups. It sets the day of the month when the full archive is created. Use `1` for the first day of the month. Values from `1` to `28` are safest because they exist in every month. If the key is missing, the code defaults to `1`.

`filename` is the base archive filename.

`fileinc` is appended to `filename` using PHP `date()` format, for example `d-m-Y`.

`typebackup` can be `file` or `mysql`. MySQL backups always use the `time` action internally.

`exclude` is a space-separated list of paths to exclude from archive/sync operations.

`mysqlbase` is a space-separated list of database names. Use `+` to dump all available databases.

`mysqlbase_table_setup` can mark tables that should be dumped without data. In the example above, `config` and `lang` table structures are included without rows.

`mysqlconfig` points to an item from the `mysql` section.

`dst` points to an item from the `config` destination section.

## Destination Config

The `config` section describes remote destinations. Currently FTP is supported.

```json
{
  "host": "mx.com",
  "user": "test",
  "pass": "1234",
  "type": "ftp"
}
```

## MySQL Config

The `mysql` section contains MySQL connection credentials used by MySQL backup jobs.

```json
{
  "host": "localhost",
  "user": "root",
  "pass": "123456"
}
```

## Logging

File logs are configured in `log.file`.

`dst` is the local log folder.

`full` controls log verbosity. Use `1` to log all messages. Any other value keeps only warning, error, and critical logs.

Mail notifications are configured in `log.mail`.

`user`, `pass`, `smtp`, and `port` configure SMTP access.

`from` is the sender email address.

`to` is a comma-separated recipient list.

`separate` controls message grouping. Use `1` to send separate messages by log type. Any other value sends one combined message.

`hostname` is used in the email subject and sender name.
