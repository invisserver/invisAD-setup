#!/usr/bin/env python
# -*- coding: utf-8 -*-
# vim: tabstop=8 expandtab shiftwidth=4 softtabstop=4
#
import kopano
import sys
from MAPI.Tags import *
from datetime import datetime
from kopano.errors import  NotFoundError

def opt_args():
    parser = kopano.parser('skpfucmUP')

    parser.add_option("--from", dest="from_date", action="store", help="Remove items starting from this date (YYYY-MM-DD)")
    parser.add_option("--until", dest="until_date", action="store", help="Remove items till this date (YYYY-MM-DD)")
    parser.add_option("--all", dest="all", action="store_true", help="Remove all items")
    parser.add_option("--subject", dest="subject", action="store", help="Subject of item")
    parser.add_option("--entryid", dest="entryid", action="store", help="entryid of item")

    parser.add_option("--dry-run", dest="dry_run", action="store_true", help="Dry run")
    return parser.parse_args()

def main():
    options, args = opt_args()

    if not options.users :
        print('Usage:\n{} -u <username>'.format(sys.argv[0]))
        sys.exit(1)

    if (not options.from_date and not options.until_date) and (not options.subject and not options.entryid):
        print('usage:\n{} -u <username> --from* YYYY-MM-D --until* YYYY-MM-DD \n'
              '* only one parameter is required '.format(sys.argv[0]))
        sys.exit(1)

    current_date = datetime.now()
    if options.from_date:
        from_date = datetime.strptime(options.from_date, '%Y-%m-%d')
    else:
        from_date = datetime.strptime('1970-01-01', '%Y-%m-%d')
    if options.until_date:
        until_date = datetime.strptime(options.until_date, '%Y-%m-%d')
    else:
        until_date = datetime.strptime('2038-01-19', '%Y-%m-%d')
    server= kopano.Server(options)

    if options.entryid:
        user = server.user(options.users[0])
        try:
            item = user.store.item(options.entryid)
        except NotFoundError:
            print('Item with entryid not found in store of user {}'.format(user.name))
            sys.exit(1)
        print('Deleting item {}'.format(item.subject))
        user.store.delete(item)
        sys.exit(0)

    for user in server.users():
        item_delete = 0
        print('Runnig for user {}'.format(user.name))
        for folder in user.store.folders():
            print('Search items in {}'.format(folder.name))
            for item in folder.items():
                if item.received == None:
                    received_date = item.created
                else:
                    received_date = item.received

                if received_date >= from_date and received_date <= until_date:
                    if options.subject  and options.subject != item.subject:
                        continue
                    item_delete += 1
                    if options.dry_run:
                        print('{} {} with entryid {}'.format(item.subject, item.received, item.entryid))
                    else:
                        print('removing item {}'.format(item.subject))
                        folder.delete(item)

        print('\nDeleted items :{}'.format(item_delete))
if __name__ == "__main__":
    main()
