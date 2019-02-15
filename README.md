# Linux Backup
 
Version 2.0.0

## Install

To install this packadge you need **git** and **composer**

```
git clone https://github.com/Edrard/linux_backup.git
composer install 
```

## Use

After instalation, configurate it. Main config file is - ftp.json

Example Config

```
{
    "backup": {
    "1": {
        "src": "\/usr\/local\/backup",
        "dstfolder": "\/site\/base",
        "local": "base\/",
        "type": "now",
        "days": "0",
        "mounths": "0",
        "filename": "base",
        "fileinc": "d-m-Y",
        "typebackup": "file",
        "exclude": "",
        "mysqlbase": "",
        "mysqlconfig": "",
        "dst": "1"
    },
    "2": {
        "src": "",
        "dstfolder": "\/site\/vamark",
        "local": "base\/mysql\/",
        "type": "time",
        "days": "5",
        "months": "0",
        "filename": "mysql",
        "fileinc": "d-m-Y",
        "typebackup": "mysql",
        "exclude": "",
        "mysqlbase": "mysql",
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
    }
}
```    

**src** - source where take files

**dstfolder** - where to store on FTP

**local** - local folder to keep files, not needed for NowFile type. Can be used in any way absolute or relative path. Must be separated for actons.

**type** - can be "now", "time", "increment"
    (if typebackup - mysql, then allways time)
    
**now** - just sync folder changes

**time** - keep last N days full archives

**increment** - keep monthly full + increment for every day.
    
**days** - its ammont of arhives to keep(only for time backup)

**months** - needed only for increment, how many full month archives to keep
    
**filename** - archive filename

**fileinc** - additional name generated by php function date()

**typebackup** - can file or mysql
    
**exclude** - disabled right now
    
**mysqlbase** - if + then backup all bases or place name of the bases separated by space

**mysqlconfig** - config number for mysql backup

**dst** - distination configuration. If no distination configuration setted, then no external syncing
    
**Config**
Right now possible only FTP

**Mysql**
just normal mysql credentials
        
```
After all add next cron job from file backup_cron to you /etc/cron.d/
```