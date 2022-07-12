#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# vim: tabstop=8 expandtab shiftwidth=4 softtabstop=4

import datetime
from datetime import timedelta
import sys
import kopano
from MAPI.Util import *

if sys.hexversion >= 0x03000000:
    def _encode(s):
        return s
else: # pragma: no cover
    def _encode(s):
        return s.encode(sys.stdout.encoding or 'utf8')

def opt_args():
    parser = kopano.parser('skpcfmUP')
    parser.add_option("--user", dest="user", action="store", help="Run script for user")
    parser.add_option("--public", dest="public", action="store_true", help="Run script for Public store")
    parser.add_option("--wastebasket", dest="wastebasket", action="store_true",
                      help="Run cleanup script for the wastebasket folder")
    parser.add_option("--archive", dest="archive", action="store", help="instead of removing items archive them into this folder")
    parser.add_option("--junk", dest="junk", action="store_true", help="Run cleanup script for the junk folder")
    parser.add_option("--force", dest="force", action="store_true", help="Force items without date to be removed")
    parser.add_option("--all", dest="all", action="store_true", help="Run over all folders")
    parser.add_option("--recursive ", dest="recursive", action="store_true", help="Run over the subfolders (Only works if -f is being used)")
    parser.add_option("--delivery-time", dest="delivery_time", action="store_true", help="Use PR_MESSAGE_DELIVERY_TIME as date filter")
    parser.add_option("--days", dest="days", action="store", help="Delete older then x days")
    parser.add_option("--verbose", dest="verbose", action="store_true", help="Verbose mode")
    parser.add_option("--dry-run", dest="dryrun", action="store_true", help="Run script in dry mode")
    parser.add_option("--empty", dest="empty", action="store_true", help="Empty the complete folder (only works with --wastebasket or --junk)")
    parser.add_option("--progressbar", dest="progressbar", action="store_true", help="Show progressbar ")

    return parser.parse_args()


def progressbar(folder, daysbeforedeleted):
    try:
        from progressbar import Bar, AdaptiveETA, Percentage, ProgressBar
    except ImportError:
        print('''Please download the progressbar library from https://github.com/niltonvolpato/python-progressbar or
        run without the --progressbar parameter''')
        sys.exit(1)
    widgets = [Percentage(),
               ' ', Bar(),
               ' ', AdaptiveETA()]
    progressmax = 0
    for item in folder.items():
        if item.received <= daysbeforedeleted:
            progressmax += 1
    pbar = ProgressBar(widgets=widgets, maxval=progressmax)
    pbar.start()

    return pbar


def deleteitems(options, user, folder):
    itemcount = 0
    daysbeforedeleted = datetime.datetime.now() - timedelta(days=int(options.days))
    FILTER_PR = PR_LAST_MODIFICATION_TIME
    if options.delivery_time:
        FILTER_PR = PR_MESSAGE_DELIVERY_TIME 

    pbar = None
    if options.progressbar:
        pbar = progressbar(folder, daysbeforedeleted)

    if options.archive:
        archive_folder = user.store.folder(options.archive, create=True)

    for item in folder.items():
        date = None

        prop = item.get_prop(FILTER_PR)
        if not prop:
            prop = item.get_prop(PR_LAST_MODIFICATION_TIME)
    
        if not prop.value and options.force:
            date = daysbeforedeleted
        elif prop.value:
            date = prop.value
        if date:
            if date <= daysbeforedeleted:
                if options.verbose:
                    if options.archive:
                        print('Archiving \'{}\''.format(_encode(item.subject)))
                    else:
                        print('Deleting \'{}\''.format(_encode(item.subject)))

                if not options.dryrun:
                    if options.archive:
                        folder.move(item, archive_folder)
                    else:
                        folder.delete(item)
                if pbar:
                    pbar.update(itemcount + 1)
                itemcount += 1

    if options.progressbar:
        pbar.finish()
    if options.public:
        username = "Public store"
    else:
        username =  user.name
    if options.archive:
        print('Archived {} item(s) for user \'{}\' in folder \'{}\' to folder \'{}\''.format(itemcount, _encode(username),
                                                                                             _encode(folder.name),
                                                                                             _encode(archive_folder.name)))
    else:
        print('Deleted {}  item(s) for user \'{}\' in folder \'{}\''.format(itemcount, _encode(username), _encode(folder.path)))

    return itemcount


def main():
    options, args = opt_args()
    if (not options.user and not options.public) or (not options.days and not options.empty):
        print('Please use:\n {} --user <username> --days <days> '.format(sys.argv[0]))
        sys.exit(1)

    server = kopano.Server(options)
    if options.public:
        if not options.folders and not options.all:
            print('public folder options only works with the options -f or --all')
        user = server.public_store
        username = "Public store"
    else: 
        user = server.user(options.user)
        username = user.name
    print('Running script for \'{}\''.format(_encode(username)))

    if options.wastebasket:
            folder = user.store.wastebasket
            if options.empty:
                print('deleting {} items'.format(folder.count))
                folder.empty()
            else:
                deleteitems(options, user, folder)

    if options.junk:
            folder = user.store.junk
            if options.empty:
                print('deleting {} items'.format(folder.count))
                folder.empty()
            else:
                deleteitems(options, user, folder)

    '''
    Loop over all the folders that are passed with the -f parameter
    '''

    if options.folders:
        folders = []
        if options.public:
            store = user
        else:
            store = user.store
        tmp  = list(store.folders(options))
        folders =  tmp
        ## combine all subfolders
        if options.recursive:
            for f in tmp:
                folders =  folders + list(f.folders())

        for folder in folders:
            deleteitems(options, user, folder)

    elif options.all:
        if options.public:
            store = user
        else:
            store = user.store
        for folder in store.folders():
            # Only delete items in a mail folder
            if not folder.container_class or folder.container_class == 'IPF.Note':
                deleteitems(options, user, folder)
                
if __name__ == "__main__":
    main()
