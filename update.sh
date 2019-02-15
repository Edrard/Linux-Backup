#!/bin/bash

mkdir update
cd update
git clone https://github.com/Edrard/linux_backup.git
cd linux_backup
rm -f ftp.json
yes | cp -rf * ../../
cd ../../
rm -rf update
rm -rf .git
chmod 777 bin/*