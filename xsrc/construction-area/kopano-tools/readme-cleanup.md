kopano-cleanup
==============

The following scripts allow you to automatically delete or archive all items older than x days in a users **Junk E-mail** and **Deleted Items** folder.

Parameters
```python
  -h, --help                         show this help message and exit
  -c FILE, --config=FILE             load settings from FILE
  -s SOCKET, --server-socket=SOCKET  connect to server SOCKET
  -k FILE, --ssl-key=FILE            SSL key file
  -p PASS, --ssl-pass=PASS           SSL key password
  -U NAME, --auth-user=NAME          login as user
  -P PASS, --auth-pass=PASS          login with password
  -f NAME, --folder=NAME             Specify folder
  -m, --modify                       enable database modification
  --user=USER                        Run script for user
  --public                           Run script for Public store
  --wastebasket                      Run cleanup script for the wastebasket
                                     folder
  --archive=ARCHIVE                  instead of removing items archive them
                                     into this folder
  --junk                             Run cleanup script for the junk folder
  --force                            Force items without date to be removed
  --all                              Run over all folders
  --recursive                        Run over the subfolders (Only works if -f
                                     is being used)
  --delivery-time                    Use PR_MESSAGE_DELIVERY_TIME as date filter
  --days=DAYS                        Delete older then x days
  --verbose                          Verbose mode
  --dry-run                          Run script in dry mode
  --empty                            Empty the complete folder (only works
                                     with --wastebasket or --junk)
  --progressbar                      Show progressbar
```

## Usage
delete
```python
python3 cleanup.py --user username --junk --wastebasket --days days
```

archive
```python
python3 cleanup.py --user username --junk --wastebasket --days days  --archive foldername

```


By default the script is using PR_LAST_MODIFICATION_TIME as the property for the date filtering
If you restore a backup this date will be set the to date of the restore, if you still want to cleanup the store after that, you can use the `--delivery-time`
Please be aware that this property is not set for all items (e.g. contact, draft items) and the PR_LAST_MODIFICATION_TIME is then used as fallback

```python
python3 cleanup.py --user username --junk --wastebasket --days days --delivery-time
```